<?php

namespace App\Models;

use App\Events\TransactionCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Transaction type constants
     */
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($transaction) {
            if ($transaction->isDirty('type')) {
                throw new \Exception('Transaction type cannot be changed after creation.');
            }
        });
    }

    // Relationships

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the owning referenceable model (Order/Manual).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes

    /**
     * Scope to filter credit transactions.
     */
    public function scopeCredits($query)
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    /**
     * Scope to filter debit transactions.
     */
    public function scopeDebits($query)
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    /**
     * Scope to filter transactions for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter transactions created by a specific user.
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to order transactions by creation date.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to filter transactions by reference.
     */
    public function scopeByReference($query, string $type, int $id)
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
            return "{$action} #order_{$this->reference_id}";
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
