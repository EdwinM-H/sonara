# Accesibilidad (WCAG 2.2)

Sonara está orientada a emprendedores con discapacidad visual. Los criterios se
organizan según los principios de WCAG 2.2 (Perceptible, Operable, Comprensible,
Robusto) con nivel objetivo **AA**.

## Perceptible

- **Texto alternativo**: todas las imágenes decorativas llevan `alt=""` y las
  informativas `alt` descriptivo. Los afiches IA generados incluyen `alt`
  generado a partir del prompt.
- **Contraste**: paleta de alto contraste (texto ≥ 4.5:1, UI ≥ 3:1); el tema de
  alto contraste adicional refuerza los colores en paneles.
- **Escalado**: diseño fluido que soporta zoom a 200% sin pérdida de contenido.
- **Audio/vídeo**: los pasos guiados por voz siempre ofrecen alternativa visual
  (formulario escrito) y viceversa.

## Operable

- **Navegación por teclado**: todos los enlaces, botones y formularios son
  alcanzables y operables con Tab/Enter/Espacio (verificado contra las páginas
  de paneles en las pruebas de humo).
- **Enlaces de salto**: existe un enlace *"Saltar al contenido principal"* en los
  layout públicos y de panel (`#main-content`).
- **Tiempo**: las sesiones no se fuerzan a caducar durante flujos largos; los
  plazos de verificación documental (7+7 días) se comunican de forma explícita.
- **Foco visible**: estados de foco destacados; el comando `ayuda` y la guía de
  voz recitan las opciones disponibles en cada paso.
- **Interacción por voz**: el registro guiado soporta comandos (`ayuda`,
  `repetir`, `volver`, `corregir`, `continuar`, `salir`) y entrada escrita como
  alternativa al micrófono (modo `mixto`).

## Comprensible

- **Idioma**: `lang="es"` en todos los documentos; etiquetas en español.
- **Consistencia**: navegación y encabezados consistentes en paneles por rol;
  componentes `panel-header`, `stat-card` y `status-badge` reutilizados.
- **Asistencia de entrada**: mensajes de error junto al campo, `aria-invalid`,
  `aria-describedby` e instrucciones claras en formularios (sección de
  documentación, formulario de solicitud, registro por voz).
- **Nombres y roles ARIA**: botones de voz anuncian estado (escuchando /
  pensando / respondiendo) con `aria-live="polite"`.

## Robusto

- **HTML semántico**: `main`, `nav`, `section`, `article`, `table` con
  `<caption>` en horarios y formularios correctamente asociados por `for`/`id`.
- **Preferencias guardadas**: `accessibility_preferences` por usuario
  (`navigation_mode`: `visual`, `voz`, `mixto`; tamaño de letra; alto
  contraste) y `accessibility_settings` globales.

## Preferencias de accesibilidad

El panel de accesibilidad permite elegir:

- **Navegación**: visual, guiada por voz o mixta.
- **Tamaño de letra**: normal / grande / extragrande.
- **Alto contraste**: activable en los paneles.
- **Reducción de movimientos**: las animaciones se mantienen mínimas.

Estas preferencias persisten y se aplican en los layout de los paneles; el modo
de voz integra el comando `continuar` para avanzar los pasos del registro.