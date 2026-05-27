<?php

namespace App\Support;

use App\Models\Affiliate;

/**
 * Post-approval creator setup checklist (profile, social, payout).
 */
class AffiliateSetupStatus
{
    public function __construct(
        public readonly bool $profileComplete,
        public readonly bool $socialComplete,
        public readonly bool $payoutComplete,
    ) {}

    public static function for(Affiliate $affiliate): self
    {
        return new self(
            $affiliate->hasCompleteProfile(),
            $affiliate->hasSocialLinks(),
            $affiliate->hasPayoutSetup(),
        );
    }

    public function allComplete(): bool
    {
        return $this->profileComplete && $this->socialComplete && $this->payoutComplete;
    }

    public function canReceivePayout(): bool
    {
        return $this->payoutComplete;
    }

    /**
     * @return list<array{key: string, label: string, done: bool, route: string}>
     */
    public function checklistItems(): array
    {
        return [
            [
                'key' => 'profile',
                'label' => 'Complete profile',
                'done' => $this->profileComplete,
                'route' => 'creator.setup.index',
                'anchor' => 'profile',
            ],
            [
                'key' => 'social',
                'label' => 'Add social links',
                'done' => $this->socialComplete,
                'route' => 'creator.setup.index',
                'anchor' => 'social',
            ],
            [
                'key' => 'payout',
                'label' => 'Complete payout information',
                'done' => $this->payoutComplete,
                'route' => 'creator.setup.index',
                'anchor' => 'payout',
            ],
        ];
    }
}
