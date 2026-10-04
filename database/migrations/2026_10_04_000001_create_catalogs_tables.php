<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogos que el admin gestiona y que el emprendedor elige al
        // registrar su emprendimiento. Las categorías siguen en su propia
        // tabla (las usan el portal y los emprendimientos); aquí viven los
        // demás tipos: sectores, etiquetas y los que el admin agregue.
        Schema::create('catalog_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('description')->nullable();
            // Los tipos del sistema (sectores, etiquetas) no se pueden borrar.
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('catalog_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_type_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            // Nombre normalizado (minúsculas, sin tildes) para comparar
            // con lo dictado por voz y evitar duplicados "Norte"/"norte".
            $table->string('normalized_name', 150);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catalog_type_id', 'normalized_name']);
        });

        $now = now();
        DB::table('catalog_types')->insert([
            ['name' => 'Sectores', 'slug' => 'sectores', 'description' => 'Zona donde se ubica el emprendimiento.', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Etiquetas', 'slug' => 'etiquetas', 'description' => 'Palabras clave opcionales para el anuncio.', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_options');
        Schema::dropIfExists('catalog_types');
    }
};
