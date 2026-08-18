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
        Schema::create('routine_template_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_template_id')->constrained()->cascadeOnDelete();

            $table->string('name_es');
            $table->text('notes_es')->nullable();

            $table->string('name_en')->nullable();
            $table->text('notes_en')->nullable();

            $table->unsignedSmallInteger('bpm');
            
            $table->enum('mode', [
                'timer',
                'classic',
            ])->default('timer');

            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['routine_template_id', 'position',]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_template_steps');
    }
};
