<?php

namespace App\Models;

use App\Events\TransactionCreated;
use Database\Factories\TransactionFactory;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @use HasFactory<TransactionFactory>
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * Transaction type constants
     */
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'created_by',
        'type',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'balance_before',
        'balance_after',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TransactionCreated::class,
    ];

    /**
     * Indicates that the transaction type is immutable after creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::updating(function ($transaction) {
            if ($transaction->isDirty('type')) {
                throw new Exception('Transaction type cannot be changed after creation.');
            }
        });
    }

    // Relationships

    /**
     * Get the user that owns the transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that created the transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the reference model (polymorphic).
     *
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes

    /**
     * Scope to filter credit transactions.
     *
     * @param Builder<Transaction> $query
     * @return Builder<Transaction>
     */
    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    /**
     * Scope to filter debit transactions.
     *
     * @param Builder<Transaction> $query
     * @return Builder<Transaction>
     */
    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    /**
     * Scope to filter transactions for a specific user.
     *
     * @param Builder<Transaction> $query
     * @param int|string $userId
     * @return Builder<Transaction>
     */
    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter transactions created by a specific user.
     *
     * @param Builder<Transaction> $query
     * @param int|string $userId
     * @return Builder<Transaction>
     */
    public function scopeCreatedBy(Builder $query, int|string $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to order transactions by creation date.
     *
     * @param Builder<Transaction> $query
     * @return Builder<Transaction>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to filter transactions by reference.
     *
     * @param Builder<Transaction> $query
     * @param string $type
     * @param int $id
     * @return Builder<Transaction>
     */
    public function scopeByReference(Builder $query, string $type, int $id): Builder
    {
        return $query->where('reference_type', $type)
                    ->where('reference_id', $id);
    }

    // Helper Methods

    /**
     * Check if transaction is a credit.
     */
    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    /**
     * Check if transaction is a debit.
     */
    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }

    /**
     * Get the balance change amount (positive for credit, negative for debit).
     */
    public function getBalanceChange(): float
    {
        return $this->isCredit() ? (float) $this->amount : -(float) $this->amount;
    }

    /**
     * Get formatted transaction description.
     */
    public function getFormattedDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        // Generate description based on reference
        if ($this->reference_type === Order::class) {
            $action = $this->isCredit() ? 'Received funds' : 'Purchased funds';
            return "$action #order_$this->reference_id";
        }

        return $this->isCredit() ? 'Credit transaction' : 'Debit transaction';
    }

    /**
     * Create a credit transaction.
     */
    public static function createCredit(
        int $userId,
        int $createdBy,
        float $amount,
        float $balanceBefore,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'created_by' => $createdBy,
            'type' => self::TYPE_CREDIT,
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
        ]);
    }

    /**
     * Create a debit transaction.
     */
    public static function createDebit(
        int $userId,
        int $createdBy,
        float $amount,
        float $balanceBefore,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'created_by' => $createdBy,
            'type' => self::TYPE_DEBIT,
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore - $amount,
        ]);
    }
}
