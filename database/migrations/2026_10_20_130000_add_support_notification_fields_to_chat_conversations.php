<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_last_seen_message_id')->default(0)->after('bot_processing_at');
            $table->timestamp('customer_last_seen_at')->nullable()->after('customer_last_seen_message_id');
            $table->unsignedBigInteger('reply_email_notified_message_id')->default(0)->after('customer_last_seen_at');
            $table->timestamp('reply_email_sent_at')->nullable()->after('reply_email_notified_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn([
                'customer_last_seen_message_id',
                'customer_last_seen_at',
                'reply_email_notified_message_id',
                'reply_email_sent_at',
            ]);
        });
    }
};
