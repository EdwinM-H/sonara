# Integración por voz (registro guiado)

Requisito clave del proyecto: permitir a una persona con discapacidad visual
**completar el registro de un emprendimiento mediante voz**, sin depender de la
escritura.

## Arquitectura

```
app/Services/Assistant/VoiceAssistantService.php   → máquina de estados (por sesión)
app/Services/Voice/
├── VoiceManager            → selección de proveedores STT/TTS
├── SpeechToTextProvider    → interfaz STT
├── BrowserSpeechToTextProvider → Web Speech API (navegador)
├── TextToSpeechProvider    → interfaz TTS
└── BrowserTextToSpeechProvider → Web Speech API (navegador)

app/Http/Controllers/VoiceRegistrationController.php → endpoints del flujo
resources/js/voice-assistant.js                      → cliente Alpine (navegador)
resources/views/voice/index.blade.php                → UI accesible
```

- `VoiceAssistantService` se **enlaza por sesión** (`voice_registration.<session_id>`)
  en `AppServiceProvider`.
- Los proveedores del navegador no requieren clave; para STT/TTS en la nube se
  configuran `STT_PROVIDER`/`TTS_PROVIDER` (`browser` | `google` | `azure`).

## Máquina de estados

El asistente avanza por pasos definidos en `VoiceAssistantService::STEPS`:

`welcome → first_name → last_name → personal_description → business_name →
business_description → category → subcategory → type → price → offerings →
region → province → district → schedule → phone → whatsapp → email →
contact_extra → username → confirmation → documentation`

En cada paso: **pregunta**, **consejo** (qué decir), **instrucción** y mapeo de
**palabras esperadas** para interpretar la respuesta hablada. Se soporta
corrección de respuestas, navegación `volver`/`continuar` y el comando `ayuda`.

## Interfaz en el navegador (Web Speech API)

`resources/js/voice-assistant.js` (registrado como `voiceAssistant` vía Alpine):

- **Reconocimiento** continuo con `lang="es-PE"` y transcripción en vivo.
- **Síntesis** de la pregunta actual con velocidad/nivel de voz ajustables.
- **Entrada escrita** como alternativa siempre disponible (campo de texto) para
  usuarios con micrófono no disponible (modo `mixto`).
- **Botones de control**: escuchar, pausar, repetir, ayuda, salir; el estado se
  anuncia con `aria-live`.
- Comandos por voz: `ayuda`, `repetir`, `volver`, `corregir`, `continuar`, `salir`.

## Endpoints

| Ruta | Acción |
|---|---|
| `GET /registro/voz` | UI del asistente |
| `GET /registro/voz/iniciar` | inicia/recupera máquina de estados |
| `GET /registro/voz/retomar` | devuelve paso actual |
| `POST /registro/voz/respuesta` | procesa una respuesta |
| `GET /registro/voz/resumen` | revisión de datos capturados |
| `POST /registro/voz/confirmar` | valida contraseña y crea la cuenta |

## Backend

`VoiceRegistrationController`:

- Persiste en el asistente los datos recitados y valida cada respuesta
  (p. ej. precio numérico, email bien formado).
- En `confirm` valida la **contraseña ≥ 8** y crea el usuario con rol
  `entrepreneur`, su perfil, emprendimiento y preferencia de accesibilidad.
- El estado completo vive en la sesión; `confirm` persiste en base de datos.
- El CSRF se lee de `<meta name="csrf-token">` (presente en `layouts.app`).

## Accesibilidad

- Todas las preguntas tienen equivalente visual en pantalla.
- El asistente puede avanzar solo por voz o solo por teclado.
- La confirmación pide usuario y contraseña con verificación por síntesis.

## Asistente global del sitio

Además de la página de registro, **todas las páginas** (layout `layouts.app`)
incluyen un asistente de voz global (`resources/js/site-voice.js` +
`resources/views/partials/voice-assistant.blade.php`):

- **Desde el inicio**: al cargar la página saluda y abre una ventana breve de
  escucha, para que la persona ciega pueda operar sin hacer clic.
- **TDD reactivación**: botón flotante 🎤 o atajo `Alt+V`.
- **Comandos de navegación**: "inicio", "explorar", "categorías",
  "registrarme", "iniciar sesión", "registro por voz", "mi perfil",
  "notificaciones", "mi panel", "leer página", "ayuda", "detente".
- El saludo global se suprime en `/registro/voz`, donde el asistente guiado
  toma el control y **arranca solo** (`data-autostart`).

## Fiabilidad del reconocimiento

- Reconocimiento **continuo** con resultados provisionales en vivo y espera de
  resultado final antes de enviar.
- **Auto-reinicio** cuando la sesión termina en silencio (`no-speech`) o por
  corte, sin mensajes intrusivos.
- Espera real a que la síntesis termine antes de abrir el micrófono (evita
  errores `aborted`/`no-speech` de Chrome).
- **Auto-escucha tras cada pregunta**: el flujo avanza manos libres.
- Selección de una **voz española de calidad** de `speechSynthesis.getVoices()`.
- Manejo granular de errores: permisos (`not-allowed`), red, tiempo.