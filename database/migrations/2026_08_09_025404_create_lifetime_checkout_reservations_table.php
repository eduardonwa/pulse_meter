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
        Schema::create('lifetime_checkout_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('slot_number')->unique();

            $table->uuid('token')->unique();

            $table->string('stripe_checkout_session_id')->nullable()->unique();

            $table->timestamp('reserved_until');
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();

            $table->index(['completed_at', 'reserved_until',]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lifetime_checkout_reservations');
    }
};
