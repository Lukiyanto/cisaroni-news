<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class Article extends Model implements HasMedia
{
    // Use the HasFactory trait for factory support
    use HasFactory, InteractsWithMedia, HasSEO;

    protected $fillabel = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'featured_image_alt',
        'user_id',
        'category_id',
        'status',
        'is_featured',
        'is_breaking',
        'published_at',
        'reading_time',
        'meta_title',
        'meta_description',
        'meta_keywords'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_breaking' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tags');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function views()
    {
        return $this->hasMany(ArticleView::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBreaking($query)
    {
        return $query->where('is_breaking', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByAuthor($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereFullText(['title', 'excerpt', 'content'], $search);
    }

    // Mutators
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        if (!$this->slug) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = $value;
        $this->attributes['reading_time'] = $this->calculateReadingTime($value);
    }

    // Accessors
    public function getFeaturedImageUrlAttribute()
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) :  null;
    }

    public function getExcerptAttribute($value)
    {
        return $value ?: Str::limit(strip_tags($this->content), 150);
    }

    // Helper Methods
    private function calculateReadingTime($content)
    {
        $wordCount = str_word_count(strip_tags($content));
        return ceil($wordCount / 200); // 200 word per minute
    }

    public function incrementViews($ipAddress = null, $userAgent = null, $userId = null)
    {
        // Check if view already exists today for this IP
        $existingView = $this->views()
            ->where('ip_address', $ipAddress)
            ->whereDate('viewed_at', today())
            ->first();

        if (!$existingView) {
            $this->view()->create([
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'user_id' => $userId
            ]);

            $this->increment('views_count');
        }
    }

    public function getDynamicSEOData(): SEOData
    {
        return new SEOData(
            title: $this->title,
            description: $this->excerpt,
        );
    }
}
