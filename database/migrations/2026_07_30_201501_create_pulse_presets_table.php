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
            
            $table->string('key')->unique();

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
