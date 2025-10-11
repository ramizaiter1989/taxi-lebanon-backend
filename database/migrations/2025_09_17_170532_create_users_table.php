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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Basic user info
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('password');

            // Verification & login
            $table->boolean('is_verified')->default(false);
            $table->string('verification_code', 6)->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->rememberToken();

            // Wallet & FCM
            $table->decimal('wallet_balance', 10, 2)->default(0);
            $table->string('fcm_token')->nullable();

            // Profile & role
            $table->enum('gender', ['male','female'])->default('female');
            $table->enum('role', ['passenger','driver','admin'])->default('passenger');
            $table->string('profile_photo')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_locked')->default(false);

            // Location
            $table->decimal('current_lat', 10, 7)->nullable();
            $table->decimal('current_lng', 10, 7)->nullable();
            $table->timestamp('last_location_update')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
