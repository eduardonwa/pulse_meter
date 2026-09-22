<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_questions', function (Blueprint $table) {
            $table->foreignId('knowledge_answer_id')
                ->nullable()
                ->after('email')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('content_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('knowledge_answer_id');
        });
    }
};
