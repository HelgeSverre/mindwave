<?php

declare(strict_types=1);

namespace Mindwave\Mindwave\Observability\Tracing\Exporters;

use InvalidArgumentException;
use OpenTelemetry\Contrib\Otlp\Protocols;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Registry;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Factory for creating OTLP (OpenTelemetry Protocol) span exporters
 *
 * This factory creates OTLP exporters that send traces to OpenTelemetry-compatible
 * backends like Jaeger, Grafana Tempo, Honeycomb, Datadog, and others.
 *
 * Supports both HTTP/protobuf and gRPC protocols for maximum compatibility.
 *
 * @see https://opentelemetry.io/docs/specs/otlp/
 */
final class OtlpExporterFactory
{
    /**
     * HTTP/Protobuf protocol constant
     */
    private const PROTOCOL_HTTP = 'http/protobuf';

    /**
     * gRPC protocol constant
     */
    private const PROTOCOL_GRPC = 'grpc';

    /**
     * Default OTLP endpoint for HTTP protocol
     */
    private const DEFAULT_HTTP_ENDPOINT = 'http://localhost:4318';

    /**
     * Default OTLP endpoint for gRPC protocol
     */
    private const DEFAULT_GRPC_ENDPOINT = 'http://localhost:4317';

    /**
     * Default timeout in milliseconds
     */
    private const DEFAULT_TIMEOUT_MS = 10000;

    /**
     * @var LoggerInterface Logger instance for error reporting
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param  LoggerInterface|null  $logger  Optional logger for error reporting
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * Create an OTLP exporter from Laravel configuration
     *
     * Reads configuration from config/mindwave-tracing.php:
     * - otlp.protocol: 'http/protobuf' or 'grpc'
     * - otlp.endpoint: The OTLP endpoint URL
     * - otlp.headers: Additional HTTP headers (e.g., API keys)
     *
     * @param  array<string, mixed>  $config  Configuration array from mindwave-tracing.otlp
     *
     * @throws InvalidArgumentException If configuration is invalid
     */
    public function fromConfig(array $config): SpanExporterInterface
    {
        $protocol = $config['protocol'] ?? self::PROTOCOL_HTTP;
        $endpoint = $config['endpoint'] ?? null;
        $headers = $config['headers'] ?? [];
        $timeout = $config['timeout_ms'] ?? self::DEFAULT_TIMEOUT_MS;

        // Validate protocol
        if (! in_array($protocol, [self::PROTOCOL_HTTP, self::PROTOCOL_GRPC], true)) {
            throw new InvalidArgumentException(
                "Invalid OTLP protocol: {$protocol}. Must be 'http/protobuf' or 'grpc'"
            );
        }

        // Use protocol-specific defaults if endpoint not specified
        if ($endpoint === null) {
            $endpoint = $protocol === self::PROTOCOL_GRPC
                ? self::DEFAULT_GRPC_ENDPOINT
                : self::DEFAULT_HTTP_ENDPOINT;
        }

        // Route to protocol-specific factory method
        return match ($protocol) {
            self::PROTOCOL_HTTP => $this->createHttpExporter($endpoint, $headers, $timeout),
            self::PROTOCOL_GRPC => $this->createGrpcExporter($endpoint, $headers, $timeout),
        };
    }

