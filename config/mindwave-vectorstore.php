<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default VectorStore
    |--------------------------------------------------------------------------
    |
    | todo: taylorized description
    */

    'default' => env('MINDWAVE_VECTORSTORE', 'pinecone'),

    /*
    |--------------------------------------------------------------------------
    | VectorStores
    |--------------------------------------------------------------------------
    |
    | todo: taylorized description
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
