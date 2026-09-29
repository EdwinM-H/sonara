<?php

namespace App\Http\Controllers;

use App\Models\AccessibilityPreference;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use App\Services\Assistant\SpokenDigits;
use App\Services\Assistant\VoiceAssistantService;
use App\Services\Audit\AuditService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoiceRegistrationController extends Controller
{
    public function __construct(
        protected VoiceAssistantService $assistant,
        protected VerificationService $verification,
        protected AuditService $audit,
    ) {
    }

    public function index()
    {
        return view('voice.index', [
            'mode' => 'register',
            'hasSession' => $this->assistant->hasSession(),
        ]);
    }

    public function start()
    {
        return response()->json($this->assistant->start());
    }

    public function resume()
    {
        return response()->json($this->assistant->resume());
    }

    public function process(Request $request)
    {
        $request->validate(['transcript' => 'required|string|max:500']);

        $step = $this->assistant->process($request->input('transcript'));

        if ($step['type'] !== 'complete') {
            return response()->json($step);
        }

        $data = $step['data'];
        $username = VoiceAssistantService::usernameFor($data['nombres'], $data['apellidos']);

        // Doble verificación por si otra persona registró el mismo nombre
        // completo mientras se respondían las demás preguntas.
        if (User::where('username', $username)->exists()) {
            return response()->json($this->assistant->restartAtNames(
                'Ya existe una cuenta con el nombre '.$username.'. Diga sus nombres completos, incluyendo su segundo nombre.'
            ));
        }

        $this->createAccount($data, $username);
        $this->assistant->resetSession();

        return response()->json([
            'type' => 'registered',
            'speak' => 'Perfecto, se creó la cuenta. '
                .'Su usuario es: '.$username.'. '
                .'Su PIN es: '.SpokenDigits::spaceOut($data['pin']).'. '
                .'Ahora le mandaremos a la pantalla de login donde deberá ingresar con las credenciales que se le dieron.',
            'redirect' => route('voice-login.index'),
        ]);
    }

    protected function createAccount(array $data, string $username): User
    {
        return DB::transaction(function () use ($data, $username) {
            $user = User::create([
                'first_name' => $data['nombres'],
                'last_name' => $data['apellidos'],
                'name' => $username,
                'username' => $username,
                // El correo y la contraseña son obligatorios en la tabla de
                // usuarios, pero este registro no los pide: se generan
                // valores internos y el acceso es con usuario + PIN.
                'email' => 'voz.'.Str::lower(Str::random(16)).'@sonara.local',
                'password' => Str::random(40),
                'phone' => $data['telefono_whatsapp'],
            ]);
            $user->voice_pin = $data['pin']; // cast "hashed" → bcrypt
            $user->save();
            $user->assignRole('entrepreneur');

            AccessibilityPreference::create([
                'user_id' => $user->id,
                'navigation_mode' => 'voz',
            ]);

            $profile = EntrepreneurProfile::create([
                'user_id' => $user->id,
                'personal_description' => $data['sobre_mi'],
                'location' => $data['ubicacion'],
            ]);

            $this->verification->startVerificationWindow($profile);

            $this->audit->log(
                'voice_registration',
                User::class,
                $user->id,
                'Registro por voz completado para '.$username,
            );

            return $user;
        });
    }
}
