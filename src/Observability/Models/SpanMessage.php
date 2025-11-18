<?php

namespace Mindwave\Mindwave\Observability\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SpanMessage Model
 *
 * Stores large message content separately from spans (opt-in feature).
 * Used to avoid bloating the spans table with full message contents.
 *
 * @property string $id UUID primary key
 * @property string $span_id OpenTelemetry span ID
 * @property string $type Message type: input, output, system
 * @property array $messages Array of messages
 * @property \Illuminate\Support\Carbon $created_at
 */
class SpanMessage extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mindwave_span_messages';

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
        'span_id',
        'type',
        'messages',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'messages' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the span that owns this message.
     *
     * @return BelongsTo<Span, SpanMessage>
     */
    public function span(): BelongsTo
    {
        return $this->belongsTo(Span::class, 'span_id', 'span_id');
    }

    /**
     * Check if this is an input message.
     *
     * @return bool
     */
    public function isInput(): bool
    {
        return $this->type === 'input';
    }

    /**
     * Check if this is an output message.
     *
     * @return bool
     */
    public function isOutput(): bool
    {
        return $this->type === 'output';
    }

    /**
     * Check if this is a system message.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    /**
     * Get the total number of messages.
     *
     * @return int
     */
    public function getMessageCount(): int
    {
        return is_array($this->messages) ? count($this->messages) : 0;
    }

    /**
     * Get the total character count of all messages.
     *
     * @return int
     */
    public function getTotalCharacters(): int
    {
        if (!is_array($this->messages)) {
            return 0;
        }

        return array_reduce($this->messages, function ($carry, $message) {
            if (is_array($message) && isset($message['content'])) {
                return $carry + strlen($message['content']);
            }

            return $carry;
        }, 0);
    }

    /**
     * Scope a query to only include messages of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
