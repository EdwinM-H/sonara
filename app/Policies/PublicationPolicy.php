<?php

namespace App\Policies;

use App\Models\Publication;
use App\Models\User;

class PublicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEntrepreneur();
    }

    public function view(User $user, Publication $publication): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur()
            && $publication->business?->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
    }

    public function create(User $user, $business = null): bool
    {
        if (! $user->isEntrepreneur() && ! $user->isAdmin()) {
            return false;
        }

        if ($business && $user->isEntrepreneur()) {
            return $business->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
        }

        return true;
    }

    public function update(User $user, Publication $publication): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur()
            && $publication->business?->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
    }

    public function delete(User $user, Publication $publication): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur()
            && $publication->business?->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
    }
}