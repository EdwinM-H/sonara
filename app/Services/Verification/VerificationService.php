<?php

namespace App\Services\Verification;

use App\Models\EntrepreneurProfile;
use App\Models\Settings;
use App\Models\VerificationDocument;
use Illuminate\Support\Facades\DB;

/**
 * Control de plazos y transiciones de estado del proceso de validación.
 *
 * Primera etapa: registro → N días (configurable en Admin > Configuración,
 * `document_deadline_days`) → carga de documentación.
 * Segunda etapa: documento cargado → N días (`validation_deadline_days`)
 * → validación administrativa.
 * Toda la lógica se calcula en el backend, no en JavaScript.
 */
class VerificationService
{
    public const DOCUMENT_DEADLINE_DAYS = 7;
    public const VALIDATION_DEADLINE_DAYS = 7;

    public function documentDeadlineDays(): int
    {
        return (int) Settings::get('document_deadline_days', self::DOCUMENT_DEADLINE_DAYS);
    }

    public function validationDeadlineDays(): int
    {
        return (int) Settings::get('validation_deadline_days', self::VALIDATION_DEADLINE_DAYS);
    }

    /**
     * Inicializa el proceso de validación al completar el registro
     * autónomo (login + perfil + empresas + publicación).
     */
    public function startVerificationWindow(EntrepreneurProfile $profile): EntrepreneurProfile
    {
        $profile->update([
            'verification_status' => EntrepreneurProfile::VERIF_PENDIENTE_DOCUMENTO,
            'registered_fully_at' => now(),
            'document_deadline_at' => now()->addDays($this->documentDeadlineDays()),
            'validation_deadline_at' => null,
        ]);

        return $profile->fresh();
    }

    public function registerDocument(EntrepreneurProfile $profile, VerificationDocument $document): EntrepreneurProfile
    {
        $profile->update([
            'verification_status' => EntrepreneurProfile::VERIF_DOCUMENTO_ENVIADO,
            'validation_deadline_at' => now()->addDays($this->validationDeadlineDays()),
        ]);

        return $profile->fresh();
    }

    public function moveToReview(EntrepreneurProfile $profile): EntrepreneurProfile
    {
        if ($profile->verification_status === EntrepreneurProfile::VERIF_EN_REVISION) {
            return $profile;
        }

        $profile->update([
            'verification_status' => EntrepreneurProfile::VERIF_EN_REVISION,
            'validation_deadline_at' => $profile->validation_deadline_at ?? now()->addDays($this->validationDeadlineDays()),
        ]);

        return $profile->fresh();
    }

    public function approve(EntrepreneurProfile $profile): EntrepreneurProfile
    {
        $profile->update([
            'verification_status' => EntrepreneurProfile::VERIF_APROBADO,
            'verified_at' => now(),
            'document_deadline_at' => null,
            'validation_deadline_at' => null,
            'review_note' => null,
        ]);

        return $profile->fresh();
    }

    public function reject(EntrepreneurProfile $profile, string $reason): EntrepreneurProfile
    {
        $profile->update([
            'verification_status' => EntrepreneurProfile::VERIF_RECHAZADO,
            'review_note' => $reason,
        ]);

        return $profile->fresh();
    }

    public function requestCorrection(EntrepreneurProfile $profile, ?string $note = null): EntrepreneurProfile
    {
        $profile->update([
            'verification_status' => EntrepreneurProfile::VERIF_PENDIENTE_DOCUMENTO,
            'review_note' => $note,
            'document_deadline_at' => now()->addDays($this->documentDeadlineDays()),
        ]);

        return $profile->fresh();
    }

    /**
     * Días restantes para la carga de documentación.
     */
    public function documentDaysRemaining(EntrepreneurProfile $profile): ?int
    {
        return $profile->daysRemainingForDocument();
    }

    /**
     * Días restantes para la validación administrativa.
     */
    public function validationDaysRemaining(EntrepreneurProfile $profile): ?int
    {
        return $profile->daysRemainingForValidation();
    }

    public function isExpired(EntrepreneurProfile $profile): bool
    {
        if ($profile->verification_status === EntrepreneurProfile::VERIF_PENDIENTE_DOCUMENTO) {
            return $profile->document_deadline_at && $profile->document_deadline_at->isPast();
        }

        if (in_array($profile->verification_status, [
            EntrepreneurProfile::VERIF_DOCUMENTO_ENVIADO,
            EntrepreneurProfile::VERIF_EN_REVISION,
        ])) {
            return $profile->validation_deadline_at && $profile->validation_deadline_at->isPast();
        }

        return false;
    }

    public function nextRequiredAction(EntrepreneurProfile $profile): ?string
    {
        return match ($profile->verification_status) {
            EntrepreneurProfile::VERIF_PENDIENTE_DOCUMENTO => 'document',
            EntrepreneurProfile::VERIF_DOCUMENTO_ENVIADO, EntrepreneurProfile::VERIF_EN_REVISION => 'admin_review',
            EntrepreneurProfile::VERIF_RECHAZADO => 'rejected',
            default => null,
        };
    }
}