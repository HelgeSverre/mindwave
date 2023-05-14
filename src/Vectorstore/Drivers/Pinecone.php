<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Mindwave\Mindwave\Contracts\Vectorstore;
use Probots\Pinecone\Client as PineconeClient;

class Pinecone implements Vectorstore
{
    protected PineconeClient $client;

    public function __construct(PineconeClient $client)
    {
        $this->client = $client;
    }
}
