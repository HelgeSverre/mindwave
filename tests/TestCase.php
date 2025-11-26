<?php

namespace Mindwave\Mindwave\Tests;

use Dotenv\Dotenv;
use HelgeSverre\Telefonkatalog\TelefonkatalogServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Mindwave\Mindwave\MindwaveServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected $loadEnvironmentVariables = true;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Mindwave\\Mindwave\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            MindwaveServiceProvider::class,
            TelefonkatalogServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }

    /** @noinspection LaravelFunctionsInspection */
    public function getEnvironmentSetUp($app)
    {
        // Load .env.test into the environment.
        if (file_exists(dirname(__DIR__).'/.env')) {
            (Dotenv::createImmutable(dirname(__DIR__), '.env'))->load();
        }

        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('mindwave-vectorstore.default', 'array');
        config()->set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
        config()->set('mindwave-llm.llms.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));
        config()->set('mindwave-llm.llms.mistral.api_key', env('MINDWAVE_MISTRAL_API_KEY'));
        config()->set('mindwave-llm.llms.anthropic.api_key', env('ANTHROPIC_API_KEY'));

        $app->useEnvironmentPath(__DIR__.'/..');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
    }

    /**
     * Define database migrations.
     * Only loads migrations when RefreshDatabase trait is used.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        // Only load migrations if the test uses RefreshDatabase trait
        if (in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive($this))) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
}
