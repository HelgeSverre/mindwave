<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Facades\Embeddings;
use Mindwave\Mindwave\Facades\Vectorstore;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

it('We can connect to weaviate in the docker container', function () {
    Config::set('mindwave-vectorstore.default', 'weaviate');
    Config::set('mindwave-vectorstore.vectorstores.weaviate.api_token', 'password');
    Config::set('mindwave-vectorstore.vectorstores.weaviate.api_url', 'http://localhost:8080/v1');
    Config::set('mindwave-vectorstore.vectorstores.weaviate.index', 'MindwaveItems');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $result = Embeddings::embedDocument(Document::make('I am a test document'));

    $id = 'mindwave-test-id';

    Vectorstore::insertVector(new VectorStoreEntry($id, $result));

    // Aka it didn't crash on the line above
    expect(true)->toBeTrue();

    $fetched = Vectorstore::fetchById($id);

    expect($fetched->id)->toEqual($id);

});

it('We can connect search weaviate', function () {
    Config::set('mindwave-vectorstore.default', 'weaviate');
    Config::set('mindwave-vectorstore.vectorstores.weaviate.api_token', 'password');
    Config::set('mindwave-vectorstore.vectorstores.weaviate.api_url', 'http://localhost:8080/v1');
    Config::set('mindwave-vectorstore.vectorstores.weaviate.index', 'MindwaveItems');
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $result = Embeddings::embedDocument(Document::make('I am a test document'));

    $id = 'mindwave-test-id';

    Vectorstore::insertVector(new VectorStoreEntry($id, $result, [
        "_mindwave_content" => "testing",
        "_mindwave_chunk_index" => "1",
    ]));

    $fetched = Vectorstore::similaritySearchByVector($result, 1);


});
