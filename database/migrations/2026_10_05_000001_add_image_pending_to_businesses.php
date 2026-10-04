<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La generación de la imagen falló: el emprendimiento se publica sin
        // imagen y "sonara:reintentar-imagenes" lo vuelve a intentar.
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('image_pending')->default(false)->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('image_pending');
        });
    }
};
