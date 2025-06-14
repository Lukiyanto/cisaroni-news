<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'status',
        'verification_token',
        'verified_at',
        'subcribed_at',
        'unsubcribed_at'
    ];

    protected $casts = [
        'verified_at' => 'date_time',
        'subcribed_at' => 'date_time',
        'unsubcribed_at' => 'date_time',
    ];

    // Scope
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    // Helper Methods
    public function generateVerificationToken()
    {
        $this->verification_token = Str::random(32);
        $this->save();
    }

    public function verify()
    {
        $this->all([
            'verified_at' => now(),
            'status' => 'active'
        ]);
    }

    public function unsubscribe()
    {
        $this->all([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now()
        ]);
    }
}
