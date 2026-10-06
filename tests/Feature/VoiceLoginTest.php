<?php

namespace Tests\Feature;

use App\Models\AccessibilityPreference;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use App\Services\Assistant\VoiceLoginAuthenticator;
use App\Services\Assistant\VoiceLoginService;
use App\Services\Verification\VerificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** Login de emprendedor por voz: usuario = nombre completo, contraseña = PIN de 4 dígitos. */
class VoiceLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        RateLimiter::clear('voice-login|rosa maria mamani quispe|127.0.0.1');
        // Clave fija: el cliente de pruebas no conserva la cookie de sesión.
        $this->app->instance(VoiceLoginService::class, new VoiceLoginService('voice-login-test'));
    }

    private function makeRosa(string $pin = '7205', string $role = 'entrepreneur'): User
    {
        $user = User::factory()->create([
            'first_name' => 'rosa maria',
            'last_name' => 'mamani quispe',
            'name' => 'rosa maria mamani quispe',
            'username' => 'rosa maria mamani quispe',
        ]);
        $user->assignRole($role);
        $user->voice_pin = $pin;
        $user->save();
        $profile = EntrepreneurProfile::create(['user_id' => $user->id]);
        app(VerificationService::class)->startVerificationWindow($profile);
        AccessibilityPreference::create(['user_id' => $user->id, 'navigation_mode' => 'voz']);

        return $user;
    }

    private function say(string $transcript): array
    {
        return $this->postJson(route('voice-login.process'), ['transcript' => $transcript])
            ->assertOk()
            ->json();
    }

    private function login(string $usuario, string $pin): array
    {
        $this->getJson(route('voice-login.start'))->assertOk();
        $this->say($usuario);

        return $this->say($pin);
    }

    public function test_page_renders_in_login_mode(): void
    {
        $this->get(route('voice-login.index'))
            ->assertOk()
            ->assertSee('data-mode="login"', false);
    }

    public function test_conversation_follows_the_script(): void
    {
        $step = $this->getJson(route('voice-login.start'))->json();
        $this->assertSame('Bienvenido a la pantalla de login. ¿Cuál es su usuario?', $step['speak']);
        $this->assertSame('usuario', $step['field']);

        $step = $this->say('rosa maria mamani quispe');
        $this->assertSame('¿Cuál es su PIN?', $step['speak']);
        $this->assertSame('pin', $step['field']);
    }

    public function test_correct_name_and_pin_logs_in_and_redirects_to_dashboard(): void
    {
        $user = $this->makeRosa();

        $result = $this->login('rosa maria mamani quispe', 'siete dos cero cinco');

        $this->assertSame('success', $result['type']);
        $this->assertSame('Perfecto, bienvenido rosa maria mamani quispe.', $result['speak']);
        $this->assertSame(route('entrepreneur.dashboard'), $result['redirect']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_username_ignores_case_accents_and_punctuation(): void
    {
        $user = $this->makeRosa();

        $result = $this->login('ROSA MARÍA Mamani Quispe.', '7 2 0 5');

        $this->assertSame('success', $result['type']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_pin_fails_generically_and_asks_again(): void
    {
        $this->makeRosa();

        $result = $this->login('rosa maria mamani quispe', '0000');

        $this->assertSame('question', $result['type']);
        $this->assertSame('usuario', $result['field'], 'Debe volver a preguntar el usuario.');
        $this->assertStringContainsString('El usuario o el PIN no son correctos', $result['speak']);
        $this->assertGuest();
    }

    public function test_unknown_user_gets_the_same_message_as_wrong_pin(): void
    {
        $this->makeRosa();

        $result = $this->login('juan perez', '7205');

        $this->assertStringContainsString('El usuario o el PIN no son correctos', $result['speak']);
        $this->assertGuest();
    }

    public function test_pin_must_have_four_digits(): void
    {
        $this->getJson(route('voice-login.start'));
        $this->say('rosa maria mamani quispe');

        $step = $this->say('setenta y dos');
        $this->assertSame('pin', $step['field']);
        $this->assertStringContainsString('cuatro dígitos', $step['speak']);
    }

    public function test_non_entrepreneur_cannot_log_in(): void
    {
        $this->makeRosa('7205', 'customer');

        $result = $this->login('rosa maria mamani quispe', '7205');

        $this->assertSame('question', $result['type']);
        $this->assertGuest();
    }

    public function test_rate_limits_after_repeated_failures(): void
    {
        $this->makeRosa();

        for ($i = 0; $i < VoiceLoginAuthenticator::MAX_ATTEMPTS; $i++) {
            $this->login('rosa maria mamani quispe', '0000');
        }

        // Incluso con el PIN correcto queda bloqueado un tiempo.
        $result = $this->login('rosa maria mamani quispe', '7205');
        $this->assertSame('locked', $result['type']);
        $this->assertGuest();
    }

    public function test_registration_then_login_end_to_end(): void
    {
        $this->app->instance(
            \App\Services\Assistant\VoiceAssistantService::class,
            new \App\Services\Assistant\VoiceAssistantService('voice-reg-e2e')
        );
        $this->getJson(route('voice-registration.start'));
        foreach (['Luis', 'Huamán Torres', '41112233', 'severa', 'si', '7788', 'Hago cerámica', 'Pisac', '984111222', 'cuatro cuatro uno uno'] as $answer) {
            $last = $this->postJson(route('voice-registration.process'), ['transcript' => $answer])->json();
        }
        $this->assertSame('registered', $last['type']);

        $result = $this->login('luis huaman torres', '4411');

        $this->assertSame('success', $result['type']);
        $this->assertSame('Perfecto, bienvenido luis huaman torres.', $result['speak']);
        $this->assertAuthenticated();
        $this->get($result['redirect'])->assertOk();
    }
}
