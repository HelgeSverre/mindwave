<?php

return [

    /*
   |--------------------------------------------------------------------------
   | Default Embeddings
   |--------------------------------------------------------------------------
   |
   | The default embeddings define the default configuration to use for embedding.
   | This configuration option allows you to specify the default embedding provider
   | to be used throughout the application. By default, the 'openai' provider is set.
   | You can customize this option by updating the 'MINDWAVE_EMBEDDINGS' environment variable.
   */
    'default' => env('MINDWAVE_EMBEDDINGS', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Providers
    |--------------------------------------------------------------------------
    |
    | Configure your embedding providers here. Embeddings convert text into
    | numerical vectors that capture semantic meaning, enabling similarity
    | search and context discovery.
    |
    | OpenAI Embedding Models:
    | - text-embedding-ada-002: 1536 dimensions, good general-purpose model
    | - text-embedding-3-small: 1536 dimensions, faster and cheaper
    | - text-embedding-3-large: 3072 dimensions, higher accuracy
    |
    | Important: Ensure your vector store dimensions match your embedding model.
    | Set MINDWAVE_QDRANT_DIMENSIONS, MINDWAVE_PINECONE_DIMENSIONS, etc.
    |
    */
    'embeddings' => [
        'openai' => [
            'api_key' => env('MINDWAVE_OPENAI_API_KEY'),
            'org_id' => env('MINDWAVE_OPENAI_ORG_ID'),
            'model' => 'text-embedding-ada-002',
        ],
    ],
];
