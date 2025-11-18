<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TNTSearch Storage Path
    |--------------------------------------------------------------------------
    |
    | Directory where ephemeral TNTSearch indexes are stored.
    | Indexes are automatically cleaned up based on TTL.
    |
    */
    'tntsearch' => [
        'storage_path' => storage_path('mindwave/tnt-indexes'),
        'ttl_hours' => env('MINDWAVE_TNT_INDEX_TTL', 24),
        'max_index_size_mb' => env('MINDWAVE_TNT_MAX_INDEX_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Pipeline Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for context aggregation and ranking.
    |
    */
    'pipeline' => [
        'default_limit' => 10,
        'deduplicate' => true,
        'format' => 'numbered', // numbered, markdown, json
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracing
    |--------------------------------------------------------------------------
    |
    | Enable OpenTelemetry tracing for context operations.
    |
    */
    'tracing' => [
        'enabled' => env('MINDWAVE_CONTEXT_TRACING', true),
        'trace_searches' => true,
        'trace_index_creation' => true,
    ],
];
