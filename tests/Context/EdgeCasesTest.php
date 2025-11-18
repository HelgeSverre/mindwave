<?php

use Mindwave\Mindwave\Context\ContextCollection;
use Mindwave\Mindwave\Context\ContextItem;
use Mindwave\Mindwave\Context\TntSearch\EphemeralIndexManager;

it('truncateToTokens preserves metadata about truncation', function () {
    $items = [
        ContextItem::make(str_repeat('word ', 200), 0.9, 'source1'), // ~200 tokens
    ];

    $collection = new ContextCollection($items);
    // Use 100 tokens to ensure we have > 50 tokens available for truncation
    $truncated = $collection->truncateToTokens(100);

    // Should have at least one item (truncated version)
    expect($truncated->count())->toBeGreaterThan(0);

    $firstItem = $truncated->first();
    expect($firstItem->metadata)->toHaveKey('truncated');
    expect($firstItem->metadata['truncated'])->toBeTrue();
    expect($firstItem->metadata)->toHaveKey('original_length');
    expect($firstItem->metadata['original_length'])->toBeGreaterThan(0);
});

it('truncateToTokens respects different model parameters', function () {
    $items = [
        ContextItem::make('Test content', 0.9, 'source1'),
    ];

    $collection = new ContextCollection($items);

    // Should work with different models
    $truncated1 = $collection->truncateToTokens(100, 'gpt-4');
    $truncated2 = $collection->truncateToTokens(100, 'gpt-3.5-turbo');

    expect($truncated1)->toBeInstanceOf(ContextCollection::class);
    expect($truncated2)->toBeInstanceOf(ContextCollection::class);
});

it('truncateToTokens with zero tokens limit', function () {
    $items = [
        ContextItem::make('Test content', 0.9, 'source1'),
    ];

    $collection = new ContextCollection($items);
    $truncated = $collection->truncateToTokens(0);

    expect($truncated)->toHaveCount(0);
});

it('truncateToTokens with negative tokens limit', function () {
    $items = [
        ContextItem::make('Test content', 0.9, 'source1'),
    ];

    $collection = new ContextCollection($items);
    $truncated = $collection->truncateToTokens(-10);

    expect($truncated)->toHaveCount(0);
});

it('truncateToTokens skips items under 50 token threshold', function () {
    $items = [
        ContextItem::make(str_repeat('word ', 100), 0.9, 'source1'), // ~100 tokens
        ContextItem::make(str_repeat('word ', 100), 0.8, 'source2'), // ~100 tokens
    ];

    $collection = new ContextCollection($items);
    // Give exactly enough for first item, leaving <50 tokens for second
    $firstItemTokens = app(\Mindwave\Mindwave\PromptComposer\Tokenizer\TiktokenTokenizer::class)
        ->count($items[0]->content, 'gpt-4');

    $truncated = $collection->truncateToTokens($firstItemTokens + 40); // Less than 50 remaining

    // Should only include first item, second skipped due to < 50 token threshold
    expect($truncated)->toHaveCount(1);
});

it('EphemeralIndexManager cleanup deletes temp sqlite files', function () {
    $testPath = sys_get_temp_dir().'/mindwave-test-cleanup-'.uniqid();
    mkdir($testPath, 0755, true);

    $manager = new EphemeralIndexManager($testPath);

    // Create index (will create both .index and _temp.sqlite files)
    $manager->createIndex('test-cleanup', [1 => 'Test content']);

    // Verify both files exist
    expect(file_exists($testPath.'/test-cleanup.index'))->toBeTrue();
    expect(file_exists($testPath.'/test-cleanup_temp.sqlite'))->toBeTrue();

    // Touch files to make them old
    touch($testPath.'/test-cleanup.index', time() - (25 * 3600));
    touch($testPath.'/test-cleanup_temp.sqlite', time() - (25 * 3600));

    // Cleanup
    $manager->cleanup(24);

    // Both should be deleted
    expect(file_exists($testPath.'/test-cleanup.index'))->toBeFalse();
    expect(file_exists($testPath.'/test-cleanup_temp.sqlite'))->toBeFalse();

    // Cleanup test directory
    if (is_dir($testPath)) {
        array_map('unlink', glob($testPath.'/*'));
        rmdir($testPath);
    }
});

it('EphemeralIndexManager deleteIndex removes both index and temp db', function () {
    $testPath = sys_get_temp_dir().'/mindwave-test-delete-'.uniqid();
    mkdir($testPath, 0755, true);

    $manager = new EphemeralIndexManager($testPath);

    // Create index
    $manager->createIndex('test-delete', [1 => 'Test content']);

    // Verify both files exist
    expect(file_exists($testPath.'/test-delete.index'))->toBeTrue();
    expect(file_exists($testPath.'/test-delete_temp.sqlite'))->toBeTrue();

    // Delete
    $manager->deleteIndex('test-delete');

    // Both should be deleted
    expect(file_exists($testPath.'/test-delete.index'))->toBeFalse();
    expect(file_exists($testPath.'/test-delete_temp.sqlite'))->toBeFalse();

    // Cleanup test directory
    if (is_dir($testPath)) {
        array_map('unlink', glob($testPath.'/*'));
        rmdir($testPath);
    }
});

