# Integración de IA (afiches)

Módulo de **generación de afiches** para publicaciones mediante un proveedor de
imágenes por IA, con un diseño desacoplado y un driver **mock** por defecto para
desarrollo y demostración.

## Arquitectura

```
app/Services/AI/
├── ImageGenerationService   → orquestación: límites, persistencia, historial
├── ImageGenerationProvider  → interfaz del proveedor
├── OpenAIImageProvider      → driver OpenAI (type intentado: gpt-image-1 / dall-e)
├── MockImageProvider        → driver demo (genera SVG local, sin API)
└── PromptBuilder            → construye el prompt accesible en español
```

- `ImageGenerationService` es un singleton registrado en `AppServiceProvider`.
- El proveedor activo se elige por `config('services.ai.provider')`
  (`AI_PROVIDER` en `.env`). Valores: `mock` (default) | `openai` | `azure` | `google`.

## Flujo

1. **`entrepreneur.flyers.index`** (`/emprendedor/publicaciones/{publication}/flyer`)
   muestra el formulario de generación con el historial por publicación.
   El límite disponible se calcula en el controlador y se expone vía
   `data-remaining` en el componente del afiche.
2. **`entrepreneur.flyers.generate`**: valida el límite mensual por usuario
   (`AI_MAX_PER_USER`, `AI_PERIOD_DAYS`), construye el prompt con `PromptBuilder`
   y pide 2 variantes al proveedor.
3. Cada resultado se persiste en `ai_generations` (`estado: en_proceso →
   generado/rechazado`), con `image_url` (mock → SVG en el disco público;
   reales → URL del proveedor), `prompt` y `provider`.
4. **`entrepreneur.flyers.approve`**: el emprendedor elige una variante; se
   publica como `flyer_image` de la publicación y el estado de la generación pasa
   a `aprobado`.
5. **`entrepreneur.flyers.history`** (`/mis-imagenes`): historial completo de
   generaciones con alt descriptivo para accesibilidad.

## Configuración (.env)

```env
AI_PROVIDER=mock        # mock | openai | azure | google
AI_API_KEY=
AI_MODEL=gpt-image-1
AI_SIZE=1024x1024
AI_MAX_PER_USER=20
AI_PERIOD_DAYS=30
```

## Accesibilidad

- El `alt` de cada afiche se genera a partir del prompt (texto alternativo real).
- Los SVGs mock usan color de alto contraste y tipografía legible.

## Notas

- Límite mensual por usuario: `config('services.ai.max_per_user')` (20) sobre
  una ventana de `AI_PERIOD_DAYS` (30) días.
- El `MockImageProvider` no consume créditos ni requiere clave; recomendado para
  pruebas funcionales (los tests usan `mock`).