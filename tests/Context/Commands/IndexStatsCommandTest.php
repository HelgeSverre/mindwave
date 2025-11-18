<?php

use Mindwave\Mindwave\Commands\IndexStatsCommand;
use Mindwave\Mindwave\Context\TntSearch\EphemeralIndexManager;

beforeEach(function () {
    $this->testIndexPath = sys_get_temp_dir().'/mindwave-test-indexes-'.uniqid();
    mkdir($this->testIndexPath, 0755, true);

    // Bind test manager
    $this->manager = new EphemeralIndexManager($this->testIndexPath);
    $this->app->instance(EphemeralIndexManager::class, $this->manager);
});

afterEach(function () {
    if (is_dir($this->testIndexPath)) {
        array_map('unlink', glob($this->testIndexPath.'/*'));
        rmdir($this->testIndexPath);
    }
});

it('displays index statistics', function () {
    // Create some test indexes
    $this->manager->createIndex('test-1', [1 => 'Content 1']);
    $this->manager->createIndex('test-2', [1 => 'Content 2']);

    $this->artisan(IndexStatsCommand::class)
        ->expectsOutputToContain('TNTSearch Index Statistics')
        ->expectsOutputToContain('Total Indexes')
        ->expectsOutputToContain('Total Size (MB)')
        ->assertExitCode(0);
});

it('shows tip when indexes exist', function () {
    $this->manager->createIndex('test', [1 => 'Content']);

    $this->artisan(IndexStatsCommand::class)
        ->expectsOutputToContain('mindwave:clear-indexes')
        ->assertExitCode(0);
});

it('handles empty index directory', function () {
    $this->artisan(IndexStatsCommand::class)
        ->expectsOutputToContain('TNTSearch Index Statistics')
        ->assertExitCode(0);
});

it('displays storage path', function () {
    $this->artisan(IndexStatsCommand::class)
        ->expectsOutputToContain($this->testIndexPath)
        ->assertExitCode(0);
});

it('shows zero count when no indexes', function () {
    $this->artisan(IndexStatsCommand::class)
        ->expectsOutputToContain('0')
        ->assertExitCode(0);
});
