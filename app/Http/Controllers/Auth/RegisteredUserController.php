<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccessibilityPreference;
use App\Models\Business;
use App\Models\EntrepreneurProfile;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\Verification\VerificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $categories = \App\Models\Category::where('is_active', true)->orderBy('sort_order')->get();

        return view('auth.register', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:customer,entrepreneur'],
            'first_name' => ['required', 'string', 'max:190'],
            'last_name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'category_id' => ['nullable', 'exists:categories,id', 'required_if:role,entrepreneur'],
            'business_name' => ['nullable', 'string', 'max:190', 'required_if:role,entrepreneur'],
            'business_description' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
        ]);

        $roleName = $validated['role'] === 'entrepreneur' ? 'entrepreneur' : 'customer';
        $user->assignRole($roleName);

        if ($validated['role'] === 'entrepreneur') {
            $profile = EntrepreneurProfile::create([
                'user_id' => $user->id,
            ]);

            app(\App\Services\Verification\VerificationService::class)->startVerificationWindow($profile);

            if (! empty($validated['business_name'])) {
                Business::create([
                    'entrepreneur_profile_id' => $profile->id,
                    'name' => $validated['business_name'],
                    'slug' => Str::slug($validated['business_name']).'-'.Str::random(4),
                    'description' => $validated['business_description'] ?? null,
                    'category_id' => $validated['category_id'] ?? null,
                    'contact_email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'whatsapp' => $validated['phone'],
                ]);
            }
        } else {
            \App\Models\CustomerProfile::create(['user_id' => $user->id]);
        }

        AccessibilityPreference::create([
            'user_id' => $user->id,
            'navigation_mode' => $validated['role'] === 'entrepreneur' ? 'voz' : 'visual',
        ]);

        event(new Registered($user));
        Auth::login($user);

        if ($validated['role'] === 'entrepreneur') {
            return redirect(route('entrepreneur.dashboard', absolute: false));
        }

        return redirect(route('public.home', absolute: false));
    }
}