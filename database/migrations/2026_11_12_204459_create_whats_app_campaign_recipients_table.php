<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whats_app_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whats_app_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 20);
            $table->string('status', 10)->default('queued')->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_campaign_recipients');
    }
};
