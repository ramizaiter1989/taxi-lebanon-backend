<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->string('relationship')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Add emergency SOS tracking to rides
        Schema::table('rides', function (Blueprint $table) {
            $table->boolean('sos_triggered')->default(false);
            $table->timestamp('sos_triggered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn(['sos_triggered', 'sos_triggered_at']);
        });
        Schema::dropIfExists('emergency_contacts');
    }
};