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
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('car_photo_front')->nullable();
            $table->string('car_photo_back')->nullable();
            $table->string('car_photo_left')->nullable();
            $table->string('car_photo_right')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('drivers', function (Blueprint $table) {
        $table->dropColumn([
            'car_photo_front',
            'car_photo_back',
            'car_photo_left',
            'car_photo_right'
        ]);
    });
}

};
