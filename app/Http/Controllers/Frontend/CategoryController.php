<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function show(Category $category, Request $request)
    {
        // Validate category status
        if (!$category->is_active) {
            abort(404, 'Category not found or inactive.');
        }

        $articles = Article::published()
            ->byCategory($category->id)
            ->with(['user:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->when($request->sort === 'popular', function ($query) {
                $query->orderBy('views_count', 'desc');
            }, function ($query) {
                $query->latest('published_at');
            })
            ->paginate(12)
            ->withQueryString();

        // Subcategories
        $subcategories = $category->children()
            ->active()
            ->withCount(['articles' => function ($query) {
                $query->published();
            }])
            ->ordered()
            ->get();

        return Inertia::render('Frontend/Category/Show', [
            'category' => $category,
            'articles' => $articles,
            'subcategories' => $subcategories,
            'sort' => $request->sort ?? 'latest',
        ]);
    }
}
