<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\Request as CustomerRequest;
use App\Notifications\RequestStatusNotification;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerRequestController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function store(Request $request, AuditService $audit)
    {
        $validated = $request->validate([
            'publication_id' => ['required', 'exists:publications,id'],
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $publication = Publication::with('business')->findOrFail($validated['publication_id']);

        $requestRecord = DB::transaction(function () use ($validated, $publication, $request) {
            $record = CustomerRequest::create([
                'customer_id' => auth()->check() ? auth()->id() : null,
                'business_id' => $publication->business_id,
                'publication_id' => $publication->id,
                'customer_name' => $validated['name'],
                'customer_phone' => $validated['phone'],
                'item_name' => $publication->name,
                'quantity' => $validated['quantity'],
                'preferred_date' => $validated['preferred_date'] ?? null,
                'preferred_time' => isset($validated['preferred_time'])
                    ? $validated['preferred_date'].' '.$validated['preferred_time'].':00'
                    : null,
                'message' => $validated['message'] ?? null,
                'status' => CustomerRequest::STATUS_ENVIADA,
            ]);

            $owner = $publication->business->entrepreneurProfile?->user;
            if ($owner) {
                $owner->notify(new \App\Notifications\NewCustomerRequestNotification($record));
            }

            $this->audit->log('request_created', CustomerRequest::class, $record->id, 'Nueva solicitud '.$record->code);

            return $record;
        });

        return redirect()
            ->route('public.business', $publication->business->slug)
            ->with('success', 'Tu solicitud '.$requestRecord->code.' fue enviada correctamente. El emprendedor la revisará próximamente.');
    }

    public function index()
    {
        $requests = CustomerRequest::with(['business', 'publication'])
            ->where('customer_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('customer.requests.index', compact('requests'));
    }

    public function show(CustomerRequest $request)
    {
        $this->authorize('view', $request);

        return view('customer.requests.show', compact('request'));
    }

    public function cancel(CustomerRequest $request)
    {
        $this->authorize('cancel', $request);

        $request->update([
            'status' => CustomerRequest::STATUS_CANCELADA,
            'response_note' => 'Solicitud cancelada por el cliente.',
        ]);

        $this->audit->log('request_cancelled', CustomerRequest::class, $request->id, 'Cliente canceló la solicitud '.$request->code);

        return back()->with('success', 'La solicitud fue cancelada.');
    }
}