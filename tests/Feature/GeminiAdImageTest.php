<?php

namespace Tests\Feature;

use App\Http\Controllers\BusinessVoiceController;
use App\Models\Business;
use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use App\Services\AI\GeminiImageGenerator;
use App\Services\Assistant\BusinessVoiceRegistrationService;
use App\Services\Catalog\CatalogService;
use App\Services\Publishing\AdPromptBuilder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/** Imagen del anuncio con Google Gemini al terminar el registro por voz. */
class GeminiAdImageTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-gemini-key-NO-DEBE-FILTRARSE';
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';

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

        $this->app->bind(BusinessVoiceRegistrationService::class, fn ($app) => new BusinessVoiceRegistrationService($app->make(CatalogService::class), 'gemini-test'));
        config([
            'services.gemini.api_key' => self::KEY,
            'services.gemini.image_model' => 'gemini-3.1-flash-lite-image',
            'services.image_server.url' => null,
            'services.publish_web.url' => null,
        ]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        foreach (glob(public_path(GeminiImageGenerator::directory().'/emprendimiento-*')) as $file) {
            File::delete($file);
        }
        parent::tearDown();
    }

    /** JPEG mínimo de 1x1 (sin depender de GD). */
    private const JPEG_BASE64 = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

    /** Respuesta con la forma real de la Interactions API (verificada contra la API). */
    private static function geminiResponse(): array
    {
        return [
            'id' => 'v1_test', 'status' => 'completed', 'object' => 'interaction',
            'model' => 'gemini-3.1-flash-lite-image',
            'steps' => [
                ['type' => 'thought', 'signature' => 'xyz'],
                ['type' => 'model_output', 'content' => [['type' => 'image', 'mime_type' => 'image/jpeg', 'data' => self::JPEG_BASE64]]],
            ],
        ];
    }

    private function registerByVoice(): string
    {
        $this->actingAs($this->entrepreneur)->getJson(route('entrepreneur.businesses.voice.start'));
        $last = null;
        foreach (['Clases de Matemática', 'Clases particulares para escolares', 'servicio', 'educación', 'centro',
            'algebra y geometria', '984111222', 'Calle Plateros 340', 'Desde 20 soles la hora', 'Lunes a sábado de 3 a 8'] as $answer) {
            $last = $this->actingAs($this->entrepreneur)->postJson(route('entrepreneur.businesses.voice.process'), ['transcript' => $answer])->json();
        }

        return $last['next'];
    }

    // 1. La API key se lee del entorno y no está en el código.
    public function test_api_key_comes_from_env_and_is_not_hardcoded(): void
    {
        $this->assertStringContainsString("env('GEMINI_API_KEY')", File::get(config_path('services.php')));

        $realKey = collect(file(base_path('.env'), FILE_IGNORE_NEW_LINES) ?: [])
            ->first(fn ($line) => str_starts_with($line, 'GEMINI_API_KEY='));
        $realKey = $realKey ? trim(substr($realKey, strlen('GEMINI_API_KEY='))) : '';
        if ($realKey === '') {
            $this->markTestSkipped('No hay GEMINI_API_KEY en .env para buscarla en el código.');
        }

        $finder = (new Finder)->files()->in([base_path('app'), base_path('config'), base_path('routes'), base_path('resources'), base_path('database'), base_path('public')])
            ->exclude(['build', 'storage', 'uploads'])->name(['*.php', '*.js', '*.json', '*.css']);
        foreach ($finder as $file) {
            $this->assertStringNotContainsString($realKey, $file->getContents(), 'Clave en '.$file->getRelativePathname());
        }
    }

    // 2. El prompt se construye con los datos del emprendimiento.
    public function test_prompt_is_built_from_business_data(): void
    {
        $this->registerByVoice();
        $prompt = app(AdPromptBuilder::class)->build(Business::firstOrFail());

        $this->assertSame(implode("\n", [
            'Design a professional, eye-catching advertising flyer for a business.',
            '',
            'BUSINESS INFORMATION TO INCLUDE IN THE FLYER:',
            '- Business name: "clases de matematica" (make this the most prominent text, large and bold)',
            '- Category: Educación',
            '- Sector / Zone: centro — calle plateros 340',
            '- Description: "clases particulares para escolares" (include as a short tagline or subtitle)',
            '- Price: desde 20 soles la hora',
            '- Hours: lunes a sabado de 3 a 8',
            '- WhatsApp: 984111222',
            '- Keywords / tags: algebra, geometria',
            '',
            'DESIGN REQUIREMENTS:',
            '- Style: modern, vibrant, professional — like a real printed or digital flyer',
            '- Layout: structured with clear visual hierarchy (title at top, details in middle, contact at bottom)',
            '- Include decorative design elements, shapes, or backgrounds that match the category "Educación"',
            '- Use bold colors and clean typography',
            '- Add a subtle "WhatsApp" label next to the phone number',
            '- Make it look like it was designed by a professional graphic designer',
            '- Format: vertical/portrait orientation, suitable for sharing on social media or WhatsApp',
            '- Do NOT add any placeholder text, watermarks, or lorem ipsum',
            '- All text in the flyer must be exactly as provided above, no invented information',
        ]), $prompt);

        // Sin datos opcionales: valores en español para el flyer y sin líneas vacías de WhatsApp/etiquetas.
        $empty = app(AdPromptBuilder::class)->build(new Business(['name' => 'x', 'description' => 'y']));
        $this->assertStringContainsString('- Price: Consultar precio', $empty);
        $this->assertStringContainsString('- Hours: Consultar horario', $empty);
        $this->assertStringNotContainsString('WhatsApp:', $empty);
        $this->assertStringNotContainsString('Keywords', $empty);
        $this->assertStringContainsString('- Do not show any phone number', $empty);
    }

    // El diseño se orienta según el rubro: la categoría entra en el prompt.
    public function test_prompt_varies_with_category(): void
    {
        $comida = Category::create(['name' => 'Comida', 'slug' => 'comida', 'is_active' => true]);
        $a = new Business(['name' => 'a', 'description' => 'd']);
        $a->setRelation('category', $this->educacion);
        $b = new Business(['name' => 'b', 'description' => 'd']);
        $b->setRelation('category', $comida);

        $this->assertStringContainsString('match the category "Educación"', app(AdPromptBuilder::class)->build($a));
        $this->assertStringContainsString('match the category "Comida"', app(AdPromptBuilder::class)->build($b));
    }

    // 3, 4, 5, 6. Gemini → imagen válida → archivo en public/uploads → URL en BD → tarjeta del muro.
    public function test_generates_saves_and_shows_the_image_on_the_wall(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::geminiResponse())]);
        $next = $this->registerByVoice();

        $done = $this->actingAs($this->entrepreneur)->postJson($next)->assertOk()->json();
        $this->assertSame(BusinessVoiceController::PUBLISHED, $done['speak']);
        $this->assertSame('Tu emprendimiento y su imagen han sido publicados exitosamente.', $done['speak']);

        $business = Business::firstOrFail();
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::ENDPOINT
            && $r->hasHeader('x-goog-api-key', self::KEY)
            && ! str_contains($r->url(), self::KEY)
            && $r['model'] === 'gemini-3.1-flash-lite-image'
            && $r['input'] === $business->image_prompt
            && $r['response_format'] === ['type' => 'image', 'aspect_ratio' => '9:16']);

        $file = public_path(GeminiImageGenerator::directory().'/emprendimiento-'.$business->id.'.jpg');
        $this->assertFileExists($file);
        $this->assertSame('image/jpeg', getimagesize($file)['mime']);

        $this->assertSame(asset(GeminiImageGenerator::directory().'/emprendimiento-'.$business->id.'.jpg'), $business->image_url);
        $this->assertFalse($business->image_pending);
        $this->assertSame(GeminiImageGenerator::directory().'/emprendimiento-'.$business->id.'.jpg', $business->adPublication->flyer_image);

        auth()->logout();
        $this->get(route('public.category', $this->educacion))->assertOk()
            ->assertSee(asset(GeminiImageGenerator::directory().'/emprendimiento-'.$business->id.'.jpg'), false);
    }

    // 7. Si Gemini falla, el emprendimiento se guarda y publica sin imagen.
    public function test_api_failure_keeps_the_business_and_marks_image_pending(): void
    {
        // 1ª llamada: cuota agotada; 2ª (el reintento): imagen.
        Http::fakeSequence(self::ENDPOINT)
            ->push(['error' => ['message' => 'Quota exceeded']], 429)
            ->push(self::geminiResponse());
        $next = $this->registerByVoice();

        $done = $this->actingAs($this->entrepreneur)->postJson($next)->assertOk()->json();
        $this->assertSame(BusinessVoiceController::IMAGE_PENDING, $done['speak']);

        $business = Business::firstOrFail();
        $this->assertNull($business->image_url);
        $this->assertTrue($business->image_pending);
        $this->assertSame(Business::PUBLISH_PUBLICADO, $business->publish_status); // la publicación siguió
        auth()->logout();
        $this->get(route('public.category', $this->educacion))->assertOk()->assertSee('clases de matematica');

        // El reintento genera la imagen y la tarjeta la toma.
        $this->assertSame(0, Artisan::call('sonara:reintentar-imagenes'));
        $business->refresh();
        $this->assertFalse($business->image_pending);
        $this->assertNotNull($business->image_url);
        $this->assertSame(GeminiImageGenerator::directory().'/emprendimiento-'.$business->id.'.jpg', $business->adPublication->flyer_image);
    }

    public function test_response_without_image_is_a_failure(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['status' => 'completed', 'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => 'No puedo']]]]])]);
        $next = $this->registerByVoice();

        $this->actingAs($this->entrepreneur)->postJson($next)->assertOk();
        $this->assertTrue(Business::firstOrFail()->image_pending);
    }

    // 8. La clave no aparece en logs, errores guardados ni páginas.
    public function test_api_key_never_leaks_to_logs_errors_or_pages(): void
    {
        $logged = [];
        Log::listen(function ($event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });

        Http::fake([self::ENDPOINT => fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28 for '.self::ENDPOINT)]);
        $next = $this->registerByVoice();
        $this->actingAs($this->entrepreneur)->postJson($next)->assertOk()->assertDontSee(self::KEY);

        $this->assertNotEmpty($logged);
        foreach ($logged as $line) {
            $this->assertStringNotContainsString(self::KEY, $line);
        }
        $business = Business::firstOrFail();
        $this->assertStringNotContainsString(self::KEY, json_encode($business->getAttributes()));

        $this->actingAs($this->entrepreneur)->get(route('entrepreneur.businesses.voice'))->assertDontSee(self::KEY);
        auth()->logout();
        $this->get(route('public.category', $this->educacion))->assertDontSee(self::KEY);
    }
}
