<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrepreneur_profile_id')->constrained('entrepreneur_profiles')->cascadeOnDelete();
            $table->string('document_type', 60)->default('carnet_acreditacion');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->enum('status', ['recibido'])->default('recibido');
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['entrepreneur_profile_id', 'uploaded_at']);
        });

        Schema::create('assistance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('message');
            $table->string('preferred_channel', 40)->default('sistema');
            $table->enum('status', ['pendiente', 'en_atencion', 'atendida', 'cerrada'])->default('pendiente');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_requests');
        Schema::dropIfExists('verification_documents');
    }
};