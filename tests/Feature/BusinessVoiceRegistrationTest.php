<?php

namespace Tests\Feature;

use App\Http\Controllers\BusinessVoiceController;
use App\Models\Business;
use App\Models\CatalogType;
use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use App\Notifications\CatalogFreeValueNotification;
use App\Services\Assistant\BusinessVoiceRegistrationService;
use App\Services\Catalog\CatalogService;
use App\Services\Publishing\AdPromptBuilder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Tareas 3 y 4: registro de emprendimiento por voz, imagen y publicación. */
class BusinessVoiceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const IMAGE_SERVER = 'https://imagenes.test/generar';
    private const PUBLISH_WEB = 'https://anuncios.test/api/anuncios';
    private const IMAGE_URL = 'https://imagenes.test/out/anuncio-1.png';

    private User $entrepreneur;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->entrepreneur = User::factory()->create(['first_name' => 'rosa', 'last_name' => 'mamani', 'name' => 'rosa mamani']);
        $this->entrepreneur->assignRole('entrepreneur');
        EntrepreneurProfile::create(['user_id' => $this->entrepreneur->id, 'location' => 'cusco']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Clave fija: el cliente de pruebas no conserva la cookie de sesión.
        $this->app->bind(BusinessVoiceRegistrationService::class, fn ($app) => new BusinessVoiceRegistrationService($app->make(CatalogService::class), 'biz-voice-test'));

        config([
            'services.ai.provider' => 'mock',
            'services.image_server.url' => self::IMAGE_SERVER,
            'services.image_server.api_key' => 'img-key',
            'services.publish_web.url' => self::PUBLISH_WEB,
            'services.publish_web.api_key' => 'pub-key',
        ]);
        Storage::fake('public');
        Http::preventStrayRequests();
    }

    /** Catálogos del admin con opciones. */
    private function seedCatalogs(): void
    {
        Category::create(['name' => 'Tecnología', 'slug' => 'tecnologia', 'is_active' => true]);
        Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        Category::create(['name' => 'Moda', 'slug' => 'moda', 'is_active' => false]); // inactiva: no se ofrece
        $sectores = CatalogType::where('slug', 'sectores')->firstOrFail();
        foreach (['Norte', 'Sur', 'Centro', 'Online'] as $name) {
            $sectores->options()->create(['name' => $name]);
        }
        $etiquetas = CatalogType::where('slug', 'etiquetas')->firstOrFail();
        foreach (['Delivery', 'Hecho a mano', 'Orgánico'] as $name) {
            $etiquetas->options()->create(['name' => $name]);
        }
    }

    private function fakeServers(): void
    {
        Http::fake([
            self::IMAGE_SERVER => Http::response(['url' => self::IMAGE_URL]),
            self::PUBLISH_WEB => Http::response(['id' => 'AD-777', 'url' => 'https://anuncios.test/tecnologia/norte/AD-777'], 201),
        ]);
    }

    private function start(): array
    {
        return $this->actingAs($this->entrepreneur)->getJson(route('entrepreneur.businesses.voice.start'))->assertOk()->json();
    }

    private function say(string $transcript): array
    {
        return $this->actingAs($this->entrepreneur)
            ->postJson(route('entrepreneur.businesses.voice.process'), ['transcript' => $transcript])
            ->assertOk()
            ->json();
    }

    /** Dicta un emprendimiento completo y devuelve la última respuesta. */
    private function registerAll(array $answers = []): array
    {
        $this->start();
        $answers = array_merge([
            'nombre' => 'Soluciones Técnicas Rosa',
            'descripcion' => 'Reparación de computadoras y celulares.',
            'tipo' => 'Un servicio',
            'categoria' => 'Tecnología',
            'sector' => 'norte',
            'etiquetas' => 'delivery, hecho a mano',
            'telefono' => '987 654 321',
            'ubicacion' => 'Av. El Sol 123, Cusco',
            'precio' => 'Desde 10 dólares',
            'horario' => 'Lunes a viernes de 8 a 5',
        ], $answers);
        $last = null;
        foreach ($answers as $answer) {
            $last = $this->say($answer);
        }

        return $last;
    }

    private function apiOptionNames(string $slug): array
    {
        return collect($this->actingAs($this->entrepreneur)->getJson(route('api.catalogs.show', $slug))->json('data.opciones'))
            ->pluck('nombre')->all();
    }

    // ------------------------------------------------------------------
    // Tarea 3: flujo por voz
    // ------------------------------------------------------------------

    public function test_voice_page_renders_in_business_mode(): void
    {
        $this->actingAs($this->entrepreneur)->get(route('entrepreneur.businesses.voice'))
            ->assertOk()
            ->assertSee('data-mode="business"', false);
    }

    public function test_dashboard_menu_leads_to_voice_registration(): void
    {
        $result = $this->actingAs($this->entrepreneur)
            ->postJson(route('entrepreneur.voice.command'), ['transcript' => 'registrar un nuevo emprendimiento'])
            ->json();

        $this->assertSame(route('entrepreneur.businesses.voice'), $result['redirect']);
    }

    public function test_asks_each_field_in_order_with_the_scripted_questions(): void
    {
        $this->seedCatalogs();

        $first = $this->start();
        $this->assertSame('nombre', $first['field']);
        $this->assertSame('Vamos a registrar tu emprendimiento. ¿Cuál es el nombre de tu emprendimiento?', $first['speak']);

        $descripcion = $this->say('Soluciones Rosa');
        $this->assertSame('descripcion', $descripcion['field']);
        $this->assertSame('Describe brevemente tu emprendimiento.', $descripcion['speak']);

        $tipo = $this->say('Reparamos computadoras');
        $this->assertSame('tipo', $tipo['field']);
        $this->assertSame('¿Ofreces un producto o un servicio?', $tipo['speak']);

        $categoria = $this->say('servicio');
        $this->assertSame('categoria', $categoria['field']);
        $this->assertSame('¿Cuál es la categoría? Las opciones son: Alimentos, Tecnología. Di el nombre de la categoría que corresponde.', $categoria['speak']);

        $sector = $this->say('tecnologia');
        $this->assertSame('sector', $sector['field']);
        $this->assertStringStartsWith('¿En qué sector se encuentra? Las opciones son: Centro, Norte, Online, Sur.', $sector['speak']);

        $etiquetas = $this->say('norte');
        $this->assertSame('etiquetas', $etiquetas['field']);
        $this->assertStringStartsWith('¿Tienes etiquetas para tu emprendimiento?', $etiquetas['speak']);
        $this->assertStringContainsString('o di ninguna para continuar', $etiquetas['speak']);

        $telefono = $this->say('ninguna');
        $this->assertSame('telefono', $telefono['field']);
        $this->assertSame('¿Cuál es el teléfono de contacto de tu emprendimiento?', $telefono['speak']);

        $ubicacion = $this->say('987654321');
        $this->assertSame('ubicacion', $ubicacion['field']);
        $this->assertSame('¿Cuál es la dirección o ubicación del emprendimiento?', $ubicacion['speak']);

        $precio = $this->say('Av El Sol 123');
        $this->assertSame('precio', $precio['field']);
        $this->assertSame('¿Cuál es el precio o rango de precios de tu emprendimiento? Por ejemplo: desde diez dólares, o precio a consultar.', $precio['speak']);

        $horario = $this->say('precio a consultar');
        $this->assertSame('horario', $horario['field']);
        $this->assertSame('¿Cuál es el horario de atención de tu emprendimiento? Por ejemplo: lunes a viernes de ocho a cinco.', $horario['speak']);
        $this->assertSame(10, $horario['total']);
        $this->assertSame(10, $horario['index']);

        // El horario es la última pregunta: luego viene la confirmación.
        $this->assertSame('working', $this->say('disponible las 24 horas')['type']);
    }

    public function test_type_must_be_product_or_service(): void
    {
        $this->seedCatalogs();
        $this->start();
        $this->say('Soluciones Rosa');
        $this->say('Reparamos computadoras');

        $this->assertSame('tipo', $this->say('no sé')['field']);
        $this->assertSame('tipo', $this->say('productos y servicios')['field']);
        $this->assertSame('categoria', $this->say('Ofrezco SERVICIOS')['field']);
    }

    public function test_options_read_to_the_user_are_the_ones_in_the_admin_api(): void
    {
        $this->seedCatalogs();
        $this->start();
        $this->say('Soluciones Rosa');
        $this->say('Reparamos computadoras');
        $categoria = $this->say('servicio');

        $api = $this->apiOptionNames('categorias');
        $this->assertSame(['Alimentos', 'Tecnología'], $api);
        $this->assertStringContainsString('Las opciones son: '.implode(', ', $api).'.', $categoria['speak']);

        // Un cambio del admin se refleja en la siguiente pregunta.
        CatalogType::where('slug', 'sectores')->firstOrFail()->options()->create(['name' => 'Valle Sagrado']);
        $sector = $this->say('Tecnología');
        $this->assertStringContainsString(implode(', ', $this->apiOptionNames('sectores')), $sector['speak']);
        $this->assertStringContainsString('Valle Sagrado', $sector['speak']);
    }

    public function test_invalid_option_is_rejected_and_options_repeated(): void
    {
        $this->seedCatalogs();
        $this->start();
        $this->say('Soluciones Rosa');
        $this->say('Reparamos computadoras');
        $this->say('servicio');

        $retry = $this->say('Moda'); // existe pero está inactiva
        $this->assertSame('categoria', $retry['field']);
        $this->assertSame('Esa opción no está disponible. Las opciones son: Alimentos, Tecnología. Por favor elige una.', $retry['speak']);

        $retry = $this->say('ferretería');
        $this->assertSame('categoria', $retry['field']);
        $this->assertStringStartsWith('Esa opción no está disponible.', $retry['speak']);

        $this->say('Tecnología');
        $retry = $this->say('este');
        $this->assertSame('sector', $retry['field']);
        $this->assertSame('Esa opción no está disponible. Las opciones son: Centro, Norte, Online, Sur. Por favor elige una.', $retry['speak']);
    }

    public function test_answers_are_normalized_before_matching_the_catalog(): void
    {
        $this->seedCatalogs();
        $this->registerAll([
            'categoria' => 'La categoría es TECNOLOGÍA!',
            'sector' => '¡Zona NORTE!',
            'etiquetas' => 'Hecho a mano y orgánico',
        ]);

        $business = Business::firstOrFail();
        $this->assertSame('Tecnología', $business->category->name);
        $this->assertSame('Norte', $business->sector);
        $this->assertSame(['Hecho a mano', 'Orgánico'], $business->tags);
        $this->assertSame('soluciones tecnicas rosa', $business->name);
    }

    public function test_empty_catalogs_accept_free_values_and_notify_admin(): void
    {
        Notification::fake();
        $this->start();
        $this->say('Panadería Rosa');
        $this->say('Pan artesanal');
        $categoria = $this->say('producto');
        $this->assertSame('¿Cuál es la categoría? No hay opciones registradas para este campo. Por favor díctame el valor que deseas usar.', $categoria['speak']);

        $sector = $this->say('Panadería');
        $this->assertSame('sector', $sector['field']);
        $this->assertStringContainsString('No hay opciones registradas para este campo.', $sector['speak']);
        $this->say('San Blas');
        $this->say('pan integral y tortas');
        $this->say('987654321');
        $this->say('Calle Tandapata 5');
        $this->say('precio a consultar');
        $last = $this->say('todos los dias');
        $this->assertSame('working', $last['type']);

        $business = Business::firstOrFail();
        $this->assertNull($business->category_id);
        $this->assertSame('panaderia', $business->custom_category);
        $this->assertSame('panaderia', $business->categoryLabel());
        $this->assertSame('san blas', $business->sector);
        $this->assertSame(['pan', 'integral', 'tortas'], $business->tags);

        Notification::assertSentTo($this->admin, CatalogFreeValueNotification::class, function ($n) {
            return array_keys($n->values) === ['categoria', 'sector', 'etiquetas']
                && $n->values['categoria'] === 'panaderia';
        });
        Notification::assertNotSentTo($this->entrepreneur, CatalogFreeValueNotification::class);
    }

    public function test_tags_none_and_invalid(): void
    {
        $this->seedCatalogs();
        $this->start();
        foreach (['Soluciones Rosa', 'Reparamos computadoras', 'servicio', 'tecnologia', 'norte'] as $answer) {
            $this->say($answer);
        }
        $retry = $this->say('baratísimo');
        $this->assertSame('etiquetas', $retry['field']);
        $this->assertStringStartsWith('Esas etiquetas no están disponibles. Las opciones son: Delivery, Hecho a mano, Orgánico.', $retry['speak']);

        $this->assertSame('telefono', $this->say('Ninguna')['field']);
    }

    public function test_phone_requires_digits_and_commands_work(): void
    {
        $this->seedCatalogs();
        $this->start();
        foreach (['Soluciones Rosa', 'Reparamos computadoras', 'servicio', 'tecnologia', 'norte', 'ninguna'] as $answer) {
            $this->say($answer);
        }
        $this->assertSame('telefono', $this->say('no tengo')['field']);
        $this->assertSame('etiquetas', $this->say('atrás')['field']);
        $this->assertSame('etiquetas', $this->say('repetir')['field']);
        $this->assertSame('exited', $this->say('cancelar')['type']);
        $this->assertSame(0, Business::count());
    }

    public function test_full_registration_is_saved_completely(): void
    {
        $this->seedCatalogs();
        $last = $this->registerAll();

        $business = Business::firstOrFail();
        $this->assertSame('working', $last['type']);
        $this->assertSame(route('entrepreneur.businesses.voice.publish', $business), $last['next']);

        $this->assertSame($this->entrepreneur->entrepreneurProfile->id, $business->entrepreneur_profile_id);
        $this->assertSame('soluciones tecnicas rosa', $business->name);
        $this->assertSame('reparacion de computadoras y celulares', $business->description);
        $this->assertSame('Tecnología', $business->category->name);
        $this->assertSame('Norte', $business->sector);
        $this->assertSame(['Delivery', 'Hecho a mano'], $business->tags);
        $this->assertSame('987654321', $business->phone);
        $this->assertSame('av el sol 123 cusco', $business->address);
        $this->assertSame(Business::TYPE_SERVICIO, $business->type);
        $this->assertSame('desde 10 dolares', $business->price_text);
        $this->assertSame('lunes a viernes de 8 a 5', $business->schedule_text);
        $this->assertSame(Business::TYPE_SERVICIO, $business->adPublication->type);
        $this->assertSame(Business::PUBLISH_PENDIENTE, $business->publish_status);

        // La sesión del asistente queda limpia para el próximo registro.
        $this->assertNull(cache()->get('biz-voice-test'));
    }

    // ------------------------------------------------------------------
    // Tarea 4: prompt, imagen, publicación y confirmación
    // ------------------------------------------------------------------

    public function test_image_prompt_is_built_from_the_business_data(): void
    {
        $this->seedCatalogs();
        $this->fakeServers();
        $this->registerAll();

        $prompt = app(AdPromptBuilder::class)->build(Business::firstOrFail());
        $this->assertStringStartsWith('Create a professional and vibrant advertisement image for a business called "soluciones tecnicas rosa".', $prompt);
        $this->assertStringContainsString('Category: Tecnología. Sector: Norte. Location: av el sol 123 cusco.', $prompt);
        $this->assertStringContainsString('Description: reparacion de computadoras y celulares.', $prompt);
        $this->assertStringContainsString('Price range: desde 10 dolares.', $prompt);
        $this->assertStringContainsString('Hours: lunes a viernes de 8 a 5.', $prompt);
        $this->assertStringContainsString('Keywords: Delivery, Hecho a mano.', $prompt);
    }

    public function test_end_to_end_publishes_and_saves_everything(): void
    {
        $this->seedCatalogs();
        $this->fakeServers();
        $last = $this->registerAll();

        $done = $this->actingAs($this->entrepreneur)->postJson($last['next'])->assertOk()->json();

        // Paso D: confirmación por voz y vuelta al dashboard.
        $this->assertSame('navigate', $done['type']);
        $this->assertSame(BusinessVoiceController::PUBLISHED, $done['speak']);
        $this->assertSame('Tu emprendimiento y su imagen han sido publicados exitosamente.', $done['speak']);
        $this->assertSame(route('entrepreneur.dashboard'), $done['redirect']);

        $business = Business::firstOrFail();
        $this->assertStringStartsWith('Create a professional and vibrant advertisement image for a business called', $business->image_prompt);
        $this->assertSame(self::IMAGE_URL, $business->image_url);
        $this->assertSame('AD-777', $business->external_ad_id);
        $this->assertSame('https://anuncios.test/tecnologia/norte/AD-777', $business->external_ad_url);
        $this->assertSame(Business::PUBLISH_PUBLICADO, $business->publish_status);
        $this->assertNotNull($business->published_at);

        // Paso B: POST al servidor de imágenes con el prompt.
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::IMAGE_SERVER
            && $r->method() === 'POST'
            && $r['prompt'] === $business->image_prompt
            && $r->hasHeader('Authorization', 'Bearer img-key'));

        // Paso C: datos + imagen organizados por categoría → sector → etiquetas.
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::PUBLISH_WEB
            && $r->method() === 'POST'
            && $r->hasHeader('Authorization', 'Bearer pub-key')
            && $r['categoria'] === 'Tecnología'
            && $r['sector'] === 'Norte'
            && $r['etiquetas'] === ['Delivery', 'Hecho a mano']
            && $r['ruta'] === ['Tecnología', 'Norte']
            && $r['imagen_url'] === self::IMAGE_URL
            && $r['titulo'] === 'soluciones tecnicas rosa'
            && $r['telefono'] === '987654321'
            && $r['ubicacion'] === 'av el sol 123 cusco');
        Http::assertSentCount(2);

        // Repetir la publicación no vuelve a publicar.
        $this->actingAs($this->entrepreneur)->postJson($last['next'])->assertOk();
        Http::assertSentCount(2);
    }

    public function test_invalid_image_server_response_publishes_without_image(): void
    {
        $this->seedCatalogs();
        Http::fake([
            self::IMAGE_SERVER => Http::response(['url' => 'no-es-una-url']),
            self::PUBLISH_WEB => Http::response(['id' => 'AD-1'], 201),
        ]);
        $last = $this->registerAll();

        $done = $this->actingAs($this->entrepreneur)->postJson($last['next'])->assertOk()->json();

        // Sin imagen se publica igual y se avisa que la imagen llegará después.
        $this->assertSame(BusinessVoiceController::IMAGE_PENDING, $done['speak']);
        $business = Business::firstOrFail();
        $this->assertNull($business->image_url);
        $this->assertTrue($business->image_pending);
        $this->assertSame(Business::PUBLISH_PUBLICADO, $business->publish_status);
        $this->assertSame('AD-1', $business->external_ad_id);
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::PUBLISH_WEB && $r['imagen_url'] === null);
    }

    public function test_publish_web_failure_keeps_image_and_retry_does_not_regenerate_it(): void
    {
        $this->seedCatalogs();
        Http::fakeSequence(self::PUBLISH_WEB)->push(['error' => 'caído'], 503)->push(['id' => 99], 201);
        Http::fake([self::IMAGE_SERVER => Http::response(['data' => [['url' => self::IMAGE_URL]]])]);
        $last = $this->registerAll();

        $this->actingAs($this->entrepreneur)->postJson($last['next'])->assertOk();
        $business = Business::firstOrFail();
        $this->assertSame(Business::PUBLISH_ERROR, $business->publish_status);
        $this->assertSame(self::IMAGE_URL, $business->image_url);

        $done = $this->actingAs($this->entrepreneur)->postJson($last['next'])->assertOk()->json();
        $this->assertSame(BusinessVoiceController::PUBLISHED, $done['speak']);
        $this->assertSame('99', $business->fresh()->external_ad_id);
        Http::assertSentCount(3); // 1 imagen + 2 intentos de publicación
    }

    public function test_without_external_urls_uses_local_image_and_sonara_portal(): void
    {
        config(['services.image_server.url' => null, 'services.publish_web.url' => null]);
        $this->seedCatalogs();
        $last = $this->registerAll();

        $done = $this->actingAs($this->entrepreneur)->postJson($last['next'])->assertOk()->json();
        $this->assertSame(BusinessVoiceController::PUBLISHED, $done['speak']);

        $business = Business::firstOrFail();
        $this->assertStringStartsWith(asset('storage/flyers/'), $business->image_url);
        $this->assertSame('sonara-'.$business->id, $business->external_ad_id);
        $this->assertSame(route('public.publication', $business->adPublication->slug), $business->external_ad_url);
        $this->get($business->external_ad_url)->assertOk();
        Http::assertNothingSent();
    }

    public function test_cannot_publish_someone_elses_business(): void
    {
        $this->seedCatalogs();
        $this->fakeServers();
        $last = $this->registerAll();

        $other = User::factory()->create();
        $other->assignRole('entrepreneur');
        EntrepreneurProfile::create(['user_id' => $other->id]);

        $this->actingAs($other)->postJson($last['next'])->assertForbidden();
        Http::assertNothingSent();
    }
}
