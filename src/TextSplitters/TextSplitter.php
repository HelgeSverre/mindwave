<?php

namespace Mindwave\Mindwave\TextSplitters;

use Exception;
use Mindwave\Mindwave\Document\Data\Document;

abstract class TextSplitter
{
    protected int $chunkSize;

    protected int $chunkOverlap;

    public function __construct(int $chunkSize = 1000, int $chunkOverlap = 200)
    {
        if ($chunkOverlap > $chunkSize) {
            throw new Exception(
                sprintf(
                    'Got a larger chunk overlap (%d) than chunk size (%d), should be smaller.',
                    $chunkOverlap,
                    $chunkSize
                )
            );
        }

        $this->chunkSize = $chunkSize;
        $this->chunkOverlap = $chunkOverlap;
    }

    abstract public function splitText(string $text): array;

    /**
     * @return Document[]
     */
    public function createDocuments(array $texts, ?array $metadata = null): array
    {
        $metadata = $metadata ?? array_fill(0, count($texts), []);
        $documents = [];
        foreach ($texts as $i => $text) {
            foreach ($this->splitText($text) as $chunk) {
                $documents[] = new Document(content: $chunk, metadata: $metadata[$i]);
            }
        }

        return $documents;
    }

    /**
     * @return Document[]
     */
    public function splitDocument(Document $document): array
    {
        return $this->splitDocuments([$document]);
    }

    public function splitDocuments(array $documents): array
    {
        return $this->createDocuments(
            texts: array_map(fn (Document $doc) => $doc->content(), $documents),
            metadata: array_map(fn (Document $doc) => $doc->metadata(), $documents)
        );
    }

    protected function joinDocuments($documents, $separator): ?string
    {
        $text = implode($separator, $documents);
        $text = trim($text);
        if ($text === '') {
            return null;
        } else {
            return $text;
        }
    }

    protected function mergeSplits(iterable $splits, string $separator): array
    {
        $separatorLength = strlen($separator);

        $documents = [];
        $currentDocument = [];
        $total = 0;

        foreach ($splits as $d) {
            $length = strlen($d);
            if ($total + $length + (count($currentDocument) > 0 ? $separatorLength : 0) > $this->chunkSize) {
                if ($total > $this->chunkSize) {
                    throw new Exception(
                        sprintf(
                            'Created a chunk of size %d, which is longer than the specified %d',
                            $total,
                            $this->chunkSize
                        )
                    );
                }

                if (count($currentDocument) > 0) {
                    $doc = $this->joinDocuments($currentDocument, $separator);
                    if ($doc !== null) {
                        $documents[] = $doc;
                    }

                    while (
                        $total > $this->chunkOverlap
                        || (
                            $total + $length + (count($currentDocument) > 0 ? $separatorLength : 0) > $this->chunkSize
                            && $total > 0
                        )
                    ) {
                        $total -= strlen($currentDocument[0]) + (count($currentDocument) > 1 ? $separatorLength : 0);
                        array_shift($currentDocument);
                    }
                }
            }

            $currentDocument[] = $d;
            $total += $length + (count($currentDocument) > 1 ? $separatorLength : 0);
        }

        if ($doc = $this->joinDocuments($currentDocument, $separator)) {
            $documents[] = $doc;
        }

        return $documents;
    }
}
