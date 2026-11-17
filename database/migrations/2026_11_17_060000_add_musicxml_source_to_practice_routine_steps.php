<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_routine_steps', function (Blueprint $table) {
            // Preserve the original full score; the simple tablature editor
            // must never rewrite notation it cannot represent.
            $table->longText('musicxml_source')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('practice_routine_steps', function (Blueprint $table) {
            $table->dropColumn('musicxml_source');
        });
    }
};
