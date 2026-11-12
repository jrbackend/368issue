<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('traffic_rules', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->json('allowed_devices');
            $table->json('allowed_languages');
            $table->json('allowed_countries');
            $table->boolean('allow_unknown_country')->default(true);
            $table->string('blocked_action')->default('block');
            $table->string('blocked_redirect_url')->nullable();
            $table->timestamps();
        });

        DB::table('traffic_rules')->insert([
            'is_enabled' => false,
            'allowed_devices' => '[]',
            'allowed_languages' => '[]',
            'allowed_countries' => '[]',
            'allow_unknown_country' => true,
            'blocked_action' => 'block',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_rules');
    }
};
