<?php

use Mindwave\Mindwave\Context\ContextCollection;
use Mindwave\Mindwave\Context\ContextItem;

beforeEach(function () {
    $this->items = [
        ContextItem::make('First item', 0.9, 'source1'),
        ContextItem::make('Second item', 0.7, 'source2'),
        ContextItem::make('Third item', 0.5, 'source1'),
    ];
    $this->collection = new ContextCollection($this->items);
});

it('extends Laravel Collection', function () {
    expect($this->collection)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('formats as numbered list', function () {
    $formatted = $this->collection->formatForPrompt('numbered');

    expect($formatted)->toContain('[1] (score: 0.90, source: source1)');
    expect($formatted)->toContain('First item');
    expect($formatted)->toContain('[2] (score: 0.70, source: source2)');
    expect($formatted)->toContain('Second item');
});

it('formats as markdown', function () {
    $formatted = $this->collection->formatForPrompt('markdown');

    expect($formatted)->toContain('### Context 1 (score: 0.90)');
    expect($formatted)->toContain('First item');
    expect($formatted)->toContain('*Source: source1*');
    expect($formatted)->toContain('---');
});

it('formats as json', function () {
    $formatted = $this->collection->formatForPrompt('json');
    $decoded = json_decode($formatted, true);

    expect($decoded)->toBeArray();
    expect($decoded[0]['content'])->toBe('First item');
    expect($decoded[0]['score'])->toBe(0.9);
});

it('deduplicates items by content hash', function () {
    $items = [
        ContextItem::make('Duplicate content', 0.5, 'source1'),
        ContextItem::make('Unique content', 0.7, 'source2'),
        ContextItem::make('Duplicate content', 0.9, 'source3'), // Higher score
    ];

    $collection = new ContextCollection($items);
    $deduplicated = $collection->deduplicate();

    expect($deduplicated)->toHaveCount(2);
    // Should keep the higher scored version
    $duplicateItem = $deduplicated->first(fn ($item) => $item->content === 'Duplicate content');
    expect($duplicateItem->score)->toBe(0.9);
    expect($duplicateItem->source)->toBe('source3');
});

it('reranks items by score descending', function () {
    $reranked = $this->collection->rerank();

    expect($reranked[0]->score)->toBe(0.9);
    expect($reranked[1]->score)->toBe(0.7);
    expect($reranked[2]->score)->toBe(0.5);
});

it('truncates to token limit', function () {
    $items = [
        ContextItem::make(str_repeat('word ', 100), 0.9, 'source1'), // ~100 tokens
        ContextItem::make(str_repeat('word ', 100), 0.8, 'source2'), // ~100 tokens
        ContextItem::make(str_repeat('word ', 100), 0.7, 'source3'), // ~100 tokens
    ];

    $collection = new ContextCollection($items);
    $truncated = $collection->truncateToTokens(150);

    // Should fit first item fully and part of second
    expect($truncated->count())->toBeLessThanOrEqual(2);
});

it('calculates total tokens correctly', function () {
    $items = [
        ContextItem::make('Short text', 0.9, 'source1'),
        ContextItem::make('Another short text', 0.8, 'source2'),
    ];

    $collection = new ContextCollection($items);
    $totalTokens = $collection->getTotalTokens();

    expect($totalTokens)->toBeGreaterThan(0);
    expect($totalTokens)->toBeLessThan(20); // Should be small for short texts
});

it('handles empty collection', function () {
    $empty = new ContextCollection([]);

    expect($empty->formatForPrompt())->toBe('');
    expect($empty->getTotalTokens())->toBe(0);
    expect($empty->deduplicate())->toHaveCount(0);
});

it('preserves metadata through operations', function () {
    $items = [
        ContextItem::make('Test', 0.9, 'source1', ['id' => 1]),
        ContextItem::make('Test', 0.7, 'source2', ['id' => 2]),
    ];

    $collection = new ContextCollection($items);
    $reranked = $collection->rerank();

    expect($reranked[0]->metadata['id'])->toBe(1);
    expect($reranked[1]->metadata['id'])->toBe(2);
});

it('defaults to numbered format', function () {
    $formatted = $this->collection->formatForPrompt();

    expect($formatted)->toContain('[1]');
    expect($formatted)->toContain('score:');
});

it('handles unknown format by defaulting to numbered', function () {
    $formatted = $this->collection->formatForPrompt('invalid-format');

    expect($formatted)->toContain('[1]');
    expect($formatted)->toContain('score:');
});

it('handles special characters in content', function () {
    $items = [
        ContextItem::make('Content with "quotes" and \'apostrophes\'', 0.9, 'source1'),
        ContextItem::make('Content with <html> tags', 0.8, 'source2'),
        ContextItem::make('Content with newlines\nand\ttabs', 0.7, 'source3'),
    ];

    $collection = new ContextCollection($items);
    $formatted = $collection->formatForPrompt('numbered');

    expect($formatted)->toContain('quotes');
    expect($formatted)->toContain('<html>');
    expect($formatted)->toContain('newlines');
});

it('deduplicates keeps first when scores are equal', function () {
    $items = [
        ContextItem::make('Same content', 0.8, 'source1'),
        ContextItem::make('Same content', 0.8, 'source2'), // Same score
        ContextItem::make('Same content', 0.8, 'source3'), // Same score
    ];

    $collection = new ContextCollection($items);
    $deduplicated = $collection->deduplicate();

    expect($deduplicated)->toHaveCount(1);
    expect($deduplicated[0]->source)->toBe('source1'); // First one kept
});

it('handles very large collections efficiently', function () {
    $items = [];
    for ($i = 0; $i < 1000; $i++) {
        $items[] = ContextItem::make("Item {$i}", 0.5, 'source');
    }

    $collection = new ContextCollection($items);

    $formatted = $collection->formatForPrompt();
    expect($formatted)->toContain('[1]');
    expect($formatted)->toContain('[1000]');

    $reranked = $collection->rerank();
    expect($reranked)->toHaveCount(1000);
});

it('truncateToTokens handles exact fit', function () {
    // Create items that fit exactly
    $items = [
        ContextItem::make('word word', 0.9, 'source1'), // ~2 tokens
        ContextItem::make('word word', 0.8, 'source2'), // ~2 tokens
    ];

    $collection = new ContextCollection($items);
    $totalTokens = $collection->getTotalTokens();

    $truncated = $collection->truncateToTokens($totalTokens);

    expect($truncated)->toHaveCount(2);
});

it('truncateToTokens with very small limit', function () {
    $items = [
        ContextItem::make('This is a longer piece of content', 0.9, 'source1'),
    ];

    $collection = new ContextCollection($items);
    $truncated = $collection->truncateToTokens(5); // Very small

    // Should either include nothing or truncated version
    expect($truncated->count())->toBeLessThanOrEqual(1);
});

it('formatJson handles metadata correctly', function () {
    $items = [
        ContextItem::make('Test', 0.9, 'source1', ['key' => 'value', 'nested' => ['data' => 123]]),
    ];

    $collection = new ContextCollection($items);
    $formatted = $collection->formatForPrompt('json');
    $decoded = json_decode($formatted, true);

    expect($decoded[0]['metadata']['key'])->toBe('value');
    expect($decoded[0]['metadata']['nested']['data'])->toBe(123);
});

it('rerank maintains collection type', function () {
    $reranked = $this->collection->rerank();

    expect($reranked)->toBeInstanceOf(ContextCollection::class);
});

it('deduplicate maintains collection type', function () {
    $deduplicated = $this->collection->deduplicate();

    expect($deduplicated)->toBeInstanceOf(ContextCollection::class);
});
