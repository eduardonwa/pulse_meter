<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('routine_template_steps', function (Blueprint $table) {
                $table->enum('mode', ['timer', 'classic', 'drill'])->default('timer')->change();
            });
        } else {
            DB::statement("ALTER TABLE routine_template_steps MODIFY COLUMN mode ENUM('timer', 'classic', 'drill') NOT NULL DEFAULT 'timer'");
        }

        DB::table('routine_template_steps')->where('mode', 'timer')->update(['mode' => 'drill']);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('routine_template_steps', function (Blueprint $table) {
                $table->enum('mode', ['classic', 'drill'])->default('drill')->change();
            });
        } else {
            DB::statement("ALTER TABLE routine_template_steps MODIFY COLUMN mode ENUM('classic', 'drill') NOT NULL DEFAULT 'drill'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('routine_template_steps', function (Blueprint $table) {
                $table->enum('mode', ['classic', 'drill', 'timer'])->default('drill')->change();
            });
        } else {
            DB::statement("ALTER TABLE routine_template_steps MODIFY COLUMN mode ENUM('classic', 'drill', 'timer') NOT NULL DEFAULT 'drill'");
        }

        DB::table('routine_template_steps')->where('mode', 'drill')->update(['mode' => 'timer']);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('routine_template_steps', function (Blueprint $table) {
                $table->enum('mode', ['timer', 'classic'])->default('timer')->change();
            });
        } else {
            DB::statement("ALTER TABLE routine_template_steps MODIFY COLUMN mode ENUM('timer', 'classic') NOT NULL DEFAULT 'timer'");
        }
    }
};
