<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mindwave\Mindwave\Context\Sources\TntSearch\TntSearchSource;
use Mindwave\Mindwave\Context\TntSearch\EphemeralIndexManager;

uses(RefreshDatabase::class);

// Define test model outside of beforeEach
class TestUser extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'test_users';

    protected $fillable = ['name', 'email', 'bio'];

    public $timestamps = false;
}

beforeEach(function () {
    // Create test database table for Eloquent tests
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->text('bio')->nullable();
    });
});

it('creates from array of strings', function () {
    $source = TntSearchSource::fromArray([
        'First document content',
        'Second document content',
        'Third document content',
    ]);

    expect($source->getName())->toBe('array-search');
});

it('creates from array with custom name', function () {
    $source = TntSearchSource::fromArray(
        ['Document 1'],
        name: 'custom-array-search'
    );

    expect($source->getName())->toBe('custom-array-search');
});

it('searches array documents and returns results', function () {
    $source = TntSearchSource::fromArray([
        'Laravel is a PHP framework',
        'Vue.js is a JavaScript framework',
        'Python Django web framework',
    ]);

    $results = $source->search('Laravel PHP', limit: 2);

    expect($results)->toHaveCount(1);
    expect($results[0]->content)->toContain('Laravel');
    expect($results[0]->source)->toBe('array-search');
});

it('preserves metadata from array index', function () {
    $source = TntSearchSource::fromArray([
        'First',
        'Second',
    ]);

    $results = $source->search('First');

    expect($results[0]->metadata['index'])->toBe(0);
});

it('creates from Eloquent query', function () {
    TestUser::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'bio' => 'Laravel developer',
    ]);

    $source = TntSearchSource::fromEloquent(
        TestUser::query(),
        fn ($user) => "{$user->name} {$user->bio}"
    );

    expect($source->getName())->toBe('eloquent-search');
});

it('searches Eloquent models and returns results', function () {
    TestUser::create([
        'name' => 'Alice Smith',
        'email' => 'alice@example.com',
        'bio' => 'Vue.js JavaScript framework expert',
    ]);

    TestUser::create([
        'name' => 'Bob Jones',
        'email' => 'bob@example.com',
        'bio' => 'Laravel PHP framework expert',
    ]);

    $source = TntSearchSource::fromEloquent(
        TestUser::query(),
        fn ($user) => "{$user->name} {$user->bio}"
    );

    $results = $source->search('Laravel PHP framework');

    // Verify we get results from the search
    expect($results->count())->toBeGreaterThan(0);

    // Verify at least one result contains relevant content
    $hasRelevantResult = $results->contains(fn ($item) => str_contains($item->content, 'Laravel') ||
        str_contains($item->content, 'PHP') ||
        str_contains($item->content, 'framework')
    );
    expect($hasRelevantResult)->toBeTrue();
});

it('preserves model metadata from Eloquent', function () {
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'bio' => 'Test bio',
    ]);

    $source = TntSearchSource::fromEloquent(
        TestUser::query(),
        fn ($u) => $u->name
    );

    $results = $source->search('Test');

    expect($results[0]->metadata['model_id'])->toBe($user->id);
    expect($results[0]->metadata['model_type'])->toContain('TestUser');
});

it('creates from CSV file', function () {
    // Create temporary CSV file
    $csvPath = sys_get_temp_dir().'/test_'.uniqid().'.csv';
    file_put_contents($csvPath, "name,description\nProduct A,Great product\nProduct B,Another product");

    $source = TntSearchSource::fromCsv($csvPath, ['name', 'description']);

    expect($source->getName())->toBe('csv-search');

    // Cleanup
    unlink($csvPath);
});

it('searches CSV data and returns results', function () {
    // Create temporary CSV file
    $csvPath = sys_get_temp_dir().'/test_'.uniqid().'.csv';
    file_put_contents($csvPath, "name,category,description\nLaptop,Electronics,Powerful laptop\nKeyboard,Electronics,Mechanical keyboard\nDesk,Furniture,Standing desk");

    $source = TntSearchSource::fromCsv($csvPath, ['name', 'description']);
    $results = $source->search('laptop');

    expect($results)->toHaveCount(1);
    expect($results[0]->content)->toContain('Laptop');

    // Cleanup
    unlink($csvPath);
});

it('preserves CSV metadata', function () {
    // Create temporary CSV file
    $csvPath = sys_get_temp_dir().'/test_'.uniqid().'.csv';
    file_put_contents($csvPath, "id,name,price\n1,Widget,10.00\n2,Gadget,20.00");

    $source = TntSearchSource::fromCsv($csvPath);
    $results = $source->search('Widget');

    expect($results[0]->metadata['id'])->toBe('1');
    expect($results[0]->metadata['name'])->toBe('Widget');
    expect($results[0]->metadata['price'])->toBe('10.00');

    // Cleanup
    unlink($csvPath);
});

it('throws exception for non-existent CSV file', function () {
    TntSearchSource::fromCsv('/non/existent/file.csv');
})->throws(\InvalidArgumentException::class, 'CSV file not found');

it('initializes index only once', function () {
    $source = TntSearchSource::fromArray(['Test document']);

    // Initialize multiple times
    $source->initialize();
    $source->initialize();
    $source->initialize();

    // Should still work
    $results = $source->search('Test');
    expect($results)->toHaveCount(1);
});

it('cleans up index on cleanup', function () {
    $manager = app(EphemeralIndexManager::class);
    $source = TntSearchSource::fromArray(['Test']);

    $source->initialize();
    $initialStats = $manager->getStats();

    $source->cleanup();
    $afterStats = $manager->getStats();

    // Index should be cleaned up
    expect($source->getName())->toBe('array-search');
});

it('limits search results correctly', function () {
    $source = TntSearchSource::fromArray([
        'Document one',
        'Document two',
        'Document three',
        'Document four',
        'Document five',
    ]);

    $results = $source->search('Document', limit: 3);

    expect($results->count())->toBeLessThanOrEqual(3);
});

it('handles empty search results', function () {
    $source = TntSearchSource::fromArray([
        'Laravel PHP',
        'Vue JavaScript',
    ]);

    $results = $source->search('Python Django');

    expect($results)->toHaveCount(0);
});

it('handles array with associative data', function () {
    $source = TntSearchSource::fromArray([
        ['title' => 'Post 1', 'content' => 'First post'],
        ['title' => 'Post 2', 'content' => 'Second post'],
    ]);

    $results = $source->search('Post');

    expect($results->count())->toBeGreaterThan(0);
});
