<?php

namespace App\Http\Controllers;

use App\Models\AIGeneration;
use App\Models\Publication;
use App\Services\AI\ImageGenerationService;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIFlyerController extends Controller
{
    public function __construct(
        protected ImageGenerationService $ai,
        protected AuditService $audit,
    ) {
    }

    public function index(Publication $publication)
    {
        $this->authorize('update', $publication);

        $generations = AIGeneration::where('publication_id', $publication->id)
            ->with('user')
            ->latest()
            ->get();

        $remaining = $this->ai->remainingFor(auth()->user());

        return view('entrepreneur.flyers.index', compact('publication', 'generations', 'remaining'));
    }

    public function generate(Request $request, Publication $publication)
    {
        $this->authorize('update', $publication);

        $request->validate([
            'style' => ['nullable', 'string', 'max:100'],
        ]);

        if ($this->ai->hasExceededLimit(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Has alcanzado el límite de generaciones de imágenes para este período.',
            ], 429);
        }

        try {
            $generation = $this->ai->generateForPublication($publication, $request->input('style'));

            $this->audit->log('flyer_generated', AIGeneration::class, $generation->id, 'Flyer generado para "'.$publication->name.'" ('.$generation->provider.')');

            return response()->json([
                'success' => true,
                'message' => 'Flyer generado correctamente.',
                'generation' => [
                    'id' => $generation->id,
                    'image' => asset($generation->image_path),
                    'status' => $generation->status,
                    'provider' => $generation->provider,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 429);
        } catch (\Throwable $e) {
            Log::error('Falla al generar flyer: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al generar el flyer. Inténtalo de nuevo.',
            ], 500);
        }
    }

    public function approve(Publication $publication, AIGeneration $generation)
    {
        $this->authorize('update', $publication);

        if ($generation->publication_id !== $publication->id) {
            abort(404);
        }

        if ($generation->status !== AIGeneration::STATUS_COMPLETADA) {
            return back()->withErrors(['flyer' => 'Solo puedes aprobar flyers generados correctamente.']);
        }

        $publication->update(['flyer_image' => $generation->image_path]);

        $this->audit->log('flyer_approved', AIGeneration::class, $generation->id, 'Flyer aprobado para "'.$publication->name.'"');

        return back()->with('success', 'Flyer aprobado. Ya puedes solicitar la publicación.');
    }

    public function history()
    {
        $generations = AIGeneration::with(['publication', 'business'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('entrepreneur.flyers.history', compact('generations'));
    }
}