<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * User role constants
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MERCHANT = 'merchant';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'amount',
        'description',
        'version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        // Events will be dispatched when these actions occur
    ];

    // Relationships

    /**
     * Get all orders for the user.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all transactions for the user.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all transactions created by this user.
     *
     * @return HasMany<Transaction, $this>
     */
    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    // Scopes

    /**
     * Scope to filter admin users.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Scope to filter merchant users.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeMerchant(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_MERCHANT);
    }

    /**
     * Scope to filter users with balance.
     *
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeWithBalance(Builder $query): Builder
    {
        return $query->where('amount', '>', 0);
    }

    // Helper Methods

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is merchant.
     */
    public function isMerchant(): bool
    {
        return $this->role === self::ROLE_MERCHANT;
    }

    /**
     * Get user's wallet balance.
     */
    public function getBalance(): float
    {
        return (float) $this->amount;
    }
}
