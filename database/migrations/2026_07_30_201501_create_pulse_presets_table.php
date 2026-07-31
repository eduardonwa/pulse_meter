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
        Schema::create('pulse_presets', function (Blueprint $table) {
            $table->id();

            // null = preset oficial
            // valor = pattern del usuario
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            // solo los oficiales necesitan key
            $table->string('key')->nullable()->unique();

            $table->string('collection')->nullable();

            $table->string('name');

            // principalmente para ordenar los del usuario
            $table->unsignedSmallInteger('position')->nullable();

            $table->unsignedTinyInteger('numerator');
            $table->unsignedTinyInteger('denominator');

            $table->json('grouping');
            $table->json('pattern');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pulse_presets');
    }
};
