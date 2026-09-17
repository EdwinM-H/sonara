<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRequest;
use App\Models\Business;
use App\Models\EntrepreneurProfile;
use App\Models\Publication;
use App\Models\User;
use App\Notifications\VerificationStatusNotification;
use App\Services\Audit\AuditService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class EntrepreneurManagementController extends Controller
{
    public function __construct(
        protected VerificationService $verification,
        protected AuditService $audit,
    ) {
    }

    public function index()
    {
        $entrepreneurs = User::with(['roles', 'entrepreneurProfile', 'businesses'])
            ->role('entrepreneur')
            ->latest()
            ->paginate(15);

        return view('admin.entrepreneurs.index', compact('entrepreneurs'));
    }

    public function create()
    {
        return view('admin.entrepreneurs.create');
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
        ]);

        $user = DB::transaction(function () use ($validated) {
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

            $this->verification->startVerificationWindow($profile);

            return $user;
        });

        $this->audit->log('entrepreneur_created', User::class, $user->id, 'Emprendedor '.$user->name.' creado por el administrador.');

        return redirect()->route('admin.entrepreneurs.index')
            ->with('success', 'Emprendedor creado. Su plazo de documentación inició hoy.');
    }

    public function show(User $user)
    {
        abort_if(! $user->isEntrepreneur(), 404);

        $profile = $user->entrepreneurProfile;
        $businesses = $profile->businesses()->withCount('publications')->get();
        $documents = $profile->documents()->latest('uploaded_at')->get();
        $publications = Publication::where('entrepreneur_profile_id', $profile->id)->latest()->get();
        $assistance = AssistanceRequest::where('user_id', $user->id)->latest()->get();

        $documentDays = $this->verification->documentDaysRemaining($profile);
        $validationDays = $this->verification->validationDaysRemaining($profile);

        return view('admin.entrepreneurs.show', compact('user', 'profile', 'businesses', 'documents', 'publications', 'assistance', 'documentDays', 'validationDays'));
    }

    public function edit(User $user)
    {
        abort_if(! $user->isEntrepreneur(), 404);

        return view('admin.entrepreneurs.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        abort_if(! $user->isEntrepreneur(), 404);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:190'],
            'last_name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'personal_description' => ['nullable', 'string', 'max:2000'],
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        $user->entrepreneurProfile->update([
            'personal_description' => $validated['personal_description'] ?? null,
        ]);

        $this->audit->log('entrepreneur_updated', User::class, $user->id, 'Emprendedor '.$user->name.' actualizado.');

        return redirect()->route('admin.entrepreneurs.index')->with('success', 'Emprendedor actualizado.');
    }

    public function approve(User $user, Request $request)
    {
        abort_if(! $user->isEntrepreneur(), 404);

        $profile = $user->entrepreneurProfile;
        $this->verification->approve($profile);

        $user->notify(new VerificationStatusNotification($profile->fresh(), 'approved'));

        $this->audit->log('entrepreneur_approved', User::class, $user->id, 'Administrador aprobó al emprendedor '.$user->name.'.');

        return back()->with('success', 'Emprendedor aprobado y verificado.');
    }

    public function reject(Request $request, User $user)
    {
        abort_if(! $user->isEntrepreneur(), 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $profile = $user->entrepreneurProfile;
        $this->verification->reject($profile, $validated['reason']);

        $user->notify(new VerificationStatusNotification($profile->fresh(), 'rejected'));

        $this->audit->log('entrepreneur_rejected', User::class, $user->id, 'Administrador rechazó al emprendedor '.$user->name.' (motivo: '.$validated['reason'].').');

        return back()->with('success', 'Emprendedor rechazado. El motivo fue registrado.');
    }

    public function requestCorrection(Request $request, User $user)
    {
        abort_if(! $user->isEntrepreneur(), 404);

        $validated = $request->validate([
            'correction_note' => ['required', 'string', 'max:2000'],
        ]);

        $profile = $user->entrepreneurProfile;
        $this->verification->requestCorrection($profile, $validated['correction_note']);

        $user->notify(new VerificationStatusNotification($profile->fresh(), 'document_pending'));
        // Registrar la nota de corrección en auditoría
        $this->audit->log('entrepreneur_correction_requested', User::class, $user->id, 'Corrección solicitada a '.$user->name.': '.$validated['correction_note'].'.');

        return back()->with('success', 'Corrección solicitada al emprendedor.');
    }
}