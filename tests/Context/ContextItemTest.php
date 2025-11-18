<?php

use Mindwave\Mindwave\Context\ContextItem;

it('creates a context item with all parameters', function () {
    $item = new ContextItem(
        content: 'Test content',
        score: 0.95,
        source: 'test-source',
        metadata: ['key' => 'value']
    );

    expect($item->content)->toBe('Test content');
    expect($item->score)->toBe(0.95);
    expect($item->source)->toBe('test-source');
    expect($item->metadata)->toBe(['key' => 'value']);
});

it('creates a context item with defaults using make', function () {
    $item = ContextItem::make('Test content');

    expect($item->content)->toBe('Test content');
    expect($item->score)->toBe(1.0);
    expect($item->source)->toBe('unknown');
    expect($item->metadata)->toBe([]);
});

it('is immutable - withScore creates new instance', function () {
    $original = ContextItem::make('Test', 0.5, 'source1');
    $modified = $original->withScore(0.9);

    expect($original->score)->toBe(0.5);
    expect($modified->score)->toBe(0.9);
    expect($modified->content)->toBe('Test');
    expect($modified->source)->toBe('source1');
});

it('is immutable - withMetadata creates new instance', function () {
    $original = ContextItem::make('Test', 1.0, 'source1', ['a' => 1]);
    $modified = $original->withMetadata(['b' => 2]);

    expect($original->metadata)->toBe(['a' => 1]);
    expect($modified->metadata)->toBe(['a' => 1, 'b' => 2]);
});

it('converts to array correctly', function () {
    $item = ContextItem::make('Content', 0.8, 'src', ['meta' => 'data']);

    expect($item->toArray())->toBe([
        'content' => 'Content',
        'score' => 0.8,
        'source' => 'src',
        'metadata' => ['meta' => 'data'],
    ]);
});

it('accepts score between 0 and 1', function () {
    $item1 = ContextItem::make('Test', 0.0);
    $item2 = ContextItem::make('Test', 1.0);
    $item3 = ContextItem::make('Test', 0.5);

    expect($item1->score)->toBe(0.0);
    expect($item2->score)->toBe(1.0);
    expect($item3->score)->toBe(0.5);
});

it('preserves content exactly as provided', function () {
    $content = "Multi-line\ncontent with\nspecial chars: @#$%";
    $item = ContextItem::make($content);

    expect($item->content)->toBe($content);
});

it('merges metadata correctly with withMetadata', function () {
    $item = ContextItem::make('Test', 1.0, 'src', ['a' => 1, 'b' => 2]);
    $updated = $item->withMetadata(['b' => 3, 'c' => 4]);

    expect($updated->metadata)->toBe(['a' => 1, 'b' => 3, 'c' => 4]);
});
