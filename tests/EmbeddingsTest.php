<?php

use Illuminate\Support\Str;
use Mindwave\Mindwave\Embeddings\OpenAIEmbeddings;
use Mindwave\Mindwave\Knowledge\Knowledge;

it('embeds a query using OpenAI API', function () {
    $text = 'This is a test query.';

    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $result = $embeddings->embedQuery($text);

    expect($result)->toBeArray();
});

it('embeds a collection of knowledge items using OpenAI API', function () {
    $items = [
        new Knowledge('hello'),
        new Knowledge('world'),
    ];

    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $result = $embeddings->embedKnowledge($items);

    expect($result)->toBeArray();

    // We have 2 embeddings
    expect($result)->toHaveCount(2);

    // And each of them contains 1536 items.
    expect($result[0])->toHaveCount(1536);
    expect($result[1])->toHaveCount(1536);
});

it('can embed knowledge that exceed the max token length of the embedding model.', function () {
    $items = [
        new Knowledge(Str::random(90000)),
    ];

    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $result = $embeddings->embedKnowledge($items);

    expect($result)->toBeArray();

    // We have 2 embeddings
    expect($result)->toHaveCount(2);

    // And each of them contains 1536 items.
    expect($result[0])->toHaveCount(1536);
    expect($result[1])->toHaveCount(1536);
})->skip("Wait with this until we figure out if this is 'possible' during normal use, since we would never use this directly, but pass it throug hthe brain to chunk the content anyways");
