<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Return the view for listing comments
        $comments = Comment::query()
            ->with(['article:id,title,slug', 'user:id,name'])
            ->when($request->search, function ($query, $search) {
                $query->where('content', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->article_id, function ($query, $articleId) {
                $query->where('article_id', $articleId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Comments/Index', [
            'comments' => $comments,
            'filters' => $request->only(['search', 'status', 'article_id'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        // Return the view for showing a single comment
        $comment->load(['article', 'user', 'parent', 'replies']);

        return Inertia::render('Admin/Comments/Show', [
            'comment' => $comment
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment)
    {
        // Validate the request data
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,spam'
        ]);

        $comment->update($validated);

        return back()->with('success', 'Comment status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment)
    {
        // Delete the comment
        $comment->delete();

        return redirect()->route('admin.comments.index')
            ->with('success', 'Comment deleted successfully.');
    }
}
