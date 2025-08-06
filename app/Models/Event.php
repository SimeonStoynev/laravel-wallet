<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'event_data',
        'metadata',
        'version',
        'occurred_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_data' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    // Relationships

    /**
     * Get the owning aggregate model.
     */
    public function aggregate(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes

    /**
     * Scope to filter events by aggregate type.
     */
    public function scopeForAggregateType($query, string $aggregateType)
    {
        return $query->where('aggregate_type', $aggregateType);
    }

    /**
     * Scope to filter events by aggregate.
     */
    public function scopeForAggregate($query, string $aggregateType, int $aggregateId)
    {
        return $query->where('aggregate_type', $aggregateType)
                    ->where('aggregate_id', $aggregateId);
    }

    /**
     * Scope to filter events by type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to order events by occurrence time.
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('occurred_at', 'asc');
    }

    /**
     * Scope to order events by reverse occurrence time.
     */
    public function scopeReverseChronological($query)
    {
        return $query->orderBy('occurred_at', 'desc');
    }

    /**
     * Scope to filter events after a specific time.
     */
    public function scopeAfter($query, $timestamp)
    {
        return $query->where('occurred_at', '>', $timestamp);
    }

    /**
     * Scope to filter events before a specific time.
     */
    public function scopeBefore($query, $timestamp)
    {
        return $query->where('occurred_at', '<', $timestamp);
    }

    /**
     * Scope to filter events by version.
     */
    public function scopeByVersion($query, int $version)
    {
        return $query->where('version', $version);
    }

    // Helper Methods

    /**
     * Create an event for a specific aggregate.
     */
    public static function createForAggregate(
        string $aggregateType,
        int $aggregateId,
        string $eventType,
        array $eventData,
        ?array $metadata = null,
        ?int $version = null
    ): self {
        return self::create([
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'metadata' => $metadata,
            'version' => $version ?? 1,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Get the next version number for an aggregate.
     */
    public static function getNextVersionForAggregate(string $aggregateType, int $aggregateId): int
    {
        $lastVersion = self::forAggregate($aggregateType, $aggregateId)
            ->max('version');

        return ($lastVersion ?? 0) + 1;
    }

    /**
     * Get event history for an aggregate.
     */
    public static function getHistoryForAggregate(string $aggregateType, int $aggregateId)
    {
        return self::forAggregate($aggregateType, $aggregateId)
            ->chronological()
            ->get();
    }

    /**
     * Replay events from a specific version.
     */
    public static function replayFromVersion(string $aggregateType, int $aggregateId, int $fromVersion)
    {
        return self::forAggregate($aggregateType, $aggregateId)
            ->where('version', '>=', $fromVersion)
            ->chronological()
            ->get();
    }
}
