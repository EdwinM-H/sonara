# Seguridad

## Autenticación y sesión

- Autenticación basada en **Breeze** (guard web, cookies HttpOnly, CSRF en todos
  los formularios POST/PATCH/DELETE).
- **Regeneración de sesión** tras login y tras el confirmar contraseña.
- **Verificación de email** (Breeze) y cierre de contraseña para acciones sensibles.
- Uso de `current_password` en `ProfileUpdateRequest` para cambios de contraseña.

## Autorización por roles

- Middleware personalizado **`active`** (`EnsureUserIsActive`): bloquea a usuarios
  suspendidos/inactivos a nivel de aplicación.
- Middleware **`role:<rol>`** por cada grupo de rutas (`admin`, `entrepreneur`,
  `customer`) — comprobar en `bootstrap/app.php`.
- **Políticas** por modelo (`UserPolicy`, `BusinessPolicy`, `PublicationPolicy`,
  `RequestPolicy`, `VerificationDocumentPolicy`, `AssistanceRequestPolicy`)
  aplicadas con `$this->authorize()` en cada controlador:
  - un emprendedor solo edita sus propios emprendimientos/publicaciones;
  - un cliente solo ve/cancela sus propias solicitudes;
  - los documentos solo los ve su propietario o el administrador;
  - el admin gestiona usuarios, categorías y revisiones.
- El controlador base `App\Http\Controllers\Controller` usa `AuthorizesRequests`.

## Protección de datos

- Documentos de verificación almacenados en disco **local** (no públicos) y
  descargados solo a través de rutas autorizadas (emprendedor propietario o admin).
- Contraseñas con `bcrypt` (rounds 12) vía `Rules\Password::defaults()`.
- Validación estricta en formularios (reglas por campo) y mensajes de error sin
  filtrar datos del servidor.
- Los nombres de archivo subidos se sanean y se almacenan con nombre UUID.

## Riesgos mitigados

| Riesgo | Mitigación |
|---|---|
| CSRF | Token en todos los formularios (`@csrf`) |
| XSS | Escapado por Blade (`{{ }}`); rutas *no* se recitan del prompt de IA |
| IP/uso excesivo IA | Límite mensual por usuario (`AI_MAX_PER_USER`/`AI_PERIOD_DAYS`) |
| Subida de archivos | `mimes:pdf,jpg,jpeg,png,webp`, `max:5120` KB |
| Fuerza bruta login | `LoginRequest` de Breeze con rate limit |
| Abuso de voz | Máquina de estados por sesión con confirmación y reintentos |
| Trazar auditoría | `AuditService` registra acción, actor, entidad y contexto en cada escritura relevante |

## Recomendaciones de despliegue

- `APP_DEBUG=false` y clave de .env protegida.
- HTTPS obligatorio (`APP_URL` https; `SESSION_SECURE_COOKIE=true`).
- Copias de seguridad de la base de datos y del disco local de documentos.
- Rotación de claves de proveedores de IA (`AI_API_KEY`) y de STT/TTS.