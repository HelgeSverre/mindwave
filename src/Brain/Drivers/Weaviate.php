<?php

namespace Mindwave\Mindwave\Brain\Drivers;

use Weaviate\Weaviate as WeaviateClient;

class Weaviate
{
    protected WeaviateClient $client;

    public function __construct(WeaviateClient $client)
    {
        $this->client = $client;
    }
}
