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
        Schema::create('domain_settings', function (Blueprint $table) {
            $table->id();
            $table->string('old_domain');
            $table->string('new_domain');
            $table->string('new_domain_url');
            $table->unsignedSmallInteger('redirect_seconds')->default(15);
            $table->timestamps();
        });

        DB::table('domain_settings')->insert([
            'old_domain' => 'bet368.cassino',
            'new_domain' => 'bet368.games',
            'new_domain_url' => 'https://bet368.games',
            'redirect_seconds' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_settings');
    }
};
