<?php

namespace App\Http\Controllers;

use App\Models\CatalogOption;
use App\Models\CatalogType;
use App\Models\Category;
use App\Services\Assistant\VoiceText;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CRUD de los catálogos que el emprendedor ve al registrar su
 * emprendimiento (sectores, etiquetas y tipos que el admin agregue).
 * Las categorías se gestionan en CategoryManagementController.
 */
class CatalogController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function index()
    {
        $types = CatalogType::withCount('options')->orderBy('id')->get();
        $categoriesCount = Category::where('is_active', true)->count();

        return view('admin.catalogs.index', compact('types', 'categoriesCount'));
    }

    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = Str::slug($validated['name']);
        if ($slug === '' || $slug === 'categorias' || CatalogType::where('slug', $slug)->exists()) {
            return back()->withInput()->withErrors(['name' => 'Ya existe un catálogo con ese nombre.']);
        }

        $type = CatalogType::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        $this->audit->log('catalog_type_created', CatalogType::class, $type->id, 'Catálogo "'.$type->name.'" creado.');

        return redirect()->route('admin.catalogs.show', $type)->with('success', 'Catálogo creado.');
    }

    public function destroyType(CatalogType $type)
    {
        abort_if($type->is_system, 403, 'Los catálogos del sistema no se pueden eliminar.');

        $type->delete();
        $this->audit->log('catalog_type_deleted', CatalogType::class, $type->id, 'Catálogo "'.$type->name.'" eliminado.');

        return redirect()->route('admin.catalogs.index')->with('success', 'Catálogo eliminado.');
    }

    public function show(CatalogType $type)
    {
        $type->load('options');

        return view('admin.catalogs.show', compact('type'));
    }

    public function storeOption(Request $request, CatalogType $type)
    {
        $validated = $this->validateOption($request, $type);

        $option = $type->options()->create($validated);
        $this->audit->log('catalog_option_created', CatalogOption::class, $option->id, 'Opción "'.$option->name.'" creada en '.$type->name.'.');

        return back()->with('success', 'Opción creada.');
    }

    public function updateOption(Request $request, CatalogType $type, CatalogOption $option)
    {
        abort_if($option->catalog_type_id !== $type->id, 404);

        $option->update($this->validateOption($request, $type, $option));
        $this->audit->log('catalog_option_updated', CatalogOption::class, $option->id, 'Opción "'.$option->name.'" actualizada en '.$type->name.'.');

        return back()->with('success', 'Opción actualizada.');
    }

    public function destroyOption(CatalogType $type, CatalogOption $option)
    {
        abort_if($option->catalog_type_id !== $type->id, 404);

        $option->delete();
        $this->audit->log('catalog_option_deleted', CatalogOption::class, $option->id, 'Opción "'.$option->name.'" eliminada de '.$type->name.'.');

        return back()->with('success', 'Opción eliminada.');
    }

    protected function validateOption(Request $request, CatalogType $type, ?CatalogOption $option = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Se compara normalizado: "Norte" y "norte" son la misma opción
        // para el reconocimiento de voz.
        $normalized = VoiceText::normalize($validated['name']);
        $duplicate = $type->options()
            ->where('normalized_name', $normalized)
            ->when($option, fn ($q) => $q->whereKeyNot($option->id))
            ->exists();

        validator(
            ['name' => $normalized],
            ['name' => ['required', function ($attribute, $value, $fail) use ($duplicate) {
                if ($duplicate) {
                    $fail('Ya existe esa opción en este catálogo.');
                }
            }]],
            ['name.required' => 'El nombre debe contener letras o números.'],
        )->validate();

        return [
            'name' => trim($validated['name']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }
}
