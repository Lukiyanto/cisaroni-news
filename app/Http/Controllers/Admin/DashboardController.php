<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Models\Comment;
use App\Models\NewsletterSubscriber;
use App\Models\ArticleView;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_articles' => Article::count(),
            'published_articles' => Article::published()->count(),
            'draft_articles' => Article::where('status', 'draft')->count(),
            'total_users' => User::count(),
            'total_comments' => Comment::count(),
            'pending_comments' => Comment::pending()->count(),
            'newsletter_subscribers' => NewsletterSubscriber::active()->count(),
            'total_views' => ArticleView::count(),
        ];

        // Recent Articles
        $recentArticles = Article::with(['user:id,name', 'category:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Popular Articles (last 30 days)
        $popularArticles = Article::with(['user:id,name', 'category:id,name'])
            ->published()
            ->where('create_at', '>=', now()->subDays(30))
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get();

        // Recent Comments
        $recentComments = Comment::with(['user:id,name', 'article:id,title'])
            ->latest()
            ->take(5)
            ->get();

        // View chart data (last 7 days)
        $viewChart = [];
        for ($week = 7; $week >= 0; $week--) {
            $date = Carbon::now()->subDays($week)->format('Y-m-d');
            $viewsCount = ArticleView::whereDate('created_at', $date)->count();
            $viewChart[] = [
                'date' => $date,
                'views' => $viewsCount,
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentArticles' => $recentArticles,
            'popularArticles' => $popularArticles,
            'recentComments' => $recentComments,
            'viewChart' => $viewChart,
        ]);
    }
}
