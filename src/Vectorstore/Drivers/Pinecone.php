<?php

namespace Mindwave\Mindwave\Vectorstore\Drivers;

use Mindwave\Mindwave\Contracts\VectorstoreContract;
use Probots\Pinecone\Client as PineconeClient;

class Pinecone implements VectorstoreContract
{
    protected PineconeClient $client;

    public function __construct(PineconeClient $client)
    {
        $this->client = $client;
    }
}
