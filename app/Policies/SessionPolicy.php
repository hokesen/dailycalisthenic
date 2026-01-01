<?php

namespace App\Policies;

use App\Models\Session;
use App\Models\User;
use App\Policies\Concerns\HasOwnershipChecks;

class SessionPolicy
{
    use HasOwnershipChecks;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Session $session): bool
    {
        return $session->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
