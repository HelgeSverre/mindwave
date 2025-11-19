<?php

namespace Mindwave\Mindwave;

use Illuminate\Support\Facades\Event;
use Mindwave\Mindwave\Commands\ClearIndexesCommand;
use Mindwave\Mindwave\Commands\ExportTracesCommand;
use Mindwave\Mindwave\Commands\IndexStatsCommand;
use Mindwave\Mindwave\Commands\PruneTracesCommand;
use Mindwave\Mindwave\Commands\ToolMakeCommand;
use Mindwave\Mindwave\Commands\TraceStatsCommand;
use Mindwave\Mindwave\Context\TntSearch\EphemeralIndexManager;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Document\Loader;
use Mindwave\Mindwave\Document\Loaders\HtmlLoader;
use Mindwave\Mindwave\Document\Loaders\PdfLoader;
use Mindwave\Mindwave\Document\Loaders\WebLoader;
use Mindwave\Mindwave\Document\Loaders\WordLoader;
use Mindwave\Mindwave\Embeddings\EmbeddingsManager;
use Mindwave\Mindwave\Facades\Vectorstore;
use Mindwave\Mindwave\LLM\LLMManager;
use Mindwave\Mindwave\Observability\Listeners\TraceEventSubscriber;
use Mindwave\Mindwave\Observability\Tracing\GenAI\GenAiInstrumentor;
use Mindwave\Mindwave\Observability\Tracing\TracerManager;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer;
use Mindwave\Mindwave\PromptComposer\Tokenizer\TokenizerInterface;
use Mindwave\Mindwave\Vectorstore\VectorstoreManager;
use Smalot\PdfParser\Parser;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MindwaveServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('mindwave')
            ->hasConfigFile([
                'mindwave-embeddings',
                'mindwave-llm',
                'mindwave-vectorstore',
                'mindwave-tracing',
                'mindwave-context',
            ])
            ->hasMigrations([
                'create_mindwave_traces_table',
                'create_mindwave_spans_table',
                'create_mindwave_span_messages_table',
            ])
            ->hasCommands([
                ToolMakeCommand::class,
                ExportTracesCommand::class,
                PruneTracesCommand::class,
                TraceStatsCommand::class,
                IndexStatsCommand::class,
                ClearIndexesCommand::class,
            ]);
    }

    public function registeringPackage()
    {
        // Managers
        $this->app->singleton('mindwave.embeddings.manager', fn ($app) => new EmbeddingsManager($app));
        $this->app->singleton('mindwave.vectorstore.manager', fn ($app) => new VectorstoreManager($app));
        $this->app->singleton('mindwave.llm.manager', fn ($app) => new LLMManager($app));

        // Interfaces
        $this->app->singleton(Embeddings::class, fn ($app) => $app['mindwave.embeddings.manager']->driver());
        $this->app->singleton(Vectorstore::class, fn ($app) => $app['mindwave.vectorstore.manager']->driver());
        $this->app->singleton(LLM::class, fn ($app) => $app['mindwave.llm.manager']->driver());

        // Tokenizer
        $this->app->singleton(TokenizerInterface::class, fn () => new TiktokenTokenizer);

        // Context Discovery - EphemeralIndexManager
        $this->app->singleton(EphemeralIndexManager::class, fn () => new EphemeralIndexManager(
            config('mindwave-context.tntsearch.storage_path')
        ));

        // OpenTelemetry Tracing
        $this->app->singleton(TracerManager::class, fn ($app) => new TracerManager(
            config('mindwave-tracing', [])
        ));

        $this->app->singleton(GenAiInstrumentor::class, fn ($app) => new GenAiInstrumentor(
            tracerManager: $app->make(TracerManager::class),
            enabled: config('mindwave-tracing.enabled', true),
            captureMessages: config('mindwave-tracing.capture_messages', false)
        ));

        // Document loader
        $this->app->bind(Loader::class, fn () => new Loader([
            'pdf' => new PdfLoader(new Parser),
            'html' => new HtmlLoader,
            'url' => new WebLoader,
            'word' => new WordLoader,
        ]));

        // Shortcut
        $this->app->singleton(Mindwave::class, fn ($app) => new Mindwave(
            llm: $app->make(LLM::class),
            embeddings: $app->make(Embeddings::class),
            vectorstore: $app->make(Vectorstore::class),
            tokenizer: $app->make(TokenizerInterface::class),
        ));
    }

    /**
     * Bootstrap any package services.
     */
    public function bootingPackage(): void
    {
        // Register event subscriber for LLM tracing events
        Event::subscribe(TraceEventSubscriber::class);
    }
}
