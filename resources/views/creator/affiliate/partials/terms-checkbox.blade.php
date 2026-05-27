<div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
    <label class="flex cursor-pointer items-start gap-3">
        <input type="checkbox" name="accepted_program_terms" value="1" required
               @checked(old('accepted_program_terms', $draft['accepted_program_terms'] ?? false))
               class="mt-1 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary">
        <span class="text-sm text-slate-700">
            I agree to the
            <a href="{{ route('creator.policies.show', 'affiliate-program-terms') }}" target="_blank" rel="noopener" class="font-semibold text-primary underline">Affiliate Program Terms</a>,
            <a href="{{ route('creator.policies.show', 'affiliate-commission-payout-policy') }}" target="_blank" rel="noopener" class="font-semibold text-primary underline">Commission &amp; Payout Policy</a>,
            <a href="{{ route('creator.policies.show', 'affiliate-attribution-cookie-policy') }}" target="_blank" rel="noopener" class="font-semibold text-primary underline">Attribution &amp; Cookie Policy</a>,
            and
            <a href="{{ route('creator.policies.show', 'affiliate-privacy-policy') }}" target="_blank" rel="noopener" class="font-semibold text-primary underline">Affiliate Privacy Policy</a>.
        </span>
    </label>
    @error('accepted_program_terms')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
