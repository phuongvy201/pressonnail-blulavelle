<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftCardTransaction;
use App\Services\GiftCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GiftCardController extends Controller
{
    public function index(Request $request)
    {
        $query = GiftCard::query();

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('code', 'like', "%{$q}%")
                    ->orWhere('recipient_email', 'like', "%{$q}%")
                    ->orWhere('purchaser_email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('balance')) {
            if ($request->balance === 'positive') {
                $query->where('balance', '>', 0);
            } elseif ($request->balance === 'zero') {
                $query->where('balance', '<=', 0);
            }
        }

        $giftCards = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.gift-cards.index', compact('giftCards'));
    }

    public function create()
    {
        return view('admin.gift-cards.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'existing_code' => 'nullable|string|max:64',
            'recipient_email' => 'nullable|email|max:255',
            'recipient_name' => 'nullable|string|max:255',
            'purchaser_email' => 'nullable|email|max:255',
            'expires_at' => 'nullable|date|after:today',
            'is_active' => 'nullable|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $amount = round((float) $data['amount'], 2);
        $currency = strtoupper($data['currency']);
        $existingCode = trim((string) ($data['existing_code'] ?? ''));

        if ($existingCode !== '') {
            /** @var GiftCardService $giftCardService */
            $giftCardService = app(GiftCardService::class);
            $existing = $giftCardService->findByCode($existingCode);
            if (!$existing) {
                return redirect()->back()->withInput()->withErrors([
                    'existing_code' => 'No gift card found with this code.',
                ]);
            }
            if ($existing->currency !== $currency) {
                return redirect()->back()->withInput()->withErrors([
                    'existing_code' => 'Currency must match the gift card currency (' . $existing->currency . ').',
                ]);
            }

            DB::transaction(function () use ($existing, $amount, $currency, $data) {
                $giftCard = GiftCard::where('id', $existing->id)->lockForUpdate()->first();
                $before = (float) $giftCard->balance;
                $after = round($before + $amount, 2);
                $giftCard->update([
                    'balance' => $after,
                    'recipient_email' => $data['recipient_email'] ?? $giftCard->recipient_email,
                    'recipient_name' => $data['recipient_name'] ?? $giftCard->recipient_name,
                    'purchaser_email' => $data['purchaser_email'] ?? $giftCard->purchaser_email,
                ]);

                GiftCardTransaction::create([
                    'gift_card_id' => $giftCard->id,
                    'type' => 'topup',
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'currency' => $currency,
                    'meta' => [
                        'source' => 'admin_manual_topup',
                        'note' => $data['note'] ?? null,
                        'admin_id' => Auth::id(),
                    ],
                ]);
            });

            return redirect()->route('admin.gift-cards.show', $existing)->with('success', 'Balance added to existing gift card.');
        }

        $code = $this->generateUniqueCode();

        DB::transaction(function () use ($request, $data, $amount, $code, $currency) {
            $giftCard = GiftCard::create([
                'code' => $code,
                'initial_balance' => $amount,
                'balance' => $amount,
                'currency' => $currency,
                'is_active' => $request->boolean('is_active', true),
                'expires_at' => $data['expires_at'] ?? null,
                'recipient_email' => $data['recipient_email'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'purchaser_email' => $data['purchaser_email'] ?? null,
                'meta' => [
                    'created_by_admin_id' => Auth::id(),
                    'note' => $data['note'] ?? null,
                    'source' => 'admin_manual_create',
                ],
            ]);

            GiftCardTransaction::create([
                'gift_card_id' => $giftCard->id,
                'type' => 'issue',
                'amount' => $amount,
                'balance_before' => 0,
                'balance_after' => $amount,
                'currency' => $giftCard->currency,
                'meta' => [
                    'source' => 'admin_manual_create',
                    'note' => $data['note'] ?? null,
                    'admin_id' => Auth::id(),
                ],
            ]);
        });

        return redirect()->route('admin.gift-cards.index')->with('success', 'Gift card created successfully.');
    }

    public function show(GiftCard $giftCard)
    {
        $giftCard->load(['transactions' => function ($query) {
            $query->with('order')->orderByDesc('created_at');
        }]);

        return view('admin.gift-cards.show', compact('giftCard'));
    }

    public function toggleActive(GiftCard $giftCard)
    {
        DB::transaction(function () use ($giftCard) {
            $locked = GiftCard::whereKey($giftCard->getKey())->lockForUpdate()->firstOrFail();
            $locked->update(['is_active' => !$locked->is_active]);
        });
        $giftCard->refresh();

        return redirect()->route('admin.gift-cards.show', $giftCard)->with(
            'success',
            $giftCard->is_active ? 'Gift card activated.' : 'Gift card deactivated.'
        );
    }

    public function adjustBalance(Request $request, GiftCard $giftCard)
    {
        $data = $request->validate([
            'new_balance' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($giftCard, $data) {
            $locked = GiftCard::whereKey($giftCard->getKey())->lockForUpdate()->firstOrFail();
            $before = (float) $locked->balance;
            $after = round((float) $data['new_balance'], 2);
            $delta = round($after - $before, 2);

            $locked->update(['balance' => $after]);

            GiftCardTransaction::create([
                'gift_card_id' => $locked->id,
                'type' => 'adjustment',
                'amount' => $delta,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency' => $locked->currency,
                'meta' => [
                    'source' => 'admin_adjustment',
                    'reason' => $data['reason'] ?? null,
                    'admin_id' => Auth::id(),
                ],
            ]);
        });

        $giftCard->refresh();

        return redirect()->route('admin.gift-cards.show', $giftCard)->with('success', 'Gift card balance adjusted.');
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'GC-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (GiftCard::whereRaw('UPPER(code) = ?', [strtoupper($code)])->exists());

        return $code;
    }
}
