<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Precio y horario tal como se dictan por voz ("desde 10 dolares",
        // "lunes a viernes de 8 a 5"); los campos numéricos de precio y la
        // tabla business_hours siguen siendo los del formulario.
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('price_text', 190)->nullable()->after('price_max');
            $table->string('schedule_text', 190)->nullable()->after('price_text');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['price_text', 'schedule_text']);
        });
    }
};
