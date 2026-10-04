<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use App\Notifications\CatalogFreeValueNotification;
use App\Services\Assistant\BusinessVoiceRegistrationService;
use App\Services\Audit\AuditService;
use App\Services\Publishing\BusinessPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Registro de emprendimiento por voz y publicación automática del anuncio. */
class BusinessVoiceController extends Controller
{
    public const PUBLISHED = 'Tu emprendimiento y su imagen han sido publicados exitosamente.';

    public const IMAGE_PENDING = 'Tu emprendimiento ha sido registrado. Se está generando tu imagen de anuncio '
        .'con inteligencia artificial. En unos momentos estará disponible en el muro.';

    public const NOT_PUBLISHED = 'Tu emprendimiento fue registrado, pero no se pudo publicar el anuncio en este momento. '
        .'Lo intentaremos de nuevo más tarde.';

    public function __construct(
        protected BusinessVoiceRegistrationService $assistant,
        protected AuditService $audit,
    ) {
    }

    public function index()
    {
        return view('entrepreneur.businesses.voice', ['hasSession' => $this->assistant->hasSession()]);
    }

    public function start()
    {
        return response()->json($this->assistant->start());
    }

    public function resume()
    {
        return response()->json($this->assistant->resume());
    }

    public function process(Request $request)
    {
        $request->validate(['transcript' => 'required|string|max:500']);

        $step = $this->assistant->process($request->input('transcript'));
        if ($step['type'] !== 'complete') {
            return response()->json($step);
        }

        $business = $this->createBusiness($step['data']);
        $this->assistant->resetSession();

        return response()->json([
            'type' => 'working',
            'speak' => 'Listo, guardé los datos. Ahora estoy creando la imagen con inteligencia artificial y publicando tu anuncio. Espera un momento, por favor.',
            'next' => route('entrepreneur.businesses.voice.publish', $business),
        ]);
    }

    public function publish(Business $business, BusinessPublisher $publisher)
    {
        $this->authorize('update', $business);

        $result = $business->publish_status === Business::PUBLISH_PUBLICADO
            ? ['image' => ! $business->image_pending, 'published' => true]
            : $publisher->publish($business);

        return response()->json([
            'type' => 'navigate',
            'speak' => match (true) {
                ! $result['published'] => self::NOT_PUBLISHED,
                ! $result['image'] => self::IMAGE_PENDING,
                default => self::PUBLISHED,
            },
            'redirect' => route('entrepreneur.dashboard'),
        ]);
    }

    protected function createBusiness(array $data): Business
    {
        $business = DB::transaction(function () use ($data) {
            $business = Business::create([
                'entrepreneur_profile_id' => auth()->user()->entrepreneurProfile->id,
                'name' => $data['nombre'],
                'slug' => Business::uniqueSlug($data['nombre']),
                'description' => $data['descripcion'],
                'type' => $data['tipo'],
                'price_text' => $data['precio'],
                'schedule_text' => $data['horario'],
                'category_id' => $data['categoria']['id'],
                'custom_category' => $data['categoria']['id'] ? null : $data['categoria']['nombre'],
                'sector' => $data['sector']['nombre'],
                'tags' => $data['etiquetas'],
                'phone' => $data['telefono'],
                'whatsapp' => $data['telefono'],
                'address' => $data['ubicacion'],
                'publish_status' => Business::PUBLISH_PENDIENTE,
            ]);

            // El muro lista publicaciones: sin esta, el emprendimiento
            // contaba en su categoría pero no se veía en ninguna parte.
            $business->ensureAdPublication();

            return $business;
        });

        $this->audit->log('business_voice_created', Business::class, $business->id, 'Emprendimiento "'.$business->name.'" registrado por voz.');

        if ($free = $this->freeValues($data)) {
            foreach (User::role('admin')->get() as $admin) {
                $admin->notify(new CatalogFreeValueNotification($business, $free));
            }
        }

        return $business;
    }

    /** @return array<string, string> campo => valor dictado fuera de catálogo */
    protected function freeValues(array $data): array
    {
        $values = [];
        foreach ($data['_libres'] ?? [] as $field) {
            $value = $data[$field];
            $values[$field] = is_array($value) ? ($value['nombre'] ?? implode(', ', $value)) : (string) $value;
        }

        return $values;
    }
}
