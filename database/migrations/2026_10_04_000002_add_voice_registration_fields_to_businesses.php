<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Categoría dictada cuando el catálogo de categorías estaba vacío.
            $table->string('custom_category', 150)->nullable()->after('subcategory_id');
            $table->string('sector', 150)->nullable()->after('custom_category');
            $table->json('tags')->nullable()->after('sector');

            // Anuncio generado y publicado en la web externa.
            $table->text('image_prompt')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('external_ad_id', 190)->nullable();
            $table->string('external_ad_url', 2048)->nullable();
            $table->string('publish_status', 20)->nullable()->comment('pendiente | publicado | error');
            $table->text('publish_error')->nullable();
            $table->timestamp('published_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'custom_category', 'sector', 'tags', 'image_prompt', 'image_url',
                'external_ad_id', 'external_ad_url', 'publish_status', 'publish_error', 'published_at',
            ]);
        });
    }
};
