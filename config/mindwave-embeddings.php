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
    | Embeddings
    |--------------------------------------------------------------------------
    |
    | todo: meaningful comments here
    */
    'embeddings' => [
        'openai' => [
            'api_key' => env('MINDWAVE_OPENAI_API_KEY'),
            'org_id' => env('MINDWAVE_OPENAI_ORG_ID'),
            'model' => 'text-embedding-ada-002',
        ],
    ],
];
