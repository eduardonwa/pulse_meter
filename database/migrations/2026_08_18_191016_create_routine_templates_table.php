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
        Schema::create('routine_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('cover_image')->nullable();

            $table->string('type', 32)->default('routine');
            $table->string('instrument', 32)->default('guitar');
            $table->string('difficulty', 32);

            $table->unsignedTinyInteger('challenge_days')->nullable();
            $table->unsignedTinyInteger('recommended_sessions')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_templates');
    }
};
