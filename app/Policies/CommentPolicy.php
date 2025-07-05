<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
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
    public function view(User $user, Comment $comment)
    {
        return $user->isEditor()
            || $comment->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment)
    {
        return $user->isEditor()
            || $comment->user_id === $user->id;
    }

    public function approve(User $user, Comment $comment)
    {
        // Admin dapat menyetujui kapan saja
        if ($user->isAdmin()) {
            return true;
        }

        // Editor hanya dapat menyetujui ketika status masih pending
        return $user->onlyEditor() && $comment->status === 'pending';  // Status komentar belum disetujui
    }

    public function reject(User $user, Comment $comment)
    {
        // Admin dapat menolak kapan saja
        if ($user->isAdmin()) {
            return true;
        }

        // Editor hanya dapat menolak ketika status masih pending
        return $user->onlyEditor() && $comment->status === 'pending'; // Status komentar belum ditolak
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment)
    {
        return $user->isEditor()
            || $comment->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comment $comment)
    {
        return $user->isAdmin();
    }
}
