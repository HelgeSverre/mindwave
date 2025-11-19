<?php

namespace Mindwave\Mindwave\Vectorstore;

use Illuminate\Support\Manager;
use Mindwave\Mindwave\Contracts\Vectorstore;
use Mindwave\Mindwave\Vectorstore\Drivers\File;
use Mindwave\Mindwave\Vectorstore\Drivers\InMemory;
use Mindwave\Mindwave\Vectorstore\Drivers\Pinecone;
use Mindwave\Mindwave\Vectorstore\Drivers\Qdrant;
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
        return new InMemory;
    }

    public function createPineconeDriver(): Vectorstore
    {
        return new Pinecone(
            new Client(
                apiKey: $this->config->get('mindwave-vectorstore.vectorstores.pinecone.api_key'),
                indexHost: $this->config->get('mindwave-vectorstore.vectorstores.pinecone.index_host'),
            ),
            index: $this->config->get('mindwave-vectorstore.vectorstores.pinecone.index')
        );
    }

    public function createQdrantDriver(): Vectorstore
    {
        return new Qdrant(
            apiKey: $this->config->get('mindwave-vectorstore.vectorstores.qdrant.api_key'),
            collection: $this->config->get('mindwave-vectorstore.vectorstores.qdrant.collection'),
            host: $this->config->get('mindwave-vectorstore.vectorstores.qdrant.host'),
            port: $this->config->get('mindwave-vectorstore.vectorstores.qdrant.port'),
            dimensions: $this->config->get('mindwave-vectorstore.vectorstores.qdrant.dimensions', 1536),
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
            className: $this->config->get('mindwave-vectorstore.vectorstores.weaviate.index'),
            dimensions: $this->config->get('mindwave-vectorstore.vectorstores.weaviate.dimensions', 1536),
        );
    }
}
