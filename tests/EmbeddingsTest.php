<?php

use Mindwave\Mindwave\Embeddings\OpenAIEmbeddings;

it('embeds a query using OpenAI API', function () {
    $text = 'This is a test query.';

    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $result = $embeddings->embedQuery($text);

    expect($result)->toBeArray();
});

it('embeds a collection of knowledge items using OpenAI API', function () {
    $items = collect(['item1', 'item2', 'item3']);
    $expectedEmbeddings = [
        ['embedding' => [0.1, 0.2, 0.3]],
        ['embedding' => [0.4, 0.5, 0.6]],
        ['embedding' => [0.7, 0.8, 0.9]],
    ];

    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $result = $embeddings->embedKnowledge($items);

    expect($result)->toBe($expectedEmbeddings);
});