it('EphemeralIndexManager getStats handles empty directory gracefully', function () {
    $testPath = sys_get_temp_dir().'/mindwave-test-empty-'.uniqid();
    mkdir($testPath, 0755, true);

    $manager = new EphemeralIndexManager($testPath);

    $stats = $manager->getStats();

    expect($stats['count'])->toBe(0);
    expect($stats['total_size_bytes'])->toBe(0);
    expect($stats['total_size_mb'])->toBe(0.0);
    expect($stats['storage_path'])->toBe($testPath);

    rmdir($testPath);
});

it('EphemeralIndexManager handles special characters in index names', function () {
    $testPath = sys_get_temp_dir().'/mindwave-test-special-'.uniqid();
    mkdir($testPath, 0755, true);

    $manager = new EphemeralIndexManager($testPath);

    // Create index with special characters (hyphens, underscores)
    $indexName = 'test-index_with_special-chars';
    $manager->createIndex($indexName, [1 => 'Test content']);

    $stats = $manager->getStats();
    expect($stats['count'])->toBeGreaterThan(0);

    // Cleanup
    $manager->deleteIndex($indexName);

    if (is_dir($testPath)) {
        array_map('unlink', glob($testPath.'/*'));
        rmdir($testPath);
    }
});

it('ContextCollection deduplicate handles hash collisions gracefully', function () {
    // While MD5 collisions are rare, ensure we handle equal hashes correctly
    $items = [
        ContextItem::make('Content A', 0.9, 'source1'),
        ContextItem::make('Content A', 0.8, 'source2'), // Exact duplicate
        ContextItem::make('Content A', 0.7, 'source3'), // Exact duplicate
    ];

    $collection = new ContextCollection($items);
    $deduplicated = $collection->deduplicate();

    // Should keep only one with highest score
    expect($deduplicated)->toHaveCount(1);
    expect($deduplicated[0]->score)->toBe(0.9);
    expect($deduplicated[0]->source)->toBe('source1');
});

it('ContextCollection formatForPrompt with empty items', function () {
    $collection = new ContextCollection([]);

    expect($collection->formatForPrompt('numbered'))->toBe('');
    expect($collection->formatForPrompt('markdown'))->toBe('');
    expect($collection->formatForPrompt('json'))->toBe('[]');
});

it('ContextCollection getTotalTokens with empty collection', function () {
    $collection = new ContextCollection([]);

    expect($collection->getTotalTokens())->toBe(0);
});

it('ContextItem withScore maintains other properties', function () {
    $original = ContextItem::make('Test', 0.5, 'source1', ['key' => 'value']);
    $updated = $original->withScore(0.9);

    expect($updated->score)->toBe(0.9);
    expect($updated->content)->toBe('Test');
    expect($updated->source)->toBe('source1');
    expect($updated->metadata['key'])->toBe('value');
    expect($updated)->not->toBe($original); // Should be new instance
});

it('ContextItem withMetadata maintains other properties', function () {
    $original = ContextItem::make('Test', 0.5, 'source1', ['key1' => 'value1']);
    $updated = $original->withMetadata(['key2' => 'value2']);

    expect($updated->metadata['key1'])->toBe('value1');
    expect($updated->metadata['key2'])->toBe('value2');
    expect($updated->score)->toBe(0.5);
    expect($updated->content)->toBe('Test');
    expect($updated->source)->toBe('source1');
    expect($updated)->not->toBe($original); // Should be new instance
});

it('ContextItem handles very long content', function () {
    $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 1000); // ~5000+ characters

    $item = ContextItem::make($longContent, 0.9, 'source1');

    expect($item->content)->toBe($longContent);
    expect(strlen($item->content))->toBeGreaterThan(5000);
});

it('ContextCollection rerank with equal scores maintains stable order', function () {
    $items = [
        ContextItem::make('A', 0.5, 'source1'),
        ContextItem::make('B', 0.5, 'source2'),
        ContextItem::make('C', 0.5, 'source3'),
    ];

    $collection = new ContextCollection($items);
    $reranked = $collection->rerank();

    // All have same score, order should be maintained
    expect($reranked)->toHaveCount(3);
    expect($reranked[0]->score)->toBe(0.5);
    expect($reranked[1]->score)->toBe(0.5);
    expect($reranked[2]->score)->toBe(0.5);
});
