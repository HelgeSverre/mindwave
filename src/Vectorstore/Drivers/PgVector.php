<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Mindwave\Mindwave\Contracts\Vectorstore;
use Weaviate\Weaviate as WeaviateClient;

class PgVector implements Vectorstore
{
    protected WeaviateClient $client;

    public function __construct(WeaviateClient $client)
    {
        $this->client = $client;
    }
}
