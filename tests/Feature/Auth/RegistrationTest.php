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
            'role' => 'customer',
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

    public function test_new_entrepreneur_can_register(): void
    {
        $category = \App\Models\Category::create([
            'name' => 'Masajes',
            'slug' => 'masajes',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->post('/register', [
            'role' => 'entrepreneur',
            'first_name' => 'Ana',
            'last_name' => 'Roca',
            'email' => 'ana@example.com',
            'phone' => '999888777',
            'password' => 'password12',
            'password_confirmation' => 'password12',
            'category_id' => $category->id,
            'business_name' => 'Masajes Ana',
            'business_description' => 'Spa y masajes',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('entrepreneur.dashboard', absolute: false));

        $this->assertDatabaseHas('businesses', ['name' => 'Masajes Ana']);
        $this->assertTrue(auth()->user()->hasRole('entrepreneur'));
    }

    public function test_role_is_required(): void
    {
        $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '999888777',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertSessionHasErrors('role');
    }
}