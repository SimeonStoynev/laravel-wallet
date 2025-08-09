<?php

namespace App\Events;

use App\Models\Event;
use DateTimeInterface;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class WalletEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The aggregate type this event belongs to.
     */
    public string $aggregateType;

    /**
     * The aggregate ID this event belongs to.
     */
    public int $aggregateId;

    /**
     * Event data payload.
     *
     * @var array<string, mixed>
     */
    public array $eventData;

    /**
     * Event metadata.
     *
     * @var array<string, mixed>
     */
    public array $metadata;

    /**
     * Event version for the aggregate.
     */
    public int $version;

    /**
     * When the event occurred.
     */
    public DateTimeInterface $occurredAt;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $eventData
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $aggregateType,
        int $aggregateId,
        array $eventData,
        array $metadata = [],
        ?int $version = null
    ) {
        $this->aggregateType = $aggregateType;
        $this->aggregateId = $aggregateId;
        $this->eventData = $eventData;
        $this->metadata = $metadata;
        $this->version = $version ?? $this->getNextVersion();
        $this->occurredAt = now();
    }

    /**
     * Get the event name.
     */
    abstract public function getEventType(): string;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('wallet'),
        ];
    }

    /**
     * Get the next version for this aggregate.
     */
    protected function getNextVersion(): int
    {
        return Event::getNextVersionForAggregate($this->aggregateType, $this->aggregateId);
    }

    /**
     * Store the event in the event store.
     */
    public function store(): Event
    {
        return Event::createForAggregate(
            $this->aggregateType,
            $this->aggregateId,
            $this->getEventType(),
            $this->eventData,
            $this->metadata,
            $this->version
        );
    }

    /**
     * Convert event to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'aggregate_type' => $this->aggregateType,
            'aggregate_id' => $this->aggregateId,
            'event_type' => $this->getEventType(),
            'event_data' => $this->eventData,
            'metadata' => $this->metadata,
            'version' => $this->version,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
