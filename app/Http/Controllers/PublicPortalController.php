<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\CatalogOption;
use App\Models\CatalogType;
use App\Models\Category;
use App\Models\EntrepreneurProfile;
use App\Models\Publication;
use App\Services\Assistant\VoiceText;
use Illuminate\Http\Request;

class PublicPortalController extends Controller
{
    /**
     * Home: búsqueda, categorías y sectores del admin, destacados (primero
     * los que tienen imagen IA) y una fila por cada sector con anuncios.
     */
    public function home()
    {
        $categories = Category::where('is_active', true)
            ->withCount('visiblePublications')
            ->orderBy('sort_order')->orderBy('name')->get();

        $visible = fn () => Publication::with(['business.category', 'business.entrepreneurProfile'])
            ->where('status', Publication::STATUS_PUBLICADA)
            ->whereHas('business', fn ($q) => $q->where('status', Business::STATUS_ACTIVO));

        $featured = $visible()
            ->orderByRaw('flyer_image IS NULL')
            ->latest('published_at')
            ->take(8)->get();

        // Un carrusel por sector del catálogo del admin, en su orden; los
        // anuncios se agrupan comparando el nombre normalizado (sin tildes
        // ni mayúsculas) y se omiten los sectores sin anuncios.
        $bySector = $visible()
            ->whereHas('business', fn ($q) => $q->whereNotNull('sector')->where('sector', '!=', ''))
            ->latest('published_at')
            ->get()
            ->groupBy(fn (Publication $p) => VoiceText::normalize($p->business->sector));

        $sectors = CatalogType::where('slug', CatalogType::SECTORES)->first()?->options
            ->map(fn (CatalogOption $option) => [
                'name' => $option->name,
                'publications' => ($bySector[$option->normalized_name] ?? collect())->take(12),
            ])
            ->filter(fn ($sector) => $sector['publications']->isNotEmpty())
            ->values() ?? collect();

        $totalBusinesses = Business::where('status', Business::STATUS_ACTIVO)->count();

        return view('public.home', compact('categories', 'featured', 'sectors', 'totalBusinesses'));
    }

    public function explore(Request $request)
    {
        $query = Publication::query()->with(['business.category', 'business.hours', 'business.entrepreneurProfile.user'])
            ->where('status', Publication::STATUS_PUBLICADA)
            ->whereHas('business', fn ($q) => $q->where('status', 'activo'));

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('business', function ($b) use ($search) {
                        $b->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('entrepreneurProfile.user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                            ->orWhere('businesses.region', 'like', "%{$search}%")
                            ->orWhere('businesses.district', 'like', "%{$search}%");
                    });
            });
        }

        if ($category = $request->input('category')) {
            $query->whereHas('business', fn ($q) => $q->where('category_id', $category));
        }

        if ($subcategory = $request->input('subcategory')) {
            $query->whereHas('business', fn ($q) => $q->where('subcategory_id', $subcategory));
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($region = trim((string) $request->input('region'))) {
            $query->whereHas('business', fn ($q) => $q->where('region', $region));
        }

        if ($district = trim((string) $request->input('district'))) {
            $query->whereHas('business', fn ($q) => $q->where('district', $district));
        }

        if ($min = $request->integer('price_min', null)) {
            $query->where(function ($q) use ($min) {
                $q->where('price', '>=', $min)->orWhere('price_max', '>=', $min);
            });
        }

        if ($max = $request->integer('price_max', null)) {
            $query->where(function ($q) use ($max) {
                $q->where('price', '<=', $max)->orWhere('price_min', '<=', $max);
            });
        }

        switch ($request->input('sort')) {
            case 'precio_asc':
                $query->orderByRaw('COALESCE(price, price_min) asc');
                break;
            case 'precio_desc':
                $query->orderByRaw('COALESCE(price, price_min) desc');
                break;
            case 'recientes':
                $query->latest('published_at');
                break;
            default:
                $query->latest('published_at');
        }

        $publications = $query->paginate(12)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('sort_order')->get();

        return view('public.explore', compact('publications', 'categories'));
    }

    public function categories()
    {
        $categories = Category::where('is_active', true)
            ->with(['subcategories' => fn ($q) => $q->where('is_active', true)])
            ->withCount('visiblePublications')
            ->orderBy('sort_order')->get();

        return view('public.categories', compact('categories'));
    }

    public function category(Category $category)
    {
        abort_if(! $category->is_active, 404);

        $publications = Publication::with(['business.category', 'business.hours', 'business.entrepreneurProfile.user'])
            ->where('status', Publication::STATUS_PUBLICADA)
            ->whereHas('business', fn ($q) => $q->where('status', 'activo')->where('category_id', $category->id))
            ->latest('published_at')
            ->paginate(12);

        return view('public.category', compact('category', 'publications'));
    }

    public function business(Business $business)
    {
        abort_if($business->status !== 'activo', 404);

        $published = $business->publishedPublications()->get();

        return view('public.business', compact('business', 'published'));
    }

    /** Perfil público del emprendedor con todos sus emprendimientos publicados. */
    public function entrepreneur(EntrepreneurProfile $profile)
    {
        abort_unless($profile->user?->isEntrepreneur() && $profile->user->isActive(), 404);

        $businesses = $profile->businesses()
            ->with(['category', 'adPublication', 'entrepreneurProfile'])
            ->where('status', Business::STATUS_ACTIVO)
            ->whereHas('publishedPublications')
            ->latest()
            ->get();

        return view('public.entrepreneur', compact('profile', 'businesses'));
    }

    public function publication(Publication $publication)
    {
        abort_if($publication->status !== Publication::STATUS_PUBLICADA || $publication->business->status !== 'activo', 404);

        $publication->load('images');

        $related = Publication::with(['business'])
            ->where('status', Publication::STATUS_PUBLICADA)
            ->where('id', '!=', $publication->id)
            ->whereHas('business', fn ($q) => $q->where('category_id', $publication->business->category_id))
            ->inRandomOrder()->take(4)->get();

        return view('public.publication', compact('publication', 'related'));
    }
}