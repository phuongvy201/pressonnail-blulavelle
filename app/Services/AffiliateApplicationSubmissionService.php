<?php

namespace App\Services;

use App\Mail\AffiliateApplicationSubmittedToAdmin;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AffiliateApplicationSubmissionService
{
    /**
     * @param  array<string, mixed>  $draft
     */
    public function validateRefCode(string $code): void
    {
        $code = strtolower(trim($code));

        if (Affiliate::query()->whereRaw('LOWER(code) = ?', [$code])->exists()) {
            throw ValidationException::withMessages([
                'proposed_ref_code' => 'This ref code is already used by an affiliate.',
            ]);
        }

        if (AffiliateApplication::query()
            ->where('status', AffiliateApplication::STATUS_PENDING)
            ->whereRaw('LOWER(proposed_ref_code) = ?', [$code])
            ->exists()) {
            throw ValidationException::withMessages([
                'proposed_ref_code' => 'This ref code already has a pending application.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    public function submit(User $user, array $draft): AffiliateApplication
    {
        $code = strtolower(trim((string) ($draft['proposed_ref_code'] ?? '')));
        $this->validateRefCode($code);

        $existingPending = AffiliateApplication::query()
            ->where('user_id', $user->id)
            ->where('status', AffiliateApplication::STATUS_PENDING)
            ->exists();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'email' => 'You already have a pending creator application.',
            ]);
        }

        $application = AffiliateApplication::query()->create([
            'user_id' => $user->id,
            'full_name' => $draft['full_name'],
            'email' => $user->email,
            'phone' => $draft['phone'] ?? null,
            'primary_platform' => $draft['primary_platform'] ?? null,
            'follower_range' => $draft['follower_range'] ?? null,
            'follower_count' => isset($draft['follower_count']) && $draft['follower_count'] !== ''
                ? (int) $draft['follower_count']
                : null,
            'content_niche' => $draft['content_niche'] ?? null,
            'proposed_ref_code' => $code,
            'social_links' => $draft['social_links'] ?? null,
            'portfolio_links' => $draft['portfolio_links'] ?? null,
            'message' => $draft['message'] ?? null,
            'status' => AffiliateApplication::STATUS_PENDING,
        ]);

        $notifyTo = config('affiliate.admin_notification_email');
        if (is_string($notifyTo) && $notifyTo !== '') {
            try {
                Mail::to($notifyTo)->send(new AffiliateApplicationSubmittedToAdmin($application));
            } catch (\Throwable $e) {
                Log::error('Affiliate application admin mail failed', [
                    'application_id' => $application->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $application;
    }
}
