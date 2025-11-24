<?php

/** @noinspection MultipleExpectChainableInspection */

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Weaviate\Weaviate;

/**
 * Check if Weaviate service is available
 */
function isWeaviateAvailable(): bool
{
    try {
        $ch = curl_init('http://localhost:8080/v1/.well-known/ready');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    } catch (\Throwable $e) {
        return false;
    }
}

it('We can connect to weaviate in the docker container', function () {
    if (! isWeaviateAvailable()) {
        $this->markTestSkipped('Weaviate service not available on localhost:8080');
    }
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $vectorstore = new \Mindwave\Mindwave\Vectorstore\Drivers\Weaviate(
        client: new Weaviate(
            apiUrl: 'http://localhost:8080/v1',
            apiToken: 'password',
        ),
        className: 'MindwaveItems',
        dimensions: 1536
    );

    $vectorstore->insert(new VectorStoreEntry(
        new EmbeddingVector(array_fill(0, 1536, 0.5)),
        new Document('Test')
    ));

    // Aka it didn't crash on the line above
    expect(true)->toBeTrue();
});

it('We can truncate weaviate index', function () {
    if (! isWeaviateAvailable()) {
        $this->markTestSkipped('Weaviate service not available on localhost:8080');
    }
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $vectorstore = new \Mindwave\Mindwave\Vectorstore\Drivers\Weaviate(
        client: new Weaviate(
            apiUrl: 'http://localhost:8080/v1',
            apiToken: 'password',
        ),
        className: 'MindwaveItems',
        dimensions: 1536
    );

    $vectorstore->insert(new VectorStoreEntry(
        new EmbeddingVector(array_fill(0, 1536, 0.5)),
        new Document('Test')
    ));

    expect($vectorstore->itemCount() > 0)->toBeTrue();

    $vectorstore->truncate();

    expect($vectorstore->itemCount())->toBe(0);
});

it('We can connect search weaviate', function () {
    if (! isWeaviateAvailable()) {
        $this->markTestSkipped('Weaviate service not available on localhost:8080');
    }
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $vectorstore = new \Mindwave\Mindwave\Vectorstore\Drivers\Weaviate(
        client: new Weaviate(
            apiUrl: 'http://localhost:8080/v1',
            apiToken: 'password',
        ),
        className: 'MindwaveItems',
        dimensions: 1536
    );
    $vectorstore->truncate();

    $vectorstore->insert(new VectorStoreEntry(
        new EmbeddingVector(array_fill(0, 1536, 1)),
        new Document('this is test 1')
    ));
    $vectorstore->insert(new VectorStoreEntry(
        new EmbeddingVector(array_fill(0, 1536, 0)),
        new Document('this is test 2')
    ));
    $vectorstore->insert(new VectorStoreEntry(
        new EmbeddingVector(array_fill(0, 1536, 2.3)),
        new Document('this is test 3')
    ));
    $vectorstore->insert(new VectorStoreEntry(
        new EmbeddingVector(array_fill(0, 1536, 4.4)),
        new Document('this is test 4')
    ));
    $vectorstore->insert(new VectorStoreEntry(
        new EmbeddingVector(array_fill(0, 1536, 5.5)),
        new Document('this is test 5')
    ));

    $fetched = $vectorstore->similaritySearch(new EmbeddingVector(array_fill(0, 1536, 1)), 1);

    expect($fetched[0]->score)->toBeNumeric();
    expect($fetched[0]->document)->toBeInstanceOf(Document::class);
    expect($fetched[0]->document->content())->toBeString();
    expect($fetched[0]->document->metadata())->toHaveKeys([
        '_mindwave_doc_chunk_index',
        '_mindwave_doc_source_id',
        '_mindwave_doc_source_type',
    ]);
    expect($fetched[0]->meta())->toHaveKeys([
        '_mindwave_doc_chunk_index',
        '_mindwave_doc_content',
        '_mindwave_doc_metadata',
        '_mindwave_doc_source_id',
        '_mindwave_doc_source_type',
    ]);
});

it('Can insert multiple in batch', function () {
    if (! isWeaviateAvailable()) {
        $this->markTestSkipped('Weaviate service not available on localhost:8080');
    }
    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $vectorstore = new \Mindwave\Mindwave\Vectorstore\Drivers\Weaviate(
        client: new Weaviate(
            apiUrl: 'http://localhost:8080/v1',
            apiToken: 'password',
        ),
        className: 'MindwaveItems',
        dimensions: 1536
    );
    $vectorstore->truncate();

    $vectorstore->insertMany([
        new VectorStoreEntry(
            new EmbeddingVector(array_fill(0, 1536, 1)),
            new Document('this is test 1')
        ),
        new VectorStoreEntry(
            new EmbeddingVector(array_fill(0, 1536, 0)),
            new Document('this is test 2')
        ),
        new VectorStoreEntry(
            new EmbeddingVector(array_fill(0, 1536, 2.3)),
            new Document('this is test 3')
        ),
        new VectorStoreEntry(
            new EmbeddingVector(array_fill(0, 1536, 4.4)),
            new Document('this is test 4')
        ),
        new VectorStoreEntry(
            new EmbeddingVector(array_fill(0, 1536, 5.5)),
            new Document('this is test 5')
        ),
    ]);

    expect($vectorstore->itemCount())->toBe(5);
});

it('throws exception when inserting vector with wrong dimensions', function () {
    $vectorstore = new \Mindwave\Mindwave\Vectorstore\Drivers\Weaviate(
        client: new Weaviate(
            apiUrl: 'http://localhost:8080/v1',
            apiToken: 'password',
        ),
        className: 'MindwaveItems',
        dimensions: 1536
    );

    expect(fn () => $vectorstore->insert(
        new VectorStoreEntry(
            new EmbeddingVector(array_fill(0, 3072, 0.5)), // Wrong dimension!
            new Document('test')
        )
    ))->toThrow(InvalidArgumentException::class, 'Expected vector dimension 1536, got 3072');
});

it('throws exception when inserting multiple vectors with wrong dimensions', function () {
    $vectorstore = new \Mindwave\Mindwave\Vectorstore\Drivers\Weaviate(
        client: new Weaviate(
            apiUrl: 'http://localhost:8080/v1',
            apiToken: 'password',
        ),
        className: 'MindwaveItems',
        dimensions: 1536
    );

    expect(fn () => $vectorstore->insertMany([
        new VectorStoreEntry(new EmbeddingVector(array_fill(0, 1536, 0.5)), new Document('test 1')),
        new VectorStoreEntry(new EmbeddingVector(array_fill(0, 3072, 0.5)), new Document('test 2')), // Wrong dimension!
    ]))->toThrow(InvalidArgumentException::class, 'Expected vector dimension 1536, got 3072');
});
