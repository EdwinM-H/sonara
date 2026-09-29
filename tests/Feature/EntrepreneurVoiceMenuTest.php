<?php

namespace Tests\Feature;

use App\Models\AccessibilityPreference;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use App\Services\Assistant\EntrepreneurVoiceMenu;
use App\Services\Verification\VerificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menú por voz del dashboard de emprendedor. */
class EntrepreneurVoiceMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function entrepreneur(string $navigationMode = 'voz'): User
    {
        $user = User::factory()->create();
        $user->assignRole('entrepreneur');
        $profile = EntrepreneurProfile::create(['user_id' => $user->id]);
        app(VerificationService::class)->startVerificationWindow($profile);
        AccessibilityPreference::create(['user_id' => $user->id, 'navigation_mode' => $navigationMode]);

        return $user;
    }

    private function command(string $transcript): array
    {
        return $this->postJson(route('entrepreneur.voice.command'), ['transcript' => $transcript])
            ->assertOk()
            ->json();
    }

    public function test_dashboard_includes_voice_menu_with_the_scripted_prompt(): void
    {
        $this->actingAs($this->entrepreneur('voz'))
            ->get(route('entrepreneur.dashboard'))
            ->assertOk()
            ->assertSee('data-mode="dashboard"', false)
            ->assertSee('Te encuentras en el dashboard de emprendedor. ¿Qué deseas hacer ahora?', false);
    }

    public function test_dashboard_is_silent_for_users_not_in_voice_mode(): void
    {
        $this->actingAs($this->entrepreneur('visual'))
            ->get(route('entrepreneur.dashboard'))
            ->assertOk()
            ->assertDontSee('data-mode="dashboard"', false);
    }

    /** @return array<string, array{string, string}> */
    public static function commands(): array
    {
        return [
            'registrar exacto' => ['registrar un nuevo emprendimiento', 'entrepreneur.businesses.create'],
            'registrar natural' => ['Quiero registrar un emprendimiento', 'entrepreneur.businesses.create'],
            'nuevo' => ['nuevo emprendimiento', 'entrepreneur.businesses.create'],
            'opcion uno' => ['la uno', 'entrepreneur.businesses.create'],
            'ver emprendimientos' => ['ver mis emprendimientos', 'entrepreneur.businesses.index'],
            'mayusculas' => ['VER MIS EMPRENDIMIENTOS.', 'entrepreneur.businesses.index'],
            'opcion dos' => ['dos', 'entrepreneur.businesses.index'],
            'solicitudes' => ['ver mis solicitudes', 'entrepreneur.requests.index'],
            'solicitud singular' => ['Solicitud', 'entrepreneur.requests.index'],
            'opcion tres' => ['tres', 'entrepreneur.requests.index'],
        ];
    }

    /** @dataProvider commands */
    public function test_recognizes_each_option(string $transcript, string $route): void
    {
        $this->actingAs($this->entrepreneur());

        $result = $this->command($transcript);

        $this->assertSame('navigate', $result['type']);
        $this->assertSame(route($route), $result['redirect']);
        $this->actingAs(User::first())->get($result['redirect'])->assertOk();
    }

    public function test_unknown_command_asks_again_with_the_options(): void
    {
        $this->actingAs($this->entrepreneur());

        $result = $this->command('cuál es el clima');

        $this->assertSame('question', $result['type']);
        $this->assertStringContainsString('No te entendí', $result['speak']);
        $this->assertStringContainsString('ver mis solicitudes', $result['speak']);
    }

    public function test_repeat_replays_the_prompt(): void
    {
        $this->actingAs($this->entrepreneur());

        $this->assertSame(EntrepreneurVoiceMenu::PROMPT, $this->command('repetir')['speak']);
    }

    public function test_command_endpoint_requires_an_entrepreneur(): void
    {
        $this->postJson(route('entrepreneur.voice.command'), ['transcript' => 'dos'])->assertUnauthorized();
    }
}
