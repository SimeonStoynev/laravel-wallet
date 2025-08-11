<?php

namespace App\Models;

use App\Events\OrderCreated;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @use HasFactory<OrderFactory>
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * Order status constants
     */
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'amount',
        'status',
        'external_reference',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => OrderCreated::class,
    ];

    // Relationships

    /**
     * Get the user that owns the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for the order.
     *
     * @return MorphMany<Transaction, $this>
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'reference');
    }

    // Scopes

    /**
     * Scope to filter orders by status.
     *
     * @param Builder<Order> $query
     * @param string $status
     * @return Builder<Order>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter recent orders.
     *
     * @param Builder<Order> $query
     * @return Builder<Order>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to filter orders for a specific user.
     *
     * @param Builder<Order> $query
     * @param int|string $userId
     * @return Builder<Order>
     */
    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter pending payment orders.
     *
     * @param Builder<Order> $query
     * @return Builder<Order>
     */
    public function scopePendingPayment(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_PAYMENT);
    }

    /**
     * Scope to filter completed orders.
     *
     * @param Builder<Order> $query
     * @return Builder<Order>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to filter cancelled orders.
     *
     * @param Builder<Order> $query
     * @return Builder<Order>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to filter refunded orders.
     *
     * @param Builder<Order> $query
     * @return Builder<Order>
     */
    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    // Helper Methods

    /**
     * Check if order is pending payment.
     */
    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    /**
     * Check if order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if order is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Mark order as completed.
     */
    public function markAsCompleted(): bool
    {
        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark order as cancelled.
     */
    public function markAsCancelled(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Mark order as refunded.
     */
    public function markAsRefunded(): bool
    {
        return $this->update(['status' => self::STATUS_REFUNDED]);
    }

    /**
     * Get available status transitions.
     *
     * @return array<int, string>
     */
    public function getAvailableTransitions(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING_PAYMENT => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [self::STATUS_REFUNDED],
            default => [],
        };
    }
}
