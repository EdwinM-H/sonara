<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * La tabla `notifications` tenía una columna `user_id` NOT NULL además
     * de las columnas polimórficas estándar de Laravel (`notifiable_id`,
     * `notifiable_type`). El canal `database` nativo de Laravel nunca
     * rellena `user_id`, así que CADA notificación (nueva solicitud,
     * verificación, publicación, asistencia) fallaba con un error de
     * restricción NOT NULL — y como `notify()` se llama dentro de
     * transacciones (p. ej. al crear una solicitud de cliente), esto
     * revertía la operación completa. La columna es redundante:
     * `notifiable_id`/`notifiable_type` ya identifican al destinatario.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
