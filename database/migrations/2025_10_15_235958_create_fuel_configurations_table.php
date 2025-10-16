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
        Schema::create('fuel_configurations', function (Blueprint $table) {
    $table->id();
    $table->float('average_consumption_l_per_100km')->default(7);
    $table->float('fuel_price_per_liter')->default(1.85);
    $table->timestamps();
});

// Seed default configuration
\DB::table('fuel_configurations')->insert([
    'average_consumption_l_per_100km' => 7,
    'fuel_price_per_liter' => 1.85,
    'created_at' => now(),
    'updated_at' => now(),
]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_configurations');
    }
};
