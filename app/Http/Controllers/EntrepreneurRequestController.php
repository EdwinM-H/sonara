<?php

namespace App\Http\Controllers;

use App\Models\Request as CustomerRequest;
use App\Notifications\RequestStatusNotification;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class EntrepreneurRequestController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function index()
    {
        $businessIds = auth()->user()->entrepreneurProfile->businesses()->pluck('id');

        $requests = CustomerRequest::with(['business', 'publication'])
            ->whereIn('business_id', $businessIds)
            ->orderByRaw("case status when 'enviada' then 0 when 'vista' then 1 else 2 end")
            ->latest()
            ->paginate(15);

        // Marcar como vistas las nuevas
        CustomerRequest::whereIn('business_id', $businessIds)
            ->where('status', CustomerRequest::STATUS_ENVIADA)
            ->whereNull('seen_at')
            ->update(['status' => CustomerRequest::STATUS_VISTA, 'seen_at' => now()]);

        return view('entrepreneur.requests.index', compact('requests'));
    }

    public function show(CustomerRequest $request)
    {
        $this->authorize('view', $request);

        return view('entrepreneur.requests.show', compact('request'));
    }

    public function accept(CustomerRequest $request)
    {
        $this->authorize('respond', $request);

        if (! $request->canTransitionTo(CustomerRequest::STATUS_ACEPTADA)) {
            return back()->withErrors(['estado' => 'La solicitud no puede aceptarse desde su estado actual.']);
        }

        $request->update([
            'status' => CustomerRequest::STATUS_ACEPTADA,
            'responded_at' => now(),
        ]);

        if ($request->customer_id) {
            $request->customer?->notify(new RequestStatusNotification($request->fresh()));
        }

        $this->audit->log('request_accepted', CustomerRequest::class, $request->id, 'Solicitud '.$request->code.' aceptada.');

        return back()->with('success', 'Solicitud aceptada.');
    }

    public function reject(CustomerRequest $request, Request $incoming)
    {
        $this->authorize('respond', $request);

        if (! $request->canTransitionTo(CustomerRequest::STATUS_RECHAZADA)) {
            return back()->withErrors(['estado' => 'La solicitud no puede rechazarse desde su estado actual.']);
        }

        $validated = $incoming->validate(['response_note' => ['nullable', 'string', 'max:2000']]);

        $request->update([
            'status' => CustomerRequest::STATUS_RECHAZADA,
            'response_note' => $validated['response_note'] ?? 'Solicitud rechazada por el emprendedor.',
            'responded_at' => now(),
        ]);

        if ($request->customer_id) {
            $request->customer?->notify(new RequestStatusNotification($request->fresh()));
        }

        $this->audit->log('request_rejected', CustomerRequest::class, $request->id, 'Solicitud '.$request->code.' rechazada.');

        return back()->with('success', 'Solicitud rechazada.');
    }

    public function complete(CustomerRequest $request)
    {
        $this->authorize('respond', $request);

        if (! $request->canTransitionTo(CustomerRequest::STATUS_COMPLETADA)) {
            return back()->withErrors(['estado' => 'La solicitud no puede completarse desde su estado actual.']);
        }

        $request->update([
            'status' => CustomerRequest::STATUS_COMPLETADA,
            'responded_at' => now(),
        ]);

        if ($request->customer_id) {
            $request->customer?->notify(new RequestStatusNotification($request->fresh()));
        }

        $this->audit->log('request_completed', CustomerRequest::class, $request->id, 'Solicitud '.$request->code.' completada.');

        return back()->with('success', 'Solicitud completada.');
    }
}