<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Get paginated users list
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getPaginatedUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::with(['orders', 'transactions'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get a single user with relationships
     */
    public function getUserWithRelations(int $userId): User
    {
        return User::with(['orders', 'transactions'])
            ->findOrFail($userId);
    }

    /**
     * Create a new user
     *
     * @param array{
     *   name: string,
     *   email: string,
     *   password: string,
     *   role?: string
     * } $data
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'merchant',
                'amount' => 0,
            ]);

            // Initialize user wallet with 0 balance
            $this->initializeUserWallet($user);

            return $user;
        });
    }

    /**
     * Update user details
     *
     * @param array{
     *   name?: string,
     *   email?: string,
     *   role?: string,
     *   password?: string
     * } $data
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        $user->refresh();
        return $user;
    }

    /**
     * Delete a user
     */
    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Check if user has pending orders
            if ($user->orders()->whereIn('status', ['pending_payment', 'completed'])->exists()) {
                throw new Exception('Cannot delete user with pending or completed orders');
            }

            // Soft delete the user
            return (bool) $user->delete();
        });
    }

    /**
     * Get user's current balance
     */
    public function getUserBalance(User $user): float
    {
        $credits = $user->transactions()
            ->where('type', 'credit')
            ->sum('amount');

        $debits = $user->transactions()
            ->where('type', 'debit')
            ->sum('amount');

        return $credits - $debits;
    }

    /**
     * Initialize user wallet
     */
    protected function initializeUserWallet(User $user): void
    {
        // Update user's amount field to 0
        $user->update(['amount' => 0]);
    }

    /**
     * Search users
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function searchUsers(string $query): LengthAwarePaginator
    {
        return User::where('name', 'like', "%$query%")
            ->orWhere('email', 'like', "%$query%")
            ->paginate(15);
    }

    /**
     * Get users by role
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getUsersByRole(string $role): LengthAwarePaginator
    {
        return User::where('role', $role)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }
}
