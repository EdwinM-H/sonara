<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryManagementController extends Controller
{
    public function __construct(protected AuditService $audit)
    {
    }

    // ---------------------------------------------------------------
    // Categorías
    // ---------------------------------------------------------------
    public function index()
    {
        $categories = Category::withCount('subcategories')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(3),
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->log('category_created', Category::class, $category->id, 'Categoría "'.$category->name.'" creada.');

        return redirect()->route('admin.categories.index')->with('success', 'Categoría creada.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->log('category_updated', Category::class, $category->id, 'Categoría "'.$category->name.'" actualizada.');

        return redirect()->route('admin.categories.index')->with('success', 'Categoría actualizada.');
    }

    public function destroy(Category $category)
    {
        $name = $category->name;
        $category->delete();

        $this->audit->log('category_deleted', Category::class, $category->id, 'Categoría "'.$name.'" eliminada.');

        return back()->with('success', 'Categoría eliminada.');
    }

    // ---------------------------------------------------------------
    // Subcategorías
    // ---------------------------------------------------------------
    public function subcategories()
    {
        $subcategories = Subcategory::with('category')->orderBy('sort_order')->get();

        return view('admin.subcategories.index', compact('subcategories'));
    }

    public function createSubcategory()
    {
        $categories = Category::orderBy('sort_order')->get();

        return view('admin.subcategories.create', compact('categories'));
    }

    public function storeSubcategory(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:190', 'unique:subcategories,name'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::random(3),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->log('subcategory_created', Subcategory::class, $subcategory->id, 'Subcategoría "'.$subcategory->name.'" creada.');

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategoría creada.');
    }

    public function editSubcategory(Subcategory $subcategory)
    {
        $categories = Category::orderBy('sort_order')->get();

        return view('admin.subcategories.edit', compact('subcategory', 'categories'));
    }

    public function updateSubcategory(Request $request, Subcategory $subcategory)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:190', 'unique:subcategories,name,'.$subcategory->id],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $subcategory->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->log('subcategory_updated', Subcategory::class, $subcategory->id, 'Subcategoría "'.$subcategory->name.'" actualizada.');

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategoría actualizada.');
    }

    public function destroySubcategory(Subcategory $subcategory)
    {
        $name = $subcategory->name;
        $subcategory->delete();

        $this->audit->log('subcategory_deleted', Subcategory::class, $subcategory->id, 'Subcategoría "'.$name.'" eliminada.');

        return back()->with('success', 'Subcategoría eliminada.');
    }
}