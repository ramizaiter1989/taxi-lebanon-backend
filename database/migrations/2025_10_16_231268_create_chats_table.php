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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('receiver_id')->constrained('users');
            $table->text('message');
            $table->enum('message_type', ['text', 'image', 'document'])->default('text');
            $table->boolean('is_read')->default(false);
            $table->softDeletes(); // Optional: for soft deletion
            $table->timestamps();

            // Indexes for performance
            $table->index(['ride_id', 'sender_id', 'receiver_id']);
            $table->index('created_at');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
