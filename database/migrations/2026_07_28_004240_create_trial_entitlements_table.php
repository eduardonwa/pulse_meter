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
        Schema::create('trial_entitlements', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // active, paused, completed o expired.
            $table->string('status', 20)->default('active')->index();

            $table->unsignedInteger('granted_seconds')->default(3600);
            $table->unsignedInteger('used_seconds')->default(0);

            // Ventana durante la cual puede usar el trial.
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->index();

            // Información de pausa.
            $table->timestamp('paused_at')->nullable();
            $table->string('pause_reason')->nullable();

            // Se utilizará después para heartbeats y varias pestañas.
            $table->string('active_session_id')->nullable()->index();
            $table->timestamp('last_heartbeat_at')->nullable();

            // Se establece cuando consume todos sus minutos.
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trial_entitlements');
    }
};
