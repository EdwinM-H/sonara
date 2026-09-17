<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin()
            || $user->id === $target->id
            || ($user->isEntrepreneur() && $target->entrepreneurProfile?->is($user->entrepreneurProfile));
    }

    public function manage(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function suspend(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->isAdmin();
    }
}