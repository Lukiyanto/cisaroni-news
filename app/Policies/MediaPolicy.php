<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return $user->isEditor();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Media $media)
    {
        return $user->isEditor()
            || $media->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->allRole();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Media $media)
    {
        return $user->isEditor()
            || $media->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Media $media)
    {
        return $user->isEditor()
            || $media->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Media $media)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Media $media)
    {
        return $user->isAdmin();
    }
}
