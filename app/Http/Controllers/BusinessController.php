<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function index()
    {
        $businesses = Business::withCount(['publications as publications_count'])
            ->where('entrepreneur_profile_id', auth()->user()->entrepreneurProfile->id)
            ->orderByDesc('created_at')
            ->get();

        return view('entrepreneur.businesses.index', compact('businesses'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        $days = BusinessHour::$days;

        return view('entrepreneur.businesses.create', compact('categories', 'days'));
    }

    public function store(Request $request)
    {
        $validated = $this->validationRules($request);

        $profile = auth()->user()->entrepreneurProfile;

        $business = Business::create([
            'entrepreneur_profile_id' => $profile->id,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'type' => $validated['type'],
            'availability' => $validated['availability'],
            'currency' => $validated['currency'] ?? 'PEN',
            'price' => $validated['price'] ?? null,
            'price_min' => $validated['price_min'] ?? null,
            'price_max' => $validated['price_max'] ?? null,
            'payment_methods' => $validated['payment_methods'] ?? null,
            'country' => $validated['country'] ?? 'Perú',
            'region' => $validated['region'] ?? 'Cusco',
            'province' => $validated['province'] ?? null,
            'district' => $validated['district'] ?? null,
            'address' => $validated['address'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
        ]);

        $this->saveHours($business, $request->input('hours', []));

        $this->audit->log('business_created', Business::class, $business->id, 'Emprendimiento "'.$business->name.'" creado.');

        return redirect()->route('entrepreneur.businesses.index')
            ->with('success', 'Emprendimiento creado correctamente.');
    }

    public function edit(Business $business)
    {
        $this->authorize('update', $business);

        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();
        $subcategories = Subcategory::where('category_id', $business->category_id)->where('is_active', true)->get();
        $days = BusinessHour::$days;

        return view('entrepreneur.businesses.edit', compact('business', 'categories', 'subcategories', 'days'));
    }

    public function update(Request $request, Business $business)
    {
        $this->authorize('update', $business);

        $validated = $this->validationRules($request);

        $business->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'subcategory_id' => $validated['subcategory_id'] ?? null,
            'type' => $validated['type'],
            'availability' => $validated['availability'],
            'currency' => $validated['currency'] ?? 'PEN',
            'price' => $validated['price'] ?? null,
            'price_min' => $validated['price_min'] ?? null,
            'price_max' => $validated['price_max'] ?? null,
            'payment_methods' => $validated['payment_methods'] ?? null,
            'country' => $validated['country'] ?? 'Perú',
            'region' => $validated['region'] ?? 'Cusco',
            'province' => $validated['province'] ?? null,
            'district' => $validated['district'] ?? null,
            'address' => $validated['address'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
        ]);

        $business->hours()->delete();
        $this->saveHours($business, $request->input('hours', []));

        $this->audit->log('business_updated', Business::class, $business->id, 'Emprendimiento "'.$business->name.'" actualizado.');

        return redirect()->route('entrepreneur.businesses.index')
            ->with('success', 'Emprendimiento actualizado correctamente.');
    }

    public function destroy(Business $business)
    {
        $this->authorize('delete', $business);

        $name = $business->name;
        $business->delete();

        $this->audit->log('business_deleted', Business::class, $business->id, 'Emprendimiento "'.$name.'" eliminado.');

        return redirect()->route('entrepreneur.businesses.index')
            ->with('success', 'Emprendimiento eliminado.');
    }

    protected function validationRules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'type' => ['required', 'in:producto,servicio'],
            'availability' => ['required', 'in:disponible,bajo_pedido,agotado'],
            'currency' => ['nullable', 'string', 'max:3'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'payment_methods' => ['nullable', 'array'],
            'region' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'hours' => ['nullable', 'array'],
            'hours.*.open' => ['nullable', 'date_format:H:i'],
            'hours.*.close' => ['nullable', 'date_format:H:i'],
            'hours.*.closed' => ['nullable', 'boolean'],
        ]);
    }

    protected function saveHours(Business $business, array $hours): void
    {
        foreach ($hours as $day => $data) {
            $dayNumber = (int) $day;
            if ($dayNumber < 1 || $dayNumber > 7) {
                continue;
            }

            $closed = ! empty($data['closed']) || empty($data['open']) || empty($data['close']);

            BusinessHour::updateOrCreate(
                ['business_id' => $business->id, 'day_of_week' => $dayNumber],
                [
                    'open_time' => $closed ? null : $data['open'],
                    'close_time' => $closed ? null : $data['close'],
                    'is_closed' => $closed,
                ],
            );
        }
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Business::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}