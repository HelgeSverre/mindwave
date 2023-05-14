<?php

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Embeddings\OpenAIEmbeddings;
use Mindwave\Mindwave\Knowledge\Data\Knowledge;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Mindwave\Mindwave\Vectorstore\Drivers\InMemory;

it('can put things into the vectorstore', function () {
    $vectorstore = new InMemory();

    $entry = new VectorStoreEntry(
        id: 'test-1',
        vector: new EmbeddingVector([1, 2, 3]),
        metadata: []
    );

    $vectorstore->insertVector($entry);

    expect($vectorstore->fetchById('test-1')->id)->toBe($entry->id);
});

it('can put multiple things into the vectorstore', function () {

    $vectorstore = new InMemory();

    $entryA = new VectorStoreEntry(
        id: 'test-1',
        vector: new EmbeddingVector([1, 2, 3]),
        metadata: []
    );

    $entryB = new VectorStoreEntry(
        id: 'test-2',
        vector: new EmbeddingVector([1, 2, 3]),
        metadata: []
    );

    $vectorstore->insertVectors([$entryA, $entryB]);

    expect($vectorstore->fetchById('test-1')->id)->toBe($entryA->id);
    expect($vectorstore->fetchById('test-2')->id)->toBe($entryB->id);
});

it('can search by similarity', function () {

    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $docA = new Knowledge('Apple');
    $docB = new Knowledge('Oranges');

    $vectorstore = new InMemory();

    $entryA = new VectorStoreEntry(
        id: 'test-1',
        vector: new EmbeddingVector([1, 2, 3]),
        metadata: $docA->toArray()
    );

    $entryB = new VectorStoreEntry(
        id: 'test-2',
        vector: new EmbeddingVector([1, 2, 3]),
        metadata: $docB->toArray()
    );

    $vectorstore->insertVectors([$entryA, $entryB]);

    expect($vectorstore->fetchById('test-1')->id)->toBe($entryA->id);
    expect($vectorstore->fetchById('test-2')->id)->toBe($entryB->id);
});
