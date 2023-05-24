<?php

namespace Mindwave\Mindwave\Vectorstore;

use Illuminate\Support\Manager;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Vectorstore\Drivers\File;
use Mindwave\Mindwave\Vectorstore\Drivers\InMemory;
use Mindwave\Mindwave\Vectorstore\Drivers\Pinecone;
use Mindwave\Mindwave\Vectorstore\Drivers\Weaviate;
use Probots\Pinecone\Client;
use Weaviate\Weaviate as WeaviateClient;

class VectorstoreManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('mindwave-vectorstore.default');
    }

    public function createFileDriver(): Vectorstore
    {
        return new File(
            path: $this->config->get('mindwave-vectorstore.vectorstores.file.path')
        );
    }

    public function createArrayDriver(): Vectorstore
    {
        return new InMemory();
    }

    public function createPineconeDriver(): Vectorstore
    {
        return new Pinecone(
            new Client(
                apiKey: $this->config->get('mindwave-vectorstore.vectorstores.pinecone.api_key'),
                environment: $this->config->get('mindwave-vectorstore.vectorstores.pinecone.environment'),
            ),
            index: $this->config->get('mindwave-vectorstore.vectorstores.pinecone.index')
        );
    }

    public function createWeaviateDriver(): Vectorstore
    {
        return new Weaviate(
            new WeaviateClient(
                apiUrl: $this->config->get('mindwave-vectorstore.vectorstores.weaviate.api_url'),
                apiToken: $this->config->get('mindwave-vectorstore.vectorstores.weaviate.api_token'),
                additionalHeaders: $this->config->get('mindwave-vectorstore.vectorstores.weaviate.additional_headers', []),
            ),
            className: $this->config->get('mindwave-vectorstore.vectorstores.weaviate.index')
        );
    }
}
