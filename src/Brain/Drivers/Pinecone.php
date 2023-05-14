<?php

namespace Mindwave\Mindwave\Brain\Drivers;

use Probots\Pinecone\Client as PineconeClient;

class Pinecone
{
    protected PineconeClient $client;

    public function __construct(PineconeClient $client)
    {
        $this->client = $client;
    }
}
//
//
//$apiKey = 'YOUR_PINECONE_API_KEY';
//$environment = 'YOU_PINECONE_ENVIRONMENT';
//
//// Initialize Pinecone
//$pinecone = new Pinecone($apiKey, $environment);
