<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hybrid website chatbot
    |--------------------------------------------------------------------------
    |
    | The bot answers from verified store data first, optionally asks an AI
    | provider to phrase that answer, and hands sensitive conversations to
    | the existing Live Chat team. Keep this disabled until an AI key and a
    | queue worker are configured in production.
    |
    */
    'enabled' => filter_var(env('CHATBOT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'ai_enabled' => filter_var(env('CHATBOT_AI_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'ai' => [
        'provider' => env('CHATBOT_PROVIDER', 'openai'),
        'api_key' => env('CHATBOT_API_KEY') ?: env('OPENAI_API_KEY', ''),
        'base_url' => rtrim((string) (env('CHATBOT_BASE_URL') ?: env('OPENAI_BASE_URL', 'https://api.openai.com/v1')), '/'),
        'model' => env('CHATBOT_MODEL') ?: env('OPENAI_MODEL', 'gpt-5.6-luna'),
        'timeout' => max(5, (int) env('CHATBOT_TIMEOUT', 25)),
        'history_messages' => max(0, min(12, (int) env('CHATBOT_HISTORY_MESSAGES', 6))),
    ],

    'max_products' => max(1, min(5, (int) env('CHATBOT_MAX_PRODUCTS', 3))),

    // Human support expectations shown to customers and used by reply alerts.
    'support_reply_within' => env('CHATBOT_SUPPORT_REPLY_WITHIN', '2 business hours'),
    'support_hours' => env('CHATBOT_SUPPORT_HOURS', 'Mon–Sat, 9am–6pm'),
    'reply_email_enabled' => filter_var(env('LIVE_CHAT_REPLY_EMAIL_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'reply_email_delay_minutes' => max(1, (int) env('LIVE_CHAT_REPLY_EMAIL_DELAY_MINUTES', 5)),

    'welcome' => env('CHATBOT_WELCOME', 'Hi! I can help you find nails, check sizing or shipping, and track an order.'),
    'fallback' => env('CHATBOT_FALLBACK', 'I don’t have confirmed information for that yet. You can rephrase your question or choose “Talk to support” and our team will help.'),
    'handoff' => env('CHATBOT_HANDOFF', 'I’ve passed this conversation to our support team. They’ll reply here as soon as they can.'),
];
