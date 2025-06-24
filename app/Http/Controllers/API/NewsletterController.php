<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers,email',
            'name' => 'nullabel|string|max:255',
        ]);

        $subscriber = NewsletterSubscriber::create([
            'email' => $validate['email'],
            'name' => $validate['name'],
            'verification_token' => Str::random(32),
        ]);

        // Send verification email
        // Mail::to($subscriber->email)->send(new NewsletterVerification($subscriber));

        return response()->json([
            'message' => 'Successfully subscribed! Please check your email for verification.',
            'status' => 'success'
        ], 201);
    }

    public function verify($token)
    {
        $subscriber = NewsletterSubscriber::where('verification_token', $token)->first();

        if (!$subscriber) {
            return response()->json([
                'message' => 'Invalid or expired token.',
                'status' => 'error'
            ], 404);
        }

        $subscriber->verify();

        return response()->json([
            'message' => 'Email verified successfully.',
            'status' => 'success'
        ]);
    }

    public function unsubscribe($token)
    {
        $subscriber = NewsletterSubscriber::where('verification_token', $token)->first();

        if (!$subscriber) {
            return response()->json([
                'message' => 'Invalid or expired token.',
                'status' => 'error'
            ], 404);
        }

        $subscriber->unsubscribe();

        return response()->json([
            'message' => 'You have been unsubscribed successfully.',
            'status' => 'success'
        ]);
    }
}
