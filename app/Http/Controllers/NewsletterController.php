<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use App\Mail\NewsletterWelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    /**
     * Subscribe to newsletter
     */
    public function subscribe(Request $request)
    {
        // Quietly accept honeypot submissions so simple bots do not learn the trap.
        if (trim((string) $request->input('company_website', '')) !== '') {
            return response()->json([
                'success' => true,
                'message' => 'Thank you for subscribing! Please check your email for a welcome message.',
            ]);
        }

        $startedAt = filter_var($request->input('form_started_at'), FILTER_VALIDATE_INT);
        $now = now()->timestamp;
        if (!$startedAt || $startedAt > $now || ($now - $startedAt) < 3 || ($now - $startedAt) > 86400) {
            return response()->json([
                'success' => false,
                'message' => 'Please refresh the page and try again.',
            ], 422);
        }

        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email:rfc|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
                'errors' => $validator->errors()
            ], 422);
        }

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        try {
            // Check if already subscribed
            if (NewsletterSubscription::isSubscribed($email)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already subscribed to our newsletter.',
                ], 409);
            }

            // Subscribe the email
            $subscription = NewsletterSubscription::subscribe($email, $ipAddress, $userAgent);

            // Send welcome email
            Mail::to($email)->send(new NewsletterWelcomeMail($email));

            Log::info('Newsletter subscription successful', [
                'email' => $email,
                'ip_address' => $ipAddress,
                'subscription_id' => $subscription->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for subscribing! Please check your email for a welcome message.',
            ]);
        } catch (\Exception $e) {
            Log::error('Newsletter subscription failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    /**
     * Show unsubscribe page
     */
    public function showUnsubscribe(Request $request, $email)
    {
        return view('newsletter.unsubscribe', compact('email'));
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe(Request $request, $email)
    {
        try {
            $subscription = NewsletterSubscription::where('email', $email)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found or already unsubscribed.',
                ], 404);
            }

            $subscription->unsubscribe();

            Log::info('Newsletter unsubscription successful', [
                'email' => $email,
                'subscription_id' => $subscription->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'You have been successfully unsubscribed from our newsletter.',
            ]);
        } catch (\Exception $e) {
            Log::error('Newsletter unsubscription failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get subscription status
     */
    public function status(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email parameter is required.',
            ], 400);
        }

        $isSubscribed = NewsletterSubscription::isSubscribed($email);

        return response()->json([
            'success' => true,
            'subscribed' => $isSubscribed,
        ]);
    }
}
