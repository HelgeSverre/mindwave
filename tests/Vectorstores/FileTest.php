<?php

use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Mindwave\Mindwave\Vectorstore\Drivers\File;

it('can put things into the vectorstore', function () {
    unlink(__DIR__.'/../data/dummy.json');
    $vectorstore = new File(__DIR__.'/../data/dummy.json');

    $entry = new VectorStoreEntry(
        vector: new EmbeddingVector([1, 2, 3]),
        document: new Document('test')
    );

    $vectorstore->insert($entry);
    expect($vectorstore->itemCount())->toBe(1);

    $vectorstore->insert($entry);
    expect($vectorstore->itemCount())->toBe(2);
});

it('can put multiple things into the vectorstore', function () {
    unlink(__DIR__.'/../data/dummy.json');
    $vectorstore = new File(__DIR__.'/../data/dummy.json');

    $entryA = new VectorStoreEntry(
        vector: new EmbeddingVector([1, 2, 3]),
        document: new Document('test 1')
    );

    $entryB = new VectorStoreEntry(
        vector: new EmbeddingVector([1, 2, 3]),
        document: new Document('test 2')
    );

    $vectorstore->insertMany([$entryA, $entryB]);

    expect($vectorstore->itemCount())->toBe(2);
});

it('can search by similarity', function () {
    unlink(__DIR__.'/../data/dummy.json');
    $vectorstore = new File(__DIR__.'/../data/dummy.json');

    $vectorstore->insertMany([
        new VectorStoreEntry(
            vector: new EmbeddingVector([3000, 22, 501234]),
            document: new Document('test 1')
        ),
        new VectorStoreEntry(
            vector: new EmbeddingVector([1, 1, 1]),
            document: new Document('test 2')
        ),
        new VectorStoreEntry(
            vector: new EmbeddingVector([1, 2, 4]), // This one is most similar
            document: new Document('test 3')
        ),
        new VectorStoreEntry(
            vector: new EmbeddingVector([2, 2, 3]),
            document: new Document('test 4')
        ),
        new VectorStoreEntry(
            vector: new EmbeddingVector([7, 8, 9]),
            document: new Document('test 5')
        ),
    ]);

    $similar = $vectorstore->similaritySearchByVector(
        embedding: new EmbeddingVector([1, 2, 3]),
        count: 5
    );

    expect($similar)->toHaveCount(5);
    expect($similar[0])->toBeInstanceOf(VectorStoreEntry::class);
});

it('can wipe entire vectorstore', function () {
    unlink(__DIR__.'/../data/dummy.json');
    $vectorstore = new File(__DIR__.'/../data/dummy.json');

    $vectorstore->insertMany([
        new VectorStoreEntry(
            vector: new EmbeddingVector([3000, 22, 501234]),
            document: new Document('test')
        ),
        new VectorStoreEntry(
            vector: new EmbeddingVector([1, 1, 1]),
            document: new Document('test')
        ),
        new VectorStoreEntry(
            vector: new EmbeddingVector([1, 2, 4]), // This one is most similar
            document: new Document('test')
        ),
        new VectorStoreEntry(
            vector: new EmbeddingVector([2, 2, 3]),
            document: new Document('test')
        ),
        new VectorStoreEntry(
            vector: new EmbeddingVector([7, 8, 9]),
            document: new Document('test')
        ),
    ]);

    expect($vectorstore->itemCount())->toBe(5);

    $vectorstore->truncate();

    expect($vectorstore->itemCount())->toBe(0);

});
