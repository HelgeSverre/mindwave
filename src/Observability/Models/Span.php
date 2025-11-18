<?php

namespace Mindwave\Mindwave\Observability\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Span Model
 *
 * Represents an individual span (LLM call, tool execution, etc.) within a trace.
 *
 * @property string $id UUID primary key
 * @property string $trace_id OpenTelemetry trace ID
 * @property string $span_id OpenTelemetry span ID (16 chars hex)
 * @property string|null $parent_span_id Parent span ID
 * @property string $name Span name
 * @property string $kind Span kind: client, server, internal, producer, consumer
 * @property int $start_time Start timestamp in nanoseconds
 * @property int|null $end_time End timestamp in nanoseconds
 * @property int|null $duration Duration in nanoseconds
 * @property string|null $operation_name GenAI operation: chat, embeddings, execute_tool
 * @property string|null $provider_name GenAI provider: openai, anthropic, etc.
 * @property string|null $request_model Model used for request
 * @property string|null $response_model Model used for response
 * @property int|null $input_tokens Number of input tokens
 * @property int|null $output_tokens Number of output tokens
 * @property int|null $cache_read_tokens Number of cache read tokens
 * @property int|null $cache_creation_tokens Number of cache creation tokens
 * @property float|null $temperature Temperature parameter
 * @property int|null $max_tokens Max tokens parameter
 * @property float|null $top_p Top-p parameter
 * @property array|null $finish_reasons Finish reasons
 * @property string $status_code Status code
 * @property string|null $status_description Status description
 * @property array|null $attributes Full attributes
 * @property array|null $events Span events
 * @property array|null $links Links to other spans
 * @property \Illuminate\Support\Carbon $created_at
 */
class Span extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mindwave_spans';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'trace_id',
        'span_id',
        'parent_span_id',
        'name',
        'kind',
        'start_time',
        'end_time',
        'duration',
        'operation_name',
        'provider_name',
        'request_model',
        'response_model',
        'input_tokens',
        'output_tokens',
        'cache_read_tokens',
        'cache_creation_tokens',
        'temperature',
        'max_tokens',
        'top_p',
        'finish_reasons',
        'status_code',
        'status_description',
        'attributes',
        'events',
        'links',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'integer',
        'end_time' => 'integer',
        'duration' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cache_read_tokens' => 'integer',
        'cache_creation_tokens' => 'integer',
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'top_p' => 'float',
        'attributes' => 'array',
        'events' => 'array',
        'links' => 'array',
        'finish_reasons' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the trace that owns this span.
     *
     * @return BelongsTo<Trace, Span>
     */
    public function trace(): BelongsTo
    {
        return $this->belongsTo(Trace::class, 'trace_id', 'trace_id');
    }

    /**
     * Get the parent span.
     *
     * @return BelongsTo<Span, Span>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Span::class, 'parent_span_id', 'span_id');
    }

    /**
     * Get the child spans.
     *
     * @return HasMany<Span>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Span::class, 'parent_span_id', 'span_id');
    }

    /**
     * Get the messages associated with this span.
     *
     * @return HasMany<SpanMessage>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SpanMessage::class, 'span_id', 'span_id');
    }

    /**
     * Get the duration in seconds.
     *
     * @return float|null
     */
    public function getDurationInSeconds(): ?float
    {
        if ($this->duration === null) {
            return null;
        }

        return $this->duration / 1_000_000_000;
    }

    /**
     * Get the duration in milliseconds.
     *
     * @return float|null
     */
    public function getDurationInMilliseconds(): ?float
    {
        if ($this->duration === null) {
            return null;
        }

        return $this->duration / 1_000_000;
    }

    /**
     * Get the total number of tokens (input + output).
     *
     * @return int
     */
    public function getTotalTokens(): int
    {
        return ($this->input_tokens ?? 0) + ($this->output_tokens ?? 0);
    }

    /**
     * Get the total cache tokens (read + creation).
     *
     * @return int
     */
    public function getTotalCacheTokens(): int
    {
        return ($this->cache_read_tokens ?? 0) + ($this->cache_creation_tokens ?? 0);
    }

    /**
     * Check if this span represents an LLM call.
     *
     * @return bool
     */
    public function isLlmCall(): bool
    {
        return in_array($this->operation_name, ['chat', 'text_completion', 'embeddings']);
    }

    /**
     * Check if this span represents a tool execution.
     *
     * @return bool
     */
    public function isToolExecution(): bool
    {
        return $this->operation_name === 'execute_tool';
    }

    /**
     * Check if this span has an error status.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->status_code === 'error';
    }

    /**
     * Check if this is a root span (no parent).
     *
     * @return bool
     */
    public function isRootSpan(): bool
    {
        return $this->parent_span_id === null;
    }

    /**
     * Get a specific attribute value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $key, $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Scope a query to only include spans of a specific operation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $operation
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOperation($query, string $operation)
    {
        return $query->where('operation_name', $operation);
    }

    /**
     * Scope a query to only include spans from a specific provider.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider_name', $provider);
    }

    /**
     * Scope a query to only include spans using a specific model.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeModel($query, string $model)
    {
        return $query->where('request_model', $model);
    }

    /**
     * Scope a query to only include slow spans.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $thresholdMs Duration threshold in milliseconds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSlow($query, int $thresholdMs = 5000)
    {
        return $query->where('duration', '>', $thresholdMs * 1_000_000);
    }

    /**
     * Scope a query to only include spans with errors.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithErrors($query)
    {
        return $query->where('status_code', 'error');
    }
}
