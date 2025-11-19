<?php

namespace Mindwave\Mindwave\Context\TntSearch;

use PDO;
use TeamTNT\TNTSearch\TNTSearch;

/**
 * Ephemeral Index Manager
 *
 * Manages temporary TNTSearch indexes with automatic cleanup.
 */
class EphemeralIndexManager
{
    private TNTSearch $tnt;

    private string $indexPath;

    /** @var array<string, string> */
    private array $activeIndexes = [];

    public function __construct(?string $storagePath = null)
    {
        $this->indexPath = $storagePath ?? storage_path('mindwave/tnt-indexes');
        $this->tnt = new TNTSearch;

        // Create directory if it doesn't exist
        if (! is_dir($this->indexPath)) {
            mkdir($this->indexPath, 0755, true);
        }

        // TNTSearch needs a config but we don't need a main database
        // We'll create separate temp databases for each index
        $this->tnt->loadConfig([
            'storage' => $this->indexPath,
        ]);
    }

    /**
     * Create an ephemeral index from array of documents.
     *
     * This creates a lightweight SQLite database and indexes the documents using TNTSearch.
     *
     * @param  string  $name  Index name (should be unique)
     * @param  array<int|string, string>  $documents  ['id' => 'content'] array
     * @return string Index file path
     */
    public function createIndex(string $name, array $documents): string
    {
        // Create a temporary SQLite database for the documents
        $tempDbPath = $this->indexPath.'/'.$name.'_temp.sqlite';
        $pdo = new PDO('sqlite:'.$tempDbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create table
        $pdo->exec('CREATE TABLE IF NOT EXISTS documents (
            id INTEGER PRIMARY KEY,
            content TEXT
        )');

        // Insert documents
        $stmt = $pdo->prepare('INSERT INTO documents (id, content) VALUES (:id, :content)');
        foreach ($documents as $id => $content) {
            $stmt->execute([':id' => $id, ':content' => $content]);
        }

        // Create TNTSearch instance with proper config
        $tnt = new TNTSearch;
        $tnt->loadConfig([
            'driver' => 'sqlite',
            'database' => $tempDbPath,
            'storage' => $this->indexPath,
        ]);

        // Now create the index
        $indexer = $tnt->createIndex($name.'.index');
        $indexer->disableOutput = true;
        $indexer->setDatabaseHandle($pdo);
        $indexer->setPrimaryKey('id');

        // Index the documents table
        $indexer->query('SELECT * FROM documents;');
        $indexer->run();

        $indexFile = $this->indexPath.'/'.$name.'.index';
        $this->activeIndexes[$name] = $indexFile;

        return $indexFile;
    }

    /**
     * Search an index.
     *
     * @param  string  $indexName  Index name (without .index extension)
     * @param  string  $query  Search query
     * @param  int  $limit  Maximum results
     * @return array<int|string, float> Array of ['id' => score] pairs
     */
    public function search(string $indexName, string $query, int $limit = 5): array
    {
        // Create a new TNTSearch instance for searching
        $tnt = new TNTSearch;
        $tnt->loadConfig([
            'storage' => $this->indexPath,
        ]);

        $tnt->selectIndex($indexName.'.index');

        $results = $tnt->search($query, $limit);

        return $results['ids'] ?? [];
    }

    /**
     * Delete an index.
     *
     * @param  string  $name  Index name
     */
    public function deleteIndex(string $name): void
    {
        $indexFile = $this->indexPath.'/'.$name.'.index';
        $tempDbFile = $this->indexPath.'/'.$name.'_temp.sqlite';

        if (file_exists($indexFile)) {
            unlink($indexFile);
        }

        if (file_exists($tempDbFile)) {
            unlink($tempDbFile);
        }

        unset($this->activeIndexes[$name]);
    }

    /**
     * Clean up old indexes (older than TTL).
     *
     * @param  int  $ttlHours  Time to live in hours
     * @return int Number of indexes deleted
     */
    public function cleanup(int $ttlHours = 24): int
    {
        $deleted = 0;
        $cutoff = time() - ($ttlHours * 3600);

        // Clean up index files
        foreach (glob($this->indexPath.'/*.index') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        // Clean up temp database files
        foreach (glob($this->indexPath.'/*_temp.sqlite') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }

        return $deleted;
    }

    /**
     * Get all active indexes.
     *
     * @return array<string, string>
     */
    public function getActiveIndexes(): array
    {
        return $this->activeIndexes;
    }

    /**
     * Get index statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $indexes = glob($this->indexPath.'/*.index');
        $totalSize = 0;

        foreach ($indexes as $file) {
            $totalSize += filesize($file);
        }

        return [
            'count' => count($indexes),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'storage_path' => $this->indexPath,
        ];
    }
}
