<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Publication;
use App\Notifications\PublicationStatusNotification;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicationController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function index()
    {
        $profile = auth()->user()->entrepreneurProfile;

        $publications = Publication::with('business')
            ->where('entrepreneur_profile_id', $profile->id)
            ->orderByDesc('created_at')
            ->get();

        return view('entrepreneur.publications.index', compact('publications'));
    }

    public function create()
    {
        $businesses = Business::where('entrepreneur_profile_id', auth()->user()->entrepreneurProfile->id)->get();

        if ($businesses->isEmpty()) {
            return redirect()->route('entrepreneur.businesses.create')
                ->with('info', 'Primero crea un emprendimiento para poder publicar productos o servicios.');
        }

        return view('entrepreneur.publications.create', compact('businesses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'in:producto,servicio'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'currency' => ['nullable', 'string', 'max:3'],
        ]);

        $business = Business::findOrFail($validated['business_id']);
        $this->authorize('create', [Publication::class, $business]);

        $publication = Publication::create([
            'business_id' => $business->id,
            'entrepreneur_profile_id' => $business->entrepreneur_profile_id,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'price' => $validated['price'] ?? null,
            'price_min' => $validated['price_min'] ?? null,
            'price_max' => $validated['price_max'] ?? null,
            'currency' => $validated['currency'] ?? 'PEN',
            'status' => Publication::STATUS_BORRADOR,
        ]);

        $this->audit->log('publication_created', Publication::class, $publication->id, 'Publicación "'.$publication->name.'" creada.');

        return redirect()->route('entrepreneur.publications.edit', $publication)
            ->with('success', 'Publicación creada. Ahora puedes generar su flyer y solicitar su publicación.');
    }

    public function edit(Publication $publication)
    {
        $this->authorize('update', $publication);

        $businesses = Business::where('entrepreneur_profile_id', auth()->user()->entrepreneurProfile->id)->get();

        return view('entrepreneur.publications.edit', compact('publication', 'businesses'));
    }

    public function update(Request $request, Publication $publication)
    {
        $this->authorize('update', $publication);

        $validated = $request->validate([
            'business_id' => ['required', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'in:producto,servicio'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'currency' => ['nullable', 'string', 'max:3'],
        ]);

        $publication->update([
            'business_id' => $validated['business_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'price' => $validated['price'] ?? null,
            'price_min' => $validated['price_min'] ?? null,
            'price_max' => $validated['price_max'] ?? null,
            'currency' => $validated['currency'] ?? 'PEN',
        ]);

        $this->audit->log('publication_updated', Publication::class, $publication->id, 'Publicación "'.$publication->name.'" actualizada.');

        return redirect()->route('entrepreneur.publications.edit', $publication)
            ->with('success', 'Publicación actualizada.');
    }

    public function requestApproval(Publication $publication)
    {
        $this->authorize('update', $publication);

        if (! $publication->flyer_image && $publication->images()->doesntExist()) {
            return back()->withErrors(['flyer' => 'Genera un flyer antes de solicitar la publicación.']);
        }

        if (! $publication->canTransitionTo(Publication::STATUS_PENDIENTE)) {
            return back()->withErrors(['estado' => 'No se puede solicitar aprobación desde el estado actual.']);
        }

        $publication->update(['status' => Publication::STATUS_PENDIENTE]);

        $this->audit->log('publication_submitted_admin', Publication::class, $publication->id, 'Publicación "'.$publication->name.'" enviada para revisión.');

        return back()->with('success', 'Tu publicación fue enviada para revisión del administrador.');
    }

    public function destroy(Publication $publication)
    {
        $this->authorize('delete', $publication);

        $name = $publication->name;
        $publication->delete();

        $this->audit->log('publication_deleted', Publication::class, $publication->id, 'Publicación "'.$name.'" eliminada.');

        return redirect()->route('entrepreneur.publications.index')
            ->with('success', 'Publicación eliminada.');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Publication::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}