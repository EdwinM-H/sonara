<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Services\Assistant\VoiceAssistantService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Registro de emprendedor por voz: 6 campos, normalización, PIN hasheado y paso al login. */
class VoiceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        // Clave fija: el progreso se guarda por sesión y el cliente de
        // pruebas no conserva la cookie de sesión entre peticiones.
        $this->app->instance(VoiceAssistantService::class, new VoiceAssistantService('voice-reg-test'));
    }

    private function say(string $transcript): array
    {
        return $this->postJson(route('voice-registration.process'), ['transcript' => $transcript])
            ->assertOk()
            ->json();
    }

    /** @return array<int, array> respuestas de cada paso */
    private function registerRosa(): array
    {
        $this->getJson(route('voice-registration.start'))->assertOk();

        return [
            $this->say('Rosa María'),
            $this->say('Mamani Quispe.'),
            $this->say('Tejo chompas de alpaca desde hace veinte años.'),
            $this->say('Cusco, San Blas'),
            $this->say('987 654 321'),
            $this->say('siete dos cero cinco'),
        ];
    }

    public function test_page_renders_in_register_mode(): void
    {
        $this->get(route('voice-registration.index'))
            ->assertOk()
            ->assertSee('data-mode="register"', false);
    }

    public function test_asks_exactly_the_six_fields_in_order(): void
    {
        $first = $this->getJson(route('voice-registration.start'))->assertOk()->json();
        $this->assertSame('question', $first['type']);
        $this->assertSame('nombres', $first['field']);
        $this->assertSame(6, $first['total']);
        $this->assertStringContainsString('pitido', $first['speak']);

        $fields = [$first['field']];
        foreach (['rosa', 'mamani', 'tejo chompas', 'cusco', '987654321'] as $answer) {
            $fields[] = $this->say($answer)['field'];
        }

        $this->assertSame(['nombres', 'apellidos', 'sobre_mi', 'ubicacion', 'telefono_whatsapp', 'pin'], $fields);
        $this->assertSame(array_keys(VoiceAssistantService::STEPS), $fields);
    }

    public function test_full_registration_creates_account_and_redirects_to_login(): void
    {
        $steps = $this->registerRosa();
        $last = end($steps);

        $this->assertSame('registered', $last['type']);
        $this->assertSame(route('voice-login.index'), $last['redirect']);
        $this->assertStringStartsWith('Perfecto, se creó la cuenta.', $last['speak']);
        $this->assertStringContainsString('rosa maria mamani quispe', $last['speak']);
        $this->assertStringContainsString('7 2 0 5', $last['speak']);
        $this->assertStringEndsWith(
            'Ahora le mandaremos a la pantalla de login donde deberá ingresar con las credenciales que se le dieron.',
            $last['speak']
        );

        $user = User::where('username', 'rosa maria mamani quispe')->firstOrFail();

        // Todo normalizado: minúsculas, sin tildes ni caracteres especiales.
        $this->assertSame('rosa maria', $user->first_name);
        $this->assertSame('mamani quispe', $user->last_name);
        $this->assertSame('987654321', $user->phone);
        $this->assertSame('tejo chompas de alpaca desde hace veinte anos', $user->entrepreneurProfile->personal_description);
        $this->assertSame('cusco san blas', $user->entrepreneurProfile->location);

        // PIN: nunca en texto plano.
        $raw = $user->getRawOriginal('voice_pin');
        $this->assertNotSame('7205', $raw);
        $this->assertStringStartsWith('$2y$', $raw, 'Debe guardarse con bcrypt.');
        $this->assertTrue(Hash::check('7205', $raw));

        $this->assertTrue($user->isEntrepreneur());
        $this->assertSame('voz', $user->accessibilityPreference->navigation_mode);
        $this->assertSame(0, Business::count(), 'El registro no debe crear datos que no se pidieron.');

        // No inicia sesión: la persona debe ingresar por el login.
        $this->assertGuest();
        // El progreso se limpia al terminar.
        $this->assertFalse(app(VoiceAssistantService::class)->hasSession());
    }

    public function test_names_reject_digits_and_do_not_advance(): void
    {
        $this->getJson(route('voice-registration.start'));

        $step = $this->say('Rosa 22');
        $this->assertSame('nombres', $step['field']);
        $this->assertStringContainsString('Solo necesito letras', $step['speak']);

        $this->assertSame('apellidos', $this->say('Rosa')['field']);
        $step = $this->say('Mamani 3');
        $this->assertSame('apellidos', $step['field']);
    }

    public function test_phone_accepts_only_digits(): void
    {
        $this->getJson(route('voice-registration.start'));
        foreach (['rosa', 'mamani', 'tejo', 'cusco'] as $a) {
            $this->say($a);
        }

        $step = $this->say('no tengo');
        $this->assertSame('telefono_whatsapp', $step['field']);
        $this->assertStringContainsString('No entendí el número', $step['speak']);

        $step = $this->say('nueve ocho siete seis cinco cuatro tres dos uno');
        $this->assertSame('pin', $step['field']);
        $this->assertSame('987654321', app(VoiceAssistantService::class)->data()['telefono_whatsapp']);
    }

    public function test_pin_requires_exactly_four_digits(): void
    {
        $this->getJson(route('voice-registration.start'));
        foreach (['rosa', 'mamani', 'tejo', 'cusco', '987654321'] as $a) {
            $this->say($a);
        }

        foreach (['setenta y dos', '12345', 'uno dos tres'] as $bad) {
            $step = $this->say($bad);
            $this->assertSame('question', $step['type']);
            $this->assertSame('pin', $step['field']);
            $this->assertStringContainsString('cuatro dígitos', $step['speak']);
        }
        $this->assertSame(0, User::count());

        $this->assertSame('registered', $this->say('1234')['type']);
    }

    public function test_duplicate_full_name_is_rejected_case_and_accent_insensitive(): void
    {
        $this->registerRosa();
        $this->app->instance(VoiceAssistantService::class, new VoiceAssistantService('voice-reg-test-2'));
        $this->getJson(route('voice-registration.start'));

        $this->say('ROSA MARIA');
        $step = $this->say('Mamaní Quispe');

        $this->assertSame('nombres', $step['field'], 'Debe volver a pedir los nombres.');
        $this->assertStringContainsString('Ya existe una cuenta', $step['speak']);
        $this->assertSame(1, User::count());
    }

    public function test_commands_repeat_back_and_cancel(): void
    {
        $this->getJson(route('voice-registration.start'));
        $this->say('rosa');

        $step = $this->say('Repetir.');
        $this->assertSame('apellidos', $step['field']);
        $this->assertSame('¿Cuáles son sus apellidos?', $step['speak']);

        $step = $this->say('Atrás');
        $this->assertSame('nombres', $step['field']);

        $step = $this->say('atras');
        $this->assertSame('nombres', $step['field']);
        $this->assertStringContainsString('primera pregunta', $step['speak']);

        $step = $this->say('cancelar');
        $this->assertSame('exited', $step['type']);
        $this->assertFalse(app(VoiceAssistantService::class)->hasSession());
    }
}
