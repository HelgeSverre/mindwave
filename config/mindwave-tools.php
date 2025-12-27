<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tool Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for built-in tools that require API keys or other settings.
    |
    */

    'brave_search' => [
        'api_key' => env('BRAVE_SEARCH_API_KEY'),
        'results_count' => env('BRAVE_SEARCH_RESULTS_COUNT', 5),
    ],

];
