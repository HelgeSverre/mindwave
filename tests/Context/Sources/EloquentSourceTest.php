<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mindwave\Mindwave\Context\Sources\EloquentSource;

uses(RefreshDatabase::class);

// Define test model
class TestPost extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'test_posts';

    protected $fillable = ['title', 'body', 'author'];

    public $timestamps = false;
}

beforeEach(function () {
    Schema::create('test_posts', function ($table) {
        $table->id();
        $table->string('title');
        $table->text('body');
        $table->string('author');
    });
});

it('creates an eloquent source', function () {
    $source = EloquentSource::create(
        TestPost::query(),
        ['title', 'body'],
        fn ($post) => $post->title
    );

    expect($source->getName())->toBe('eloquent');
});

it('searches using LIKE on specified columns', function () {
    TestPost::create([
        'title' => 'Laravel Best Practices',
        'body' => 'Learn how to write clean Laravel code',
        'author' => 'John',
    ]);

    TestPost::create([
        'title' => 'Vue.js Guide',
        'body' => 'Introduction to Vue.js framework',
        'author' => 'Jane',
    ]);

    $source = EloquentSource::create(
        TestPost::query(),
        ['title', 'body'],
        fn ($post) => "{$post->title}: {$post->body}"
    );

    $results = $source->search('Laravel');

    expect($results->count())->toBeGreaterThan(0);
    expect($results[0]->content)->toContain('Laravel');
});

it('applies transformer to format content', function () {
    TestPost::create([
        'title' => 'Test Post',
        'body' => 'Test content',
        'author' => 'Author Name',
    ]);

    $source = EloquentSource::create(
        TestPost::query(),
        ['title'],
        fn ($post) => "Title: {$post->title} by {$post->author}"
    );

    $results = $source->search('Test');

    expect($results[0]->content)->toContain('Title: Test Post by Author Name');
});

it('preserves model metadata', function () {
    $post = TestPost::create([
        'title' => 'Sample Post',
        'body' => 'Sample content',
        'author' => 'Author',
    ]);

    $source = EloquentSource::create(
        TestPost::query(),
        ['title'],
        fn ($p) => $p->title
    );

    $results = $source->search('Sample');

    expect($results[0]->metadata['model_id'])->toBe($post->id);
    expect($results[0]->metadata['model_type'])->toContain('TestPost');
});

it('respects query limit', function () {
    for ($i = 1; $i <= 5; $i++) {
        TestPost::create([
            'title' => "Post {$i} about Laravel",
            'body' => 'Content',
            'author' => 'Author',
        ]);
    }

    $source = EloquentSource::create(
        TestPost::query(),
        ['title'],
        fn ($post) => $post->title
    );

    $results = $source->search('Laravel', limit: 3);

    expect($results->count())->toBeLessThanOrEqual(3);
});

it('works with existing query constraints', function () {
    TestPost::create(['title' => 'Published Post', 'body' => 'Content', 'author' => 'John']);
    TestPost::create(['title' => 'Draft Post', 'body' => 'Content', 'author' => 'Jane']);

    $source = EloquentSource::create(
        TestPost::where('author', 'John'),
        ['title'],
        fn ($post) => $post->title
    );

    $results = $source->search('Post');

    expect($results)->toHaveCount(1);
    expect($results[0]->content)->toBe('Published Post');
});

it('returns empty collection when no matches', function () {
    TestPost::create(['title' => 'Laravel', 'body' => 'Content', 'author' => 'Author']);

    $source = EloquentSource::create(
        TestPost::query(),
        ['title'],
        fn ($post) => $post->title
    );

    $results = $source->search('Python Django');

    expect($results)->toHaveCount(0);
});

it('searches across multiple columns', function () {
    TestPost::create([
        'title' => 'Introduction',
        'body' => 'This post covers Laravel basics',
        'author' => 'Author',
    ]);

    $source = EloquentSource::create(
        TestPost::query(),
        ['title', 'body', 'author'],
        fn ($post) => $post->title
    );

    // Search term only in body
    $results = $source->search('Laravel');
    expect($results->count())->toBeGreaterThan(0);
});

it('uses custom source name', function () {
    $source = EloquentSource::create(
        TestPost::query(),
        ['title'],
        fn ($post) => $post->title,
        'custom-eloquent-source'
    );

    expect($source->getName())->toBe('custom-eloquent-source');
});
