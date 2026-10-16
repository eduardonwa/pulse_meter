<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::table('practice_routine_steps')->where('mode', 'timer')->update(['mode' => 'drill']);
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
            DB::table('practice_routine_steps')->where('mode', 'drill')->update(['mode' => 'timer']);
            return;
        }

        DB::statement("ALTER TABLE practice_routine_steps MODIFY COLUMN mode ENUM('classic', 'drill', 'timer') NOT NULL DEFAULT 'drill'");

        DB::table('practice_routine_steps')
            ->where('mode', 'drill')
            ->update(['mode' => 'timer']);

        DB::statement("ALTER TABLE practice_routine_steps MODIFY COLUMN mode ENUM('timer', 'classic') NOT NULL DEFAULT 'timer'");
    }
};
