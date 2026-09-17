<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessibility_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('speech_rate', ['lenta', 'normal', 'rapida'])->default('normal');
            $table->enum('volume', ['bajo', 'normal', 'alto'])->default('normal');
            $table->boolean('auto_read')->default(false);
            $table->boolean('repeat_prompts')->default(true);
            $table->boolean('high_contrast')->default(false);
            $table->enum('font_size', ['pequena', 'normal', 'grande'])->default('normal');
            $table->enum('navigation_mode', ['visual', 'voz', 'mixto'])->default('visual');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('accessibility_preferences');
    }
};