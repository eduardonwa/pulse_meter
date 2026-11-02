<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('practice_routines', function (Blueprint $table) {
            $table->foreignId('shared_routine_id')
                ->nullable()
                ->constrained('shared_routines')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('practice_routines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shared_routine_id');
        });
    }
};
