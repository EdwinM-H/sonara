<?php

namespace App\Http\Controllers;

use App\Models\AccessibilityPreference;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class AccessibilityController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function entrepreneurIndex()
    {
        $user = auth()->user();
        $preferences = $user->getsAccessibilityPreference();

        return view('entrepreneur.accessibility', compact('preferences'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'speech_rate' => ['required', 'in:lenta,normal,rapida'],
            'volume' => ['required', 'in:bajo,normal,alto'],
            'auto_read' => ['nullable', 'boolean'],
            'repeat_prompts' => ['nullable', 'boolean'],
            'high_contrast' => ['nullable', 'boolean'],
            'font_size' => ['required', 'in:pequena,normal,grande'],
            'navigation_mode' => ['required', 'in:visual,voz,mixto'],
        ]);

        $user = auth()->user();

        $user->accessibilityPreference()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'speech_rate' => $validated['speech_rate'],
                'volume' => $validated['volume'],
                'auto_read' => ! empty($request->boolean('auto_read')),
                'repeat_prompts' => ! empty($request->boolean('repeat_prompts')),
                'high_contrast' => ! empty($request->boolean('high_contrast')),
                'font_size' => $validated['font_size'],
                'navigation_mode' => $validated['navigation_mode'],
            ],
        );

        $this->audit->log('accessibility_updated', AccessibilityPreference::class, $user->accessibilityPreference?->id, 'Preferencias de accesibilidad actualizadas.');

        return back()->with('success', 'Tus preferencias de accesibilidad fueron guardadas.');
    }
}