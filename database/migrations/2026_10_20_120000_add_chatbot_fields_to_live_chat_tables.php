<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->string('handling_mode', 20)->default('bot')->after('status');
            $table->boolean('bot_enabled')->default(true)->after('handling_mode');
            $table->timestamp('handed_off_at')->nullable()->after('bot_enabled');
            $table->string('last_bot_intent', 80)->nullable()->after('handed_off_at');
            $table->timestamp('bot_processing_at')->nullable()->after('last_bot_intent');
            $table->index(['handling_mode', 'status']);
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('sender_type', 20)->default('customer')->after('is_from_customer');
            $table->json('metadata')->nullable()->after('body');
            $table->string('intent', 80)->nullable()->after('metadata');
            $table->decimal('confidence', 5, 4)->nullable()->after('intent');
            $table->index(['conversation_id', 'sender_type']);
        });

        DB::table('chat_messages')
            ->where('is_from_customer', false)
            ->update(['sender_type' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'sender_type']);
            $table->dropColumn(['sender_type', 'metadata', 'intent', 'confidence']);
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex(['handling_mode', 'status']);
            $table->dropColumn([
                'handling_mode',
                'bot_enabled',
                'handed_off_at',
                'last_bot_intent',
                'bot_processing_at',
            ]);
        });
    }
};
