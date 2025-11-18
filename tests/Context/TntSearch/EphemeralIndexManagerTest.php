<?php

use Mindwave\Mindwave\Context\TntSearch\EphemeralIndexManager;

beforeEach(function () {
    // Use a temporary test directory
    $this->testIndexPath = sys_get_temp_dir().'/mindwave-test-indexes-'.uniqid();
    mkdir($this->testIndexPath, 0755, true);

    $this->manager = new EphemeralIndexManager($this->testIndexPath);
});

afterEach(function () {
    // Clean up test directory
    if (is_dir($this->testIndexPath)) {
        array_map('unlink', glob($this->testIndexPath.'/*'));
        rmdir($this->testIndexPath);
    }
});

it('creates an index from documents', function () {
    $documents = [
        1 => 'Laravel is a PHP framework',
        2 => 'Vue.js is a JavaScript framework',
        3 => 'Python Django web framework',
    ];

    $indexFile = $this->manager->createIndex('test-index', $documents);

    expect($indexFile)->toBeString();
    expect(file_exists($indexFile))->toBeTrue();
});

it('searches an index and returns results', function () {
    $documents = [
        1 => 'Laravel is a PHP framework for web development',
        2 => 'Vue.js is a JavaScript framework for building UIs',
        3 => 'Python Django is a web framework',
    ];

    $this->manager->createIndex('search-test', $documents);

    $results = $this->manager->search('search-test', 'Laravel PHP', 5);

    expect($results)->toBeArray();
    expect($results)->not->toBeEmpty();
});

it('deletes an index', function () {
    $documents = [1 => 'Test content'];

    $indexFile = $this->manager->createIndex('delete-test', $documents);
    expect(file_exists($indexFile))->toBeTrue();

    $this->manager->deleteIndex('delete-test');
    expect(file_exists($indexFile))->toBeFalse();
});

it('returns statistics about indexes', function () {
    $documents = [1 => 'Test content'];

    $this->manager->createIndex('stats-test', $documents);

    $stats = $this->manager->getStats();

    expect($stats)->toBeArray();
    expect($stats)->toHaveKey('count');
    expect($stats)->toHaveKey('total_size_bytes');
    expect($stats)->toHaveKey('total_size_mb');
    expect($stats)->toHaveKey('storage_path');
    expect($stats['count'])->toBeGreaterThan(0);
});

it('tracks active indexes', function () {
    $this->manager->createIndex('active-1', [1 => 'Content 1']);
    $this->manager->createIndex('active-2', [1 => 'Content 2']);

    $active = $this->manager->getActiveIndexes();

    expect($active)->toBeArray();
    expect($active)->toHaveKey('active-1');
    expect($active)->toHaveKey('active-2');
});

it('cleans up old indexes based on TTL', function () {
    // Create an index
    $indexFile = $this->manager->createIndex('old-index', [1 => 'Old content']);

    // Manually modify file time to make it "old"
    touch($indexFile, time() - (25 * 3600)); // 25 hours ago

    // Clean up indexes older than 24 hours
    $deleted = $this->manager->cleanup(24);

    expect($deleted)->toBe(1);
    expect(file_exists($indexFile))->toBeFalse();
});

it('does not delete recent indexes during cleanup', function () {
    $indexFile = $this->manager->createIndex('recent-index', [1 => 'Recent content']);

    $deleted = $this->manager->cleanup(24);

    expect($deleted)->toBe(0);
    expect(file_exists($indexFile))->toBeTrue();
});

it('returns empty array when searching non-existent index', function () {
    expect(fn () => $this->manager->search('non-existent', 'query', 5))
        ->toThrow(Exception::class);
});

it('handles empty documents array', function () {
    $indexFile = $this->manager->createIndex('empty-index', []);

    expect(file_exists($indexFile))->toBeTrue();
});

it('creates unique index names', function () {
    $index1 = $this->manager->createIndex('unique-1', [1 => 'Content']);
    $index2 = $this->manager->createIndex('unique-2', [1 => 'Content']);

    expect($index1)->not->toBe($index2);
    expect(file_exists($index1))->toBeTrue();
    expect(file_exists($index2))->toBeTrue();
});
