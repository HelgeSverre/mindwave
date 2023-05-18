<?php

use Illuminate\Support\Str;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Embeddings\Drivers\OpenAIEmbeddings;
use Mindwave\Mindwave\Facades\Embeddings;

it('can resolve the embeddings from the container', function () {

    $result = Embeddings::embedQuery('This is a test query.');

    expect($result)->toBeInstanceOf(EmbeddingVector::class);
    expect($result->values)->toBeArray();
    expect($result->values)->toHaveCount(1536);
});

it('embeds a query using OpenAI API', function () {
    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $result = $embeddings->embedQuery('This is a test query.');

    expect($result)->toBeInstanceOf(EmbeddingVector::class);
    expect($result->values)->toBeArray();
    expect($result->values)->toHaveCount(1536);
});

it('embeds a collection of knowledge items using OpenAI API', function () {
    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $result = $embeddings->embedMultiple([
        new Document('hello'),
        new Document('world'),
    ]);

    expect($result)->toBeArray();

    // We have 2 embeddings
    expect($result)->toHaveCount(2);

    // And each of them contains 1536 items.
    expect($result[0])->toHaveCount(1536);
    expect($result[1])->toHaveCount(1536);
});

it('can embed knowledge that exceed the max token length of the embedding model.', function () {
    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $result = $embeddings->embedMultiple([
        new Document(Str::random(90000)),
    ]);

    expect($result)->toBeArray();

    // We have 2 embeddings
    expect($result)->toHaveCount(2);

    // And each of them contains 1536 items.
    expect($result[0])->toHaveCount(1536);
    expect($result[1])->toHaveCount(1536);
})->skip('We dont handle splitting and averaging too large inputs yet.');
