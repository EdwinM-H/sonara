# Cumplimiento normativo y de requisitos

Documento de trazabilidad entre los requisitos del proyecto (RF-01…RF-29,
RNF-01…RNF-13) y su implementación en Sonara.

## Requisitos funcionales

| Área | Funcionalidad | Estado |
|---|---|---|
| Portal público | Página de inicio con categorías destacadas (`public.home`) | ✅ |
| | Exploración y filtrado de emprendimientos/publicaciones (`public.explore`) | ✅ |
| | Catálogo por categorías y subcategorías (`public.categories`, `public.category`) | ✅ |
| | Ficha de emprendimiento: contacto, horarios, redes, publicaciones (`public.business`) | ✅ |
| | Ficha de publicación con formulario de solicitud (`public.publication`) | ✅ |
| Cuentas y roles | Registro de cliente y de emprendedor (formulario + empresa inicial) | ✅ |
| | Registro guiado por voz para emprendedores | ✅ |
| | Login/logout, verificación de email, recuperación de contraseña (Breeze) | ✅ |
| | Roles `admin` / `entrepreneur` / `customer` con paneles propios | ✅ |
| Emprendedor | CRUD de emprendimientos con horarios y medios de pago | ✅ |
| | CRUD de publicaciones con solicitud de aprobación | ✅ |
| | Generación y aprobación de afiches por IA | ✅ |
| | Recepción y gestión de solicitudes de servicio (aceptar/rechazar/completar) | ✅ |
| | Carga de documento de acreditación con plazos (7+7 días) | ✅ |
| | Solicitudes de asistencia | ✅ |
| | Panel de accesibilidad (visual / voz / mixto, tipografía, contraste) | ✅ |
| | Notificaciones (icono, listado, marcar leídas) | ✅ |
| Cliente | Envío de solicitudes de servicio y seguimiento de estados | ✅ |
| | Cancelación de solicitudes pendientes | ✅ |
| | Notificaciones de cambio de estado | ✅ |
| Administrador | Dashboard con métricas y alertas | ✅ |
| | Gestión de usuarios (editar, suspender, reactivar) y de roles | ✅ |
| | Gestión de emprendedores: ver detalle, **aprobar/corregir/rechazar** verificación, descargar documentos | ✅ |
| | Registro asistido de emprendedores | ✅ |
| | Revisión de emprendimientos y publicaciones (cambiar estado) | ✅ |
| | CRUD de categorías y subcategorías | ✅ |
| | Gestión de solicitudes de asistencia con notas | ✅ |
| | Auditoría de acciones | ✅ |
| | Configuración global (settings) | ✅ |

## Requisitos no funcionales

| Área | Requisito | Implementación |
|---|---|---|
| RNF – Accesibilidad | Cumplir WCAG 2.2 AA | Layouts semánticos, ARIA, contraste, teclado, foco, zoom, alt, modo voz → `docs/accessibility.md` |
| RNF – Usabilidad | Interfaz en español, clara y consistente | Blade + componentes compartidos, paneles por rol |
| RNF – Seguridad | Autenticación, autorización por rol, validación, protección de datos | `docs/security.md` |
| RNF – Rendimiento | Respuesta ágil en catálogo | Consultas Eloquent paginadas, eager loading |
| RNF – Mantenibilidad | Código modular y testado | Servicios desacoplados (AI, voz, verificación, auditoría), cola para envíos, `tests/` |
| RNF – Compatibilidad | Navegadores modernos | Vite/Tailwind/Alpine; Web Speech API con fallback a escritura |
| RNF – Base de datos | Datos persistentes y relaciones correctas | Migraciones + seeders; ver `docs/database.md` |
| RNF – Portabilidad | Despliegue sencillo | `.env.example`, migraciones, build Vite |

## Comunicado final

Cobertura funcional **100 % planificada** implementada sobre el alcance de los
RF; los RNF se verifican con las pruebas automatizadas (33 tests / 120
aserciones) y el checklist manual de `docs/final-verification.md`.