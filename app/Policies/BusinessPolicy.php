<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEntrepreneur();
    }

    public function view(User $user, Business $business): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur()
            && $business->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
    }

    public function create(User $user): bool
    {
        return $user->isEntrepreneur() || $user->isAdmin();
    }

    public function update(User $user, Business $business): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur()
            && $business->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
    }

    public function delete(User $user, Business $business): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur()
            && $business->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
    }
}