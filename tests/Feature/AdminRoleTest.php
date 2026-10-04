<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Assistant\VoiceAssistantService;
use App\Services\Assistant\VoiceLoginService;
use App\Services\Verification\EntrepreneurRecordValidator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/** Tarea 1: rol admin, lista de emprendedores y el PIN nunca en texto plano. */
class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private const PIN = '7205';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->app->instance(VoiceAssistantService::class, new VoiceAssistantService('admin-role-test'));
        $this->app->instance(VoiceLoginService::class, new VoiceLoginService('admin-role-login-test'));
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['email' => 'admin@sonara.test', 'password' => 'Password123!']);
        $admin->assignRole('admin');

        return $admin;
    }

    /** Registra a "Rosa" por voz y devuelve el usuario creado. */
    private function registerByVoice(): User
    {
        $this->getJson(route('voice-registration.start'))->assertOk();
        foreach (['Rosa María', 'Mamani Quispe', 'Tejo chompas de alpaca', 'Cusco, San Blas', '987 654 321'] as $answer) {
            $this->postJson(route('voice-registration.process'), ['transcript' => $answer])->assertOk();
        }
        $last = $this->postJson(route('voice-registration.process'), ['transcript' => 'siete dos cero cinco'])->assertOk()->json();
        $this->assertSame('registered', $last['type']);

        return User::where('username', 'rosa maria mamani quispe')->firstOrFail();
    }

    public function test_admin_login_redirects_to_admin_panel(): void
    {
        $this->makeAdmin();

        $this->post('/login', ['email' => 'admin@sonara.test', 'password' => 'Password123!'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_roles_are_kept_apart(): void
    {
        $admin = $this->makeAdmin();
        $entrepreneur = $this->registerByVoice();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isEntrepreneur());
        $this->assertTrue($entrepreneur->isEntrepreneur());
        $this->assertFalse($entrepreneur->isAdmin());

        $this->actingAs($entrepreneur)->get(route('admin.entrepreneurs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('entrepreneur.dashboard'))->assertForbidden();
    }

    public function test_admin_cannot_use_entrepreneur_voice_login(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['username' => 'admin sonara'])->save();
        $admin->voice_pin = self::PIN;
        $admin->save();

        $this->getJson(route('voice-login.start'))->assertOk();
        $this->postJson(route('voice-login.process'), ['transcript' => 'admin sonara'])->assertOk();
        $result = $this->postJson(route('voice-login.process'), ['transcript' => self::PIN])->assertOk()->json();

        $this->assertStringContainsString('no son correctos', $result['speak']);
        $this->assertGuest();
    }

    public function test_voice_registration_stores_every_field_and_hashes_the_pin(): void
    {
        $user = $this->registerByVoice();

        $this->assertSame([], app(EntrepreneurRecordValidator::class)->issuesFor($user));
        $this->assertSame('rosa maria', $user->first_name);
        $this->assertSame('mamani quispe', $user->last_name);
        $this->assertSame('987654321', $user->phone);
        $this->assertSame('tejo chompas de alpaca', $user->entrepreneurProfile->personal_description);
        $this->assertSame('cusco san blas', $user->entrepreneurProfile->location);

        $raw = $user->getRawOriginal('voice_pin');
        $this->assertNotSame(self::PIN, $raw);
        $this->assertTrue(EntrepreneurRecordValidator::isHash($raw));
    }

    public function test_entrepreneur_list_shows_data_but_never_the_pin(): void
    {
        $entrepreneur = $this->registerByVoice();
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.entrepreneurs.index'))->assertOk();

        $response->assertSee('rosa maria')
            ->assertSee('mamani quispe')
            ->assertSee('tejo chompas de alpaca')
            ->assertSee('cusco san blas')
            ->assertSee('987654321')
            ->assertSee('Configurado')
            ->assertSee('Completo');

        $html = $response->getContent();
        $this->assertStringNotContainsString(self::PIN, $html);
        $this->assertStringNotContainsString('7 2 0 5', $html);
        $this->assertStringNotContainsString($entrepreneur->getRawOriginal('voice_pin'), $html);

        $show = $this->actingAs($admin)->get(route('admin.entrepreneurs.show', $entrepreneur))->assertOk()->getContent();
        $this->assertStringNotContainsString(self::PIN, $show);
        $this->assertStringNotContainsString($entrepreneur->getRawOriginal('voice_pin'), $show);
    }

    public function test_list_flags_incomplete_records(): void
    {
        $this->registerByVoice()->entrepreneurProfile->update(['location' => null]);

        $this->actingAs($this->makeAdmin())->get(route('admin.entrepreneurs.index'))
            ->assertOk()
            ->assertSee('Incompleto (1)')
            ->assertSee('Ubicación');
    }

    public function test_pin_never_appears_in_logs_audit_cache_or_json(): void
    {
        $logFile = storage_path('logs/laravel.log');
        $logBefore = is_file($logFile) ? (string) file_get_contents($logFile) : '';

        $user = $this->registerByVoice();

        $logAfter = is_file($logFile) ? (string) file_get_contents($logFile) : '';
        $this->assertStringNotContainsString('7205', substr($logAfter, strlen($logBefore)));

        foreach (AuditLog::all() as $log) {
            $this->assertStringNotContainsString(self::PIN, json_encode($log->toArray()));
        }

        // La sesión del asistente ya no guarda el PIN en claro.
        $this->assertNull(cache()->get('admin-role-test'));

        // Serializado (APIs, logs de modelos) el PIN está oculto.
        $this->assertArrayNotHasKey('voice_pin', $user->toArray());
    }

    public function test_registration_confirmation_masks_pin_on_screen(): void
    {
        $this->getJson(route('voice-registration.start'))->assertOk();
        foreach (['Rosa María', 'Mamani Quispe', 'Tejo chompas', 'Cusco', '987654321'] as $answer) {
            $this->postJson(route('voice-registration.process'), ['transcript' => $answer]);
        }
        $last = $this->postJson(route('voice-registration.process'), ['transcript' => '7205'])->json();

        // Se dice en voz alta (es la única vez que el usuario lo escucha)
        // pero en pantalla se muestra enmascarado.
        $this->assertStringContainsString('7 2 0 5', $last['speak']);
        $this->assertStringNotContainsString('7205', $last['display']);
        $this->assertStringNotContainsString('7 2 0 5', $last['display']);
    }

    public function test_verification_command_reports_incomplete_records_without_pin(): void
    {
        $user = $this->registerByVoice();

        $this->assertSame(0, Artisan::call('sonara:verificar-emprendedores'));
        $this->assertStringNotContainsString(self::PIN, Artisan::output());

        $user->entrepreneurProfile->update(['location' => '']);
        $this->assertSame(1, Artisan::call('sonara:verificar-emprendedores'));
        $output = Artisan::output();
        $this->assertStringContainsString('Ubicación (vacío)', $output);
        $this->assertStringNotContainsString($user->getRawOriginal('voice_pin'), $output);
    }

    public function test_admin_can_complete_missing_location(): void
    {
        $user = $this->registerByVoice();
        $user->entrepreneurProfile->update(['location' => null]);

        $this->actingAs($this->makeAdmin())->patch(route('admin.entrepreneurs.update', $user), [
            'first_name' => 'rosa maria',
            'last_name' => 'mamani quispe',
            'email' => $user->email,
            'phone' => '987654321',
            'personal_description' => 'tejo chompas de alpaca',
            'location' => 'Cusco, San Blas',
        ])->assertRedirect(route('admin.entrepreneurs.index'));

        $user->refresh();
        $this->assertSame('cusco san blas', $user->entrepreneurProfile->location);
        $this->assertSame('rosa maria mamani quispe', $user->username);
        $this->assertSame([], app(EntrepreneurRecordValidator::class)->issuesFor($user));
    }
}
