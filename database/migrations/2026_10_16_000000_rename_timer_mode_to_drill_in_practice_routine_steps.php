<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('practice_routine_steps', function (Blueprint $table) {
                $table->enum('mode', ['timer', 'classic', 'drill'])->default('timer')->change();
            });

            DB::table('practice_routine_steps')->where('mode', 'timer')->update(['mode' => 'drill']);

            Schema::table('practice_routine_steps', function (Blueprint $table) {
                $table->enum('mode', ['classic', 'drill'])->default('drill')->change();
            });
            return;
        }

        // MySQL cannot update existing timer rows directly if drill is
        // not yet a permitted ENUM value.
        DB::statement("ALTER TABLE practice_routine_steps MODIFY COLUMN mode ENUM('timer', 'classic', 'drill') NOT NULL DEFAULT 'timer'");

        DB::table('practice_routine_steps')
            ->where('mode', 'timer')
            ->update(['mode' => 'drill']);

        DB::statement("ALTER TABLE practice_routine_steps MODIFY COLUMN mode ENUM('classic', 'drill') NOT NULL DEFAULT 'drill'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('practice_routine_steps', function (Blueprint $table) {
                $table->enum('mode', ['classic', 'drill', 'timer'])->default('drill')->change();
            });

            DB::table('practice_routine_steps')->where('mode', 'drill')->update(['mode' => 'timer']);

            Schema::table('practice_routine_steps', function (Blueprint $table) {
                $table->enum('mode', ['timer', 'classic'])->default('timer')->change();
            });
            return;
        }

        DB::statement("ALTER TABLE practice_routine_steps MODIFY COLUMN mode ENUM('classic', 'drill', 'timer') NOT NULL DEFAULT 'drill'");

        DB::table('practice_routine_steps')
            ->where('mode', 'drill')
            ->update(['mode' => 'timer']);

        DB::statement("ALTER TABLE practice_routine_steps MODIFY COLUMN mode ENUM('timer', 'classic') NOT NULL DEFAULT 'timer'");
    }
};
