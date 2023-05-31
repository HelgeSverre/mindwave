<?php

use Illuminate\Support\Facades\Config;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Facades\Embeddings;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;
use Mindwave\Mindwave\Vectorstore\Drivers\Pinecone;
use Probots\Pinecone\Client;

it('can wipe the entire vectorstore', function () {

    $vectorstore = new Pinecone(
        new Client(
            env('MINDWAVE_PINECONE_API_KEY'),
            env('MINDWAVE_PINECONE_ENVIRONMENT')
        ),
        env('MINDWAVE_PINECONE_INDEX')
    );

    $vectorstore->insertMany([
        new VectorStoreEntry(new EmbeddingVector(array_fill(0, 1536, 0.5)), new Document('test 1')),
        new VectorStoreEntry(new EmbeddingVector(array_fill(0, 1536, 0.5)), new Document('test 2')),
        new VectorStoreEntry(new EmbeddingVector(array_fill(0, 1536, 0.5)), new Document('test 3')),
    ]);

    $vectorstore->truncate();

    expect($vectorstore->itemCount())->toBe(0);

});

it('We can perform similarity search on documents in pinecone', function () {

    Config::set('mindwave-embeddings.embeddings.openai.api_key', env('MINDWAVE_OPENAI_API_KEY'));

    $vectorstore = new Pinecone(
        new Client(
            env('MINDWAVE_PINECONE_API_KEY'),
            env('MINDWAVE_PINECONE_ENVIRONMENT')
        ),
        env('MINDWAVE_PINECONE_INDEX')
    );

    $docs = [
        Document::make('fruit flies'),
        Document::make('There are, however, two exceptions to this: If the King is in residence at Stiftsgården in Trondheim or is on board the Royal Yacht Norge (and in Norwegian waters) neither the Royal Standard nor any other flag is flown over the Royal Palace. The flag pole at the Palace remains bare. The reason for this is that on these occasions the Royal Standard is hoisted either at Stiftsgården or on the Royal Yacht and as a main rule the Royal Standard is not flown in two places at once.'),
        Document::make('It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.'),
        Document::make('banana'),
    ];

    $vectors = collect($docs)
        ->zip(Embeddings::embedDocuments($docs))
        ->map(fn ($embeddingAndDocs) => new VectorStoreEntry(
            vector: $embeddingAndDocs[1],
            document: $embeddingAndDocs[0]
        ))
        ->flatten()
        ->toArray();

    $vectorstore->truncate();
    $vectorstore->insertMany($vectors);

    $fetched = $vectorstore->similaritySearchByVector(Embeddings::embedText('banana'), 2);

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

    $contents = collect($fetched)
        ->pluck('document')
        ->map(fn (Document $doc) => $doc->content())
        ->sort()
        ->toArray();

    // this is crude, but it works
    expect($contents)->toMatchArray(['banana', 'fruit flies']);
});
