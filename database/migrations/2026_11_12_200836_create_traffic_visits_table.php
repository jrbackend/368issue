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
        Schema::create('traffic_visits', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->char('country_code', 2)->nullable();
            $table->string('region_name')->nullable();
            $table->string('city_name')->nullable();
            $table->string('device', 10);
            $table->boolean('is_bot')->default(false);
            $table->string('operating_system')->nullable();
            $table->string('browser')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->boolean('was_allowed')->index();
            $table->string('blocked_reason', 20)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_visits');
    }
};
