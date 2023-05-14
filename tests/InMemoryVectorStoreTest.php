<?php

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Mindwave\Mindwave\Vectorstore\Drivers\InMemory;

it('can put things into the vectorstore', function () {
    $vectorstore = new InMemory();

    $entry = new VectorStoreEntry(
        id: 'test-1',
        vector: new EmbeddingVector([1, 2, 3]),
    );

    $vectorstore->insertVector($entry);

    expect($vectorstore->fetchById('test-1')->id)->toBe($entry->id);
});

it('can put multiple things into the vectorstore', function () {

    $vectorstore = new InMemory();

    $entryA = new VectorStoreEntry(
        id: 'test-1',
        vector: new EmbeddingVector([1, 2, 3]),
    );

    $entryB = new VectorStoreEntry(
        id: 'test-2',
        vector: new EmbeddingVector([1, 2, 3]),
    );

    $vectorstore->insertVectors([$entryA, $entryB]);

    expect($vectorstore->fetchById('test-1')->id)->toBe($entryA->id);
    expect($vectorstore->fetchById('test-2')->id)->toBe($entryB->id);
});

it('can search by similarity', function () {

    $vectorstore = new InMemory();

    $vectorstore->insertVectors([
        new VectorStoreEntry(
            id: 'test-1',
            vector: new EmbeddingVector([3000, 22, 501234]),
        ),
        new VectorStoreEntry(
            id: 'banana',
            vector: new EmbeddingVector([1, 1, 1]),
        ),
        new VectorStoreEntry(
            id: 'test-2',
            vector: new EmbeddingVector([1, 2, 4]), // This one is most similar
        ),
        new VectorStoreEntry(
            id: 'soap',
            vector: new EmbeddingVector([2, 2, 3]),
        ),
        new VectorStoreEntry(
            id: 'apple',
            vector: new EmbeddingVector([7, 8, 9]),
        ),
    ]);

    $similar = $vectorstore->similaritySearchByVector(
        embedding: new EmbeddingVector([1, 2, 3]),
        count: 5
    );

    expect($similar)->toHaveCount(5);
    expect($similar[0])->toBeInstanceOf(VectorStoreEntry::class);
    expect($similar[0]->id)->toBe('test-2');
    expect($similar[1]->id)->toBe('soap');
});
