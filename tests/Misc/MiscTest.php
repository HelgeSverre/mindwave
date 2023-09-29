<?php

use Mindwave\Mindwave\Embeddings\Drivers\OpenAIEmbeddings;
use Mindwave\Mindwave\Support\Similarity;

it('Calculates similarity correctly', function () {

    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $similarityValue = Similarity::cosine(
        vectorA: $embeddings->embedText('This is a test query.'),
        vectorB: $embeddings->embedText('This is another test query.')
    );
    dump('Cosine Similarity: '.$similarityValue);

    $similarityValue = Similarity::cosine(
        vectorA: $embeddings->embedText('panda'),
        vectorB: $embeddings->embedText('php programming language')
    );
    dump('Cosine Similarity: '.$similarityValue);
})->doesNotPerformAssertions();
