<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fare_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_fare', 8, 2)->default(5.00);      // Base fare
            $table->decimal('per_km_rate', 8, 2)->default(1.50);     // Rate per km
            $table->decimal('per_minute_rate', 8, 2)->default(0.50); // Rate per minute
            $table->decimal('minimum_fare', 8, 2)->default(0);
            $table->decimal('cancellation_fee', 8, 2)->default(0);
            $table->float('peak_multiplier')->default(1.0);
            $table->timestamps();
        });

        // Insert default settings
        DB::table('fare_settings')->insert([
            'base_fare' => 5.00,
            'per_km_rate' => 1.50,
            'per_minute_rate' => 0.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fare_settings');
    }
};
