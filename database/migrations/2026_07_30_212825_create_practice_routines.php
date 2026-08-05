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
        Schema::create('practice_routines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'position']);

            $table->string('sync_source', 32)->nullable();
            $table->unique(['user_id', 'sync_source'], 'practice_routines_user_sync_source_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_routines');
    }
};
