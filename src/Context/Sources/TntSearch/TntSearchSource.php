<?php

namespace Mindwave\Mindwave\Context\Sources\TntSearch;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Mindwave\Mindwave\Context\ContextCollection;
use Mindwave\Mindwave\Context\ContextItem;
use Mindwave\Mindwave\Context\Contracts\ContextSource;
use Mindwave\Mindwave\Context\TntSearch\EphemeralIndexManager;
use Mindwave\Mindwave\Observability\Tracing\TracerManager;

/**
 * TNTSearch Context Source
 *
 * Full-text search using TNTSearch with BM25 ranking.
 */
class TntSearchSource implements ContextSource
{
    private EphemeralIndexManager $indexManager;

    private ?string $indexName = null;

    /** @var array<int|string, array{content: string, metadata: array<string, mixed>}> */
    private array $documents = [];

    private bool $initialized = false;

    public function __construct(
        private string $name = 'tntsearch',
        ?EphemeralIndexManager $indexManager = null
    ) {
        $this->indexManager = $indexManager ?? app(EphemeralIndexManager::class);
    }

    /**
     * Create from Eloquent query.
     *
     * @param  Builder  $query  Eloquent query
     * @param  Closure  $transform  Closure to transform model to searchable text
     * @param  string  $name  Source name for identification
     */
    public static function fromEloquent(
        Builder $query,
        Closure $transform,
        string $name = 'eloquent-search'
    ): self {
        $instance = new self($name);

        $models = $query->get();

        foreach ($models as $index => $model) {
            $content = $transform($model);
            $instance->documents[$index] = [
                'content' => $content,
                'metadata' => [
                    'model_id' => $model->getKey(),
                    'model_type' => get_class($model),
                ],
            ];
        }

        return $instance;
    }

    /**
     * Create from array of strings or associative arrays.
     *
     * @param  array<int|string, string|array<string, mixed>>  $documents  Array of strings or ['key' => 'value'] pairs
     * @param  string  $name  Source name
     */
    public static function fromArray(
        array $documents,
        string $name = 'array-search'
    ): self {
        $instance = new self($name);

        foreach ($documents as $index => $content) {
            $instance->documents[$index] = [
                'content' => is_string($content) ? $content : json_encode($content),
                'metadata' => ['index' => $index],
            ];
        }

        return $instance;
    }

    /**
     * Create from CSV file.
     *
     * @param  string  $filepath  Path to CSV file
     * @param  array<string>  $columns  Columns to index (empty = all columns)
     * @param  string  $name  Source name
     */
    public static function fromCsv(
        string $filepath,
        array $columns = [],
        string $name = 'csv-search'
    ): self {
        $instance = new self($name);

        if (! file_exists($filepath)) {
            throw new \InvalidArgumentException("CSV file not found: {$filepath}");
        }

        $csv = array_map('str_getcsv', file($filepath));
        $headers = array_shift($csv);

        $columnsToUse = empty($columns) ? $headers : $columns;

        foreach ($csv as $index => $row) {
            $data = array_combine($headers, $row);

            $content = implode(' ', array_intersect_key(
                $data,
                array_flip($columnsToUse)
            ));

            $instance->documents[$index] = [
                'content' => $content,
                'metadata' => $data,
            ];
        }

        return $instance;
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // Create span for index creation tracing
        $span = null;
        if (config('mindwave-context.tracing.enabled', true) && config('mindwave-context.tracing.trace_index_creation', true)) {
            try {
                $tracerManager = app(TracerManager::class);
                $span = $tracerManager->spanBuilder('context.index.create')
                    ->setAttribute('context.source', $this->getName())
                    ->setAttribute('context.source.type', 'tntsearch')
                    ->setAttribute('context.document_count', count($this->documents))
                    ->start();
            } catch (\Throwable $e) {
                // Tracing is optional, continue without it
            }
        }

        try {
            // Extract just the content strings for indexing
            $indexableContent = array_map(
                fn ($doc) => $doc['content'],
                $this->documents
            );

            // Generate unique index name
            $this->indexName = 'ephemeral_'.md5(serialize($indexableContent).time());

            // Create the index
            $this->indexManager->createIndex($this->indexName, $indexableContent);

            $this->initialized = true;

            // Record success
            if ($span) {
                $span->setAttribute('context.index_name', $this->indexName);
                $span->setStatus('ok');
            }
        } catch (\Throwable $e) {
            if ($span) {
                $span->recordException($e);
                $span->setStatus('error', $e->getMessage());
            }

            throw $e;
        } finally {
            if ($span) {
                $span->end();
            }
        }
    }

    public function search(string $query, int $limit = 5): ContextCollection
    {
        // Create span for tracing
        $span = null;
        if (config('mindwave-context.tracing.enabled', true) && config('mindwave-context.tracing.trace_searches', true)) {
            try {
                $tracerManager = app(TracerManager::class);
                $span = $tracerManager->spanBuilder('context.search')
                    ->setAttribute('context.source', $this->getName())
                    ->setAttribute('context.source.type', 'tntsearch')
                    ->setAttribute('context.query', $query)
                    ->setAttribute('context.limit', $limit)
                    ->start();
            } catch (\Throwable $e) {
                // Tracing is optional, continue without it
            }
        }

        try {
            if (! $this->initialized) {
                $this->initialize();
            }

            $resultIds = $this->indexManager->search($this->indexName, $query, $limit);

            $items = [];

            foreach ($resultIds as $id => $score) {
                if (isset($this->documents[$id])) {
                    $doc = $this->documents[$id];

                    $items[] = ContextItem::make(
                        content: $doc['content'],
                        score: (float) $score,
                        source: $this->name,
                        metadata: $doc['metadata']
                    );
                }
            }

            $collection = new ContextCollection($items);

            // Record success metrics
            if ($span) {
                $span->setAttribute('context.result_count', count($collection));
                $span->setAttribute('context.index_name', $this->indexName ?? 'not_initialized');
                $span->setStatus('ok');
            }

            return $collection;
        } catch (\Throwable $e) {
            // Record error
            if ($span) {
                $span->recordException($e);
                $span->setStatus('error', $e->getMessage());
            }

            throw $e;
        } finally {
            if ($span) {
                $span->end();
            }
        }
    }

    public function cleanup(): void
    {
        if ($this->indexName) {
            $this->indexManager->deleteIndex($this->indexName);
            $this->indexName = null;
            $this->initialized = false;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Destructor - cleanup index on object destruction.
     */
    public function __destruct()
    {
        $this->cleanup();
    }
}
