<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Notifications\PublicationStatusNotification;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class PublicationReviewController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function index()
    {
        $publications = Publication::with(['business.entrepreneurProfile.user', 'business.category'])
            ->latest()
            ->paginate(15);

        return view('admin.publications.index', compact('publications'));
    }

    public function show(Publication $publication)
    {
        $publication->load(['business.entrepreneurProfile.user', 'business.category', 'aiGenerations']);

        return view('admin.publications.show', compact('publication'));
    }

    public function changeStatus(Request $request, Publication $publication)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:publicada,rechazada,suspendida'],
            'rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:status,rechazada'],
        ]);

        if (! in_array($validated['status'], Publication::TRANSITIONS[$publication->status] ?? [], true)) {
            return back()->withErrors(['status' => 'Transición de estado no permitida.']);
        }

        $publication->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'published_at' => $validated['status'] === 'publicada' ? ($publication->published_at ?? now()) : $publication->published_at,
        ]);

        $owner = $publication->business?->entrepreneurProfile?->user;
        if ($owner) {
            $owner->notify(new PublicationStatusNotification($publication->fresh(), $validated['status']));
        }

        $this->audit->log(
            'publication_status_changed',
            Publication::class,
            $publication->id,
            'Publicación "'.$publication->name.'" '.$validated['status'].'.',
        );

        return back()->with('success', 'Publicación actualizada.');
    }
}