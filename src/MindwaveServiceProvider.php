<?php

namespace Mindwave\Mindwave;

use Mindwave\Mindwave\Document\DocumentLoader;
use Mindwave\Mindwave\Embeddings\EmbeddingsManager;
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
        $this->app->bind('mindwave.document.loader', function () {
            return new DocumentLoader();
        });

        $this->app->singleton('mindwave.embeddings.manager', fn ($app) => new EmbeddingsManager($app));
        $this->app->singleton('mindwave.vectorstore.manager', fn ($app) => new VectorstoreManager($app));
        $this->app->singleton('mindwave.llm.manager', fn ($app) => new LLMManager($app));
    }
}
