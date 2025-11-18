<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tracing Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether OpenTelemetry tracing is enabled for Mindwave
    | operations. When enabled, all LLM interactions, tool executions, and other
    | operations will be traced and stored according to your configured exporters.
    |
    */

    'enabled' => env('MINDWAVE_TRACING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Service Name
    |--------------------------------------------------------------------------
    |
    | The service name identifies your application in distributed tracing systems.
    | This will appear in tracing UIs like Jaeger, Grafana, or other OTLP-compatible
    | systems. Defaults to your application name if not specified.
    |
    */

    'service_name' => env('MINDWAVE_SERVICE_NAME', env('APP_NAME', 'laravel-app')),

    /*
    |--------------------------------------------------------------------------
    | Database Storage
    |--------------------------------------------------------------------------
    |
    | Store traces in your application database for local querying and analysis.
    | This is useful for building admin dashboards, cost tracking, and debugging.
    | You can specify which database connection to use, or null for the default.
    |
    */

    'database' => [
        'enabled' => env('MINDWAVE_TRACE_DATABASE', true),
        'connection' => env('MINDWAVE_TRACE_DB_CONNECTION', null), // null = default connection
    ],

    /*
    |--------------------------------------------------------------------------
    | OTLP Exporter
    |--------------------------------------------------------------------------
    |
    | Export traces to OTLP-compatible systems like Jaeger, Grafana Tempo,
    | Honeycomb, or other observability platforms. This enables integration
    | with your existing monitoring infrastructure.
    |
    | Supported protocols: 'http/protobuf', 'grpc'
    | Common endpoints:
    |   - Jaeger: http://localhost:4318 (OTLP HTTP)
    |   - Grafana Tempo: http://localhost:4318
    |   - Honeycomb: https://api.honeycomb.io
    |
    */

    'otlp' => [
        'enabled' => env('MINDWAVE_TRACE_OTLP_ENABLED', false),
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
        'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'), // http/protobuf, grpc
        'headers' => env('OTEL_EXPORTER_OTLP_HEADERS', []),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampling Configuration
    |--------------------------------------------------------------------------
    |
    | Control which traces are recorded to manage data volume and costs.
    | Sampling strategies:
    |   - always_on: Record all traces (useful for development)
    |   - always_off: Disable all tracing
    |   - traceidratio: Sample a percentage of traces (e.g., 0.1 = 10%)
    |
    */

    'sampler' => [
        'type' => env('MINDWAVE_TRACE_SAMPLER', 'always_on'), // always_on, always_off, traceidratio
        'ratio' => (float) env('MINDWAVE_TRACE_SAMPLE_RATIO', 1.0), // 0.0 to 1.0 for traceidratio sampler
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Processing
    |--------------------------------------------------------------------------
    |
    | Configure how spans are batched before export. Batching improves
    | performance by reducing the number of export calls. Adjust these
    | settings based on your application's load and latency requirements.
    |
    */

    'batch' => [
        'max_queue_size' => (int) env('MINDWAVE_TRACE_BATCH_MAX_QUEUE', 2048),
        'scheduled_delay_ms' => (int) env('MINDWAVE_TRACE_BATCH_DELAY', 5000),
        'export_timeout_ms' => (int) env('MINDWAVE_TRACE_BATCH_TIMEOUT', 512),
        'max_export_batch_size' => (int) env('MINDWAVE_TRACE_BATCH_SIZE', 256),
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy & Security
    |--------------------------------------------------------------------------
    |
    | Control what data is captured in traces. By default, message content
    | is NOT captured to protect sensitive information. Enable message capture
    | only in development or when you have proper data governance in place.
    |
    | The pii_redact array lists OpenTelemetry attribute names that should
    | be redacted when message capture is enabled.
    |
    */

    'capture_messages' => env('MINDWAVE_TRACE_CAPTURE_MESSAGES', false),

    'pii_redact' => [
        'gen_ai.input.messages',
        'gen_ai.output.messages',
        'gen_ai.system_instructions',
        'gen_ai.tool.call.arguments',
        'gen_ai.tool.call.result',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep trace data in the database (in days). Older traces
    | will be automatically pruned. This helps manage database size and
    | comply with data retention policies.
    |
    */

    'retention_days' => (int) env('MINDWAVE_TRACE_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Cost Estimation
    |--------------------------------------------------------------------------
    |
    | Enable automatic cost calculation for LLM operations based on token
    | usage and provider pricing. Costs are calculated per 1000 tokens
    | for both input and output tokens.
    |
    | Prices are in USD per 1000 tokens and should be updated periodically
    | to reflect current provider pricing.
    |
    */

    'cost_estimation' => [
        'enabled' => env('MINDWAVE_COST_ESTIMATION_ENABLED', true),

        'pricing' => [
            // OpenAI Pricing (per 1000 tokens)
            'openai' => [
                'gpt-4' => [
                    'input' => 0.03,
                    'output' => 0.06,
                ],
                'gpt-4-turbo' => [
                    'input' => 0.01,
                    'output' => 0.03,
                ],
                'gpt-4-turbo-preview' => [
                    'input' => 0.01,
                    'output' => 0.03,
                ],
                'gpt-4-1106-preview' => [
                    'input' => 0.01,
                    'output' => 0.03,
                ],
                'gpt-4-0125-preview' => [
                    'input' => 0.01,
                    'output' => 0.03,
                ],
                'gpt-3.5-turbo' => [
                    'input' => 0.0005,
                    'output' => 0.0015,
                ],
                'gpt-3.5-turbo-1106' => [
                    'input' => 0.001,
                    'output' => 0.002,
                ],
                'gpt-3.5-turbo-0125' => [
                    'input' => 0.0005,
                    'output' => 0.0015,
                ],
            ],

            // Anthropic Claude Pricing (per 1000 tokens)
            'anthropic' => [
                'claude-3-opus-20240229' => [
                    'input' => 0.015,
                    'output' => 0.075,
                ],
                'claude-3-opus' => [
                    'input' => 0.015,
                    'output' => 0.075,
                ],
                'claude-3-sonnet-20240229' => [
                    'input' => 0.003,
                    'output' => 0.015,
                ],
                'claude-3-sonnet' => [
                    'input' => 0.003,
                    'output' => 0.015,
                ],
                'claude-3-haiku-20240307' => [
                    'input' => 0.00025,
                    'output' => 0.00125,
                ],
                'claude-3-haiku' => [
                    'input' => 0.00025,
                    'output' => 0.00125,
                ],
                'claude-2.1' => [
                    'input' => 0.008,
                    'output' => 0.024,
                ],
                'claude-2' => [
                    'input' => 0.008,
                    'output' => 0.024,
                ],
            ],

            // Mistral AI Pricing (per 1000 tokens)
            'mistral' => [
                'mistral-large-latest' => [
                    'input' => 0.004,
                    'output' => 0.012,
                ],
                'mistral-medium-latest' => [
                    'input' => 0.0027,
                    'output' => 0.0081,
                ],
                'mistral-small-latest' => [
                    'input' => 0.001,
                    'output' => 0.003,
                ],
                'mistral-tiny' => [
                    'input' => 0.00025,
                    'output' => 0.00025,
                ],
            ],

            // Google Gemini Pricing (per 1000 tokens)
            'google' => [
                'gemini-pro' => [
                    'input' => 0.00025,
                    'output' => 0.0005,
                ],
                'gemini-pro-vision' => [
                    'input' => 0.00025,
                    'output' => 0.0005,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Attributes
    |--------------------------------------------------------------------------
    |
    | Additional resource attributes to include in all traces. These help
    | identify and filter traces in your observability platform. Common
    | attributes include deployment environment, version, etc.
    |
    */

    'resource_attributes' => [
        'deployment.environment' => env('APP_ENV', 'production'),
        'service.version' => env('APP_VERSION', '1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Instrumentation
    |--------------------------------------------------------------------------
    |
    | Control which Mindwave components are instrumented. You can disable
    | tracing for specific components if needed.
    |
    */

    'instrumentation' => [
        'llm' => env('MINDWAVE_TRACE_LLM', true),
        'tools' => env('MINDWAVE_TRACE_TOOLS', true),
        'vectorstore' => env('MINDWAVE_TRACE_VECTORSTORE', true),
        'embeddings' => env('MINDWAVE_TRACE_EMBEDDINGS', true),
        'memory' => env('MINDWAVE_TRACE_MEMORY', true),
    ],
];
