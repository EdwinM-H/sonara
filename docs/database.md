# Base de datos

Motor recomendado: MySQL 8+/MariaDB. Para pruebas unitarias se usa SQLite en memoria (`phpunit.xml`).

## Esquema (migraciones)

| Migración | Tabla(s) | Propósito |
|---|---|---|
| `0001_01_01_000000` | `users`, `password_reset_tokens`, `sessions` | Usuarios (first_name, last_name, name, email, phone, status), reseteos y sesiones |
| `0001_01_01_000001` | `cache`, `cache_locks` | Caché |
| `0001_01_01_000002` | `jobs`, `job_batches`, `failed_jobs` | Cola |
| `2026_09_14_195746` | `roles`, `permissions`, `model_has_roles`, `role_has_permissions` | Spatie permissions |
| `2026_09_14_200001` | `categories`, `subcategories` | Catálogo: categorías y subcategorías con `slug`, `is_active`, `sort_order` |
| `2026_09_14_200002` | `entrepreneur_profiles`, `customer_profiles`, `accessibility_preferences` | Perfiles por rol y preferencias de accesibilidad |
| `2026_09_14_200003` | `businesses`, `business_hours`, `business_payment_methods` | Emprendimientos, horarios y medios de pago |
| `2026_09_14_200004` | `publications` | Publicaciones (estado, precio, moneda, región, flyer) |
| `2026_09_14_200005` | `ai_generations` | Historial afiches IA (estado, prompts, urls, motivo de rechazo) |
| `2026_09_14_200006` | `verification_documents`, `assistance_requests` | Documentos y solicitudes de asistencia |
| `2026_09_14_200007` | `requests`, `audit_logs` | Solicitudes de servicio y auditoría |
| `2026_09_14_200008` | `accessibility_settings` | Configuración global del sistema |
| `2026_09_14_200009` | `notifications` | Notificaciones tipo database |

## Entidades centrales

### `users`
Campos clave: `first_name`, `last_name`, `name`, `email`, `phone`, `status`
(`STATUS_ACTIVO`, `STATUS_SUSPENDIDO`, …). Relaciones: roles (Spatie),
`entrepreneurProfile`, `customerProfile`, `notifications`.

### `entrepreneur_profiles`
- `verification_status`: `pendiente_documento | documento_enviado | en_revision | aprobado | rechazado`
- Plazos: `registered_fully_at`, `document_deadline_at`, `validation_deadline_at`, `verified_at`
- Verificación: 7 días para subir documento + 7 días para revisión (`VerificationService::DOCUMENT_DEADLINE_DAYS = 7`, `VALIDATION_DEADLINE_DAYS = 7`)

### `businesses`
`name`, `slug`, `description`, `category_id`, `type` (`producto`/`servicio`),
`availability`, `currency`, `price`, `region`, `phone`, `whatsapp`, `status`.
Horarios por día de semana (`business_hours`) y medios de pago aceptados.

### `publications`
`name`, `slug`, `description`, `type`, `price`, `currency`, `status`,
`flyer_image`, `published_at`. Estados vía mapa de transiciones
(`Publication::TRANSITIONS`), p. ej.: `borrador → en_revision → publicado`.

### `requests` (solicitudes de servicio)
Cliente → publicación: `quantity`, `preferred_date_time`, `message`, `status`
(pendiente/aceptada/completada/cancelada/rechazada). Relación con `publication` y `user`.

### `ai_generations`
`publication_id`, `user_id`, `status`, `prompt`, `image_url`, `rejection_reason`,
`provider`, `created_at`. Límite mensual configurables: `AI_MAX_PER_USER` (20),
`AI_PERIOD_DAYS` (30).

### `audit_logs`
`user_id`, `action`, `auditable_type`, `auditable_id`, `metadata`, `ip_address`,
`created_at`. Registrado vía `AuditService`.

## Seeders

- `RoleSeeder` — roles `admin`, `entrepreneur`, `customer`
- `CategorySeeder` — categorías y subcategorías
- `SettingsSeeder` — `accessibility_settings` por defecto
- `UserSeeder` — cuentas demo (ver README)
- `DemoDataSeeder` — emprendimiento, publicación y datos de ejemplo verificados