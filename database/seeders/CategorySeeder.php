<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Gastronomía',
                'icon' => '🍽',
                'subcategories' => ['Cocina criolla', 'Repostería', 'Comida saludable', 'Panadería'],
            ],
            [
                'name' => 'Artesanía',
                'icon' => '🎨',
                'subcategories' => ['Cerámica', 'Textiles', 'Joyería artesanal', 'Decoración'],
            ],
            [
                'name' => 'Belleza',
                'icon' => '💇',
                'subcategories' => ['Estética', 'Manicure', 'Peluquería', 'Maquillaje'],
            ],
            [
                'name' => 'Textiles',
                'icon' => '🧵',
                'subcategories' => ['Tejidos', 'Confección', 'Bordados', 'Tapices'],
            ],
            [
                'name' => 'Tecnología',
                'icon' => '💻',
                'subcategories' => ['Reparación de equipos', 'Desarrollo web', 'Soporte técnico', 'Venta de accesorios'],
            ],
            [
                'name' => 'Servicios profesionales',
                'icon' => '📝',
                'subcategories' => ['Contabilidad', 'Asesoría legal', 'Traducción', 'Marketing'],
            ],
            [
                'name' => 'Educación',
                'icon' => '📚',
                'subcategories' => ['Tutorías', 'Cursos de idiomas', 'Clases particulares', 'Talleres'],
            ],
            [
                'name' => 'Masajes',
                'icon' => '💆',
                'subcategories' => ['Masajes terapéuticos', 'Masajes relajantes', 'Reflexología'],
            ],
            [
                'name' => 'Limpieza',
                'icon' => '🧹',
                'subcategories' => ['Limpieza de oficinas', 'Limpieza del hogar', 'Desinfección'],
            ],
            [
                'name' => 'Repostería',
                'icon' => '🍰',
                'subcategories' => ['Pasteles', 'Postres', 'Tortas personalizadas', 'Buffets dulces'],
            ],
            [
                'name' => 'Venta de productos',
                'icon' => '🛍',
                'subcategories' => ['Productos de abarrotes', 'Cosméticos', 'Ropa', 'Accesorios'],
            ],
            [
                'name' => 'Otros',
                'icon' => '✨',
                'subcategories' => ['Otros productos', 'Otros servicios'],
            ],
        ];

        foreach ($categories as $i => $categoryData) {
            $category = Category::updateOrCreate(
                ['name' => $categoryData['name']],
                [
                    'slug' => \Illuminate\Support\Str::slug($categoryData['name']),
                    'icon' => $categoryData['icon'],
                    'is_active' => true,
                    'sort_order' => $i,
                    'description' => 'Productos y servicios de '.strtolower($categoryData['name']),
                ],
            );

            foreach ($categoryData['subcategories'] as $j => $subName) {
                Subcategory::updateOrCreate(
                    ['category_id' => $category->id, 'name' => $subName],
                    [
                        'slug' => \Illuminate\Support\Str::slug($subName).'-'.$category->id,
                        'is_active' => true,
                        'sort_order' => $j,
                    ],
                );
            }
        }
    }
}