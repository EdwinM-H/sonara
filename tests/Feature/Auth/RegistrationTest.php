<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_customer_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '999888777',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('public.home', absolute: false));

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertTrue(auth()->user()->hasRole('customer'));
    }

    public function test_register_never_creates_entrepreneurs(): void
    {
        $this->get('/register')->assertOk()
            ->assertDontSee('name="role"', false)
            ->assertDontSee('business_name', false)
            ->assertSee(route('voice-registration.index'), false);

        // Aunque se fuerce el campo, la cuenta es de cliente.
        $this->post('/register', [
            'role' => 'entrepreneur',
            'first_name' => 'Ana',
            'last_name' => 'Roca',
            'email' => 'ana@example.com',
            'phone' => '999888777',
            'password' => 'password12',
            'password_confirmation' => 'password12',
            'business_name' => 'Masajes Ana',
        ])->assertRedirect(route('public.home', absolute: false));

        $this->assertTrue(auth()->user()->hasRole('customer'));
        $this->assertFalse(auth()->user()->hasRole('entrepreneur'));
        $this->assertDatabaseMissing('businesses', ['name' => 'Masajes Ana']);
    }
}