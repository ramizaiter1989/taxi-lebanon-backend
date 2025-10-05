<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('value', 10, 2); // percentage or fixed amount
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);
            $table->decimal('min_fare', 10, 2)->nullable(); // minimum fare to apply
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add promo code tracking to rides
        Schema::table('rides', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('final_fare', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'discount', 'final_fare']);
        });
        Schema::dropIfExists('promo_codes');
    }
};