    /**
     * Create an HTTP/Protobuf OTLP exporter
     *
     * Creates an exporter that sends traces via HTTP using Protocol Buffers encoding.
     * This is the most widely supported OTLP transport.
     *
     * Common endpoints:
     * - Jaeger: http://localhost:4318/v1/traces
     * - Grafana Tempo: http://tempo:4318/v1/traces
     * - Honeycomb: https://api.honeycomb.io/v1/traces
     * - Datadog: http://localhost:4318/v1/traces (via Datadog Agent)
     *
     * @param  string  $endpoint  The OTLP HTTP endpoint URL (including /v1/traces path)
     * @param  array<string, string>  $headers  Additional HTTP headers (e.g., authentication)
     * @param  int  $timeoutMs  Request timeout in milliseconds
     *
     * @throws InvalidArgumentException If endpoint is invalid
     */
    public function createHttpExporter(
        string $endpoint,
        array $headers = [],
        int $timeoutMs = self::DEFAULT_TIMEOUT_MS
    ): SpanExporterInterface {
        $this->validateEndpoint($endpoint);
        $this->validateTimeout($timeoutMs);

        // Ensure endpoint includes the traces path if not already present
        $endpoint = $this->normalizeHttpEndpoint($endpoint);

        $this->logger->debug('Creating OTLP HTTP exporter', [
            'endpoint' => $endpoint,
            'headers_count' => count($headers),
            'timeout_ms' => $timeoutMs,
        ]);

        try {
            // Get the HTTP transport factory from the registry
            $protocol = Protocols::HTTP_PROTOBUF;
            $factoryClass = Registry::transportFactory($protocol);
            $factory = new $factoryClass;

            // Create transport with configuration
            $contentType = Protocols::contentType($protocol);
            $timeout = $timeoutMs / 1000; // Convert to seconds

            $transport = $factory->create(
                $endpoint,
                $contentType,
                $headers,
                'none', // compression
                $timeout
            );

            return new SpanExporter($transport);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create OTLP HTTP exporter', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new InvalidArgumentException(
                "Failed to create OTLP HTTP exporter: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Create a gRPC OTLP exporter
     *
     * Creates an exporter that sends traces via gRPC. This provides better performance
     * and streaming capabilities compared to HTTP, but requires gRPC support.
     *
     * Note: Requires the grpc PHP extension to be installed.
     *
     * Common endpoints:
     * - Jaeger: localhost:4317
     * - Grafana Tempo: tempo:4317
     *
     * @param  string  $endpoint  The OTLP gRPC endpoint (host:port format)
     * @param  array<string, string>  $headers  Additional gRPC metadata headers
     * @param  int  $timeoutMs  Request timeout in milliseconds
     *
     * @throws InvalidArgumentException If endpoint is invalid or gRPC is not available
     */
    public function createGrpcExporter(
        string $endpoint,
        array $headers = [],
        int $timeoutMs = self::DEFAULT_TIMEOUT_MS
    ): SpanExporterInterface {
        // Check if gRPC extension is available
        if (! extension_loaded('grpc')) {
            throw new InvalidArgumentException(
                'gRPC protocol requires the grpc PHP extension to be installed. '
                .'Install it with: pecl install grpc'
            );
        }

        $this->validateEndpoint($endpoint);
        $this->validateTimeout($timeoutMs);

        // Normalize gRPC endpoint
        $endpoint = $this->normalizeGrpcEndpoint($endpoint);

        $this->logger->debug('Creating OTLP gRPC exporter', [
            'endpoint' => $endpoint,
            'headers_count' => count($headers),
            'timeout_ms' => $timeoutMs,
        ]);

        try {
            // Get the gRPC transport factory from the registry
            $protocol = Protocols::GRPC;

            // Check if gRPC transport factory is available
            try {
                $factoryClass = Registry::transportFactory($protocol);
            } catch (\Throwable $e) {
                throw new RuntimeException(
                    'gRPC transport not available. Ensure the grpc extension is installed and enabled.',
                    0,
                    $e
                );
            }

            $factory = new $factoryClass;

            // Create transport with configuration
            $contentType = Protocols::contentType($protocol);
            $timeout = $timeoutMs / 1000; // Convert to seconds

            $transport = $factory->create(
                $endpoint,
                $contentType,
                $headers,
                'none', // compression
                $timeout
            );

            return new SpanExporter($transport);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create OTLP gRPC exporter', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new InvalidArgumentException(
                "Failed to create OTLP gRPC exporter: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Validate endpoint URL
     *
     * @throws InvalidArgumentException If endpoint is empty or invalid
     */
    private function validateEndpoint(string $endpoint): void
    {
        if (empty($endpoint)) {
            throw new InvalidArgumentException('OTLP endpoint cannot be empty');
        }

        // Basic URL validation
        if (! filter_var($endpoint, FILTER_VALIDATE_URL) && ! str_contains($endpoint, ':')) {
            throw new InvalidArgumentException(
                "Invalid OTLP endpoint format: {$endpoint}"
            );
        }
    }

    /**
     * Validate timeout value
     *
     * @throws InvalidArgumentException If timeout is invalid
     */
    private function validateTimeout(int $timeoutMs): void
    {
        if ($timeoutMs <= 0) {
            throw new InvalidArgumentException(
                "Timeout must be positive, got: {$timeoutMs}"
            );
        }

        if ($timeoutMs > 300000) { // 5 minutes
            $this->logger->warning('Very high OTLP timeout configured', [
                'timeout_ms' => $timeoutMs,
            ]);
        }
    }

    /**
     * Normalize HTTP endpoint to include /v1/traces path
     */
    private function normalizeHttpEndpoint(string $endpoint): string
    {
        // Remove trailing slash
        $endpoint = rtrim($endpoint, '/');

        // Add /v1/traces if not present
        if (! str_ends_with($endpoint, '/v1/traces')) {
            $endpoint .= '/v1/traces';
        }

        return $endpoint;
    }

    /**
     * Normalize gRPC endpoint to host:port format
     */
    private function normalizeGrpcEndpoint(string $endpoint): string
    {
        // Remove http:// or https:// prefix if present
        $endpoint = preg_replace('/^https?:\/\//', '', $endpoint);

        // Remove trailing slash
        $endpoint = rtrim($endpoint ?? '', '/');

        return $endpoint;
    }

    /**
     * Parse headers from environment variable format
     *
     * Parses OTLP headers from the standard environment variable format:
     * "key1=value1,key2=value2"
     *
     * @param  string  $headersString  Headers in environment variable format
     * @return array<string, string>
     */
    public static function parseHeadersFromEnv(string $headersString): array
    {
        if (empty($headersString)) {
            return [];
        }

        $headers = [];
        $pairs = explode(',', $headersString);

        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (empty($pair)) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if ($key !== '' && $value !== '') {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * Create exporter with smart defaults based on environment
     *
     * This method detects common OTLP environment variables and creates
     * an appropriate exporter automatically.
     *
     * Environment variables checked:
     * - OTEL_EXPORTER_OTLP_ENDPOINT
     * - OTEL_EXPORTER_OTLP_TRACES_ENDPOINT
     * - OTEL_EXPORTER_OTLP_PROTOCOL
     * - OTEL_EXPORTER_OTLP_HEADERS
     * - OTEL_EXPORTER_OTLP_TIMEOUT
     */
    public function createFromEnvironment(): SpanExporterInterface
    {
        // Prefer traces-specific endpoint, fall back to general endpoint
        $endpoint = getenv('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT')
            ?: getenv('OTEL_EXPORTER_OTLP_ENDPOINT')
            ?: self::DEFAULT_HTTP_ENDPOINT;

        $protocol = getenv('OTEL_EXPORTER_OTLP_PROTOCOL') ?: self::PROTOCOL_HTTP;

        $headersString = getenv('OTEL_EXPORTER_OTLP_HEADERS') ?: '';
        $headers = self::parseHeadersFromEnv($headersString);

        $timeoutString = getenv('OTEL_EXPORTER_OTLP_TIMEOUT');
        $timeout = $timeoutString !== false
            ? (int) ($timeoutString * 1000) // Convert seconds to milliseconds
            : self::DEFAULT_TIMEOUT_MS;

        $this->logger->info('Creating OTLP exporter from environment', [
            'endpoint' => $endpoint,
            'protocol' => $protocol,
            'has_headers' => ! empty($headers),
        ]);

        return $this->fromConfig([
            'endpoint' => $endpoint,
            'protocol' => $protocol,
            'headers' => $headers,
            'timeout_ms' => $timeout,
        ]);
    }
}
