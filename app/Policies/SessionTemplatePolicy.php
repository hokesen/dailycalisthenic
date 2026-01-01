<?php

namespace App\Policies;

use App\Models\SessionTemplate;
use App\Models\User;
use App\Policies\Concerns\HasOwnershipChecks;

class SessionTemplatePolicy
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
    public function view(User $user, SessionTemplate $sessionTemplate): bool
    {
        return $sessionTemplate->user_id === null || $sessionTemplate->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can modify the model (used for template mutations).
     * This allows viewing/using system templates but requires ownership for direct modification.
     */
    public function modify(User $user, SessionTemplate $sessionTemplate): bool
    {
        return $sessionTemplate->user_id === null || $sessionTemplate->user_id === $user->id;
    }
}
