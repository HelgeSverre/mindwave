<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Mindwave\Mindwave\Contracts\VectorstoreContract;
use Weaviate\Weaviate as WeaviateClient;

class Weaviate implements VectorstoreContract
{
    protected WeaviateClient $client;

    public function __construct(WeaviateClient $client)
    {
        $this->client = $client;
    }
}
