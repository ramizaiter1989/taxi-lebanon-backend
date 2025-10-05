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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();

            // Nullable at first so auto-create works
            $table->string('license_number')->nullable();
            $table->string('vehicle_type')->default('car');
            $table->string('vehicle_number')->nullable();
            $table->decimal('rating', 3, 1)->default(5.0);
            $table->boolean('availability_status')->default(true);

            $table->string('car_photo')->nullable();
            $table->string('car_photo_front')->nullable();
            $table->string('car_photo_back')->nullable();
            $table->string('car_photo_left')->nullable();
            $table->string('car_photo_right')->nullable();
            $table->string('license_photo')->nullable();
            $table->string('id_photo')->nullable();
            $table->string('insurance_photo')->nullable();

            $table->decimal('current_driver_lat', 10, 7)->nullable();
            $table->decimal('current_driver_lng', 10, 7)->nullable();
            $table->decimal('scanning_range_km', 10, 7)->nullable();

            $table->timestamp('active_at')->nullable();
            $table->timestamp('inactive_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};