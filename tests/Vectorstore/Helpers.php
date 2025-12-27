<?php

declare(strict_types=1);

use Mindwave\Mindwave\Document\Data\Document;
use Mindwave\Mindwave\Embeddings\Data\EmbeddingVector;
use Mindwave\Mindwave\Vectorstore\Data\VectorStoreEntry;

if (! function_exists('createTestEntry')) {
    function createTestEntry(array $vector, string $content, array $metadata = []): VectorStoreEntry
    {
        return new VectorStoreEntry(
            vector: new EmbeddingVector($vector),
            document: new Document($content, $metadata)
        );
    }
}
