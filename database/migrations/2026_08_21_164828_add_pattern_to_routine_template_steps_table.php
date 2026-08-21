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
        Schema::table('routine_template_steps', function (Blueprint $table) {
            $table->text('alpha_tex')->nullable()->after('mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routine_template_steps', function (Blueprint $table) {
            //
        });
    }
};
