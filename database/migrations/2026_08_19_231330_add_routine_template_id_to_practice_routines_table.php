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
        Schema::table('practice_routines', function (Blueprint $table) {
            $table
                ->foreignId('routine_template_id')
                ->nullable()
                ->after('user_id')
                ->constrained('routine_templates')
                ->nullOnDelete();

            $table->unique([
                'user_id',
                'routine_template_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_routines', function (Blueprint $table) {
            $table->dropUnique([
                'user_id',
                'routine_template_id',
            ]);

            $table->dropConstrainedForeignId(
                'routine_template_id'
            );
        });
    }
};
