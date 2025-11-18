<?php

use Mindwave\Mindwave\Brain\Brain;
use Mindwave\Mindwave\Context\Sources\VectorStoreSource;

it('creates from brain instance', function () {
    $brain = Mockery::mock(Brain::class);

    $source = VectorStoreSource::fromBrain($brain);

    expect($source->getName())->toBe('vectorstore');
    expect($source->getBrain())->toBe($brain);
});

it('uses custom source name', function () {
    $brain = Mockery::mock(Brain::class);

    $source = VectorStoreSource::fromBrain($brain, 'custom-vector-source');

    expect($source->getName())->toBe('custom-vector-source');
});

it('delegates search to brain', function () {
    $brain = Mockery::mock(Brain::class);

    $brain->shouldReceive('search')
        ->once()
        ->with('test query', 5)
        ->andReturn([
            ['content' => 'Result 1', 'score' => 0.9, 'metadata' => ['id' => 1]],
            ['content' => 'Result 2', 'score' => 0.7, 'metadata' => ['id' => 2]],
        ]);

    $source = VectorStoreSource::fromBrain($brain);
    $results = $source->search('test query', 5);

    expect($results)->toHaveCount(2);
    expect($results[0]->content)->toBe('Result 1');
    expect($results[0]->score)->toBe(0.9);
    expect($results[0]->metadata['id'])->toBe(1);
});

it('handles brain results with distance instead of score', function () {
    $brain = Mockery::mock(Brain::class);

    $brain->shouldReceive('search')
        ->once()
        ->andReturn([
            ['content' => 'Result 1', 'distance' => 0.85],
        ]);

    $source = VectorStoreSource::fromBrain($brain);
    $results = $source->search('query');

    expect($results[0]->score)->toBe(0.85);
});

it('handles empty brain results', function () {
    $brain = Mockery::mock(Brain::class);

    $brain->shouldReceive('search')
        ->once()
        ->andReturn([]);

    $source = VectorStoreSource::fromBrain($brain);
    $results = $source->search('query');

    expect($results)->toHaveCount(0);
});
