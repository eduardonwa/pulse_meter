<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('randomized_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('bpm');
            $table->string('root', 3);
            $table->string('scale', 40);
            $table->string('playback_mode', 10);
            $table->unsignedTinyInteger('numerator');
            $table->unsignedTinyInteger('denominator');
            $table->unsignedTinyInteger('subdivision');
            $table->json('grouping');
            $table->json('pattern');
            $table->timestamps();

            $table->index(['user_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('randomized_sessions');
    }
};
