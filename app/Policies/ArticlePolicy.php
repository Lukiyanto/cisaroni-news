<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Article $article)
    {
        return $user->isEditor()
            || $article->user_id === $user->id;
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
    public function update(User $user, Article $article)
    {
        return $user->isEditor()
            || $article->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Article $article)
    {
        return $user->isAdmin()
            || ($user->onlyEditor() && $article->user_id === $user->id) // Editor dapat menghapus artikel miliknya sendiri, jika ingin Editor dapat menghapus artikel orang lain kecuali miliknya gunakan (!==)
            || $article->user_id === $user->id; // Author dapat menghapus artikel miliknya sendiri
    }

    public function deleteAny(User $user, Article $article)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Article $article)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Article $article)
    {
        return $user->isAdmin();
    }
}
