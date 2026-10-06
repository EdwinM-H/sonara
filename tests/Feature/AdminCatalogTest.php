<?php

namespace Tests\Feature;

use App\Models\CatalogOption;
use App\Models\CatalogType;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tarea 2: CRUD de catálogos del admin y su reflejo en la API. */
class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $entrepreneur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->entrepreneur = User::factory()->create();
        $this->entrepreneur->assignRole('entrepreneur');
    }

    /** Nombres de las opciones del catálogo, tal como los ve el emprendedor en la API. */
    private function apiNames(string $slug): array
    {
        return collect(
            $this->actingAs($this->entrepreneur)->getJson(route('api.catalogs.show', $slug))->assertOk()->json('data.opciones')
        )->pluck('nombre')->all();
    }

    public function test_system_catalogs_exist_and_api_lists_all(): void
    {
        $data = $this->actingAs($this->entrepreneur)->getJson(route('api.catalogs.index'))->assertOk()->json('data');

        $this->assertSame(['categorias', 'sectores', 'etiquetas'], array_keys($data));
    }

    public function test_category_create_edit_delete_reflected_in_api(): void
    {
        $this->actingAs($this->admin)->post(route('admin.categories.store'), ['name' => 'Tecnología', 'is_active' => 1])
            ->assertRedirect(route('admin.categories.index'));
        $this->assertSame(['Tecnología'], $this->apiNames('categorias'));
        $this->assertSame('tecnologia', $this->actingAs($this->entrepreneur)->getJson(route('api.catalogs.show', 'categorias'))->json('data.opciones.0.valor'));

        $category = Category::where('name', 'Tecnología')->firstOrFail();
        $this->actingAs($this->admin)->patch(route('admin.categories.update', $category), ['name' => 'Tecnología y software', 'is_active' => 1])
            ->assertRedirect();
        $this->assertSame(['Tecnología y software'], $this->apiNames('categorias'));

        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();
        $this->assertSame([], $this->apiNames('categorias'));
    }

    /** @dataProvider systemCatalogs */
    public function test_option_create_edit_delete_reflected_in_api(string $slug, string $first, string $renamed): void
    {
        $type = CatalogType::where('slug', $slug)->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.catalogs.options.store', $type), ['name' => $first])->assertRedirect();
        $this->assertSame([$first], $this->apiNames($slug));

        $option = CatalogOption::where('name', $first)->firstOrFail();
        $this->actingAs($this->admin)->patch(route('admin.catalogs.options.update', [$type, $option]), ['name' => $renamed])->assertRedirect();
        $this->assertSame([$renamed], $this->apiNames($slug));

        $this->actingAs($this->admin)->delete(route('admin.catalogs.options.destroy', [$type, $option]))->assertRedirect();
        $this->assertSame([], $this->apiNames($slug));
    }

    public static function systemCatalogs(): array
    {
        return [
            'sectores' => ['sectores', 'Norte', 'Zona Norte'],
            'etiquetas' => ['etiquetas', 'Delivery', 'Envío a domicilio'],
        ];
    }

    public function test_duplicate_options_are_rejected_ignoring_case_and_accents(): void
    {
        $type = CatalogType::where('slug', 'sectores')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.catalogs.options.store', $type), ['name' => 'Centro']);

        $this->actingAs($this->admin)->post(route('admin.catalogs.options.store', $type), ['name' => 'céntro!'])
            ->assertSessionHasErrors('name');
        $this->assertSame(1, $type->options()->count());
    }

    public function test_admin_can_add_and_remove_new_catalog_types(): void
    {
        $this->actingAs($this->admin)->post(route('admin.catalogs.store'), ['name' => 'Métodos de pago'])
            ->assertRedirect(route('admin.catalogs.show', 'metodos-de-pago'));

        $type = CatalogType::where('slug', 'metodos-de-pago')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.catalogs.options.store', $type), ['name' => 'Yape']);
        $this->assertSame(['Yape'], $this->apiNames('metodos-de-pago'));

        $this->actingAs($this->admin)->delete(route('admin.catalogs.destroy', $type))->assertRedirect(route('admin.catalogs.index'));
        $this->actingAs($this->entrepreneur)->getJson(route('api.catalogs.show', 'metodos-de-pago'))->assertNotFound();
    }

    public function test_system_catalogs_cannot_be_deleted(): void
    {
        $type = CatalogType::where('slug', 'sectores')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.catalogs.destroy', $type))->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $type = CatalogType::where('slug', 'etiquetas')->firstOrFail();
        $type->options()->create(['name' => 'Artesanal']);

        $this->actingAs($this->admin)->get(route('admin.catalogs.index'))->assertOk()->assertSee('Sectores')->assertSee('Etiquetas');
        $this->actingAs($this->admin)->get(route('admin.catalogs.show', $type))->assertOk()->assertSee('Artesanal');
    }

    public function test_only_admin_manages_catalogs_and_guests_cannot_read_api(): void
    {
        $type = CatalogType::where('slug', 'sectores')->firstOrFail();

        $this->actingAs($this->entrepreneur)->post(route('admin.catalogs.options.store', $type), ['name' => 'Sur'])->assertForbidden();
        $this->actingAs($this->entrepreneur)->get(route('admin.catalogs.index'))->assertRedirect(route('login'));

        auth()->logout();
        $this->getJson(route('api.catalogs.index'))->assertUnauthorized();
    }
}
