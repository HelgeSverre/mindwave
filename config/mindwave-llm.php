<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LLM
    |--------------------------------------------------------------------------
    |
    | The default LLM (Language Model) configuration defines the default LLM to use
    | for generating responses. This option allows you to specify the default LLM
    | provider to be used throughout the application. By default, the 'openai_chat'
    | LLM is set. You can customize this option by updating the 'MINDWAVE_LLM' environment variable.
    */

    'default' => env('MINDWAVE_LLM', 'openai_chat'),

    /*
    |--------------------------------------------------------------------------
    | LLMs
    |--------------------------------------------------------------------------
    |
    | The LLMs configuration allows you to define different Language Model providers and their settings.
    | Here we have 'openai_chat' and 'openai_completion' LLM providers with their respective models,
    | API keys, organization IDs, and temperature settings for response generation.
    */

    'llms' => [
        'openai_chat' => [
            'api_key' => env('MINDWAVE_OPENAI_API_KEY'),
            'org_id' => env('MINDWAVE_OPENAI_ORG_ID'),
            'model' => env('MINDWAVE_OPENAI_MODEL', 'gpt-3.5-turbo'),
            'max_tokens' => 1000,
            'temperature' => 0.4,
        ],

        'openai_completion' => [
            'api_key' => env('MINDWAVE_OPENAI_API_KEY'),
            'org_id' => env('MINDWAVE_OPENAI_ORG_ID'),
            'model' => env('MINDWAVE_OPENAI_MODEL', 'text-davinci-003'),
            'max_tokens' => 1000,
            'temperature' => 0.4,
        ],
    ],
];
