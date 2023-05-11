<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LLM
    |--------------------------------------------------------------------------
    |
    | todo: taylorized description
    */

    'default' => env('MINDWAVE_LLM', 'openai_chat'),

    /*
    |--------------------------------------------------------------------------
    | LLMs
    |--------------------------------------------------------------------------
    |
    | todo: taylorized description
    */
    'llms' => [
        'openai_chat' => [
            'api_key' => env('MINDWAVE_LLM_OPENAI_CHAT_API_KEY'),
            'org_id' => env('MINDWAVE_LLM_OPENAI_CHAT_ORG_ID'),
            'model' => env('MINDWAVE_LLM_OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),
            'temperature' => 0.4,
        ],

        'openai_completion' => [
            'api_key' => env('MINDWAVE_LLM_OPENAI_COMPLETION_API_KEY'),
            'org_id' => env('MINDWAVE_LLM_OPENAI_COMPLETION_ORG_ID'),
            'model' => 'text-davinci-003',
            'temperature' => 0.4,
        ],
    ],

];
