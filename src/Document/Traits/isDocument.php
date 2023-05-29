<?php

namespace Mindwave\Mindwave\Document\Traits;

use Illuminate\Database\Eloquent\Model;
use Mindwave\Mindwave\Document\Data\Document;

/**
 * @mixin Model
 *
 * @experimental
 */
trait isDocument
{
    abstract public function getDocumentContent(): string;

    public function getDocumentMetadata(): ?array
    {
        return [];
    }

    public function asDocument(): Document
    {
        return new Document(
            content: $this->getDocumentContent(),
            metadata: array_merge($this->getDocumentMetadata(), [
                '_mindwave_source_id' => $this->getKey(),
                '_mindwave_source_type' => $this->getMorphClass(),
            ])
        );
    }
}
