<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->foreignId('publication_id')->nullable()->constrained('publications')->nullOnDelete();
            $table->text('prompt');
            $table->string('style')->nullable();
            $table->string('provider')->nullable();
            $table->enum('status', ['pendiente', 'generando', 'completada', 'error'])->default('pendiente');
            $table->string('image_path')->nullable();
            $table->string('reference')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('generation_time_ms')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};