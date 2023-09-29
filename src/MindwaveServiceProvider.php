<?php

namespace Mindwave\Mindwave;

use Mindwave\Mindwave\Commands\ToolMakeCommand;
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
            ])
            ->hasCommands([
                ToolMakeCommand::class,
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

        // Document loader
        $this->app->bind(Loader::class, fn () => new Loader([
            'pdf' => new PdfLoader(new Parser()),
            'html' => new HtmlLoader(),
            'url' => new WebLoader(),
            'word' => new WordLoader(),
        ]));

        // Shortcut
        $this->app->singleton(Mindwave::class, fn ($app) => new Mindwave(
            llm: $app->make(LLM::class),
            embeddings: $app->make(Embeddings::class),
            vectorstore: $app->make(Vectorstore::class),
        ));
    }
}
