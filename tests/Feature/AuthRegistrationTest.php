<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_defaults_to_merchant_role_with_json(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)->assertJsonFragment(['message' => 'Registered successfully']);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'merchant',
        ]);
    }

    public function test_register_redirects_with_html_accepts(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'test2@example.com',
            'role' => 'merchant',
        ]);
    }
}
