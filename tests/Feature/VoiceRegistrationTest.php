<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Services\Assistant\VoiceAssistantService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Registro de emprendedor por voz: campos en orden (CONADIS condicional), normalización, PIN hasheado y paso al login. */
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
            $this->say('cuatro cinco seis siete 8 9 0 1'),
            $this->say('Moderada'),
            $this->say('Sí, claro'),
            $this->say('uno dos tres cuatro cinco'),
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

    public function test_asks_the_fields_in_order_skipping_conadis_number_when_no(): void
    {
        $first = $this->getJson(route('voice-registration.start'))->assertOk()->json();
        $this->assertSame('question', $first['type']);
        $this->assertSame('nombres', $first['field']);
        $this->assertSame(9, $first['total'], 'Sin carnet CONADIS son nueve preguntas.');
        $this->assertStringContainsString('pitido', $first['speak']);

        $fields = [$first['field']];
        foreach (['rosa', 'mamani', '45678901', 'leve', 'no', 'tejo chompas', 'cusco', '987654321'] as $answer) {
            $fields[] = $this->say($answer)['field'];
        }

        $this->assertSame(['nombres', 'apellidos', 'dni', 'grado_discapacidad', 'tiene_carnet_conadis', 'sobre_mi', 'ubicacion', 'telefono_whatsapp', 'pin'], $fields);
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
        $this->assertSame('45678901', $user->entrepreneurProfile->dni);
        $this->assertSame('MODERADA', $user->entrepreneurProfile->grado_discapacidad);
        $this->assertTrue($user->entrepreneurProfile->tiene_carnet_conadis);
        $this->assertSame('12345', $user->entrepreneurProfile->numero_carnet_conadis);

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
        foreach (['rosa', 'mamani', '45678901', 'leve', 'no', 'tejo', 'cusco'] as $a) {
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
        foreach (['rosa', 'mamani', '45678901', 'leve', 'no', 'tejo', 'cusco', '987654321'] as $a) {
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

    private function reachConadis(): void
    {
        $this->getJson(route('voice-registration.start'));
        foreach (['rosa', 'mamani', '45678901', 'severa'] as $a) {
            $this->say($a);
        }
    }

    public function test_disability_grade_only_accepts_the_three_options(): void
    {
        $this->getJson(route('voice-registration.start'));
        foreach (['rosa', 'mamani', '45678901'] as $a) {
            $this->say($a);
        }

        foreach (['alta', 'no se', 'leve o moderada', ''] as $bad) {
            $step = $this->postJson(route('voice-registration.process'), ['transcript' => $bad ?: 'mmm'])->json();
            $this->assertSame('grado_discapacidad', $step['field']);
            $this->assertStringContainsString('LEVE, MODERADA o SEVERA', $step['speak'], 'Debe repetir la pregunta con las opciones.');
        }

        $this->assertSame('tiene_carnet_conadis', $this->say('Es moderado')['field']);
        $this->assertSame('MODERADA', app(VoiceAssistantService::class)->data()['grado_discapacidad']);
    }

    public function test_conadis_yes_no_variants(): void
    {
        foreach (['sí' => true, 'Si' => true, 'claro' => true, 'sí tengo' => true, 'no' => false, 'negativo' => false, 'no tengo' => false, 'claro que no' => false] as $said => $expected) {
            $this->assertSame($expected, VoiceAssistantService::parseYesNo($said), $said);
        }
        $this->assertNull(VoiceAssistantService::parseYesNo('tal vez'));

        $this->reachConadis();
        $step = $this->say('tal vez');
        $this->assertSame('tiene_carnet_conadis', $step['field']);
        $this->assertStringContainsString('Diga SÍ o NO', $step['speak']);
    }

    public function test_conadis_no_skips_number_and_saves_null(): void
    {
        $this->reachConadis();
        $this->assertSame('sobre_mi', $this->say('negativo')['field']);

        foreach (['tejo', 'cusco', '987654321'] as $a) {
            $this->say($a);
        }
        $this->assertSame('registered', $this->say('1234')['type']);

        $profile = User::firstOrFail()->entrepreneurProfile;
        $this->assertSame('SEVERA', $profile->grado_discapacidad);
        $this->assertFalse($profile->tiene_carnet_conadis);
        $this->assertNull($profile->numero_carnet_conadis);
    }

    public function test_conadis_yes_asks_number_and_back_from_next_question_returns_to_it(): void
    {
        $this->reachConadis();
        $step = $this->say('si');
        $this->assertSame('numero_carnet_conadis', $step['field']);
        $this->assertSame(10, $step['total']);

        $this->assertSame('sobre_mi', $this->say('cero cero siete siete')['field']);
        $this->assertSame('numero_carnet_conadis', $this->say('atrás')['field']);
    }

    public function test_dni_must_be_digits_and_unique(): void
    {
        $this->registerRosa();
        $this->app->instance(VoiceAssistantService::class, new VoiceAssistantService('voice-reg-test-3'));
        $this->getJson(route('voice-registration.start'));
        $this->say('pedro');
        $this->say('flores');

        $step = $this->say('no lo se');
        $this->assertSame('dni', $step['field']);
        $this->assertStringContainsString('No entendí el número', $step['speak']);

        $step = $this->say('45678901');
        $this->assertSame('dni', $step['field']);
        $this->assertStringContainsString('ya está registrado', $step['speak']);

        $this->assertSame('grado_discapacidad', $this->say('4 5 6 7 8 9 0 2')['field']);
    }
}