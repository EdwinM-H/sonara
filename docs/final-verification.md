# Verificación final y manual de pruebas

## Pruebas automatizadas

```bash
php artisan test
```

Cobertura actual:

- `Tests\Feature\SmokeTest` — render de 200 respuestas OK para **todas** las
  páginas públicas y de los tres paneles (admin, emprendedor, cliente) +
  autorización por rol + bloqueo de usuarios suspendidos.
- `Tests\Feature\Auth\*` — autenticación, verificación, confirmación de
  contraseña, reseteo, actualización de contraseña, **registro** (cliente y
  emprendedor con creación de empresa, campo `role` obligatorio).
- `Tests\Feature\ProfileTest` — actualización de perfil (nombre/apellidos/email),
  verificación de email, borrado de cuenta.
- `php artisan view:cache` — compila correctamente todos los Blade.
- `npm run build` — bundling de Vite correcto (Alpine, voz, afiches).

## Checklist manual

### Portal público
- [ ] `/` carga categorías destacadas y enlaces correctos.
- [ ] `/explorar` filtra por categoría, subcategoría y término; paginación.
- [ ] `/categorias/{slug}` lista emprendimientos activos.
- [ ] Ficha de emprendimiento: datos verificados, botones Llamar/WhatsApp/Copiar número, tabla de horarios, grid de publicaciones.
- [ ] Ficha de publicación: formulario de solicitud (usuario logueado como cliente), publicaciones relacionadas, ∞ alternativo auto.

### Registro por voz (emprendedora y referencia)
- [ ] `/registro/voz` con micrófono: responder `primero`, `último`, nombre del emprendimiento, categoría, precio…
- [ ] Comandos `ayuda`, `repetir`, `volver`, `corregir`, `continuar`, `salir`.
- [ ] Alternativa por teclado (modo mixto).
- [ ] Confirmación con contraseña ≥ 8; cuenta creada con rol emprendedor y empresa.

### Panel emprendedor
- [ ] Dashboard con métricas y alertas de documentación.
- [ ] Emprendimientos: crear/editar con horarios y pagos; validación correcta.
- [ ] Publicaciones: crear, editar, solicitar aprobación.
- [ ] Afiches: generar (mock), ver historial, aprobar variante → portada.
- [ ] Solicitudes: aceptar/rechazar/completar; el cliente recibe notificación.
- [ ] Documentación: subir PDF/JPG/PNG ≤ 5 MB, ver plazos, descargar.
- [ ] Asistencia: crear solicitud y seguir estados.
- [ ] Accesibilidad: modo visual/voz/mixto persiste tras recargar.
- [ ] Notificaciones: listado y marcado de leídas.

### Panel cliente
- [ ] Dashboard y listado de solicitudes.
- [ ] Enviar solicitud desde una publicación y cancelar mientras pendiente.

### Panel administrador
- [ ] Dashboard con cifras por estado y alertas.
- [ ] Usuarios: editar rol, suspender y reactivar (el suspendido queda bloqueado).
- [ ] Emprendedores: ver detalle, aprobar/corregir/rechazar, descargar documentos, registro asistido.
- [ ] Emprendimientos: toggle de estado.
- [ ] Publicaciones: cambiar estado (borrador→en_revision→publicado/rechazado).
- [ ] Categorías/subcategorías: CRUD completo.
- [ ] Solicitudes de asistencia con notas y estados.
- [ ] Auditoría: historial legible solo por admin.
- [ ] Configuración: guardar y persistir settings.

### Transversales
- [ ] `.env.example` documenta las variables de IA y voz.
- [ ] `php artisan migrate:fresh --seed` y `php artisan test` en entorno limpio.
- [ ] Navegación 100 % por teclado en los paneles.
- [ ] Idiomas y fechas en español; accesible a 200 % de zoom.

## Estado

**Última ejecución:** 33 tests / 120 aserciones en verde; `view:cache` OK;
`vite build` OK; smoke HTTP de las páginas públicas con respuesta 200.