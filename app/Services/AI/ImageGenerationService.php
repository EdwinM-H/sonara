<?php

namespace App\Services\AI;

use App\Models\AIGeneration;
use App\Models\Business;
use App\Models\Publication;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Capa de abstracción para la generación de imágenes con IA.
 *
 * Selecciona el proveedor concreto según configuración (AI_PROVIDER)
 * y encapsula el registro de uso (historial, límites, errores).
 */
class ImageGenerationService
{
    public function __construct(protected array $providers = [])
    {
        if (empty($providers)) {
            $this->providers = [
                'openai' => new OpenAIImageProvider,
                'google' => new GoogleImageProvider,
                'pollinations' => new PollinationsImageProvider,
                'mock' => new MockImageProvider,
            ];
        }
    }

    public function provider(): ImageGenerationProvider
    {
        $configured = strtolower((string) config('services.ai.provider', 'mock'));

        if (! isset($this->providers[$configured])) {
            Log::warning("Proveedor de IA desconocido '{$configured}', usando mock.");
            $configured = 'mock';
        }

        return $this->providers[$configured];
    }

    /**
     * Límite de generaciones por usuario y período de días.
     */
    public function remainingFor(User $user): int
    {
        $limit = (int) Settings::get('ai_limit_per_user', config('services.ai.max_per_user', 20));
        $days = (int) config('services.ai.period_days', 30);
        $used = AIGeneration::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        return max(0, $limit - $used);
    }

    public function canGenerate(User $user): bool
    {
        return $this->remainingFor($user) > 0;
    }

    public function hasExceededLimit(User $user): bool
    {
        return ! $this->canGenerate($user);
    }

    /**
     * Genera un flyer para una publicación, registrando todo el historial.
     */
    public function generateForPublication(Publication $publication, string $style = null): AIGeneration
    {
        $entrepreneur = $publication->business->entrepreneurProfile->user;

        if ($this->hasExceededLimit($entrepreneur)) {
            throw new \DomainException('Has alcanzado el límite de generaciones de imágenes para este período.');
        }

        $generation = AIGeneration::create([
            'user_id' => $entrepreneur->id,
            'business_id' => $publication->business_id,
            'publication_id' => $publication->id,
            'prompt' => (new PromptBuilder)->build($publication),
            'style' => $style,
            'provider' => $this->provider()->name(),
            'status' => AIGeneration::STATUS_PENDIENTE,
        ]);

        $start = hrtime(true);

        try {
            $generation->update(['status' => AIGeneration::STATUS_GENERANDO]);

            $result = $this->provider()->generate($generation->prompt, $style);

            $generation->update([
                'status' => AIGeneration::STATUS_COMPLETADA,
                'image_path' => $result['image_path'],
                'reference' => $result['reference'],
                'cost' => $result['cost'],
                'generation_time_ms' => (int) ((hrtime(true) - $start) / 1_000_000),
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Fallo en la generación de imagen IA', [
                'generation_id' => $generation->id,
                'error' => $e->getMessage(),
            ]);

            $generation->update([
                'status' => AIGeneration::STATUS_ERROR,
                'error' => $e->getMessage(),
                'generation_time_ms' => (int) ((hrtime(true) - $start) / 1_000_000),
            ]);

            throw $e;
        }

        return $generation->fresh();
    }
}