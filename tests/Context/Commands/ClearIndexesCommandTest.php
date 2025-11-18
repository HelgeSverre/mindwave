<?php

use Mindwave\Mindwave\Commands\ClearIndexesCommand;
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

it('clears old indexes with confirmation', function () {
    // Create old index
    $indexFile = $this->manager->createIndex('old-index', [1 => 'Old content']);
    touch($indexFile, time() - (25 * 3600)); // 25 hours ago

    $this->artisan(ClearIndexesCommand::class)
        ->expectsConfirmation('Do you want to proceed?', 'yes')
        ->expectsOutputToContain('Cleared')
        ->assertExitCode(0);

    expect(file_exists($indexFile))->toBeFalse();
});

it('clears indexes with --force flag', function () {
    $indexFile = $this->manager->createIndex('old-index', [1 => 'Old content']);
    touch($indexFile, time() - (25 * 3600));

    $this->artisan(ClearIndexesCommand::class, ['--force' => true])
        ->doesntExpectOutput('Do you want to proceed?')
        ->expectsOutputToContain('Cleared')
        ->assertExitCode(0);
});

it('respects custom TTL parameter', function () {
    // Create index that's 2 hours old
    $indexFile = $this->manager->createIndex('semi-old', [1 => 'Content']);
    touch($indexFile, time() - (2 * 3600));

    // With default TTL (24h), shouldn't delete
    $this->artisan(ClearIndexesCommand::class, ['--force' => true])
        ->assertExitCode(0);
    expect(file_exists($indexFile))->toBeTrue();

    // With TTL of 1 hour, should delete
    $this->artisan(ClearIndexesCommand::class, ['--ttl' => 1, '--force' => true])
        ->expectsOutputToContain('Cleared 1')
        ->assertExitCode(0);
    expect(file_exists($indexFile))->toBeFalse();
});

it('handles empty index directory gracefully', function () {
    $this->artisan(ClearIndexesCommand::class, ['--force' => true])
        ->expectsOutputToContain('No indexes to clear')
        ->assertExitCode(0);
});

it('cancels when user declines confirmation', function () {
    $indexFile = $this->manager->createIndex('old-index', [1 => 'Old content']);
    touch($indexFile, time() - (25 * 3600));

    $this->artisan(ClearIndexesCommand::class)
        ->expectsConfirmation('Do you want to proceed?', 'no')
        ->expectsOutputToContain('Cancelled')
        ->assertExitCode(0);

    expect(file_exists($indexFile))->toBeTrue();
});

it('shows freed disk space', function () {
    $indexFile = $this->manager->createIndex('old-index', [1 => 'Old content']);
    touch($indexFile, time() - (25 * 3600));

    $this->artisan(ClearIndexesCommand::class, ['--force' => true])
        ->expectsOutputToContain('Freed')
        ->expectsOutputToContain('MB')
        ->assertExitCode(0);
});

it('shows remaining indexes after cleanup', function () {
    // Create one old and one new index
    $oldFile = $this->manager->createIndex('old-index', [1 => 'Old']);
    touch($oldFile, time() - (25 * 3600));
    $this->manager->createIndex('new-index', [1 => 'New']);

    $this->artisan(ClearIndexesCommand::class, ['--force' => true])
        ->expectsOutputToContain('active index(es) remaining')
        ->assertExitCode(0);
});
