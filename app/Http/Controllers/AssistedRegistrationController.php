<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\Publication;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Registro asistido: el administrador crea el usuario emprendedor,
 * su perfil, su emprendimiento y su publicación desde el panel.
 */
class AssistedRegistrationController extends Controller
{
    public function __construct(
        protected VerificationService $verification,
        protected AuditService $audit,
    ) {
    }

    public function index()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.assisted.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:190'],
            'last_name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'personal_description' => ['nullable', 'string', 'max:2000'],
            'business_name' => ['required', 'string', 'max:190'],
            'business_description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'type' => ['required', 'in:producto,servicio'],
            'region' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'phone_business' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'publication_name' => ['required', 'string', 'max:190'],
            'publication_type' => ['required', 'in:producto,servicio'],
            'publication_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = DB::transaction(function () use ($validated) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => trim($validated['first_name'].' '.$validated['last_name']),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
            ]);
            $user->assignRole('entrepreneur');

            $profile = EntrepreneurProfile::create([
                'user_id' => $user->id,
                'personal_description' => $validated['personal_description'] ?? null,
            ]);

            $business = Business::create([
                'entrepreneur_profile_id' => $profile->id,
                'name' => $validated['business_name'],
                'slug' => Str::slug($validated['business_name']).'-'.Str::random(4),
                'description' => $validated['business_description'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'type' => $validated['type'],
                'region' => $validated['region'] ?? 'Cusco',
                'province' => $validated['province'] ?? null,
                'district' => $validated['district'] ?? null,
                'phone' => $validated['phone_business'] ?? $validated['phone'],
                'whatsapp' => $validated['whatsapp'] ?? $validated['phone'],
                'contact_email' => $validated['email'],
            ]);

            $publication = Publication::create([
                'business_id' => $business->id,
                'entrepreneur_profile_id' => $profile->id,
                'name' => $validated['publication_name'],
                'slug' => Str::slug($validated['publication_name']).'-'.Str::random(4),
                'type' => $validated['publication_type'],
                'price' => $validated['publication_price'] ?? null,
                'currency' => 'PEN',
                'status' => Publication::STATUS_BORRADOR,
            ]);

            $this->verification->startVerificationWindow($profile);

            return compact('user', 'business', 'publication');
        });

        $this->audit->log('assisted_registration', User::class, $result['user']->id, 'Registro asistido completado para '.$result['user']->name.' (emprendimiento "'.$result['business']->name.'").');

        return redirect()->route('admin.entrepreneurs.show', $result['user'])
            ->with('success', 'Registro asistido completado. El plazo de documentación ('.$this->verification->documentDeadlineDays().' días) inició hoy.');
    }
}