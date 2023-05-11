<?php

namespace Mindwave\Mindwave;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Mindwave\Mindwave\Commands\MindwaveCommand;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_mindwave_table')
            ->hasCommand(MindwaveCommand::class);
    }
}
