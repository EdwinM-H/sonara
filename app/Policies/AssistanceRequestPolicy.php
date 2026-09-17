<?php

namespace App\Policies;

use App\Models\AssistanceRequest;
use App\Models\User;

class AssistanceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEntrepreneur();
    }

    public function view(User $user, AssistanceRequest $assistance): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur() && $assistance->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isEntrepreneur() || $user->isCustomer();
    }

    public function handle(User $user, AssistanceRequest $assistance): bool
    {
        return $user->isAdmin();
    }
}