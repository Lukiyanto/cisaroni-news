<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Inertia\Inertia;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Breaking News
        $breakingNews = Article::published()
            ->breaking()
            ->with(['user:id,name', 'category:id,name,slug'])
            ->latest('published_at')
            ->take(3)
            ->get();

        // Featured Articles
        $featuredArticles = Article::published()
            ->featured()
            ->with(['user:id,name', 'category:id,name,slug'])
            ->latest('published_at')
            ->take(6)
            ->get();

        // Latest Articles
        $latestArticles = Article::published()
            ->with(['user:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->latest('published_at')
            ->take(12)
            ->get();

        // Popular articles (most viewed in the last 7 days)
        $popularArticles = Article::published()
            ->with(['user:id,name', 'category:id,name,slug'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get();

        // Categories with article count
        $categories = Category::active()
            ->withCount(['articles' => function ($query) {
                $query->published();
            }])
            ->ordered()
            ->get(['id', 'name', 'slug', 'articles_count']);

        return Inertia::render('Frontend/Home', [
            'breakingNews' => $breakingNews,
            'featuredArticles' => $featuredArticles,
            'latestArticles' => $latestArticles,
            'popularArticles' => $popularArticles,
            'categories' => $categories,
        ]);
    }
}
