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
        'pinecone' => [
            'api_key' => env('MINDWAVE_LLM_OPENAI_CHAT_API_KEY'),
            'collection' => 'items', // TODO(18 May 2023) ~ Helge: configurable
        ],

        'weaviate' => [
            'api_key' => env('MINDWAVE_LLM_OPENAI_COMPLETION_API_KEY'),
            'collection' => 'items', // TODO(18 May 2023) ~ Helge: configurable
        ],
    ],

];
