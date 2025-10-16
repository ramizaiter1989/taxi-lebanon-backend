<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('benz_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained()->onDelete('cascade');
            $table->float('distance_km');
            $table->float('duration_min');
            $table->float('average_consumption_l_per_100km');
            $table->float('fuel_price_per_liter');
            $table->float('fuel_used_liters');
            $table->float('fuel_cost');
            $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('benz_consumptions');
    }
};
