<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $token, TelegramBotService $telegram): JsonResponse
    {
        $expected = (string) config('services.telegram.webhook_token');
        if ($expected === '' || !hash_equals($expected, $token)) {
            return response()->json(['ok' => false], 403);
        }

        $update = $request->all();
        if (!$telegram->isAuthorizedUpdate($update)) {
            return response()->json(['ok' => true]);
        }

        $text = trim((string) data_get($update, 'message.text', ''));
        if ($text === '') {
            return response()->json(['ok' => true]);
        }

        [$conversationId, $replyText] = $this->parseReplyCommand($text, $update);
        if (!$conversationId || $replyText === '') {
            $telegram->sendMessage("Invalid format. Use: /r <conversation_id> <message>\nExample: /r 27 Hi Tra My");
            return response()->json(['ok' => true, 'hint' => 'Use /r <conversation_id> <message>']);
        }

        $conversation = ChatConversation::open()->find($conversationId);
        if (!$conversation) {
            $telegram->sendMessage("Conversation #{$conversationId} not found or closed.");
            return response()->json(['ok' => true]);
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'is_from_customer' => false,
            'body' => $replyText,
        ]);

        $telegram->sendMessage("Sent to customer (CID #{$conversation->id}).");

        return response()->json(['ok' => true]);
    }

    private function parseReplyCommand(string $text, array $update): array
    {
        // Accept: /r 27 hi, /reply 27 hi, /r@BotName 27 hi
        if (preg_match('/^\/(?:r|reply)(?:@[A-Za-z0-9_]+)?\s+(\d+)\s+(.+)$/is', $text, $m)) {
            return [(int) $m[1], trim($m[2])];
        }

        // Accept common typo: "/ 27 hi"
        if (preg_match('/^\/\s*(\d+)\s+(.+)$/is', $text, $m)) {
            return [(int) $m[1], trim($m[2])];
        }

        $replyToText = (string) data_get($update, 'message.reply_to_message.text', '');
        if ($replyToText !== '' && preg_match('/CID:\s*#?(\d+)/i', $replyToText, $m)) {
            return [(int) $m[1], $text];
        }

        return [0, ''];
    }
}
