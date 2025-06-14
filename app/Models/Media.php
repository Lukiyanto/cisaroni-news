<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    // The Media model represents media files in the application, such as images, videos, or documents.
    use HasFactory;

    protected $fillable = [
        'filename',
        'origin_name',
        'mime_type',
        'size',
        'type',
        'path',
        'url',
        'alt_text',
        'caption',
        'user_id',
    ];

    // Relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocument($query)
    {
        return $query->where('mime_type', 'not like', 'image/%');
    }

    // Helper Methods
    public function isImage()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getFileSize()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->size;

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
