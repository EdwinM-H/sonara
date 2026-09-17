# Trazabilidad (auditoría y notificaciones)

Sonara registra **qué** ocurrió, **quién** lo hizo y **cuándo**, y notifica a los
actores implicados en cada estado del ciclo del emprendimiento.

## Auditoría

`AuditService` (singleton en `app/Services/Audit`) escribe en `audit_logs`:

- `user_id` — actor autenticado
- `action` — verbo normalizado (`document_uploaded`, `publication_status_changed`, `request_accepted`, …)
- `auditable_type`/`auditable_id` — entidad afectada
- `metadata` — contexto adicional
- `ip_address` — origen

Acciones auditadas (entre otras):

- Carga de documentos de acreditación (`admin` revisa después).
- Cambios de estado de publicaciones (solicitud de aprobación, aprobación, rechazo).
- Transiciones de solicitudes de servicio (creada, aceptada, completada, cancelada, rechazada).
- Acciones administrativas sobre usuarios/emprendedores (suspender, reactivar, aprobar, corrección).
- Registros asistidos y solicitudes de asistencia.

Restricción: el historial se lee solo por **administradores** en
`/admin/auditoria` (`admin.audit.index`).

## Notificaciones

Tipo **database** (tabla `notifications`), con `data.title` y `data.message`.
Enlaces por rol:

- Emprendedor: `entrepreneur.notifications.*`
- Administrador: `admin.notifications.*`
- Un helper en `layouts/navigation` y en `notifications/index` selecciona la ruta
  de marcado según el rol.

Clases (`app/Notifications`):

| Clase | Uso |
|---|---|
| `NewCustomerRequestNotification` | al emprendedor cuando llega una solicitud |
| `RequestStatusNotification` | al cliente cuando su solicitud cambia de estado |
| `PublicationStatusNotification` | al emprendedor al aprobarse/rechazarse su publicación |
| `VerificationStatusNotification` | al emprendedor al cambiar su verificación |
| `AssistanceRequestNotification` | al admin ante solicitudes de asistencia |
| `AssistanceStatusNotification` | al emprendedor cuando el admin responde asistencia |

## Panel administrativo

`/admin/dashboard` resume métricas de trazabilidad: solicitudes por estado,
publicaciones por estado, emprendedores por estado de verificación, categorías
con más actividad y últimos movimientos de auditoría.