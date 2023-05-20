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
            'index' => 'items', // TODO(18 May 2023) ~ Helge: this concept needs to be implemented in vectorstore
        ],

        'weaviate' => [
            'api_url' => env('MINDWAVE_WEAVIATE_URL'),
            'api_token' => env('MINDWAVE_WEAVIATE_API_TOKEN'),
            'additional_headers' => [],
            'index' => 'items', // TODO(18 May 2023) ~ Helge: this concept needs to be implemented in vectorstore
        ],
    ],

];
