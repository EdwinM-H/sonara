<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Usuario del login por voz: nombres + apellidos normalizados
        // (minúsculas, sin tildes). Único para que el login no sea ambiguo.
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 190)->nullable()->unique()->after('name');
        });

        Schema::table('entrepreneur_profiles', function (Blueprint $table) {
            $table->string('location', 150)->nullable()->after('personal_description');
        });
    }

    public function down(): void
    {
        Schema::table('entrepreneur_profiles', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
