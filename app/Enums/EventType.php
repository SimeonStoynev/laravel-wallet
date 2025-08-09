<?php

namespace App\Enums;

enum EventType: string
{
    // User Events
    case USER_CREATED = 'UserCreated';
    case USER_UPDATED = 'UserUpdated';
    case USER_ROLE_CHANGED = 'UserRoleChanged';
    case WALLET_BALANCE_CHANGED = 'WalletBalanceChanged';

    // Order Events
    case ORDER_CREATED = 'OrderCreated';
    case ORDER_STATUS_CHANGED = 'OrderStatusChanged';
    case ORDER_COMPLETED = 'OrderCompleted';
    case ORDER_CANCELLED = 'OrderCancelled';
    case ORDER_REFUNDED = 'OrderRefunded';

    // Transaction Events
    case TRANSACTION_CREATED = 'TransactionCreated';
    case BALANCE_UPDATED = 'BalanceUpdated';

    // System Events
    case SYSTEM_MAINTENANCE = 'SystemMaintenance';

    /**
     * Get all user-related event types.
     *
     * @return array<EventType>
     */
    public static function userEvents(): array
    {
        return [
            self::USER_CREATED,
            self::USER_UPDATED,
            self::USER_ROLE_CHANGED,
            self::WALLET_BALANCE_CHANGED,
        ];
    }

    /**
     * Get all order-related event types.
     *
     * @return array<EventType>
     */
    public static function orderEvents(): array
    {
        return [
            self::ORDER_CREATED,
            self::ORDER_STATUS_CHANGED,
            self::ORDER_COMPLETED,
            self::ORDER_CANCELLED,
            self::ORDER_REFUNDED,
        ];
    }

    /**
     * Get all transaction-related event types.
     *
     * @return array<EventType>
     */
    public static function transactionEvents(): array
    {
        return [
            self::TRANSACTION_CREATED,
            self::BALANCE_UPDATED,
        ];
    }

    /**
     * Get all event types as array of strings.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
