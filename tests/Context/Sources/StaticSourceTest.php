<?php

use Mindwave\Mindwave\Context\Sources\StaticSource;

it('creates from array of strings', function () {
    $source = StaticSource::fromStrings([
        'How do I reset my password?',
        'What are your business hours?',
        'How can I contact support?',
    ]);

    expect($source->getName())->toBe('static-strings');
});

it('searches and finds exact phrase match', function () {
    $source = StaticSource::fromStrings([
        'How do I reset my password?',
        'What are your business hours?',
        'How can I contact support?',
    ]);

    $results = $source->search('reset my password');

    expect($results)->toHaveCount(1);
    expect($results[0]->content)->toContain('reset my password');
    expect($results[0]->score)->toBe(1.0); // Exact phrase match
});

it('searches and finds keyword matches', function () {
    $source = StaticSource::fromStrings([
        'Laravel is a PHP framework for web development',
        'Vue.js is a JavaScript framework',
        'Python Django is great for rapid development',
    ]);

    $results = $source->search('framework development');

    expect($results->count())->toBeGreaterThan(0);
    $hasFramework = $results->contains(fn ($item) => str_contains($item->content, 'framework'));
    expect($hasFramework)->toBeTrue();
});

it('returns empty collection for no matches', function () {
    $source = StaticSource::fromStrings([
        'Laravel PHP framework',
        'Vue JavaScript framework',
    ]);

    $results = $source->search('Python Django Ruby');

    expect($results)->toHaveCount(0);
});

it('limits search results', function () {
    $source = StaticSource::fromStrings([
        'Document about Laravel',
        'Document about PHP',
        'Document about framework',
        'Document about web development',
        'Document about Laravel framework',
    ]);

    $results = $source->search('Laravel', limit: 2);

    expect($results->count())->toBeLessThanOrEqual(2);
});

it('creates from structured items with keywords', function () {
    $source = StaticSource::fromItems([
        [
            'content' => 'How to install Laravel',
            'keywords' => ['install', 'laravel', 'setup'],
            'metadata' => ['category' => 'installation'],
        ],
        [
            'content' => 'How to deploy Laravel',
            'keywords' => ['deploy', 'laravel', 'production'],
            'metadata' => ['category' => 'deployment'],
        ],
    ]);

    expect($source->getName())->toBe('static-items');
});

it('preserves metadata from structured items', function () {
    $source = StaticSource::fromItems([
        [
            'content' => 'Laravel installation guide',
            'keywords' => ['install', 'laravel'],
            'metadata' => ['category' => 'docs', 'version' => '10'],
        ],
    ]);

    $results = $source->search('Laravel');

    expect($results[0]->metadata['category'])->toBe('docs');
    expect($results[0]->metadata['version'])->toBe('10');
});

it('auto-extracts keywords when not provided', function () {
    $source = StaticSource::fromItems([
        ['content' => 'Laravel is a PHP framework for web development'],
    ]);

    $results = $source->search('PHP framework');

    expect($results->count())->toBeGreaterThan(0);
});

it('scores results by relevance', function () {
    $source = StaticSource::fromStrings([
        'Laravel PHP framework',
        'PHP development',
        'Framework architecture',
    ]);

    $results = $source->search('PHP framework');

    // Results should be ordered by score
    expect($results[0]->score)->toBeGreaterThanOrEqual($results[1]->score ?? 0);
});

it('handles special characters in search', function () {
    $source = StaticSource::fromStrings([
        'Email: support@example.com',
        'Phone: +1-555-0123',
    ]);

    $results = $source->search('support@example.com');

    expect($results->count())->toBeGreaterThan(0);
});
