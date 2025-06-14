<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Search and filter media files
        $media = Media::query()
            ->with('user:id,name')
            ->when($request->search, function ($query, $search) {
                $query->Where('original_name', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                if ($type === 'image') {
                    $query->where('mime_type', 'like', 'image/%');
                } elseif ($type === 'document') {
                    $query->where('mime_type', 'not like', 'image/%');
                }
            })
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Admin/Media/Index', [
            'media' => $media,
            'filters' => request()->only(['search', 'type']),
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
        // Validate the request
        $validate = $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:10240' // 10MB max
        ]);

        $uploadFiles = [];

        foreach ($request->file('files') as $file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('media', $filename, 'public');

            $media = Media::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'user_id' => auth()->id(),
            ]);

            $uploadFiles[] = $media;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Media $media)
    {
        // 
        return Inertia::render('Admin/Media/Show', [
            'media' => $media->load('user'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Media $media)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Media $media)
    {
        //
        $validated = $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
        ]);

        $media->update($validated);

        return back()->with('success', 'Media updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Media $media)
    {
        // 
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return redirect()->route('admin.media.index')
            ->with('success', 'Media deleted successfully.');
    }
}
