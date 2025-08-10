<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role = 'merchant'): User
    {
        return User::factory()->create([
            'role' => $role,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);
        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_merchant_cannot_access_admin_dashboard(): void
    {
        $merchant = $this->makeUser('merchant');
        $this->actingAs($merchant);
        $this->get('/admin/dashboard')->assertStatus(403);
    }
}
