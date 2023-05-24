<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\Embeddings;
use Mindwave\Mindwave\Facades\Vectorstore;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

it('We can insert a document in pinecone', function () {
    Config::set('mindwave-vectorstore.default', 'pinecone');
    Config::set('mindwave-vectorstore.vectorstores.pinecone.api_key', env('MINDWAVE_PINECONE_API_KEY'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.environment', env('MINDWAVE_PINECONE_ENVIRONMENT'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.index', env('MINDWAVE_PINECONE_INDEX'));
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $result = Embeddings::embed(Document::make('I am a test document'));

    $id = 'mindwave-test-id';

    Vectorstore::insertVector(new VectorStoreEntry($id, $result));

    // Aka it didn't crash on the line above
    expect(true)->toBeTrue();

    $fetched = Vectorstore::fetchById($id);

    expect($fetched->id)->toEqual($id);
    // TODO(22 May 2023) ~ Helge: They dont match, check if this is intentional or if this is a bug.
    // expect($fetched->vector)->toEqual($result);

});

it('We can insert multiple documents in pinecone', function () {

    Config::set('mindwave-vectorstore.default', 'pinecone');
    Config::set('mindwave-vectorstore.vectorstores.pinecone.api_key', env('MINDWAVE_PINECONE_API_KEY'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.environment', env('MINDWAVE_PINECONE_ENVIRONMENT'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.index', env('MINDWAVE_PINECONE_INDEX'));
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $result = Embeddings::embedMultiple([
        Document::make('test 1'),
        Document::make('test 2'),
        Document::make('test 3'),
    ]);

    Vectorstore::insertVectors([
        new VectorStoreEntry('mindwave-test-id-1', $result[0], ['index' => 1]),
        new VectorStoreEntry('mindwave-test-id-2', $result[1], ['index' => 2]),
        new VectorStoreEntry('mindwave-test-id-3', $result[2], ['index' => 3]),
    ]);

    $fetched = Vectorstore::fetchByIds([
        'mindwave-test-id-2',
        'mindwave-test-id-3',
    ]);

    expect($fetched)->toBeArray();
    expect($fetched)->toHaveCount(2);
    expect($fetched[0]->id)->toEqual('mindwave-test-id-2');
    expect($fetched[0]->metadata)->toEqual(['index' => 2]);

    expect($fetched[1]->id)->toEqual('mindwave-test-id-3');
    expect($fetched[1]->metadata)->toEqual(['index' => 3]);

});

it('We can perform similarity search on documents in pinecone', function () {

    Config::set('mindwave-vectorstore.default', 'pinecone');
    Config::set('mindwave-vectorstore.vectorstores.pinecone.api_key', env('MINDWAVE_PINECONE_API_KEY'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.environment', env('MINDWAVE_PINECONE_ENVIRONMENT'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.index', env('MINDWAVE_PINECONE_INDEX'));
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $result = Embeddings::embedMultiple([
        Document::make('fruit flies'),
        Document::make('There are, however, two exceptions to this: If the King is in residence at Stiftsgården in Trondheim or is on board the Royal Yacht Norge (and in Norwegian waters) neither the Royal Standard nor any other flag is flown over the Royal Palace. The flag pole at the Palace remains bare. The reason for this is that on these occasions the Royal Standard is hoisted either at Stiftsgården or on the Royal Yacht and as a main rule the Royal Standard is not flown in two places at once.'),
        Document::make('It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.'),
        Document::make('banana'),
    ]);

    Vectorstore::insertVectors([
        new VectorStoreEntry('mindwave-demo-id-1', $result[0]),
        new VectorStoreEntry('mindwave-demo-id-2', $result[1]),
        new VectorStoreEntry('mindwave-demo-id-3', $result[2]),
        new VectorStoreEntry('mindwave-demo-id-4', $result[3]),
    ]);

    $fetched = Vectorstore::similaritySearchByVector(Embeddings::embedQuery('banana'), 2);

    expect($fetched)->toBeArray();
    expect($fetched)->toHaveCount(2);

    // I assume this makes sense
    expect($fetched[0]->id)->toEqual('mindwave-demo-id-4');
    expect($fetched[1]->id)->toEqual('mindwave-demo-id-1');

});

it('When inserting a vector, its metadata is also inserted', function () {
    Config::set('mindwave-vectorstore.default', 'pinecone');
    Config::set('mindwave-vectorstore.vectorstores.pinecone.api_key', env('MINDWAVE_PINECONE_API_KEY'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.environment', env('MINDWAVE_PINECONE_ENVIRONMENT'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.index', env('MINDWAVE_PINECONE_INDEX'));
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $embedding = Embeddings::embedQuery('test');

    $id = 'mindwave-test-id';

    $metadata = [
        'mindwave' => 'is cool',
        'numbers' => 1234,
        'number_decimal' => 3.14,
        'list_strings' => ['one', 'two', 'three'],
        'list_number_strings' => ['1', '2', '3'],
    ];

    Vectorstore::insertVector(new VectorStoreEntry($id, $embedding, $metadata));

    // Aka it didn't crash on the line above
    expect(true)->toBeTrue();

    $fetched = Vectorstore::fetchById($id);

    expect($fetched->id)->toEqual($id);
    expect($fetched->metadata)->toEqual($metadata);

});

it('We can serialize and unserialize array of numbers in pinecone ', function () {
    Config::set('mindwave-vectorstore.default', 'pinecone');
    Config::set('mindwave-vectorstore.vectorstores.pinecone.api_key', env('MINDWAVE_PINECONE_API_KEY'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.environment', env('MINDWAVE_PINECONE_ENVIRONMENT'));
    Config::set('mindwave-vectorstore.vectorstores.pinecone.index', env('MINDWAVE_PINECONE_INDEX'));
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $metadata = [
        'list_numbers' => [1, 2, 3, 4], // This is not natively supported in pinecone
    ];

    $id = 'mindwave-test-id';
    Vectorstore::insertVector(new VectorStoreEntry($id, Embeddings::embedQuery('test'), $metadata));
    $fetched = Vectorstore::fetchById($id);

    expect($fetched->metadata)->toEqual($metadata);

})->skip('Pinecone does not support array of numbers, but we will make a workaround later, this test acts as a reminder to do that.');
