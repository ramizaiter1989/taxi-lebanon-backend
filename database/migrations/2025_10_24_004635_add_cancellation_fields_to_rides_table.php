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
        Schema::table('rides', function (Blueprint $table) {
            // Who cancelled (driver or passenger)
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');

            // Cancellation details
            $table->enum('cancellation_reason', [
                'driver_no_show',
                'wrong_location',
                'changed_mind',
                'too_expensive',
                'emergency',
                'other',
            ])->nullable();

            $table->string('cancellation_note', 200)->nullable();

            // When the cancellation happened
            $table->timestamp('cancelled_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_by', 'cancellation_reason', 'cancellation_note', 'cancelled_at']);
        });
    }
};
