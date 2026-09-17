<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationDocument;

class VerificationDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEntrepreneur();
    }

    public function view(User $user, VerificationDocument $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur()
            && $document->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
    }

    public function create(User $user): bool
    {
        return $user->isEntrepreneur();
    }
}