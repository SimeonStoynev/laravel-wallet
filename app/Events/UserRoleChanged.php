<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

class UserRoleChanged extends WalletEvent
{
    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        User $user,
        string $previousRole,
        string $newRole,
        int $changedBy,
        array $metadata = []
    ) {
        $eventData = [
            'user_id' => $user->id,
            'previous_role' => $previousRole,
            'new_role' => $newRole,
            'changed_by' => $changedBy,
            'changed_at' => now()->toISOString(),
        ];

        parent::__construct(
            User::class,
            $user->id,
            $eventData,
            array_merge([
                'user_email' => $user->email,
                'user_name' => $user->name,
            ], $metadata)
        );
    }

    /**
     * Get the event type.
     */
    public function getEventType(): string
    {
        return 'UserRoleChanged';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.$this->aggregateId"),
            new PrivateChannel('admin.users'),
        ];
    }
}
