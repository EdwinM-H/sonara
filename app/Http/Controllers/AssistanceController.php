<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRequest;
use App\Notifications\AssistanceRequestNotification;
use App\Notifications\AssistanceStatusNotification;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class AssistanceController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    // ------------------------------------------------------------------
    // Emprendedor
    // ------------------------------------------------------------------
    public function index()
    {
        $assistanceRequests = AssistanceRequest::where('user_id', auth()->id())->latest()->paginate(10);

        return view('entrepreneur.assistance.index', compact('assistanceRequests'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:3000'],
            'preferred_channel' => ['required', 'in:sistema,telefono,whatsapp,voz'],
        ]);

        $assistance = AssistanceRequest::create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'preferred_channel' => $validated['preferred_channel'],
            'status' => AssistanceRequest::STATUS_PENDIENTE,
        ]);

        // Notificar a todos los administradores
        $admins = \App\Models\User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AssistanceRequestNotification($assistance));
        }

        $this->audit->log('assistance_requested', AssistanceRequest::class, $assistance->id, 'Solicitud de asistencia creada.');

        return back()->with('success', 'Tu solicitud de asistencia fue registrada. El administrador te contactará.');
    }

    // ------------------------------------------------------------------
    // Administrador
    // ------------------------------------------------------------------
    public function adminIndex()
    {
        $assistanceRequests = AssistanceRequest::with('user')->latest()->paginate(15);

        return view('admin.assistance.index', compact('assistanceRequests'));
    }

    public function adminShow(AssistanceRequest $assistance)
    {
        $this->authorize('handle', $assistance);

        return view('admin.assistance.show', compact('assistance'));
    }

    public function adminUpdate(Request $request, AssistanceRequest $assistance)
    {
        $this->authorize('handle', $assistance);

        $validated = $request->validate([
            'status' => ['required', 'in:en_atencion,atendida,cerrada'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (! $assistance->canTransitionTo($validated['status'])) {
            return back()->withErrors(['status' => 'Transición de estado no permitida.']);
        }

        $assistance->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $assistance->admin_notes,
            'handled_by' => auth()->id(),
            'handled_at' => in_array($validated['status'], ['atendida', 'cerrada'], true) ? now() : null,
        ]);

        $assistance->user?->notify(new AssistanceStatusNotification($assistance->fresh()));

        $this->audit->log('assistance_updated', AssistanceRequest::class, $assistance->id, 'Solicitud de asistencia actualizada a '.$validated['status']);

        return back()->with('success', 'Solicitud de asistencia actualizada.');
    }
}