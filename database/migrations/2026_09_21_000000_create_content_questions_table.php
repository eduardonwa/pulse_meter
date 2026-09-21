<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_questions', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 2);
            $table->text('question');
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('status')->default('pending');
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_questions');
    }
};
