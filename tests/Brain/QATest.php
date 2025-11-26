<?php

use Mindwave\Mindwave\Brain\QA;
use Mindwave\Mindwave\Contracts\Embeddings;
use Mindwave\Mindwave\Contracts\LLM;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

describe('QA', function () {
    describe('Constructor', function () {
        it('initializes with LLM, Vectorstore, and Embeddings', function () {
            $llm = Mockery::mock(LLM::class);
            $vectorstore = Mockery::mock(Vectorstore::class);
            $embeddings = Mockery::mock(Embeddings::class);

            $qa = new QA($llm, $vectorstore, $embeddings);

            expect($qa)->toBeInstanceOf(QA::class);
        });
    });

    describe('answerQuestion()', function () {
        it('embeds question and searches vectorstore', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Relevant context', []);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->once()
                ->with('What is the capital?')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->once()
                ->with($embedding)
                ->andReturn([
                    new VectorStoreEntry($embedding, $document, 0.95),
                ]);

            $llm = Mockery::mock(LLM::class);
            $llm->shouldReceive('setSystemMessage')->once();
            $llm->shouldReceive('generateText')
                ->andReturn('Paris is the capital.');

            $qa = new QA($llm, $vectorstore, $embeddings);
            $answer = $qa->answerQuestion('What is the capital?');

            expect($answer)->toBe('Paris is the capital.');
        });

        it('sets system message with context on LLM', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Paris is the capital of France', []);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->andReturn([
                    new VectorStoreEntry($embedding, $document, 0.95),
                ]);

            $llm = Mockery::mock(LLM::class);
            $llm->shouldReceive('setSystemMessage')
                ->once()
                ->with(Mockery::on(function ($message) {
                    return str_contains($message, 'Paris is the capital of France')
                        && str_contains($message, 'Use the following pieces of context');
                }));
            $llm->shouldReceive('generateText')
                ->andReturn('Paris');

            $qa = new QA($llm, $vectorstore, $embeddings);
            $qa->answerQuestion('What is the capital of France?');
        });

        it('generates text response from LLM', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('Test context', []);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->andReturn([
                    new VectorStoreEntry($embedding, $document, 0.95),
                ]);

            $llm = Mockery::mock(LLM::class);
            $llm->shouldReceive('setSystemMessage');
            $llm->shouldReceive('generateText')
                ->once()
                ->with('User question')
                ->andReturn('Generated answer');

            $qa = new QA($llm, $vectorstore, $embeddings);
            $answer = $qa->answerQuestion('User question');

            expect($answer)->toBe('Generated answer');
        });

        it('returns "I dont know" message when no documents found', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->andReturn([]); // No results

            $llm = Mockery::mock(LLM::class);
            $llm->shouldReceive('setSystemMessage')
                ->once()
                ->with(Mockery::on(function ($message) {
                    return str_contains($message, "I don't know");
                }));
            $llm->shouldReceive('generateText')
                ->andReturn("I don't know the answer");

            $qa = new QA($llm, $vectorstore, $embeddings);
            $qa->answerQuestion('Unknown question');
        });

        it('concatenates multiple document contents in context', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);
            $doc1 = new Document('First document content', []);
            $doc2 = new Document('Second document content', []);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->andReturn([
                    new VectorStoreEntry($embedding, $doc1, 0.95),
                    new VectorStoreEntry($embedding, $doc2, 0.90),
                ]);

            $llm = Mockery::mock(LLM::class);
            $llm->shouldReceive('setSystemMessage')
                ->once()
                ->with(Mockery::on(function ($message) {
                    return str_contains($message, 'First document content')
                        && str_contains($message, 'Second document content');
                }));
            $llm->shouldReceive('generateText')
                ->andReturn('Answer');

            $qa = new QA($llm, $vectorstore, $embeddings);
            $qa->answerQuestion('Question');
        });
    });

    describe('Edge Cases', function () {
        it('handles empty question string', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->with('')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->andReturn([]);

            $llm = Mockery::mock(LLM::class);
            $llm->shouldReceive('setSystemMessage');
            $llm->shouldReceive('generateText')
                ->andReturn('No question provided');

            $qa = new QA($llm, $vectorstore, $embeddings);
            $answer = $qa->answerQuestion('');

            expect($answer)->toBe('No question provided');
        });

        it('handles document with empty content', function () {
            $embedding = new EmbeddingVector([0.1, 0.2, 0.3]);
            $document = new Document('', []); // Empty content

            $embeddings = Mockery::mock(Embeddings::class);
            $embeddings->shouldReceive('embedText')
                ->andReturn($embedding);

            $vectorstore = Mockery::mock(Vectorstore::class);
            $vectorstore->shouldReceive('similaritySearch')
                ->andReturn([
                    new VectorStoreEntry($embedding, $document, 0.95),
                ]);

            $llm = Mockery::mock(LLM::class);
            $llm->shouldReceive('setSystemMessage');
            $llm->shouldReceive('generateText')
                ->andReturn('Answer');

            $qa = new QA($llm, $vectorstore, $embeddings);
            $answer = $qa->answerQuestion('Question');

            expect($answer)->toBe('Answer');
        });
    });
});
