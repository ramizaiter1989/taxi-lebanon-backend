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
        Schema::create('rides', function (Blueprint $table) {
            $table->id();

            // Passenger & Driver
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            /**
             * driver_id is null until a driver accepts the ride
             */

            // Locations
            $table->decimal('origin_lat', 10, 7);
            $table->decimal('origin_lng', 10, 7);
            $table->decimal('destination_lat', 10, 7);
            $table->decimal('destination_lng', 10, 7);

            // Ride status lifecycle
            $table->enum('status', [
                'pending',      // created by passenger, waiting for driver
                'accepted',     // driver accepted
                'in_progress',  // ride started (driver & passenger traveling to destination)
                'arrived',      // driver reached destination (within ~5m) OR passenger ends early; fare later added to driver balance
                'cancelled'     // ride cancelled
            ])->default('pending');

            // Fare info
            $table->decimal('fare', 8, 2)->nullable();
            $table->float('distance')->nullable();
            $table->float('duration')->nullable();

            // Ride lifecycle timestamps
            $table->timestamp('accepted_at')->nullable();   // when driver accepts
            $table->timestamp('started_at')->nullable();    // when ride begins
            $table->timestamp('arrived_at')->nullable();    // when driver/passenger ends trip
            $table->timestamp('completed_at')->nullable();  // optional: final settlement timestamp

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
