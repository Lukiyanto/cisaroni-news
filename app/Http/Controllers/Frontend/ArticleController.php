<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function show(Article $article)
    {
        if ($article->status !== 'published' || $article->published_at > now()) {
            abort(404, 'Article not found or not published yet.');
        }

        // Increment article views
        $article->incrementViews(
            request()->ip(),
            request()->userAgent(),
            auth()->id
        );

        // Load relationships
        $article->load([
            'user:id,name,avatar,bio',
            'category:id,name,slug',
            'tags:id,name,slug',
            'comments' => function ($query) {
                $query->approve()
                    ->parent()
                    ->with(['user:id,name', 'replies' => function ($subQuery) {
                        $subQuery->approve()
                            ->with('user:id,name');
                    }])
                    ->latest();
            }
        ]);

        // Related articles
        $relatedArticles = Article::published()
            ->where('id', '!=', $article->id)
            ->where('category_id', $article->category_id)
            ->with(['user:id,name', 'category:id,name,slug'])
            ->orderBy('views_count', 'desc')
            ->take(4)
            ->get();

        // Previous and next articles
        $previousArticle = Article::published()
            ->where('published_at', '<', $article->published_at)
            ->orderBy('published_at', 'asc')
            ->first(['id', 'title', 'slug']);
        $nextArticle = Article::published()
            ->where('published_at', '>', $article->published_at)
            ->orderBy('published_at', 'desc')
            ->first(['id', 'title', 'slug']);

        return Inertia::render('Frontend/Articles/Show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'previousArticle' => $previousArticle,
            'nextArticle' => $nextArticle,
        ]);
    }

    public function storeComment(Request $request, Article $article)
    {
        $validate = $request->validate([
            'content' => 'required|string|max:50000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);


        // Create the comment
        $comment = $article->comments()->create([
            'user_id' => auth()->id(),
            'parent_id' => $validate['parent_id'] ?? null,
            'author_name' => auth()->user()->name,
            'author_email' => auth()->user()->email,
            'content' => $validate['content'],
            'status' => 'pending', // Default status for new comments
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Comment submitted successfully and is awaiting approval.');
    }
}
