<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRequest;
use App\Models\Business;
use App\Models\Publication;
use App\Models\Request as CustomerRequest;
use App\Services\AI\ImageGenerationService;
use App\Services\Verification\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EntrepreneurController extends Controller
{
    public function __construct(protected VerificationService $verification, protected ImageGenerationService $imageGeneration)
    {
    }

    public function dashboard()
    {
        $profile = auth()->user()->entrepreneurProfile;

        $businesses = Business::withCount([
            'publications as published_count' => fn ($q) => $q->where('status', Publication::STATUS_PUBLICADA),
        ])->where('entrepreneur_profile_id', $profile->id)->get();

        $publications = Publication::where('entrepreneur_profile_id', $profile->id)->get();
        $requests = CustomerRequest::whereIn('business_id', $businesses->pluck('id'))->latest()->take(5)->get();
        $assistance = AssistanceRequest::where('user_id', auth()->id())->latest()->take(3)->get();

        $documentDays = $this->verification->documentDaysRemaining($profile);
        $validationDays = $this->verification->validationDaysRemaining($profile);

        $aiRemaining = $this->imageGeneration->remainingFor(auth()->user());
        $aiLimit = (int) \App\Models\Settings::get('ai_limit_per_user', config('services.ai.max_per_user', 20));

        return view('entrepreneur.dashboard', compact(
            'profile', 'businesses', 'publications', 'requests', 'assistance',
            'documentDays', 'validationDays', 'aiRemaining', 'aiLimit',
        ));
    }

    public function profile()
    {
        $user = auth()->user();
        $profile = $user->entrepreneurProfile;

        return view('entrepreneur.profile', compact('user', 'profile'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:190'],
            'last_name' => ['required', 'string', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'personal_description' => ['nullable', 'string', 'max:2000'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'phone' => $validated['phone'],
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->entrepreneurProfile->update(['personal_description' => $validated['personal_description'] ?? null]);

        return back()->with('success', 'Tu perfil fue actualizado.');
    }
}