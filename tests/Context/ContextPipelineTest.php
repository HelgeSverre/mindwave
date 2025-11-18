<?php

use Mindwave\Mindwave\Context\ContextCollection;
use Mindwave\Mindwave\Context\ContextItem;
use Mindwave\Mindwave\Context\ContextPipeline;
use Mindwave\Mindwave\Context\Contracts\ContextSource;

it('creates an empty pipeline', function () {
    $pipeline = new ContextPipeline;

    expect($pipeline->getSources())->toBeArray()->toBeEmpty();
});

it('adds a single source', function () {
    $source = Mockery::mock(ContextSource::class);

    $pipeline = (new ContextPipeline)->addSource($source);

    expect($pipeline->getSources())->toHaveCount(1);
    expect($pipeline->getSources()[0])->toBe($source);
});

it('adds multiple sources', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source2 = Mockery::mock(ContextSource::class);

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2);

    expect($pipeline->getSources())->toHaveCount(2);
});

it('adds multiple sources at once', function () {
    $sources = [
        Mockery::mock(ContextSource::class),
        Mockery::mock(ContextSource::class),
        Mockery::mock(ContextSource::class),
    ];

    $pipeline = (new ContextPipeline)->addSources($sources);

    expect($pipeline->getSources())->toHaveCount(3);
});

it('aggregates results from multiple sources', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source1->shouldReceive('initialize')->once();
    $source1->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Result from source 1', 0.9, 'source1'),
    ]));

    $source2 = Mockery::mock(ContextSource::class);
    $source2->shouldReceive('initialize')->once();
    $source2->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Result from source 2', 0.8, 'source2'),
    ]));

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2);

    $results = $pipeline->search('test query', 10);

    expect($results)->toHaveCount(2);
    expect($results[0]->source)->toBe('source1'); // Higher score first
    expect($results[1]->source)->toBe('source2');
});

it('deduplicates results by default', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source1->shouldReceive('initialize')->once();
    $source1->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Duplicate content', 0.9, 'source1'),
    ]));

    $source2 = Mockery::mock(ContextSource::class);
    $source2->shouldReceive('initialize')->once();
    $source2->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Duplicate content', 0.7, 'source2'),
    ]));

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2);

    $results = $pipeline->search('test');

    // Should keep only the higher scored version
    expect($results)->toHaveCount(1);
    expect($results[0]->score)->toBe(0.9);
    expect($results[0]->source)->toBe('source1');
});

it('can disable deduplication', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source1->shouldReceive('initialize')->once();
    $source1->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Duplicate content', 0.9, 'source1'),
    ]));

    $source2 = Mockery::mock(ContextSource::class);
    $source2->shouldReceive('initialize')->once();
    $source2->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Duplicate content', 0.7, 'source2'),
    ]));

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2)
        ->deduplicate(false);

    $results = $pipeline->search('test');

    // Should keep both duplicates
    expect($results)->toHaveCount(2);
});

it('re-ranks results by score by default', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source1->shouldReceive('initialize')->once();
    $source1->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Low score', 0.3, 'source1'),
    ]));

    $source2 = Mockery::mock(ContextSource::class);
    $source2->shouldReceive('initialize')->once();
    $source2->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('High score', 0.9, 'source2'),
    ]));

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2);

    $results = $pipeline->search('test');

    // Higher score should be first
    expect($results[0]->score)->toBe(0.9);
    expect($results[1]->score)->toBe(0.3);
});

it('respects limit parameter', function () {
    $source = Mockery::mock(ContextSource::class);
    $source->shouldReceive('initialize')->once();
    $source->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Result 1', 0.9, 'source'),
        ContextItem::make('Result 2', 0.8, 'source'),
        ContextItem::make('Result 3', 0.7, 'source'),
        ContextItem::make('Result 4', 0.6, 'source'),
        ContextItem::make('Result 5', 0.5, 'source'),
    ]));

    $pipeline = (new ContextPipeline)->addSource($source);

    $results = $pipeline->search('test', limit: 3);

    expect($results)->toHaveCount(3);
});

it('returns empty collection when no sources', function () {
    $pipeline = new ContextPipeline;

    $results = $pipeline->search('test');

    expect($results)->toBeInstanceOf(ContextCollection::class);
    expect($results)->toHaveCount(0);
});

it('cleans up all sources', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source1->shouldReceive('cleanup')->once();

    $source2 = Mockery::mock(ContextSource::class);
    $source2->shouldReceive('cleanup')->once();

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2);

    $pipeline->cleanup();
});

it('can disable re-ranking', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source1->shouldReceive('initialize')->once();
    $source1->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('First', 0.3, 'source1'),
    ]));

    $source2 = Mockery::mock(ContextSource::class);
    $source2->shouldReceive('initialize')->once();
    $source2->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Second', 0.9, 'source2'),
    ]));

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2)
        ->rerank(false);

    $results = $pipeline->search('test');

    // Without re-ranking, order depends on source order
    expect($results)->toHaveCount(2);
});

it('handles sources returning empty results', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source1->shouldReceive('initialize')->once();
    $source1->shouldReceive('search')->andReturn(new ContextCollection([]));

    $source2 = Mockery::mock(ContextSource::class);
    $source2->shouldReceive('initialize')->once();
    $source2->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Result', 0.9, 'source2'),
    ]));

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2);

    $results = $pipeline->search('test');

    expect($results)->toHaveCount(1);
    expect($results[0]->source)->toBe('source2');
});

it('requests more items per source to account for deduplication', function () {
    $source = Mockery::mock(ContextSource::class);
    $source->shouldReceive('initialize')->once();
    $source->shouldReceive('search')
        ->with('test', Mockery::type('int'))
        ->andReturnUsing(function ($query, $limit) {
            // Verify we're requesting more than the final limit
            expect($limit)->toBeGreaterThan(10);

            return new ContextCollection([
                ContextItem::make('Result', 0.9, 'source'),
            ]);
        });

    $pipeline = (new ContextPipeline)->addSource($source);

    $pipeline->search('test', 10);
});

it('handles mixed score ranges from different sources', function () {
    $source1 = Mockery::mock(ContextSource::class);
    $source1->shouldReceive('initialize')->once();
    $source1->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('High1', 0.95, 'source1'),
        ContextItem::make('High2', 0.90, 'source1'),
    ]));

    $source2 = Mockery::mock(ContextSource::class);
    $source2->shouldReceive('initialize')->once();
    $source2->shouldReceive('search')->andReturn(new ContextCollection([
        ContextItem::make('Low1', 0.3, 'source2'),
        ContextItem::make('Low2', 0.2, 'source2'),
    ]));

    $pipeline = (new ContextPipeline)
        ->addSource($source1)
        ->addSource($source2);

    $results = $pipeline->search('test', 4);

    expect($results)->toHaveCount(4);
    expect($results[0]->score)->toBe(0.95);
    expect($results[3]->score)->toBe(0.2);
});

it('returns ContextCollection instance', function () {
    $source = Mockery::mock(ContextSource::class);
    $source->shouldReceive('initialize')->once();
    $source->shouldReceive('search')->andReturn(new ContextCollection([]));

    $pipeline = (new ContextPipeline)->addSource($source);

    $results = $pipeline->search('test');

    expect($results)->toBeInstanceOf(ContextCollection::class);
});
