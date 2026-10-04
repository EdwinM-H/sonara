<?php

namespace App\Http\Controllers;

use App\Services\Catalog\CatalogService;

/** API de solo lectura de los catálogos, para el panel del emprendedor. */
class CatalogApiController extends Controller
{
    public function index(CatalogService $catalogs)
    {
        return response()->json(['data' => $catalogs->all()]);
    }

    public function show(string $slug, CatalogService $catalogs)
    {
        $catalog = $catalogs->get($slug);
        abort_if($catalog === null, 404);

        return response()->json(['data' => ['tipo' => $slug] + $catalog]);
    }
}
