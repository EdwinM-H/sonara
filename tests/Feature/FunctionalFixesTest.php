<?php

namespace Tests\Feature;

use App\Models\AccessibilityPreference;
use App\Models\AIGeneration;
use App\Models\Business;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\EntrepreneurProfile;
use App\Models\Request as CustomerRequest;
use App\Models\Settings;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Notifications\RequestStatusNotification;
use App\Services\AI\ImageGenerationService;
use App\Services\Assistant\VoiceAssistantService;
use App\Services\Verification\VerificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre las correcciones funcionales realizadas tras la auditoría contra
 * el documento de requisitos (RF-01 a RF-29 / RNF-01 a RNF-13): comandos
 * de voz que no deben sobrescribir datos, parámetros de administración
 * que deben afectar realmente al comportamiento del sistema, y datos que
 * antes se perdían silenciosamente en la interfaz.
 */
class FunctionalFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeEntrepreneur(): User
    {
        $user = User::factory()->create(['email' => 'entre@fix.test']);
        $user->assignRole('entrepreneur');
        $profile = EntrepreneurProfile::create(['user_id' => $user->id]);
        app(VerificationService::class)->startVerificationWindow($profile);
        AccessibilityPreference::create(['user_id' => $user->id]);

        return $user;
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create(['email' => 'admin@fix.test']);
        $user->assignRole('admin');

        return $user;
    }

    private function makeCustomer(): User
    {
        $user = User::factory()->create(['email' => 'cust@fix.test']);
        $user->assignRole('customer');
        CustomerProfile::create(['user_id' => $user->id]);

        return $user;
    }

    private function makeBusiness(EntrepreneurProfile $profile): Business
    {
        return Business::create([
            'entrepreneur_profile_id' => $profile->id,
            'name' => 'Negocio de Prueba',
            'slug' => 'negocio-de-prueba-'.uniqid(),
            'type' => 'servicio',
            'availability' => 'disponible',
            'currency' => 'PEN',
            'region' => 'Cusco',
        ]);
    }

    /** El comando de voz "continuar" ya no debe guardarse como respuesta literal del campo actual. */
    public function test_voice_continue_command_does_not_overwrite_field_data(): void
    {
        $service = new VoiceAssistantService('voice-test-'.uniqid());

        $step = $service->start();
        $this->assertSame('welcome', $step['state']);
        $this->assertSame('first_name', $step['field']);

        $step = $service->process('continuar');
        $this->assertSame('question', $step['type']);
        $this->assertSame('welcome', $step['state'], 'Debe permanecer en el mismo paso, no avanzar.');
        $this->assertNotNull($step['message']);

        $step = $service->process('Rosa Mamani');
        $this->assertSame('first_name', $step['state']);
        $this->assertSame(['first_name' => 'Rosa Mamani'], $service->data());
    }

    /** Los comandos "corregir" y "guardar" tampoco deben corromper el dato capturado. */
    public function test_voice_fix_and_save_commands_do_not_corrupt_data(): void
    {
        $service = new VoiceAssistantService('voice-test-'.uniqid());
        $service->start();
        $service->process('corregir');
        $step = $service->process('guardar');

        $this->assertSame('welcome', $step['state']);
        $this->assertArrayNotHasKey('first_name', $service->data());
    }

    /** Cambiar los plazos en Admin > Configuración debe afectar de verdad el cálculo de vencimientos. */
    public function test_admin_settings_actually_change_verification_deadlines(): void
    {
        Settings::set('document_deadline_days', 3);
        Settings::set('validation_deadline_days', 10);

        $service = app(VerificationService::class);
        $this->assertSame(3, $service->documentDeadlineDays());
        $this->assertSame(10, $service->validationDeadlineDays());

        $entrepreneur = $this->makeEntrepreneur();
        $profile = $entrepreneur->entrepreneurProfile->fresh();

        $this->assertEqualsWithDelta(
            now()->addDays(3)->timestamp,
            $profile->document_deadline_at->timestamp,
            5,
            'La ventana de verificación debe usar el plazo configurado (3 días), no el valor por defecto.',
        );
    }

    /** Cambiar el límite de IA en Admin > Configuración debe afectar de verdad cuántas generaciones se permiten. */
    public function test_admin_settings_actually_change_ai_generation_limit(): void
    {
        Settings::set('ai_limit_per_user', 2);

        $entrepreneur = $this->makeEntrepreneur();
        $service = app(ImageGenerationService::class);

        $this->assertSame(2, $service->remainingFor($entrepreneur));

        AIGeneration::create([
            'user_id' => $entrepreneur->id,
            'prompt' => 'prueba',
            'provider' => 'mock',
            'status' => AIGeneration::STATUS_COMPLETADA,
        ]);

        $this->assertSame(1, $service->remainingFor($entrepreneur));
    }

    /** La fecha/hora preferida de una solicitud debe verse en el detalle, no perderse silenciosamente. */
    public function test_request_preferred_at_accessor_combines_date_and_time(): void
    {
        $entrepreneur = $this->makeEntrepreneur();
        $business = $this->makeBusiness($entrepreneur->entrepreneurProfile);

        $request = CustomerRequest::create([
            'business_id' => $business->id,
            'customer_name' => 'Juan Pérez',
            'customer_phone' => '999111222',
            'item_name' => 'Servicio de prueba',
            'quantity' => 1,
            'preferred_date' => '2026-10-01',
            'preferred_time' => '2026-10-01 15:30:00',
            'status' => CustomerRequest::STATUS_ENVIADA,
        ]);

        $this->assertNotNull($request->preferred_at);
        $this->assertSame('01/10/2026 15:30', $request->preferred_at->format('d/m/Y H:i'));
    }

    /** Cuando el admin rechaza a un emprendedor, el motivo debe quedar visible para él, no solo en auditoría. */
    public function test_rejected_entrepreneur_can_see_the_review_note(): void
    {
        $admin = $this->makeAdmin();
        $entrepreneur = $this->makeEntrepreneur();

        $this->actingAs($admin)->post(route('admin.entrepreneurs.reject', $entrepreneur), [
            'reason' => 'La foto del carné está borrosa, por favor sube una imagen más nítida.',
        ])->assertRedirect();

        $entrepreneur->entrepreneurProfile->refresh();
        $this->assertSame('rechazado', $entrepreneur->entrepreneurProfile->verification_status);
        $this->assertSame(
            'La foto del carné está borrosa, por favor sube una imagen más nítida.',
            $entrepreneur->entrepreneurProfile->review_note,
        );

        $this->actingAs($entrepreneur)
            ->get(route('entrepreneur.dashboard'))
            ->assertOk()
            ->assertSee('foto del carné está borrosa');
    }

    /** Un emprendedor jamás debe poder descargar el documento privado de otro (RNF-07/RNF-08). */
    public function test_entrepreneur_cannot_access_another_entrepreneurs_document(): void
    {
        $owner = $this->makeEntrepreneur();
        $intruder = User::factory()->create(['email' => 'intruder@fix.test']);
        $intruder->assignRole('entrepreneur');
        $intruderProfile = EntrepreneurProfile::create(['user_id' => $intruder->id]);
        app(VerificationService::class)->startVerificationWindow($intruderProfile);

        $document = VerificationDocument::create([
            'entrepreneur_profile_id' => $owner->entrepreneurProfile->id,
            'document_type' => VerificationDocument::TYPE_CARNET,
            'original_name' => 'carnet.pdf',
            'stored_name' => 'carnet-privado.pdf',
            'path' => 'documents/private-test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'status' => 'recibido',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($intruder)
            ->get(route('entrepreneur.documents.show', $document))
            ->assertForbidden();
    }

    /** Las notificaciones de estado deben poder llegar también por correo, no solo quedar en la campanita. */
    public function test_request_status_notification_includes_mail_channel_and_actually_persists(): void
    {
        $entrepreneur = $this->makeEntrepreneur();
        $business = $this->makeBusiness($entrepreneur->entrepreneurProfile);
        $customer = $this->makeCustomer();

        $request = CustomerRequest::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => '999000000',
            'item_name' => 'Servicio de prueba',
            'quantity' => 1,
            'status' => CustomerRequest::STATUS_ACEPTADA,
        ]);

        $notification = new RequestStatusNotification($request);
        $this->assertContains('mail', $notification->via($customer));
        $this->assertContains('database', $notification->via($customer));

        // Regresión: la tabla notifications tenía una columna user_id NOT
        // NULL que el canal database nativo de Laravel nunca rellenaba,
        // lo que hacía fallar (y revertía la transacción) cada notificación.
        $customer->notify($notification);
        $this->assertSame(1, $customer->notifications()->count());
    }
}
