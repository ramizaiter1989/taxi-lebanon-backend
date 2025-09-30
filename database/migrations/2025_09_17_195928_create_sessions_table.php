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
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('sessions');
        Schema::dropIfExists('driver_active_durations');
        Schema::dropIfExists('ride_logs');
        Schema::dropIfExists('fare_settings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('rides');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
    }
};
