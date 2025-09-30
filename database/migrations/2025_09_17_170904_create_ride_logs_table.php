<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained('rides')->onDelete('cascade');

            // Locations
            $table->decimal('driver_lat', 10, 7)->nullable();
            $table->decimal('driver_lng', 10, 7)->nullable();
            $table->decimal('passenger_lat', 10, 7)->nullable();
            $table->decimal('passenger_lng', 10, 7)->nullable();

            // Durations (calculated later by observers or controller)
            $table->integer('pickup_duration_seconds')->nullable();   // pending → in_progress
            $table->integer('trip_duration_seconds')->nullable();     // in_progress → arrived
            $table->integer('total_duration_seconds')->nullable();    // pickup + trip

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_logs');
    }
};
