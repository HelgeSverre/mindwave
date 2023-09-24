<?php

namespace Mindwave\Mindwave\Support;

use InvalidArgumentException;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;

class Similarity
{
    // AI Generated code.
    public static function cosine(EmbeddingVector $vectorA, EmbeddingVector $vectorB): float
    {
        // Check if the vectors have the same length
        if (count($vectorA) !== count($vectorB)) {
            throw new InvalidArgumentException(sprintf('Vectors must have the same length, got %s and %s', count($vectorA), count($vectorB)));
        }

        // Calculate the dot product and magnitudes
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        foreach ($vectorA as $key => $value) {
            $dotProduct += $value * $vectorB[$key];
            $magnitudeA += $value * $value;
            $magnitudeB += $vectorB[$key] * $vectorB[$key];
        }

        // Calculate the cosine similarity
        return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
    }
}
