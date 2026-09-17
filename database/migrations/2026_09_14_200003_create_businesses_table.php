<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrepreneur_profile_id')->constrained('entrepreneur_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->nullOnDelete();
            $table->enum('type', ['producto', 'servicio'])->default('producto');
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->enum('availability', ['disponible', 'bajo_pedido', 'agotado'])->default('disponible');
            $table->string('currency', 3)->default('PEN');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_min', 10, 2)->nullable();
            $table->decimal('price_max', 10, 2)->nullable();
            $table->json('payment_methods')->nullable();
            $table->string('country', 100)->default('Perú');
            $table->string('region', 100)->default('Cusco');
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id', 'region', 'district']);
        });

        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('1=Lunes ... 7=Domingo');
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['business_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours');
        Schema::dropIfExists('businesses');
    }
};