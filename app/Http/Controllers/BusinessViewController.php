<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class BusinessViewController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function index()
    {
        $businesses = Business::with(['entrepreneurProfile.user', 'category'])
            ->withCount('publications')
            ->latest()
            ->paginate(15);

        return view('admin.businesses.index', compact('businesses'));
    }

    public function show(Business $business)
    {
        $business->load(['entrepreneurProfile.user', 'category', 'subcategory', 'hours', 'publications']);

        return view('admin.businesses.show', compact('business'));
    }

    public function toggleStatus(Request $request, Business $business)
    {
        $validated = $request->validate(['status' => 'required|in:activo,inactivo']);

        $business->update(['status' => $validated['status']]);

        $this->audit->log('business_status_changed', Business::class, $business->id, 'Emprendimiento "'.$business->name.'" '.($validated['status'] === 'activo' ? 'activado' : 'desactivado').'.');

        return back()->with('success', 'Emprendimiento actualizado.');
    }
}