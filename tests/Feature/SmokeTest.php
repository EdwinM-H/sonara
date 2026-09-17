<?php

namespace Tests\Feature;

use App\Models\AccessibilityPreference;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\EntrepreneurProfile;
use App\Models\Publication;
use App\Models\User;
use App\Services\Verification\VerificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['email' => 'admin@test.dev']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeEntrepreneur(): User
    {
        $user = User::factory()->create(['email' => 'entre@test.dev']);
        $user->assignRole('entrepreneur');

        $profile = EntrepreneurProfile::create(['user_id' => $user->id]);
        app(VerificationService::class)->startVerificationWindow($profile);

        $category = Category::create([
            'name' => 'Categoría de prueba',
            'slug' => 'categoria-de-prueba',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $business = Business::create([
            'entrepreneur_profile_id' => $profile->id,
            'name' => 'Negocio de Prueba',
            'slug' => 'negocio-de-prueba',
            'description' => 'Descripción',
            'category_id' => $category->id,
            'type' => 'servicio',
            'availability' => 'disponible',
            'currency' => 'PEN',
            'price' => 50,
            'region' => 'Cusco',
            'phone' => '999888777',
            'whatsapp' => '999888777',
        ]);

        BusinessHour::create([
            'business_id' => $business->id,
            'day_of_week' => 1,
            'open_time' => '09:00',
            'close_time' => '18:00',
            'is_closed' => false,
        ]);

        $pub = Publication::create([
            'business_id' => $business->id,
            'entrepreneur_profile_id' => $profile->id,
            'name' => 'Producto de prueba',
            'slug' => 'producto-de-prueba',
            'description' => 'Descripción',
            'type' => 'servicio',
            'price' => 50,
            'currency' => 'PEN',
            'status' => Publication::STATUS_BORRADOR,
            'flyer_image' => 'flyers/seed-1.svg',
        ]);

        AccessibilityPreference::create([
            'user_id' => $user->id,
            'navigation_mode' => 'mixto',
        ]);

        return $user;
    }

    private function makeCustomer(): User
    {
        $user = User::factory()->create(['email' => 'cust@test.dev']);
        $user->assignRole('customer');
        CustomerProfile::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_public_pages_render(): void
    {
        $category = Category::create([
            'name' => 'Masajes',
            'slug' => 'masajes-test',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('public.home'))->assertOk();
        $this->get(route('public.explore'))->assertOk();
        $this->get(route('public.categories'))->assertOk();
        $this->get(route('public.category', $category->slug))->assertOk();
        $this->get(route('voice-registration.index'))->assertOk();
    }

    public function test_entrepreneur_pages_render(): void
    {
        $this->withoutExceptionHandling();
        $user = $this->makeEntrepreneur();

        $routes = [
            'entrepreneur.dashboard',
            'entrepreneur.profile',
            'entrepreneur.accessibility',
            'entrepreneur.businesses.index',
            'entrepreneur.businesses.create',
            'entrepreneur.businesses.edit',
            'entrepreneur.publications.index',
            'entrepreneur.publications.create',
            'entrepreneur.publications.edit',
            'entrepreneur.flyers.index',
            'entrepreneur.flyers.history',
            'entrepreneur.requests.index',
            'entrepreneur.documents.index',
            'entrepreneur.assistance.index',
            'entrepreneur.notifications.index',
        ];

        foreach ($routes as $route) {
            $params = match (true) {
                str_ends_with($route, 'businesses.edit') => ['business' => $user->entrepreneurProfile->businesses->first()],
                str_ends_with($route, 'publications.edit') || str_ends_with($route, 'flyers.index') => ['publication' => $user->entrepreneurProfile->businesses->first()->publications->first()],
                default => [],
            };

            $response = $this->actingAs($user)->get(route($route, $params));
            $this->assertTrue(
                $response->status() === 200,
                "Ruta $route devolvió ".$response->status()."\n".($response->exception?->getMessage() ?? ''),
            );
        }

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_customer_pages_render(): void
    {
        $user = $this->makeCustomer();

        $this->actingAs($user)->get(route('customer.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('customer.requests.index'))->assertOk();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_admin_pages_render(): void
    {
        $user = $this->makeAdmin();
        $entrepreneur = $this->makeEntrepreneur();
        $business = $entrepreneur->entrepreneurProfile->businesses->first();
        $publication = $business->publications->first();
        $category = Category::first();

        $routes = [
            'admin.dashboard' => [],
            'admin.users' => [],
            'admin.users.edit' => ['user' => $entrepreneur],
            'admin.entrepreneurs.index' => [],
            'admin.entrepreneurs.create' => [],
            'admin.entrepreneurs.show' => ['user' => $entrepreneur],
            'admin.entrepreneurs.edit' => ['user' => $entrepreneur],
            'admin.assisted.index' => [],
            'admin.businesses.index' => [],
            'admin.businesses.show' => ['business' => $business],
            'admin.publications.index' => [],
            'admin.publications.show' => ['publication' => $publication],
            'admin.categories.index' => [],
            'admin.categories.create' => [],
            'admin.categories.edit' => ['category' => $category],
            'admin.subcategories.index' => [],
            'admin.subcategories.create' => [],
            'admin.requests' => [],
            'admin.assistance.index' => [],
            'admin.audit.index' => [],
            'admin.settings.index' => [],
            'admin.notifications.index' => [],
        ];

        foreach ($routes as $route => $params) {
            $response = $this->actingAs($user)->get(route($route, $params));
            $this->assertTrue(
                $response->status() === 200,
                "Ruta $route devolvió ".$response->status()."\n".($response->exception?->getMessage() ?? ''),
            );
        }
    }

    public function test_role_middleware_blocks_unauthorized(): void
    {
        $customer = $this->makeCustomer();
        $admin = $this->makeAdmin();

        $this->actingAs($customer)->get(route('entrepreneur.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('entrepreneur.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_suspended_user_is_blocked(): void
    {
        $user = $this->makeEntrepreneur();
        $user->update(['status' => User::STATUS_SUSPENDIDO]);

        $this->actingAs($user)->get(route('entrepreneur.dashboard'))->assertStatus(302);
    }
}