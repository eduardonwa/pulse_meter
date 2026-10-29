<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_routine_steps', function (Blueprint $table) {
            $table->string('tab_source', 16)->nullable();
        });

        // Older template imports already contain alphaTex, but did not track its origin.
        DB::table('practice_routine_steps')
            ->whereNotNull('alpha_tex')
            ->whereIn('practice_routine_id', function ($query) {
                $query->select('id')
                    ->from('practice_routines')
                    ->whereNotNull('routine_template_id');
            })
            ->update(['tab_source' => 'template']);
    }

    public function down(): void
    {
        Schema::table('practice_routine_steps', function (Blueprint $table) {
            $table->dropColumn('tab_source');
        });
    }
};
