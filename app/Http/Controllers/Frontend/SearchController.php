<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q');

        if (!$query) {
            return Inertia::render('Frontend/Search/Index', [
                'articles' => null,
                'query' => null,
                'total' => 0
            ]);
        }

        $articles = Article::published()
            ->search($query)
            ->with(['user:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Frontend/Search/Index', [
            'articles' => $articles,
            'query' => $query,
            'total' => $articles->total(),
        ]);
    }
}
