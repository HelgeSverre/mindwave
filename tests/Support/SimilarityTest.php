<?php

declare(strict_types=1);

use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Support\Similarity;

describe('Similarity', function () {
    describe('Cosine Similarity', function () {
        it('returns approximately 1.0 for identical vectors', function () {
            $vectorA = new EmbeddingVector([0.5, 0.5, 0.5]);
            $vectorB = new EmbeddingVector([0.5, 0.5, 0.5]);

            $similarity = Similarity::cosine($vectorA, $vectorB);

            expect($similarity)->toEqualWithDelta(1.0, 0.0001);
        });

        it('returns -1.0 for opposite vectors', function () {
            $vectorA = new EmbeddingVector([1.0, 0.0, 0.0]);
            $vectorB = new EmbeddingVector([-1.0, 0.0, 0.0]);

            $similarity = Similarity::cosine($vectorA, $vectorB);

            expect($similarity)->toBe(-1.0);
        });

        it('returns 0.0 for orthogonal vectors', function () {
            $vectorA = new EmbeddingVector([1.0, 0.0, 0.0]);
            $vectorB = new EmbeddingVector([0.0, 1.0, 0.0]);

            $similarity = Similarity::cosine($vectorA, $vectorB);

            expect($similarity)->toBe(0.0);
        });

        it('is symmetric', function () {
            $vectorA = new EmbeddingVector([0.1, 0.2, 0.3]);
            $vectorB = new EmbeddingVector([0.4, 0.5, 0.6]);

            $similarity1 = Similarity::cosine($vectorA, $vectorB);
            $similarity2 = Similarity::cosine($vectorB, $vectorA);

            expect($similarity1)->toBe($similarity2);
        });

        it('handles normalized vectors', function () {
            // Unit vectors
            $vectorA = new EmbeddingVector([1.0, 0.0]);
            $vectorB = new EmbeddingVector([
                cos(M_PI / 4), // 45 degrees
                sin(M_PI / 4),
            ]);

            $similarity = Similarity::cosine($vectorA, $vectorB);

            expect($similarity)->toEqualWithDelta(cos(M_PI / 4), 0.0001);
        });

        it('handles high-dimensional vectors', function () {
            $values1 = array_fill(0, 1536, 0.1);
            $values2 = array_fill(0, 1536, 0.1);
            $vectorA = new EmbeddingVector($values1);
            $vectorB = new EmbeddingVector($values2);

            $similarity = Similarity::cosine($vectorA, $vectorB);

            expect($similarity)->toEqualWithDelta(1.0, 0.0001);
        });

        it('calculates correct similarity for known vectors', function () {
            // Manually calculated: cos(θ) = (a·b) / (|a| * |b|)
            // a = [1, 2, 3], b = [4, 5, 6]
            // a·b = 4 + 10 + 18 = 32
            // |a| = sqrt(1 + 4 + 9) = sqrt(14)
            // |b| = sqrt(16 + 25 + 36) = sqrt(77)
            // cos(θ) = 32 / sqrt(14 * 77) = 32 / sqrt(1078) ≈ 0.9746
            $vectorA = new EmbeddingVector([1.0, 2.0, 3.0]);
            $vectorB = new EmbeddingVector([4.0, 5.0, 6.0]);

            $similarity = Similarity::cosine($vectorA, $vectorB);

            expect($similarity)->toEqualWithDelta(0.9746, 0.001);
        });

        it('handles similar but not identical vectors', function () {
            $vectorA = new EmbeddingVector([0.1, 0.2, 0.3]);
            $vectorB = new EmbeddingVector([0.15, 0.25, 0.35]);

            $similarity = Similarity::cosine($vectorA, $vectorB);

            expect($similarity)->toBeGreaterThan(0.99);
            expect($similarity)->toBeLessThanOrEqual(1.0);
        });
    });

    describe('Error Handling', function () {
        it('throws for dimension mismatch', function () {
            $vectorA = new EmbeddingVector([0.1, 0.2, 0.3]);
            $vectorB = new EmbeddingVector([0.1, 0.2]);

            expect(fn () => Similarity::cosine($vectorA, $vectorB))
                ->toThrow(InvalidArgumentException::class, 'Vectors must have the same length, got 3 and 2');
        });

        it('throws for empty vs non-empty vectors', function () {
            $vectorA = new EmbeddingVector([]);
            $vectorB = new EmbeddingVector([0.1, 0.2]);

            expect(fn () => Similarity::cosine($vectorA, $vectorB))
                ->toThrow(InvalidArgumentException::class, 'Vectors must have the same length, got 0 and 2');
        });

        it('throws DivisionByZeroError for two empty vectors', function () {
            $vectorA = new EmbeddingVector([]);
            $vectorB = new EmbeddingVector([]);

            expect(fn () => Similarity::cosine($vectorA, $vectorB))
                ->toThrow(DivisionByZeroError::class);
        });

        it('throws DivisionByZeroError for zero vector', function () {
            $vectorA = new EmbeddingVector([0.0, 0.0, 0.0]);
            $vectorB = new EmbeddingVector([1.0, 2.0, 3.0]);

            expect(fn () => Similarity::cosine($vectorA, $vectorB))
                ->toThrow(DivisionByZeroError::class);
        });
    });
});
