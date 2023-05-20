<?php

namespace Mindwave\Mindwave;

use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Document\Loader;
use Mindwave\Mindwave\Embeddings\EmbeddingsManager;
use Mindwave\Mindwave\Facades\Vectorstore;
use Mindwave\Mindwave\LLM\LLMManager;
use Mindwave\Mindwave\Vectorstore\VectorstoreManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MindwaveServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {

        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('mindwave')
            ->hasConfigFile([
                'mindwave-embeddings',
                'mindwave-llm',
                'mindwave-vectorstore',
            ]);
        // ->hasViews()
        // ->hasMigration('create_mindwave_table')
        // ->hasCommand(MindwaveCommand::class)
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

        // Misc
        $this->app->bind('mindwave.document.loader', fn () => new Loader());

        // Shortcut
        $this->app->singleton('mindwave', fn ($app) => new Mindwave(
            llm: $app->make(LLM::class),
            embeddings: $app->make(Embeddings::class),
            vectorstore: $app->make(Vectorstore::class),
        ));
    }
}
