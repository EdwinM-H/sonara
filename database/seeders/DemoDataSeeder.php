<?php

namespace Database\Seeders;

use App\Models\AIGeneration;
use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\Publication;
use App\Models\Request as CustomerRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $entrepreneur = User::where('email', 'emprendedor@sonara.test')->firstOrFail();
        $customer = User::where('email', 'cliente@sonara.test')->firstOrFail();

        $profile = $entrepreneur->entrepreneurProfile;

        $gastronomia = Category::where('name', 'Gastronomía')->first();
        $textiles = Category::where('name', 'Textiles')->first();
        $masajes = Category::where('name', 'Masajes')->first();
        $reposteria = Category::where('name', 'Repostería')->first();

        $subReposteria = $reposteria?->subcategories()->first();
        $subTextil = $textiles?->subcategories()->first();
        $subMasaje = $masajes?->subcategories()->first();
        $subCocina = $gastronomia?->subcategories()->first();

        $flyerPath = $this->seedFlyerPlaceholder('Masajes Terapéuticos', 'Salud y Bienestar');

        $business = Business::updateOrCreate(
            ['name' => 'Masajes Terapéuticos Sonara'],
            [
                'entrepreneur_profile_id' => $profile->id,
                'slug' => 'masajes-terapeuticos-sonara',
                'description' => 'Alivia el estrés y relaja tu cuerpo con masajes terapéuticos personalizados, a domicilio.',
                'category_id' => $masajes?->id,
                'subcategory_id' => $subMasaje?->id,
                'type' => Business::TYPE_SERVICIO,
                'status' => Business::STATUS_ACTIVO,
                'availability' => 'disponible',
                'currency' => 'PEN',
                'price' => 50.00,
                'payment_methods' => ['efectivo', 'tarjeta', 'yape'],
                'country' => 'Perú',
                'region' => 'Cusco',
                'province' => 'Cusco',
                'district' => 'San Sebastián',
                'address' => 'Av. Ccapacalle 234',
                'reference' => 'Cerca del mercado San Pedro',
                'phone' => '984123456',
                'whatsapp' => '984123456',
                'contact_email' => $entrepreneur->email,
            ],
        );

        $this->seedHours($business, [1, 2, 3, 4, 5, 6], '09:00', '18:00');

        // Segunda empresa de otro emprendedor para demo de aislamiento
        $extraEntrepreneur = User::updateOrCreate(
            ['email' => 'otra-demo@sonara.test'],
            [
                'first_name' => 'José',
                'last_name' => 'Condori',
                'name' => 'José Condori',
                'phone' => '999777888',
                'password' => UserSeeder::DEMO_PASSWORD,
                'email_verified_at' => now(),
            ],
        );
        $extraEntrepreneur->syncRoles(['entrepreneur']);

        $extraProfile = EntrepreneurProfile::updateOrCreate(
            ['user_id' => $extraEntrepreneur->id],
            ['personal_description' => 'Emprendedor textil.'],
        );

        $textilBusiness = Business::updateOrCreate(
            ['name' => 'Textiles Andinos Condori'],
            [
                'entrepreneur_profile_id' => $extraProfile->id,
                'slug' => 'textiles-andinos-condori',
                'description' => 'Tejidos a mano con diseños andinos tradicionales, alpaca baby y lana de oveja.',
                'category_id' => $textiles?->id,
                'subcategory_id' => $subTextil?->id,
                'type' => Business::TYPE_PRODUCTO,
                'status' => Business::STATUS_ACTIVO,
                'availability' => 'disponible',
                'currency' => 'PEN',
                'price' => 80.00,
                'payment_methods' => ['efectivo', 'yape'],
                'region' => 'Cusco',
                'province' => 'Cusco',
                'district' => 'San Jerónimo',
                'phone' => '984654321',
                'whatsapp' => '984654321',
            ],
        );
        $this->seedHours($textilBusiness, [1, 2, 3, 4, 5, 6, 7], '08:00', '20:00');

        // Publicaciones
        $p1 = Publication::updateOrCreate(
            ['slug' => 'masaje-terapeutico-60min'],
            [
                'business_id' => $business->id,
                'entrepreneur_profile_id' => $profile->id,
                'name' => 'Masaje terapéutico 60 min',
                'description' => 'Masaje terapéutico de 60 minutos enfocado en aliviar tensiones musculares y mejorar la circulación.',
                'type' => Publication::TYPE_SERVICIO,
                'price' => 50.00,
                'currency' => 'PEN',
                'status' => Publication::STATUS_PUBLICADA,
                'flyer_image' => $flyerPath,
                'published_at' => now()->subDays(10),
            ],
        );

        $p2 = Publication::updateOrCreate(
            ['slug' => 'masaje-relajante-90min'],
            [
                'business_id' => $business->id,
                'entrepreneur_profile_id' => $profile->id,
                'name' => 'Masaje relajante 90 min',
                'description' => 'Sesión completa de relajación con aromaterapia y música suave.',
                'type' => Publication::TYPE_SERVICIO,
                'price' => 75.00,
                'currency' => 'PEN',
                'status' => Publication::STATUS_PUBLICADA,
                'flyer_image' => $flyerPath,
                'published_at' => now()->subDays(8),
            ],
        );

        $p3 = Publication::updateOrCreate(
            ['slug' => 'chalina-alpaca-baby'],
            [
                'business_id' => $textilBusiness->id,
                'entrepreneur_profile_id' => $extraProfile->id,
                'name' => 'Chalina de alpaca baby',
                'description' => 'Chalina tejida a mano en alpaca baby, disponible en varios colores andinos.',
                'type' => Publication::TYPE_PRODUCTO,
                'price' => 80.00,
                'currency' => 'PEN',
                'status' => Publication::STATUS_PUBLICADA,
                'flyer_image' => $flyerPath,
                'published_at' => now()->subDays(12),
            ],
        );

        // Historial de IA con datos estructurados
        AIGeneration::create([
            'user_id' => $entrepreneur->id,
            'business_id' => $business->id,
            'publication_id' => $p1->id,
            'prompt' => "Crea un flyer publicitario profesional y accesible para un emprendimiento.\n\nNombre: Masaje terapéutico 60 min\nCategoría: Masajes\nPrecio: S/ 50.00\nDescripción: ...\nUbicación: San Sebastián, Cusco\nHorario: Lunes a sábado",
            'style' => 'Moderno',
            'provider' => 'mock',
            'status' => AIGeneration::STATUS_COMPLETADA,
            'image_path' => $flyerPath,
            'generation_time_ms' => 320,
            'cost' => 0,
            'completed_at' => now()->subDays(9),
        ]);

        // Solicitudes de demostración
        CustomerRequest::create([
            'code' => 'SOL-DEMO-001',
            'customer_id' => $customer->id,
            'business_id' => $business->id,
            'publication_id' => $p1->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'item_name' => $p1->name,
            'quantity' => 1,
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => now()->addDays(2)->setTime(16, 0),
            'message' => 'Hola, me gustaría agendar una cita para el masaje terapéutico por la tarde.',
            'status' => CustomerRequest::STATUS_ENVIADA,
        ]);

        CustomerRequest::create([
            'code' => 'SOL-DEMO-002',
            'customer_id' => $customer->id,
            'business_id' => $business->id,
            'publication_id' => $p2->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'item_name' => $p2->name,
            'quantity' => 1,
            'preferred_date' => now()->addDays(5)->toDateString(),
            'message' => '¿Están disponibles los sábados por la mañana?',
            'status' => CustomerRequest::STATUS_VISTA,
            'seen_at' => now(),
        ]);
    }

    protected function seedHours(Business $business, array $days, string $open, string $close): void
    {
        foreach (range(1, 7) as $day) {
            $isClosed = ! in_array($day, $days, true);

            BusinessHour::updateOrCreate(
                ['business_id' => $business->id, 'day_of_week' => $day],
                [
                    'open_time' => $isClosed ? null : $open,
                    'close_time' => $isClosed ? null : $close,
                    'is_closed' => $isClosed,
                ],
            );
        }
    }

    protected function seedFlyerPlaceholder(string $title, string $subtitle): string
    {
        $path = 'flyers/seed-'.Str::slug($title).'.svg';

        if (Storage::disk('public')->exists($path)) {
            return 'storage/'.$path;
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1024" height="1024" viewBox="0 0 1024 1024">
  <rect width="1024" height="1024" fill="#F9F6F1"/>
  <rect x="40" y="40" width="944" height="944" rx="48" fill="#FFFFFF" stroke="#7C3AED" stroke-width="6"/>
  <circle cx="512" cy="340" r="110" fill="#7C3AED"/>
  <path d="M492 340 l22 22 l44 -44" stroke="#FFFFFF" stroke-width="12" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="512" y="560" text-anchor="middle" font-family="Arial, sans-serif" font-size="52" font-weight="bold" fill="#1F2937">{$title}</text>
  <text x="512" y="620" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" fill="#6B7280">{$subtitle}</text>
  <text x="512" y="760" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="#9CA3AF">Emprendimiento verificado · SONARA</text>
</svg>
SVG;

        Storage::disk('public')->put($path, $svg);

        return 'storage/'.$path;
    }
}