<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleView extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'ip_address',
        'user_agent',
        'user_id',
        'viewed_at'
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public $timestamps = false;

    // Relationships
    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
