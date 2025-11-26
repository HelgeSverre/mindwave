<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default VectorStore
    |--------------------------------------------------------------------------
    |
    | This option controls the default vector store driver that will be used
    | by Mindwave for storing and searching document embeddings. Vector stores
    | enable semantic similarity search, which is essential for RAG (Retrieval
    | Augmented Generation) applications.
    |
    | Supported: "array", "file", "pinecone", "qdrant", "weaviate"
    |
    */

    'default' => env('MINDWAVE_VECTORSTORE', 'pinecone'),

    /*
    |--------------------------------------------------------------------------
    | VectorStore Configurations
    |--------------------------------------------------------------------------
    |
    | Configure connection information for each vector store driver.
    |
    | When choosing a driver, consider:
    | - "array": In-memory, ephemeral - great for testing
    | - "file": Local JSON file - simple persistence without external services
    | - "pinecone": Managed cloud service with excellent scalability
    | - "weaviate": Self-hosted or cloud with hybrid search capabilities
    | - "qdrant": High-performance with filtering support
    |
    | All production stores support configurable dimensions to match your
    | embedding model: 1536 for OpenAI ada-002/3-small, 3072 for 3-large.
    |
    */
    'vectorstores' => [
        'array' => [
            // Has no configuration, used for testing
        ],

        'file' => [
            'path' => env('MINDWAVE_VECTORSTORE_PATH', storage_path('mindwave/vectorstore.json')),
        ],

        'pinecone' => [
            'api_key' => env('MINDWAVE_PINECONE_API_KEY'),
            'environment' => env('MINDWAVE_PINECONE_ENVIRONMENT'),
            'index' => env('MINDWAVE_PINECONE_INDEX'),
            // Pinecone index must be created with the correct dimension beforehand
            // Common values: 1536 (OpenAI ada-002, 3-small), 3072 (OpenAI 3-large)
            'dimensions' => env('MINDWAVE_PINECONE_DIMENSIONS', 1536),
        ],

        'weaviate' => [
            'api_url' => env('MINDWAVE_WEAVIATE_URL', 'http://localhost:8080/v1'),
            'api_token' => env('MINDWAVE_WEAVIATE_API_TOKEN', 'password'),
            'index' => env('MINDWAVE_WEAVIATE_INDEX', 'items'),
            'additional_headers' => [],
            // Embedding dimension for schema validation
            // Common values: 1536 (OpenAI ada-002, 3-small), 3072 (OpenAI 3-large)
            'dimensions' => env('MINDWAVE_WEAVIATE_DIMENSIONS', 1536),
        ],

        'qdrant' => [
            'host' => env('MINDWAVE_QDRANT_HOST', 'localhost'),
            'port' => env('MINDWAVE_QDRANT_PORT', '6333'),
            'api_key' => env('MINDWAVE_QDRANT_API_KEY', ''),
            'collection' => env('MINDWAVE_QDRANT_COLLECTION', 'items'),
            // Embedding dimension - must match your embedding model
            // Common values: 1536 (OpenAI ada-002, 3-small), 3072 (OpenAI 3-large)
            'dimensions' => env('MINDWAVE_QDRANT_DIMENSIONS', 1536),
        ],
    ],

];
