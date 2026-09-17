<?php

namespace App\Http\Controllers;

use App\Models\AccessibilityPreference;
use App\Models\Business;
use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\User;
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

        return response()->json($this->assistant->process($request->input('transcript')));
    }

    public function review()
    {
        return response()->json($this->assistant->review());
    }

    public function confirm(Request $request)
    {
        $request->validate(['password' => 'required|string|min:8']);

        $data = $this->assistant->data();

        return DB::transaction(function () use ($data, $request) {
            // Relacionar categoría si coincide
            $category = $this->resolveCategory($data['category'] ?? null);

            $email = strtolower(trim($data['email'] ?? ('usuario_'.mb_substr((string) (($data['first_name'] ?? 'e')), 0, 12).'_'.Str::random(4).'@sonara.test')));

            $name = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) ?: 'Emprendedor';

            $user = User::create([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'name' => $name,
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'password' => $request->input('password'),
            ]);
            $user->assignRole('entrepreneur');

            AccessibilityPreference::create([
                'user_id' => $user->id,
                'navigation_mode' => 'voz',
            ]);

            $profile = EntrepreneurProfile::create([
                'user_id' => $user->id,
                'personal_description' => $data['personal_description'] ?? null,
            ]);

            $businessSlug = Str::slug($data['business_name'] ?? 'emprendimiento-'.$user->id, '-').'-'.Str::random(4);

            $business = Business::create([
                'entrepreneur_profile_id' => $profile->id,
                'name' => $data['business_name'] ?? 'Mi emprendimiento',
                'slug' => $businessSlug,
                'description' => $data['business_description'] ?? null,
                'category_id' => $category?->id,
                'type' => mb_strtolower(trim((string) ($data['type'] ?? 'producto'))) === 'servicio' ? 'servicio' : 'producto',
                'price' => is_numeric(str_replace(',', '.', (string) ($data['price'] ?? 'null'))) ? (float) str_replace(',', '.', (string) $data['price']) : null,
                'region' => $data['region'] ?? 'Cusco',
                'province' => $data['province'] ?? null,
                'district' => $data['district'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? $data['phone'] ?? null,
                'contact_email' => $email,
            ]);

            $this->verification->startVerificationWindow($profile);

            $this->audit->log(
                'voice_registration',
                User::class,
                $user->id,
                'Registro autónomo por voz completado para '.$name,
            );

            $this->assistant->resetSession();

            auth()->login($user);

            return response()->json([
                'success' => true,
                'redirect' => route('entrepreneur.dashboard'),
            ]);
        });
    }

    protected function resolveCategory(?string $name): ?Category
    {
        if (! $name) {
            return Category::query()->where('name', 'like', '%otros%')->first();
        }

        $category = Category::query()
            ->where('name', 'like', '%'.trim($name).'%')
            ->first();

        if ($category) {
            return $category;
        }

        return Category::query()->where('name', 'like', '%otros%')->first();
    }
}