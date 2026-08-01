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
        Schema::create('practice_routine_steps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('practice_routine_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->unsignedSmallInteger('bpm');

            $table->enum('mode', [
                'timer',
                'classic',
            ])->default('timer');

            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('position')->default(0);

            $table->string('origin')->default('custom');

            $table->timestamps();

            $table->index(['practice_routine_id', 'position',]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_routine_steps');
    }
};
