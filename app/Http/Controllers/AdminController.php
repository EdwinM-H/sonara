<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRequest;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\EntrepreneurProfile;
use App\Models\Publication;
use App\Models\Request as CustomerRequest;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'entrepreneurs' => User::role('entrepreneur')->count(),
            'customers' => User::role('customer')->count(),
            'businesses' => Business::count(),
            'publications' => Publication::count(),
            'requests' => CustomerRequest::count(),
            'pending_verifications' => \App\Models\EntrepreneurProfile::whereIn('verification_status', ['pendiente_documento', 'documento_enviado'])->count(),
            'pending_publications' => Publication::where('status', 'pendiente')->count(),
            'assistance' => AssistanceRequest::whereIn('status', ['pendiente', 'en_atencion'])->count(),
            'expected_requests' => CustomerRequest::where('status', 'enviada')->count(),
        ];

        $recentUsers = User::latest()->take(8)->get();
        $recentAssistance = AssistanceRequest::with('user')->latest()->take(6)->get();
        $recentPublications = Publication::with('business')->latest()->take(6)->get();
        $businessesByCategory = Business::with('category')
            ->get()
            ->groupBy(fn ($b) => $b->category?->name ?? 'Sin categoría')
            ->map->count()
            ->sortDesc()
            ->take(8);

        $requestsByStatus = CustomerRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        $pendingValidations = EntrepreneurProfile::with('user', 'latestDocument')
            ->whereIn('verification_status', ['pendiente_documento', 'documento_enviado', 'en_revision'])
            ->latest('updated_at')
            ->take(8)
            ->get();

        $weeklySignups = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->startOfDay();

            return [
                'label' => $date->translatedFormat('D'),
                'date' => $date->format('d/m'),
                'total' => User::whereDate('created_at', $date)->count(),
            ];
        });

        $recentAuditLogs = AuditLog::with('user')->latest()->take(8)->get();

        return view('admin.dashboard', compact(
            'stats', 'recentUsers', 'recentAssistance', 'recentPublications', 'businessesByCategory',
            'requestsByStatus', 'pendingValidations', 'weeklySignups', 'recentAuditLogs',
        ));
    }

    public function users()
    {
        $users = User::with('roles')->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:190'],
            'last_name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:admin,entrepreneur,customer'],
        ]);

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        $user->syncRoles($validated['role']);

        $this->audit->log('user_updated', User::class, $user->id, 'Usuario '.$user->name.' actualizado (rol '.$validated['role'].').');

        return redirect()->route('admin.users')->with('success', 'Usuario actualizado.');
    }

    public function suspend(User $user)
    {
        $this->authorize('suspend', $user);

        $user->update(['status' => User::STATUS_SUSPENDIDO]);

        $this->audit->log('user_suspended', User::class, $user->id, 'Usuario '.$user->name.' suspendido.');

        return back()->with('success', 'Usuario suspendido.');
    }

    public function reactivate(User $user)
    {
        $user->update(['status' => User::STATUS_ACTIVO]);

        $this->audit->log('user_reactivated', User::class, $user->id, 'Usuario '.$user->name.' reactivado.');

        return back()->with('success', 'Usuario reactivado.');
    }

    public function requests()
    {
        $requests = CustomerRequest::with(['business', 'publication', 'customer'])->latest()->paginate(15);

        return view('admin.requests.index', compact('requests'));
    }
}