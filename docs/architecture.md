# Arquitectura

## Pila tecnológica

- **Backend**: Laravel 12 (PHP 8.2)
- **Frontend**: Blade + Tailwind CSS + Alpine.js (vía Vite)
- **Base de datos**: MySQL / MariaDB (SQLite `:memory:` en pruebas)
- **Autenticación/autorización**: Breeze + package Spatie `laravel-permission` (roles `admin`, `entrepreneur`, `customer`)
- **Colas**: cola por base de datos (`database`) para notificaciones
- **IA (afiches)**: abstracción de proveedor con driver `mock`, `openai`, `azure`, `google`
- **Voz**: Web Speech API (reconocimiento y síntesis en el navegador) con backend de máquina de estados

## Capas

```
routes/            → web, public, customer, entrepreneur, admin, auth
app/Http/Controllers → orquestación por módulo/rol
app/Services        → lógica de negocio desacoplada
    AI/                → ImageGenerationService + drivers
    Assistant/         → VoiceAssistantService (máquina de estados)
    Verification/      → plazos y transición documental
    Audit/             → registro de auditoría
    Voice/             → gestión de proveedores STT/TTS (reserva)
app/Policies        → autorización basada en modelos
app/Models          → Eloquent (User, Category, Business, Publication, …)
app/Notifications   → notificaciones tipo database
resources/views     → Blade (portales públicos, paneles por rol, auth, componentes)
resources/js        → Alpine + módulos voz/afiches
database/migrations → esquema
database/seeders    → roles, categorías, settings, demo
```

## Patrones y decisiones

- **Role-centric routing**: cada rol tiene un prefijo y grupo de middleware propio
  (`/emprendedor`, `/cliente`, `/admin`) con `role:<rol>`.
- **Blade layout por rol**: `layouts.panel` (sidebar + header compartidos) y
  `layouts.panel-entrepreneur/customer/admin` que solo pasan datos — **no** deben
  definir `@section('content')` (heredan la sección del panel base). Las páginas
  muestran su contenido vía `@section('panel-content')`.
- **Servicios singleton** registrados en `AppServiceProvider`: auditoría, verificación,
  generación de imágenes y gestor de voz. `VoiceAssistantService` se enlaza por sesión.
- **Máquina de estados** explícita para `Publication`, `Request` y verificación
  documental; la transición se valida antes de persistir.
- **Dispatcher de `DashboardController`**: la ruta `/dashboard` redirige al panel
  correspondiente según el rol (usada por Breeze tras login/confirmación de contraseña,
  `routes/web.php`).

## Flujos principales

1. **Registro cliente/emprendedor**: formulario clásico (`/register`) o registro guiado
   por voz (`/registro/voz`). El emprendedor inicia ventana de verificación de 7+7 días.
2. **Alta de emprendimiento/publicación**: el emprendedor crea, luego solicita aprobación;
   el admin revisa y cambia estado (borrador → en_revision → publicado / rechazado).
3. **Verificación documental**: el emprendedor sube su documento de acreditación; el admin
   aprueba/solicita corrección/rechaza; cada acción queda auditada y notificada.
4. **Afiche con IA**: el emprendedor genera (con límite mensual por usuario), elige una
   variante, la aprueba como portada de la publicación.
5. **Solicitud de servicio**: cliente envía solicitud → emprendedor acepta/rechaza → completa;
   estados notificados y auditados.
6. **Asistencia**: emprendedor o admin registran solicitudes de asistencia con seguimiento.

## Convenciones

- Nombres de rutas con prefijo por rol (`admin.businesses`, `entrepreneur.documents`, `customer.dashboard`).
- `resources/views/admin/…` comparte layouts de panel; hay ponentes reutilizables: `stat-card`,
  `status-badge`, `panel-header`, `publication-card`.
- Los errores HTTP personalizados viven en `resources/views/errors/` (403, 404, 419, 429, 500, 503).