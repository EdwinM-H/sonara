<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\Publication;
use App\Models\User;
use App\Services\Assistant\BusinessVoiceRegistrationService;
use App\Services\Catalog\CatalogService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Problema 1: un emprendimiento registrado por voz debe verse en el muro. */
class VoiceBusinessWallTest extends TestCase
{
    use RefreshDatabase;

    private User $entrepreneur;

    private Category $educacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->entrepreneur = User::factory()->create();
        $this->entrepreneur->assignRole('entrepreneur');
        EntrepreneurProfile::create(['user_id' => $this->entrepreneur->id]);
        $this->educacion = Category::create(['name' => 'Educación', 'slug' => 'educacion', 'is_active' => true]);

        $this->app->bind(BusinessVoiceRegistrationService::class, fn ($app) => new BusinessVoiceRegistrationService($app->make(CatalogService::class), 'wall-test'));
        config(['services.ai.provider' => 'mock', 'services.image_server.url' => null, 'services.publish_web.url' => null]);
        Storage::fake('public');
    }

    /** Registra por voz y devuelve la URL del paso de publicación. */
    private function registerByVoice(): string
    {
        $this->actingAs($this->entrepreneur)->getJson(route('entrepreneur.businesses.voice.start'));
        $last = null;
        foreach (['Clases de Matemática', 'Clases particulares para escolares', 'servicio', 'educación', 'centro', 'algebra y geometria', '984111222', 'Calle Plateros 340', 'Desde 20 soles la hora', 'Lunes a sábado de 3 a 8 de la noche'] as $answer) {
            $last = $this->actingAs($this->entrepreneur)->postJson(route('entrepreneur.businesses.voice.process'), ['transcript' => $answer])->json();
        }
        $this->assertSame('working', $last['type']);

        return $last['next'];
    }

    public function test_registered_business_is_on_the_wall_even_before_the_image_is_ready(): void
    {
        $this->registerByVoice();

        $publication = Business::firstOrFail()->adPublication;
        $this->assertSame(Publication::STATUS_PUBLICADA, $publication->status);
        $this->assertNull($publication->flyer_image);

        $this->get(route('public.category', $this->educacion))
            ->assertOk()
            ->assertSee('clases de matematica')
            ->assertSee('desde 20 soles la hora')
            ->assertDontSee('S/ 0.00')
            ->assertDontSee('Aún no hay publicaciones');
    }

    public function test_card_opens_detail_with_all_info_and_image_after_publishing(): void
    {
        $next = $this->registerByVoice();
        $this->actingAs($this->entrepreneur)->postJson($next)->assertOk();
        auth()->logout();

        $business = Business::firstOrFail();
        $publication = $business->adPublication;
        $this->assertStringStartsWith('storage/flyers/', $publication->flyer_image);
        $this->assertSame(route('public.publication', $publication->slug), $business->external_ad_url);

        $wall = $this->get(route('public.category', $this->educacion))->assertOk();
        $wall->assertSee(route('public.publication', $publication->slug), false);
        $wall->assertSee(asset($publication->flyer_image), false);

        $this->get(route('public.publication', $publication->slug))
            ->assertOk()
            ->assertSee('clases de matematica')
            ->assertSee('clases particulares para escolares')
            ->assertSee('Educación')
            ->assertSee('calle plateros 340')
            ->assertSee('984111222');

        $this->get(route('public.explore', ['q' => 'matematica']))->assertOk()->assertSee('clases de matematica');
        $this->get(route('public.home'))->assertOk()->assertSee('clases de matematica');
    }

    /** Paso 6: registro con todos los campos → muro → detalle completo. */
    public function test_end_to_end_all_fields_on_card_and_detail(): void
    {
        $next = $this->registerByVoice();
        $this->actingAs($this->entrepreneur)->postJson($next)->assertOk();
        auth()->logout();

        $business = Business::firstOrFail();
        $this->assertSame(Business::TYPE_SERVICIO, $business->type);
        $this->assertSame('desde 20 soles la hora', $business->price_text);
        $this->assertSame('lunes a sabado de 3 a 8 de la noche', $business->schedule_text);
        $this->assertStringContainsString("- Price: desde 20 soles la hora\n- Hours: lunes a sabado de 3 a 8 de la noche", $business->image_prompt);

        // Tarjeta del muro: precio y horario.
        $this->get(route('public.category', $this->educacion))->assertOk()
            ->assertSeeInOrder(['desde 20 soles la hora', 'clases de matematica', 'calle plateros 340', 'lunes a sabado de 3 a 8 de la noche']);

        // Detalle que abre la tarjeta: todos los campos.
        $this->get(route('public.publication', $business->adPublication->slug))->assertOk()
            ->assertSee('clases de matematica')
            ->assertSee('clases particulares para escolares')
            ->assertSee('Servicio')
            ->assertSee('Educación')
            ->assertSee('Sector: centro')
            ->assertSee('#algebra')
            ->assertSee('#geometria')
            ->assertSee('desde 20 soles la hora')
            ->assertSee('lunes a sabado de 3 a 8 de la noche')
            ->assertSee('calle plateros 340')
            ->assertSee('Llamar al 984111222')
            ->assertSee(asset($business->adPublication->flyer_image), false);

        // Página del emprendimiento.
        $this->get(route('public.business', $business->slug))->assertOk()
            ->assertSee('desde 20 soles la hora')
            ->assertDontSee('S/ </dd>', false)
            ->assertSee('lunes a sabado de 3 a 8 de la noche')
            ->assertSee('Sector centro')
            ->assertSee('#algebra');
    }

    public function test_category_counter_matches_visible_cards(): void
    {
        $this->registerByVoice();
        // Un emprendimiento sin publicaciones no cuenta: no se vería en el muro.
        Business::create([
            'entrepreneur_profile_id' => $this->entrepreneur->entrepreneurProfile->id,
            'name' => 'Sin publicaciones', 'slug' => 'sin-publicaciones', 'category_id' => $this->educacion->id,
        ]);

        $visible = Publication::where('status', Publication::STATUS_PUBLICADA)
            ->whereHas('business', fn ($q) => $q->where('category_id', $this->educacion->id)->where('status', 'activo'))
            ->count();
        $this->assertSame(1, $visible);

        $this->get(route('public.categories'))->assertOk()->assertSee('<span class="badge badge-neutral">1</span>', false);
        $this->get(route('public.home'))->assertOk()
            ->assertSee(route('public.category', $this->educacion), false)
            ->assertSee('clases de matematica');

        $this->get(route('public.category', $this->educacion))->assertOk()->assertSee('clases de matematica');
    }

    public function test_repair_command_publishes_businesses_registered_before_the_fix(): void
    {
        $business = Business::create([
            'entrepreneur_profile_id' => $this->entrepreneur->entrepreneurProfile->id,
            'name' => 'clases de matematica', 'slug' => 'clases-de-matematica', 'category_id' => $this->educacion->id,
            'publish_status' => Business::PUBLISH_PUBLICADO, 'image_url' => asset('storage/flyers/x.svg'),
        ]);
        $this->get(route('public.category', $this->educacion))->assertSee('Aún no hay publicaciones');

        $this->assertSame(0, Artisan::call('sonara:sincronizar-muro'));
        $this->assertSame('storage/flyers/x.svg', $business->fresh()->adPublication->flyer_image);
        $this->get(route('public.category', $this->educacion))->assertSee('clases de matematica');

        // Es idempotente.
        Artisan::call('sonara:sincronizar-muro');
        $this->assertSame(1, $business->publications()->count());
    }
}
