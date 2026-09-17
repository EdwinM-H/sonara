<?php

namespace App\Policies;

use App\Models\Request as CustomerRequest;
use App\Models\User;

class RequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEntrepreneur() || $user->isCustomer() || $user->isAdmin();
    }

    public function view(User $user, CustomerRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Emprendedor dueño del negocio
        if ($user->isEntrepreneur()) {
            return $request->business?->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
        }

        // Cliente que realizó la solicitud
        return $request->customer_id === $user->id;
    }

    public function respond(User $user, CustomerRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEntrepreneur()
            && $request->business?->entrepreneur_profile_id === $user->entrepreneurProfile?->id;
    }

    public function cancel(User $user, CustomerRequest $request): bool
    {
        return $request->customer_id === $user->id
            && in_array($request->status, ['enviada', 'vista'], true);
    }
}