<?php

namespace Mindwave\Mindwave\Observability\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trace Model
 *
 * Represents a complete trace (one per request/conversation) containing multiple spans.
 *
 * @property string $id UUID primary key
 * @property string $trace_id OpenTelemetry trace ID (32 chars hex)
 * @property string $service_name Name of the service
 * @property int $start_time Start timestamp in nanoseconds
 * @property int|null $end_time End timestamp in nanoseconds
 * @property int|null $duration Duration in nanoseconds
 * @property string $status Status code: ok, error, unset
 * @property string|null $root_span_id Root span ID
 * @property int $total_spans Total number of spans in this trace
 * @property int $total_input_tokens Total input tokens across all spans
 * @property int $total_output_tokens Total output tokens across all spans
 * @property float $estimated_cost Estimated cost in USD
 * @property array|null $metadata Additional metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Trace extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mindwave_traces';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'trace_id',
        'service_name',
        'start_time',
        'end_time',
        'duration',
        'status',
        'root_span_id',
        'total_spans',
        'total_input_tokens',
        'total_output_tokens',
        'estimated_cost',
        'metadata',
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
        'total_spans' => 'integer',
        'total_input_tokens' => 'integer',
        'total_output_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
        'metadata' => 'array',
    ];

    /**
     * Get all spans belonging to this trace.
     *
     * @return HasMany<Span>
     */
    public function spans(): HasMany
    {
        return $this->hasMany(Span::class, 'trace_id', 'trace_id');
    }

    /**
     * Get the root span of this trace.
     *
     * @return HasOne<Span>
     */
    public function rootSpan(): HasOne
    {
        return $this->hasOne(Span::class, 'span_id', 'root_span_id');
    }

    /**
     * Scope a query to only include slow traces.
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
     * Scope a query to only include expensive traces.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $minCost Minimum cost in USD
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpensive($query, float $minCost = 0.01)
    {
        return $query->where('estimated_cost', '>', $minCost);
    }

    /**
     * Get the total number of tokens (input + output).
     *
     * @return int
     */
    public function getTotalTokens(): int
    {
        return $this->total_input_tokens + $this->total_output_tokens;
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
     * Check if the trace has an error status.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Check if the trace is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->end_time !== null;
    }
}
