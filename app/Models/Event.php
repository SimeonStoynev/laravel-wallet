<?php

namespace App\Models;

use Database\Factories\EventFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    /**
     * @use HasFactory<EventFactory>
     */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     *
     * @return MorphTo<Model, $this>
     */
    public function aggregate(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes

    /**
     * Scope to filter events by aggregate type.
     *
     * @param Builder<Event> $query
     * @return Builder<Event>
     */
    public function scopeForAggregateType(Builder $query, string $aggregateType): Builder
    {
        return $query->where('aggregate_type', $aggregateType);
    }

    /**
     * Scope to filter events by aggregate.
     *
     * @param Builder<Event> $query
     * @return Builder<Event>
     */
    public function scopeForAggregate(Builder $query, string $aggregateType, int $aggregateId): Builder
    {
        return $query->where('aggregate_type', $aggregateType)
                    ->where('aggregate_id', $aggregateId);
    }

    /**
     * Scope to filter events by type.
     *
     * @param Builder<Event> $query
     * @return Builder<Event>
     */
    public function scopeByEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to order events by occurrence time.
     *
     * @param Builder<Event> $query
     * @return Builder<Event>
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('occurred_at');
    }

    /**
     * Scope to order events by reverse occurrence time.
     *
     * @param Builder<Event> $query
     * @return Builder<Event>
     */
    public function scopeReverseChronological(Builder $query): Builder
    {
        return $query->orderBy('occurred_at', 'desc');
    }

    /**
     * Scope to filter events after a specific time.
     *
     * @param Builder<Event> $query
     * @param DateTimeInterface|string $timestamp
     * @return Builder<Event>
     */
    public function scopeAfter(Builder $query, DateTimeInterface|string $timestamp): Builder
    {
        return $query->where('occurred_at', '>', $timestamp);
    }

    /**
     * Scope to filter events before a specific time.
     *
     * @param Builder<Event> $query
     * @param DateTimeInterface|string $timestamp
     * @return Builder<Event>
     */
    public function scopeBefore(Builder $query, DateTimeInterface|string $timestamp): Builder
    {
        return $query->where('occurred_at', '<', $timestamp);
    }

    /**
     * Scope to filter events by version.
     *
     * @param Builder<Event> $query
     * @return Builder<Event>
     */
    public function scopeByVersion(Builder $query, int $version): Builder
    {
        return $query->where('version', $version);
    }

    // Helper Methods

    /**
     * Create an event for a specific aggregate.
     *
     * @param array<string, mixed> $eventData
     * @param array<string, mixed>|null $metadata
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

        return (is_numeric($lastVersion) ? (int) $lastVersion : 0) + 1;
    }

    /**
     * Get event history for an aggregate.
     *
     * @return Collection<int, Event>
     */
    public static function getHistoryForAggregate(string $aggregateType, int $aggregateId): Collection
    {
        return self::forAggregate($aggregateType, $aggregateId)
            ->chronological()
            ->get();
    }

    /**
     * Replay events from a specific version.
     *
     * @return Collection<int, Event>
     */
    public static function replayFromVersion(string $aggregateType, int $aggregateId, int $fromVersion): Collection
    {
        return self::forAggregate($aggregateType, $aggregateId)
            ->where('version', '>=', $fromVersion)
            ->chronological()
            ->get();
    }
}
