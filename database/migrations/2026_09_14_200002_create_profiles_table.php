<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrepreneur_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('personal_description')->nullable();
            $table->enum('verification_status', [
                'pendiente_documento',
                'documento_enviado',
                'en_revision',
                'aprobado',
                'rechazado',
            ])->default('pendiente_documento');
            $table->timestamp('registered_fully_at')->nullable();
            $table->timestamp('document_deadline_at')->nullable();
            $table->timestamp('validation_deadline_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('preferences')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
        Schema::dropIfExists('entrepreneur_profiles');
    }
};