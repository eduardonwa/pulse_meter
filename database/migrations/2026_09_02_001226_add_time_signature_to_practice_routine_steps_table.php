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
        Schema::table('practice_routine_steps', function (Blueprint $table) {
            $table
                ->unsignedTinyInteger('time_signature_numerator')
                ->default(4)
                ->after('bpm');

            $table
                ->unsignedTinyInteger('time_signature_denominator')
                ->default(4)
                ->after('time_signature_numerator');
        });
    }

    public function down(): void
    {
        Schema::table('practice_routine_steps', function (Blueprint $table) {
            $table->dropColumn([
                'time_signature_numerator',
                'time_signature_denominator',
            ]);
        });
    }
};
