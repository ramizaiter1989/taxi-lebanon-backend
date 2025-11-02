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
        Schema::create('ride_declines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('ride_id')->constrained('rides')->onDelete('cascade');
    $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_declines');
    }
};
