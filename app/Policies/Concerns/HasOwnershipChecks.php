<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HasOwnershipChecks
{
    /**
     * Check if user owns the model
     */
    protected function userOwnsModel(User $user, Model $model): bool
    {
        return $model->user_id === $user->id;
    }

    /**
     * Determine if user can update the model
     */
    public function update(User $user, Model $model): bool
    {
        return $this->userOwnsModel($user, $model);
    }

    /**
     * Determine if user can delete the model
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->userOwnsModel($user, $model);
    }

    /**
     * Determine if user can restore the model
     */
    public function restore(User $user, Model $model): bool
    {
        return $this->userOwnsModel($user, $model);
    }

    /**
     * Determine if user can force delete the model
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return $this->userOwnsModel($user, $model);
    }
}
