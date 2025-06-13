<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Search and filter articles
        $articles = Article::query()
            ->with(['user:id,name', 'category:id,name', 'tags:id,name,slug'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->user_id, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when(!auth()->user()->isEditor(), function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Category::active()->get(['id', 'name']);

        return Inertia::render('Admin/Articles/Index', [
            'articles' => $articles,
            'categories' => $categories,
            'filters' => request()->only(['search', 'status', 'category_id', 'user_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Return the view for creating a new article
        $categories = Category::active()->ordered()->get(['id', 'name']);
        $tags = Tag::active()->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Articles/Create', [
            'catagories' => $categories,
            'tags' => $tags
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'slug' => 'nullable|string|max:500|unique:articles',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:draft,published,scheduled,archived',
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'published_at' => 'nullable|date',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string'
        ]);

        $validated['user_id'] = auth()->id();

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('articles', 'public');
        }

        if ($validated['status'] === 'published' && !$validated['published_at']) {
            $validated['published_at'] = now();
        }

        $article = Article::create($validated);

        if ($request->tag_ids) {
            $article->tags()->attach($request->tag_ids);
        }

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article)
    {
        // 
        $this->authorize('view', $article);

        $article->load(['user', 'category', 'tags', 'comments' => function ($query) {
            $query->latest()->take(5);
        }]);

        return Inertia::render('Admin/Articles/Show', [
            'article' => $article
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Article $article)
    {
        //
        $this->authorize('update', $article);

        $article->load('tags:id');
        $categories = Category::active()->ordered->get(['id', 'name']);
        $tags = Tag::active()->get(['id', 'name']);

        return Inertia::render('Admin/Articles/Edit', [
            'article' => $article,
            'categories' => $categories,
            'tags' => $tags
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Article $article)
    {
        // Validate the request data
        $this->authorize('update', $article);

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'slug' => 'nullable|string|max:500|unique:articles,slug,' . $article->id,
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:2048',
            'featured_image_alt' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:draft,published,scheduled,archived',
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'published_at' => 'nullable|date',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string'
        ]);

        if ($request->hasFile('featured_image')) {
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')
                ->store('articles', 'public');
        }

        if ($validated['status'] === 'published' && !$article->published_at && !$validated['published_at']) {
            $validated['published_at'] = now();
        }

        $article->update($validated);

        $article->tags()->sync($request->tag_ids ?? []);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        // Delete the article
        $this->authorize('delete', $article);

        if ($article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
        }

        $article->delete();

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article deleted successfully.');
    }
}
