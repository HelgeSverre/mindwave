<?php

use Mindwave\Mindwave\Embeddings\OpenAIEmbeddings;
use Mindwave\Mindwave\Support\Similarity;

it('Calculates similarity correctly', function () {

    $client = OpenAI::client(env('OPENAI_API_KEY'));
    $embeddings = new OpenAIEmbeddings($client);

    $similarityValue = Similarity::cosine(
        vectorA: $embeddings->embedQuery('This is a test query.'),
        vectorB: $embeddings->embedQuery('This is another test query.')
    );
    dump('Cosine Similarity: '.$similarityValue);

    $similarityValue = Similarity::cosine(
        vectorA: $embeddings->embedQuery('panda'),
        vectorB: $embeddings->embedQuery('php programming language')
    );
    dump('Cosine Similarity: '.$similarityValue);
});
