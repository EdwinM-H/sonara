<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de identidad y discapacidad que se piden en el registro por voz:
 * DNI, grado de discapacidad (LEVE, MODERADA, SEVERA) y carnet CONADIS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrepreneur_profiles', function (Blueprint $table) {
            $table->string('dni', 20)->nullable()->unique()->after('user_id');
            $table->enum('grado_discapacidad', ['LEVE', 'MODERADA', 'SEVERA'])->nullable()->after('dni');
            $table->boolean('tiene_carnet_conadis')->nullable()->after('grado_discapacidad');
            $table->string('numero_carnet_conadis', 30)->nullable()->after('tiene_carnet_conadis');
        });
    }

    public function down(): void
    {
        Schema::table('entrepreneur_profiles', function (Blueprint $table) {
            $table->dropUnique(['dni']);
            $table->dropColumn(['dni', 'grado_discapacidad', 'tiene_carnet_conadis', 'numero_carnet_conadis']);
        });
    }
};
