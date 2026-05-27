<?php

namespace App\Services;

use App\Mail\AffiliateApplicationApprovedToCreator;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AffiliateApplicationReviewService
{
    public function approve(AffiliateApplication $application, User $reviewer): Affiliate
    {
        if ($application->status !== AffiliateApplication::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Application is already reviewed.',
            ]);
        }

        $affiliate = DB::transaction(function () use ($application, $reviewer) {
            $user = $application->user;

            if (! $user && $application->email) {
                $user = User::query()->where('email', $application->email)->first();
            }

            if (! $user) {
                throw ValidationException::withMessages([
                    'user_id' => 'Link a user account before approving (applicant must complete Step 2).',
                ]);
            }

            $code = strtolower($application->proposed_ref_code);

            $existing = Affiliate::query()->where('code', $code)->first();
            if ($existing && (int) $existing->user_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'proposed_ref_code' => 'Ref code is already assigned to another affiliate.',
                ]);
            }

            $affiliate = Affiliate::query()->firstOrCreate(
                ['code' => $code],
                [
                    'user_id' => $user->id,
                    'display_name' => $application->full_name,
                    'tier' => config('affiliate.default_tier', 'basic'),
                    'is_active' => true,
                ]
            );

            $affiliate->fillFromApplication($application);
            $affiliate->fill([
                'user_id' => $user->id,
                'display_name' => $application->full_name,
                'is_active' => true,
            ]);
            $affiliate->save();

            $application->update([
                'status' => AffiliateApplication::STATUS_APPROVED,
                'user_id' => $user->id,
                'processed_at' => now(),
                'reviewed_by' => $reviewer->id,
            ]);

            return $affiliate->fresh();
        });

        $this->notifyCreatorOfApproval($application->fresh(), $affiliate);

        return $affiliate;
    }

    protected function notifyCreatorOfApproval(AffiliateApplication $application, Affiliate $affiliate): void
    {
        $to = $application->email;
        if (! is_string($to) || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $to = $application->user?->email;
        }

        if (! is_string($to) || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Affiliate approval mail skipped: no valid creator email', [
                'application_id' => $application->id,
                'affiliate_id' => $affiliate->id,
            ]);

            return;
        }

        try {
            Mail::to($to)->send(new AffiliateApplicationApprovedToCreator($application, $affiliate));
        } catch (\Throwable $e) {
            Log::error('Affiliate approval creator mail failed', [
                'application_id' => $application->id,
                'affiliate_id' => $affiliate->id,
                'to' => $to,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function reject(AffiliateApplication $application, User $reviewer, ?string $adminNote = null): void
    {
        if ($application->status !== AffiliateApplication::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Application is already reviewed.',
            ]);
        }

        $application->update([
            'status' => AffiliateApplication::STATUS_REJECTED,
            'admin_note' => $adminNote,
            'processed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ]);
    }
}
