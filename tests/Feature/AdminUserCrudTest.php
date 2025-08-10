<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_index_lists_users(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create();
        $this->actingAs($admin);
        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Users');
    }

    public function test_store_creates_user_default_merchant(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $payload = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $this->post(route('admin.users.store'), $payload)
            ->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'role' => 'merchant',
        ]);
    }

    public function test_update_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'merchant']);
        $this->actingAs($admin);
        $this->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'admin',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_delete_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $this->actingAs($admin);
        $this->delete(route('admin.users.destroy', $user))
            ->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_add_money_increases_balance(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['amount' => 0]);
        $this->actingAs($admin);
        $this->post(route('admin.users.add-money', $user), [
            'amount' => 50,
            'description' => 'Top up',
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->amount >= 50);
    }
}
