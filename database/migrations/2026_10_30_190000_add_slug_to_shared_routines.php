<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_routines', function (Blueprint $table) {
            $table->string('slug', 100)->nullable()->unique();
        });

        DB::table('shared_routines')
            ->join('practice_routines', 'shared_routines.practice_routine_id', '=', 'practice_routines.id')
            ->select('shared_routines.id', 'practice_routines.name')
            ->orderBy('shared_routines.id')
            ->get()
            ->each(function ($record) {
                do {
                    $slug = Str::limit(Str::slug($record->name), 65, '') ?: 'routine';
                    $slug .= '-' . Str::random(10);
                } while (DB::table('shared_routines')->where('slug', $slug)->exists());

                DB::table('shared_routines')->where('id', $record->id)->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        Schema::table('shared_routines', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
