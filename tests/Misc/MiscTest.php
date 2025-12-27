<?php

use Mindwave\Mindwave\Embeddings\Drivers\OpenAIEmbeddings;
use Mindwave\Mindwave\Support\Similarity;

it('calculates similarity correctly for similar texts', function () {
    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $similarityValue = Similarity::cosine(
        vectorA: $embeddings->embedText('This is a test query.'),
        vectorB: $embeddings->embedText('This is another test query.')
    );

    expect($similarityValue)->toBeGreaterThan(0.8)
        ->and($similarityValue)->toBeLessThanOrEqual(1.0);
})->skip('Requires OpenAI API key and makes external API calls');

it('calculates similarity correctly for dissimilar texts', function () {
    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $similarityValue = Similarity::cosine(
        vectorA: $embeddings->embedText('panda'),
        vectorB: $embeddings->embedText('php programming language')
    );

    expect($similarityValue)->toBeGreaterThan(0.0)
        ->and($similarityValue)->toBeLessThan(0.7);
})->skip('Requires OpenAI API key and makes external API calls');
