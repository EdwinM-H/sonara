<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CatalogOption;
use App\Models\CatalogType;
use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Home estilo marketplace, perfil público del emprendedor, compartir y estadísticas del admin. */
class PortalRedesignTest extends TestCase
{
    use RefreshDatabase;

    private Category $comida;

    private Category $educacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->comida = Category::create(['name' => 'Comida', 'slug' => 'comida', 'icon' => '🍲', 'is_active' => true, 'sort_order' => 1]);
        $this->educacion = Category::create(['name' => 'Educación', 'slug' => 'educacion', 'is_active' => true, 'sort_order' => 2]);
        Category::create(['name' => 'Oculta', 'slug' => 'oculta', 'is_active' => false]);

        $sectores = CatalogType::where('slug', CatalogType::SECTORES)->firstOrFail();
        foreach (['Centro', 'San Blas', 'Wanchaq'] as $i => $name) {
            CatalogOption::create(['catalog_type_id' => $sectores->id, 'name' => $name, 'sort_order' => $i]);
        }
    }

    private function entrepreneur(array $profile = [], array $user = []): EntrepreneurProfile
    {
        $u = User::factory()->create($user + ['first_name' => 'rosa maria', 'last_name' => 'mamani quispe', 'name' => 'rosa maria mamani quispe']);
        $u->assignRole('entrepreneur');

        return EntrepreneurProfile::create($profile + ['user_id' => $u->id, 'personal_description' => 'tejo chompas', 'location' => 'cusco san blas']);
    }

    private function business(EntrepreneurProfile $profile, string $name, array $attrs = []): Business
    {
        $business = Business::create($attrs + [
            'entrepreneur_profile_id' => $profile->id,
            'name' => $name,
            'slug' => Business::uniqueSlug($name),
            'description' => 'Descripción de '.$name,
            'category_id' => $this->comida->id,
            'price_text' => 'desde 10 soles',
            'schedule_text' => 'lunes a viernes',
            'whatsapp' => '984111222',
        ]);
        $business->ensureAdPublication();

        return $business;
    }

    public function test_home_has_search_admin_categories_featured_sectors_and_banner_last(): void
    {
        $profile = $this->entrepreneur();
        $this->business($profile, 'Pollería Centro', ['sector' => 'centro', 'image_url' => asset('uploads/emprendimientos/x.jpg')]);
        $this->business($profile, 'Tejidos San Blas', ['sector' => 'San Blas', 'category_id' => $this->educacion->id]);

        $html = $this->get(route('public.home'))->assertOk()->getContent();

        // Orden: búsqueda → categorías → destacados → sectores → banner.
        $positions = array_map(fn ($needle) => strpos($html, $needle), [
            'id="home-search"', 'id="home-categorias"', 'id="home-destacados"', 'Sector <span', 'id="home-banner"',
        ]);
        $this->assertNotContains(false, $positions);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Las secciones del home deben ir en el orden pedido.');

        // Categorías: solo las activas del admin.
        $this->assertStringContainsString(route('public.category', $this->comida), $html);
        $this->assertStringContainsString('🍲', $html);
        $this->assertStringNotContainsString('Oculta', $html);

        // Sectores con anuncios (sin importar mayúsculas); Wanchaq no tiene y no aparece.
        $this->assertStringContainsString('Sector <span class="text-purple-700">Centro</span>', $html);
        $this->assertStringContainsString('Sector <span class="text-purple-700">San Blas</span>', $html);
        $this->assertStringNotContainsString('Wanchaq', $html);

        // Tarjeta: nombre, categoría, precio y "Ver más".
        $this->assertStringContainsString('Pollería Centro', $html);
        $this->assertStringContainsString('desde 10 soles', $html);
        $this->assertStringContainsString('Ver más', $html);

        // Sin botón flotante de audio.
        $this->assertStringNotContainsString('voice-fab', $html);
    }

    public function test_detail_pages_have_share_and_entrepreneur_link(): void
    {
        $profile = $this->entrepreneur();
        $business = $this->business($profile, 'Pollería Centro');
        $publication = $business->adPublication;
        $publication->update(['flyer_image' => 'uploads/emprendimientos/flyer.jpg']);

        foreach ([route('public.publication', $publication->slug), route('public.business', $business)] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('data-share-button', false)
                ->assertSee('Ver perfil')
                ->assertSee('Rosa Maria Mamani Quispe')
                ->assertSee(route('public.entrepreneur', $profile), false);
        }

        // El texto compartido lleva detalles, el flyer y el enlace (va como JSON en x-data).
        $html = $this->get(route('public.publication', $publication->slug))->getContent();
        foreach (['Precio: desde 10 soles', 'Horario: lunes a viernes', 'Contacto: 984111222', 'flyer.jpg', $publication->slug] as $piece) {
            $this->assertStringContainsString(str_replace('/', '\/', $piece), html_entity_decode($html), $piece);
        }
    }

    public function test_entrepreneur_profile_lists_published_businesses_and_verification(): void
    {
        $profile = $this->entrepreneur();
        $this->business($profile, 'Pollería Centro', ['tags' => ['delivery']]);
        $this->business($profile, 'Clases de quechua', ['category_id' => $this->educacion->id, 'type' => 'servicio']);
        $this->business($profile, 'Inactivo', ['status' => Business::STATUS_INACTIVO]);

        $this->get(route('public.entrepreneur', $profile))->assertOk()
            ->assertSee('Rosa Maria Mamani Quispe')
            ->assertSee('No verificado')
            ->assertSee('Cusco San Blas')
            ->assertSee('Tejo chompas')
            ->assertSee('Pollería Centro')
            ->assertSee('Clases de quechua')
            ->assertDontSee('>Inactivo<', false)
            ->assertSee('Emprendimientos publicados <span class="text-gray-600">(2)</span>', false)
            ->assertSee('Comida')->assertSee('Educación')->assertSee('Delivery');

        $profile->update(['verification_status' => EntrepreneurProfile::VERIF_APROBADO]);
        $this->get(route('public.entrepreneur', $profile))->assertSee('Verificado')->assertDontSee('No verificado');

        $this->assertStringEndsWith('/emprendedor/'.$profile->id.'/perfil', route('public.entrepreneur', $profile));
    }

    public function test_profile_of_non_entrepreneur_is_not_found(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $profile = EntrepreneurProfile::create(['user_id' => $customer->id]);

        $this->get(route('public.entrepreneur', $profile))->assertNotFound();
        $this->get('/emprendedor/999/perfil')->assertNotFound();
    }

    public function test_admin_stats_read_real_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $a = $this->entrepreneur(['dni' => '11111111', 'grado_discapacidad' => 'LEVE', 'tiene_carnet_conadis' => true, 'numero_carnet_conadis' => '123']);
        $this->entrepreneur(['dni' => '22222222', 'grado_discapacidad' => 'SEVERA', 'tiene_carnet_conadis' => false], ['first_name' => 'b', 'name' => 'b']);
        $this->entrepreneur(['dni' => '33333333', 'grado_discapacidad' => 'SEVERA', 'tiene_carnet_conadis' => false], ['first_name' => 'c', 'name' => 'c']);
        $old = $this->entrepreneur([], ['first_name' => 'd', 'name' => 'd']);
        $old->user->forceFill(['created_at' => now()->subDays(60)])->save();

        $this->business($a, 'Con imagen', ['image_url' => asset('uploads/x.jpg')]);
        $this->business($a, 'Sin imagen');

        $response = $this->actingAs($admin)->get(route('admin.stats'))->assertOk();
        $kpis = $response->viewData('kpis');
        $this->assertSame(['entrepreneurs' => 4, 'businesses' => 2, 'with_ai_image' => 1, 'with_conadis' => 1, 'without_conadis' => 2], $kpis);
        $this->assertSame(['LEVE' => 1, 'MODERADA' => 0, 'SEVERA' => 2], $response->viewData('byGrade')->all());

        // Rango por defecto: últimos 30 días (las 3 altas de hoy, no la de hace 60 días).
        $signups = $response->viewData('signups');
        $this->assertCount(30, $signups['labels']);
        $this->assertSame(3, array_sum($signups['values']));
        $this->assertSame(3, end($signups['values']));

        // Con rango y agrupado por mes entra también la antigua.
        $signups = $this->actingAs($admin)->get(route('admin.stats', [
            'desde' => now()->subDays(90)->toDateString(), 'hasta' => now()->toDateString(), 'agrupar' => 'mes',
        ]))->assertOk()->viewData('signups');
        $this->assertSame(4, array_sum($signups['values']));

        $this->actingAs($admin)->get(route('admin.stats', ['agrupar' => 'semana']))->assertOk();
        $this->actingAs($admin)->get(route('admin.stats', ['desde' => '2026-05-01', 'hasta' => '2026-04-01']))
            ->assertSessionHasErrors('hasta');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee(route('admin.stats'), false);
        $this->actingAs($a->user)->get(route('admin.stats'))->assertRedirect(route('login'));
    }
}
