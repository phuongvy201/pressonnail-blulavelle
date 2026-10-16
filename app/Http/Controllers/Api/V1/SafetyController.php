<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Review;
use App\Models\UgcBlock;
use App\Models\UgcReport;
use App\Support\ApiV1\ApiResponse;
use App\Support\ContentSafety;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SafetyController extends Controller
{
    public function report(Request $request): JsonResponse
    {
        if (! Schema::hasTable('ugc_reports')) {
            return ApiResponse::error('SAFETY_UNAVAILABLE', 'Safety tools are not ready on this server yet.', 503);
        }

        $validated = $request->validate([
            'targetType' => ['required', 'in:chat_message,review'],
            'targetId' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'in:'.implode(',', ContentSafety::reportReasons())],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $targetType = $validated['targetType'];
        $targetId = (int) $validated['targetId'];

        if ($targetType === 'chat_message' && ! ChatMessage::query()->whereKey($targetId)->exists()) {
            return ApiResponse::error('NOT_FOUND', 'That message could not be found.', 404);
        }

        if ($targetType === 'review' && ! Review::query()->whereKey($targetId)->exists()) {
            return ApiResponse::error('NOT_FOUND', 'That review could not be found.', 404);
        }

        $identity = $this->identity($request);
        if ($identity === null) {
            return ApiResponse::error('SAFETY_IDENTITY', 'Sign in or open chat before reporting.', 422);
        }

        $report = UgcReport::query()->create([
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'reporter_user_id' => $identity['userId'],
            'reporter_guest_token' => $identity['guestToken'],
            'status' => 'open',
        ]);

        Log::warning('UGC report filed', [
            'report_id' => $report->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $validated['reason'],
        ]);

        return ApiResponse::success([
            'reported' => true,
            'reportId' => $report->id,
            'message' => 'Thanks. BluLavelle reviews reports within 24 hours.',
        ], null, 201);
    }

    public function block(Request $request): JsonResponse
    {
        if (! Schema::hasTable('ugc_blocks')) {
            return ApiResponse::error('SAFETY_UNAVAILABLE', 'Safety tools are not ready on this server yet.', 503);
        }

        $validated = $request->validate([
            'scope' => ['required', 'in:chat_conversation'],
            'targetId' => ['required', 'integer', 'min:1'],
        ]);

        $conversation = ChatConversation::query()->find($validated['targetId']);
        if (! $conversation) {
            return ApiResponse::error('NOT_FOUND', 'That conversation could not be found.', 404);
        }

        if (! $this->ownsConversation($request, $conversation)) {
            return ApiResponse::error('FORBIDDEN', 'You can only block your own chat.', 403);
        }

        $identity = $this->identity($request);
        if ($identity === null) {
            return ApiResponse::error('SAFETY_IDENTITY', 'Sign in or open chat before blocking.', 422);
        }

        UgcBlock::query()->updateOrCreate(
            [
                'scope' => 'chat_conversation',
                'target_id' => $conversation->id,
                'blocker_key' => $identity['blockerKey'],
            ],
            [
                'blocker_user_id' => $identity['userId'],
                'blocker_guest_token' => $identity['guestToken'],
            ]
        );

        return ApiResponse::success([
            'blocked' => true,
            'scope' => 'chat_conversation',
            'targetId' => $conversation->id,
        ]);
    }

    public function unblock(Request $request): JsonResponse
    {
        if (! Schema::hasTable('ugc_blocks')) {
            return ApiResponse::error('SAFETY_UNAVAILABLE', 'Safety tools are not ready on this server yet.', 503);
        }

        $validated = $request->validate([
            'scope' => ['required', 'in:chat_conversation'],
            'targetId' => ['required', 'integer', 'min:1'],
        ]);

        $identity = $this->identity($request);
        if ($identity === null) {
            return ApiResponse::error('SAFETY_IDENTITY', 'Sign in or open chat before unblocking.', 422);
        }

        UgcBlock::query()
            ->where('scope', $validated['scope'])
            ->where('target_id', (int) $validated['targetId'])
            ->where('blocker_key', $identity['blockerKey'])
            ->delete();

        return ApiResponse::success([
            'blocked' => false,
            'scope' => $validated['scope'],
            'targetId' => (int) $validated['targetId'],
        ]);
    }

    public function blocks(Request $request): JsonResponse
    {
        if (! Schema::hasTable('ugc_blocks')) {
            return ApiResponse::success(['conversationIds' => []]);
        }

        $identity = $this->identity($request);
        if ($identity === null) {
            return ApiResponse::success(['conversationIds' => []]);
        }

        $ids = UgcBlock::query()
            ->where('scope', 'chat_conversation')
            ->where('blocker_key', $identity['blockerKey'])
            ->pluck('target_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return ApiResponse::success(['conversationIds' => $ids]);
    }

    /**
     * @return array{userId: int|null, guestToken: string|null, blockerKey: string}|null
     */
    private function identity(Request $request): ?array
    {
        $user = $request->user();
        if ($user) {
            return [
                'userId' => $user->id,
                'guestToken' => null,
                'blockerKey' => 'user:'.$user->id,
            ];
        }

        $token = $request->header('X-Live-Chat-Guest');
        if (is_string($token) && preg_match('/^[A-Za-z0-9_-]{32,64}$/', $token) === 1) {
            return [
                'userId' => null,
                'guestToken' => $token,
                'blockerKey' => 'guest:'.$token,
            ];
        }

        return null;
    }

    private function ownsConversation(Request $request, ChatConversation $conversation): bool
    {
        $user = $request->user();
        if ($user) {
            return (int) $conversation->customer_user_id === (int) $user->id;
        }

        $token = $request->header('X-Live-Chat-Guest');

        return is_string($token)
            && $conversation->guest_session_id === $token
            && $conversation->customer_user_id === null;
    }
}
