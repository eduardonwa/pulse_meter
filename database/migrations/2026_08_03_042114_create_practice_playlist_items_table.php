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
        Schema::create('practice_playlist_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('practice_playlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_routine_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
            
            $table->index(['practice_playlist_id', 'position']);
            $table->unique(['practice_playlist_id', 'practice_routine_id'], 'playlist_routine_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_playlist_items');
    }
};
