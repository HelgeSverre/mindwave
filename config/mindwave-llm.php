<?php

use Mindwave\Mindwave\LLM\Drivers\OpenAI\Model;

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

    'default' => env('MINDWAVE_LLM', 'openai'),

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

        'openai' => [
            'api_key' => env('MINDWAVE_OPENAI_API_KEY'),
            'org_id' => env('MINDWAVE_OPENAI_ORG_ID'),
            'model' => env('MINDWAVE_OPENAI_MODEL', 'gpt-4-1106-preview'),
            'max_tokens' => env('MINDWAVE_OPENAI_MAX_TOKENS', 1000),
            'temperature' => env('MINDWAVE_OPENAI_TEMPERATURE', 0.4),
        ],

        'mistral' => [
            'api_key' => env('MINDWAVE_MISTRAL_API_KEY'),
            'base_url' => env('MINDWAVE_MISTRAL_BASE_URL'),
            'model' => env('MINDWAVE_MISTRAL_MODEL', 'mistral-medium'),
            'system_message' => env('MINDWAVE_MISTRAL_SYSTEM_MESSAGE'),
            'max_tokens' => env('MINDWAVE_MISTRAL_MAX_TOKENS', 1000),
            'temperature' => env('MINDWAVE_MISTRAL_TEMPERATURE', 0.4),
            'safe_mode' => env('MINDWAVE_MISTRAL_SAFE_MODE', false),
            'random_seed' => env('MINDWAVE_MISTRAL_RANDOM_SEED'),
        ],

        'anthropic' => [
            'api_key' => env('MINDWAVE_ANTHROPIC_API_KEY'),
            'model' => env('MINDWAVE_ANTHROPIC_MODEL', 'claude-sonnet-4-5-20250929'),
            'system_message' => env('MINDWAVE_ANTHROPIC_SYSTEM_MESSAGE'),
            'max_tokens' => env('MINDWAVE_ANTHROPIC_MAX_TOKENS', 4096),
            'temperature' => env('MINDWAVE_ANTHROPIC_TEMPERATURE', 1.0),
        ],
    ],
];
