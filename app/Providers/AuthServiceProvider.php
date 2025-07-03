<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\CategoryPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Article::class => ArticlePolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
