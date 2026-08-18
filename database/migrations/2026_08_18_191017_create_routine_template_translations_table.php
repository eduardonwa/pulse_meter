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
        Schema::create('routine_template_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_template_id')->constrained()->cascadeOnDelete();
            
            $table->string('locale', 2);

            $table->string('title');
            $table->string('slug');

            $table->string('cover_alt')->nullable();
            $table->text('summary');

            $table->text('purpose')->nullable();
            $table->text('instructions')->nullable();

            $table->string('meta_title');
            $table->text('meta_description');

            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['routine_template_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_template_translations');
    }
};
