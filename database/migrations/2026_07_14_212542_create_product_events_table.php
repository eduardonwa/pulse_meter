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
        Schema::create('product_events', function (Blueprint $table) {
            $table->id();

            // Evita duplicados si una petición se reintenta.
            $table->uuid('event_id')->unique();

            // Identifica el navegador de forma anónima.
            $table->uuid('visitor_id');

            // Identifica esta visita/pestaña.
            $table->uuid('session_id');

            $table->string('event_name', 80);
            $table->string('stage', 20);

            $table->json('properties')->nullable();
            $table->string('path', 255)->nullable();

            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['event_name', 'occurred_at']);
            $table->index(['visitor_id', 'occurred_at']);
            $table->index(['session_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_events');
    }
};
