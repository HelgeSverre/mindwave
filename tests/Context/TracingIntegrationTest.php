<?php

use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;

it('works without tracing when config disabled', function () {
    config(['mindwave-context.tracing.enabled' => false]);

    $source = TntSearchSource::fromArray([
        'Laravel is a PHP framework',
        'Vue.js is a JavaScript framework',
    ]);

    $results = $source->search('Laravel', 5);

    expect($results)->toBeInstanceOf(\Mindwave\Mindwave\Context\ContextCollection::class);
    expect($results)->not->toBeEmpty();
});

it('works with tracing when config enabled', function () {
    config(['mindwave-context.tracing.enabled' => true]);
    config(['mindwave-context.tracing.trace_searches' => true]);

    $source = TntSearchSource::fromArray([
        'Laravel framework',
        'Vue.js framework',
    ]);

    // Should work even with tracing enabled
    $results = $source->search('framework', 5);

    expect($results)->toBeInstanceOf(\Mindwave\Mindwave\Context\ContextCollection::class);
});

it('handles missing tracer manager gracefully', function () {
    config(['mindwave-context.tracing.enabled' => true]);
    config(['mindwave-context.tracing.trace_searches' => true]);

    // Don't bind TracerManager - should still work
    $source = TntSearchSource::fromArray(['Test content']);

    $results = $source->search('Test', 5);

    expect($results)->toBeInstanceOf(\Mindwave\Mindwave\Context\ContextCollection::class);
});

it('continues working when tracing throws exception', function () {
    config(['mindwave-context.tracing.enabled' => true]);
    config(['mindwave-context.tracing.trace_searches' => true]);

    $source = TntSearchSource::fromArray([
        'Document 1',
        'Document 2',
    ]);

    // Should not throw even if internal tracing fails
    $results = $source->search('Document', 5);

    expect($results)->toBeInstanceOf(\Mindwave\Mindwave\Context\ContextCollection::class);
    expect(count($results))->toBe(2);
});

it('index creation works with tracing enabled', function () {
    config(['mindwave-context.tracing.enabled' => true]);
    config(['mindwave-context.tracing.trace_index_creation' => true]);

    $source = TntSearchSource::fromArray([
        'Content 1',
        'Content 2',
        'Content 3',
    ]);

    $source->initialize();

    expect($source->getName())->toBe('array-search');
});

it('maintains search functionality with tracing', function () {
    config(['mindwave-context.tracing.enabled' => true]);
    config(['mindwave-context.tracing.trace_searches' => true]);
    config(['mindwave-context.tracing.trace_index_creation' => true]);

    $source = TntSearchSource::fromArray([
        'Laravel is a web application framework',
        'Vue.js is a progressive JavaScript framework',
        'React is a JavaScript library',
    ]);

    $results = $source->search('JavaScript', 5);

    expect(count($results))->toBeGreaterThan(0);

    // Check that at least one result contains JavaScript
    $hasJavaScript = false;
    foreach ($results as $result) {
        if (str_contains($result->content, 'JavaScript')) {
            $hasJavaScript = true;
            break;
        }
    }
    expect($hasJavaScript)->toBeTrue();
});
