/**
 * SONARA — Motor del Asistente de Voz del emprendedor.
 *
 * Capa cliente de Speech-to-Text y Text-to-Speech sobre la Web Speech API.
 * El backend define cada conversación; este módulo habla, escucha y
 * navega, en tres modos (según data-mode del contenedor):
 *  - register: registro (nombres, apellidos, sobre mí, ubicación,
 *    WhatsApp, PIN) y, al terminar, redirección al login.
 *  - login: usuario (nombre completo) + PIN y redirección al dashboard.
 *  - dashboard: menú por voz del dashboard de emprendedor.
 *
 * Reglas que gobiernan este archivo:
 *  - Todo mensaje nuevo se dice en voz alta, no solo en pantalla.
 *  - Cada vez que se abre el micrófono suena antes un pitido corto y leve.
 *  - Todo lo escuchado se pasa a minúsculas, sin tildes ni caracteres
 *    especiales antes de enviarse.
 *  - La captura de voz espera un silencio real antes de dar por terminada
 *    la respuesta (no corta al primer corte que detecte el navegador).
 */
import { pickBestSpanishVoice, readVoicePrefs } from './voice-quality';
import { audioAllowed, beep, normalizeVoiceText } from './voice-core';

const NO_RESPONSE_TIMEOUT_MS = 6000;
const CAPTURE_SILENCE_MS = 2000;
const MAX_LISTEN_CEILING_MS = 15000;

export function registerVoiceAssistant() {
    window.Alpine.data('voiceAssistant', () => ({
        running: false,
        listening: false,
        blocked: false,
        mode: 'register', // register | login | dashboard
        field: null,
        index: 0,
        total: 0,
        // Lo último que dijo el asistente (para "repetir") y la pregunta
        // en curso (para volver a hacerla si no se escucha nada).
        program: '',
        prompt: '',
        message: '',
        typedAnswer: '',
        hasSession: false,
        speechRecognition: null,
        _synth: null,
        _utterance: null,
        _priorText: '',
        _sessionFinal: '',
        _sessionInterim: '',
        _lastSpokenNormalized: '',
        _hasHeardAnything: false,
        _wantsListen: false,
        _restartTimer: null,
        _captureTimer: null,
        _noResponseTimer: null,
        _maxListenTimer: null,
        _restartAttempts: 0,
        _reopenAttempts: 0,
        _lastErrorCode: null,

        init() {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (SR) {
                this.speechRecognition = new SR();
                this.speechRecognition.lang = 'es-PE';
                this.speechRecognition.interimResults = true;
                this.speechRecognition.maxAlternatives = 5;
                this.speechRecognition.continuous = true;

                this.speechRecognition.onresult = (event) => this._onResult(event);
                this.speechRecognition.onerror = (event) => this._onError(event);
                this.speechRecognition.onend = () => this._onEnd();
            }
            this._synth = window.speechSynthesis || null;
            if (this._synth && this._synth.addEventListener) {
                this._synth.addEventListener('voiceschanged', () => this._synth.getVoices());
            }
            const d = this.$root.dataset;
            this.mode = d.mode || 'register';
            this.hasSession = d.hasSession === '1';
            this._tryAutoStart();
        },

        // ---------------------------------------------------------------
        // Arranque: sin teclado cuando el navegador lo permite (p. ej.
        // tras llegar desde otra pantalla de voz del mismo sitio). Si el
        // navegador exige antes un gesto, arranca con la primera tecla o
        // clic en cualquier parte de la página.
        // ---------------------------------------------------------------
        async _tryAutoStart() {
            if (await audioAllowed()) {
                this.begin();
                return;
            }
            this._waitForGesture();
        },

        _waitForGesture() {
            this.blocked = true;
            this.running = false;
            const trigger = () => {
                document.removeEventListener('keydown', trigger, true);
                document.removeEventListener('click', trigger, true);
                this.blocked = false;
                if (!this.running) this.begin();
            };
            document.addEventListener('keydown', trigger, true);
            document.addEventListener('click', trigger, true);
        },

        begin() {
            if (this.running) return;
            this.running = true;
            if (this.mode === 'dashboard') {
                this._applyStep({ type: 'question', speak: this.$root.dataset.prompt });
                return;
            }
            const d = this.$root.dataset;
            const route = this.mode === 'login'
                ? d.startRoute
                : (this.hasSession ? d.resumeRoute : d.startRoute);
            this._get(route);
        },

        restart() {
            this.stopListening();
            this.stopSpeak();
            this.running = false;
            this.hasSession = false;
            this.begin();
        },

        _applyStep(data) {
            this.program = data.speak || '';
            this.message = '';
            if (data.field !== undefined) this.field = data.field;
            if (data.index) { this.index = data.index; this.total = data.total; }

            switch (data.type) {
                case 'question':
                    this.prompt = data.prompt || data.speak;
                    this._play(this.program, { listen: true });
                    break;
                case 'registered':
                case 'success':
                case 'navigate':
                    this._play(this.program, { then: () => { window.location.href = data.redirect; } });
                    break;
                default: // exited, locked
                    this.running = false;
                    this._play(this.program);
            }
        },

        // ---------------------------------------------------------------
        // Captura de voz (robusta): espera un silencio real antes de dar
        // por terminada la respuesta, y avisa por voz si no escucha nada.
        // ---------------------------------------------------------------
        startListening() {
            if (!this.speechRecognition) {
                this.message = 'Tu navegador no soporta reconocimiento de voz. Usa Google Chrome o Microsoft Edge.';
                this._play(this.message);
                return;
            }
            this.stopSpeak();
            this._priorText = '';
            this._sessionFinal = '';
            this._sessionInterim = '';
            this._hasHeardAnything = false;
            this._wantsListen = true;
            this._restartAttempts = 0;
            this._reopenAttempts = 0;
            // Pausa breve tras la síntesis (su "onend" puede llegar antes
            // de que el audio termine de sonar), luego el pitido, y recién
            // al terminar el pitido se abre el micrófono: así no capta ni
            // la cola de la voz del asistente ni el propio pitido.
            setTimeout(async () => {
                if (!this._wantsListen) return;
                await beep();
                if (!this._wantsListen) return;
                try {
                    this.speechRecognition.lang = 'es-PE';
                    this.speechRecognition.start();
                    this.listening = true;
                    this.message = 'Escuchando…';
                    this._startNoResponseWatchdog();
                    this._startMaxListenCeiling();
                } catch (e) {
                    if (String(e).indexOf('already') === -1) {
                        console.error('[voiceAssistant] No se pudo iniciar el reconocimiento:', e);
                    }
                    this.listening = true;
                }
            }, 300);
        },

        stopListening() {
            this._wantsListen = false;
            this.listening = false;
            this._clearTimers();
            if (this.speechRecognition) {
                try { this.speechRecognition.stop(); } catch (e) { /* ignore */ }
            }
        },

        _onResult(event) {
            const results = event.results;
            // Se recalcula TODO lo final de la sesión actual en cada
            // evento: si un resultado pasa de interino a final sin cambiar
            // de posición, rastrear un índice "ya visto" lo perdería.
            let sessionFinal = '';
            for (let i = 0; i < results.length; i++) {
                if (results[i].isFinal) {
                    sessionFinal += results[i][0].transcript;
                }
            }
            this._sessionFinal = sessionFinal;
            this._sessionInterim = this._getInterim(results);

            this._restartAttempts = 0;
            this._reopenAttempts = 0;
            this._hasHeardAnything = true;
            this._clearNoResponseWatchdog();

            const best = this._bestTranscript();
            if (best) this.message = 'He escuchado: "' + normalizeVoiceText(best) + '".';

            this._scheduleCapture();
        },

        _scheduleCapture() {
            if (this._captureTimer) clearTimeout(this._captureTimer);
            this._captureTimer = setTimeout(() => this._finalizeCapture(), CAPTURE_SILENCE_MS);
        },

        // Lo confirmado en sesiones anteriores (si el navegador cortó y se
        // reabrió el micrófono a mitad de la respuesta) + lo final y lo
        // interino de la sesión actual.
        _bestTranscript() {
            return [this._priorText, this._sessionFinal, this._sessionInterim]
                .filter((part) => part && part.trim())
                .join(' ')
                .trim();
        },

        // ¿Lo "escuchado" es el micrófono captando la voz del propio
        // asistente? Se compara por solape de palabras. Solo aplica a
        // capturas largas: una respuesta corta que repite palabras de la
        // pregunta ("ver mis solicitudes") es justamente lo esperado.
        _looksLikeSelfEcho(capturedNormalized) {
            const spoken = this._lastSpokenNormalized;
            if (!spoken || !capturedNormalized) return false;
            const capturedWords = capturedNormalized.split(' ').filter(Boolean);
            if (capturedWords.length < 6) return false;
            const spokenWords = new Set(spoken.split(' ').filter(Boolean));
            const overlap = capturedWords.filter((w) => spokenWords.has(w)).length;
            return (overlap / capturedWords.length) > 0.7;
        },

        _finalizeCapture() {
            this._captureTimer = null;
            const text = this._bestTranscript();
            if (!text) return;
            if (this._looksLikeSelfEcho(normalizeVoiceText(text))) {
                this._priorText = '';
                this._sessionFinal = '';
                this._sessionInterim = '';
                return;
            }
            this.stopListening();
            this._dispatchAnswer(text);
        },

        _getInterim(results) {
            for (let i = results.length - 1; i >= 0; i--) {
                if (!results[i].isFinal) {
                    return results[i][0].transcript;
                }
            }
            return '';
        },

        _dispatchAnswer(text) {
            const normalized = normalizeVoiceText(text);
            if (!normalized) {
                this._play('No le entendí. ' + this.prompt, { listen: true });
                return;
            }
            this.message = 'He escuchado: "' + normalized + '".';
            this._post(this.$root.dataset.processRoute, { transcript: normalized });
        },

        _onError(event) {
            const err = (event && event.error) || '';
            this._lastErrorCode = err;
            if (err === 'no-speech' || err === 'network' || err === 'aborted') {
                // _onEnd() (que siempre sigue a onerror) decide si reintenta.
                if (err === 'no-speech') this.listening = false;
                return;
            }
            console.error('[voiceAssistant] Error de reconocimiento:', err, event);
            this._wantsListen = false;
            this.listening = false;
            if (err === 'not-allowed' || err === 'service-not-allowed') {
                this.message = 'No tengo permiso para usar el micrófono. Actívalo en el navegador.';
            } else if (err === 'audio-capture') {
                this.message = 'No se detecta ningún micrófono. Verifica que esté conectado.';
            } else {
                this.message = 'No pude escuchar (error: ' + err + '). Inténtalo de nuevo.';
            }
            this._play(this.message);
        },

        _onEnd() {
            this.listening = false;
            if (!this._wantsListen) return;

            if (this._hasHeardAnything) {
                // El navegador cortó a mitad de la respuesta: se reabre
                // para seguir escuchando la MISMA respuesta, con límite.
                this._reopenAttempts += 1;
                if (this._reopenAttempts > 4) {
                    this._giveUpMidCapture();
                    return;
                }
                const delay = Math.min(400 * this._reopenAttempts, 2000);
                this._restartTimer = setTimeout(() => {
                    if (!this._wantsListen) return;
                    this._reopenForSameAnswer();
                }, delay);
                return;
            }
            this._scheduleRestart();
        },

        _giveUpMidCapture() {
            this.stopListening();
            if (this._lastErrorCode === 'network' || this._lastErrorCode === 'aborted') {
                this.message = 'Se perdió la conexión con el servicio de reconocimiento de voz. Revisa tu conexión a internet.';
            } else {
                this.message = 'No logro mantener la escucha activa. Inténtalo de nuevo.';
            }
            this._play(this.message);
        },

        _reopenForSameAnswer() {
            this._priorText = this._bestTranscript();
            this._sessionFinal = '';
            this._sessionInterim = '';
            try {
                this.speechRecognition.start();
                this.listening = true;
            } catch (e) {
                // El temporizador de captura igual disparará con lo acumulado.
            }
        },

        _scheduleRestart() {
            if (!this._wantsListen) return;
            this._clearTimers();
            this._restartAttempts += 1;
            if (this._restartAttempts > 4) {
                this._wantsListen = false;
                if (this._lastErrorCode === 'network' || this._lastErrorCode === 'aborted') {
                    this.message = 'No se pudo conectar con el servicio de reconocimiento de voz. Revisa tu conexión a internet.';
                } else {
                    this.message = 'No logro captar audio de tu micrófono. Revisa que no esté silenciado.';
                }
                this._play(this.message);
                return;
            }
            const delay = Math.min(500 * this._restartAttempts, 3000);
            this._restartTimer = setTimeout(() => {
                if (!this._wantsListen || this.listening) return;
                try {
                    this._sessionFinal = '';
                    this._sessionInterim = '';
                    this.speechRecognition.start();
                    this.listening = true;
                } catch (e) {
                    this._restartTimer = null;
                }
            }, delay);
        },

        // Si no se detecta voz en unos segundos, se avisa y se repite la
        // pregunta (con un nuevo pitido), en vez de escuchar en silencio.
        _startNoResponseWatchdog() {
            this._clearNoResponseWatchdog();
            this._noResponseTimer = setTimeout(() => {
                if (!this._wantsListen || this._hasHeardAnything) return;
                this.stopListening();
                this._play('No le escuché. ' + this.prompt, { listen: true });
            }, NO_RESPONSE_TIMEOUT_MS);
        },

        _clearNoResponseWatchdog() {
            if (this._noResponseTimer) { clearTimeout(this._noResponseTimer); this._noResponseTimer = null; }
        },

        // Techo absoluto que no se reinicia con ruido de fondo: evita que
        // el asistente se quede "escuchando para siempre".
        _startMaxListenCeiling() {
            this._clearMaxListenCeiling();
            this._maxListenTimer = setTimeout(() => this._forceFinalize(), MAX_LISTEN_CEILING_MS);
        },

        _clearMaxListenCeiling() {
            if (this._maxListenTimer) { clearTimeout(this._maxListenTimer); this._maxListenTimer = null; }
        },

        _forceFinalize() {
            this._maxListenTimer = null;
            const text = this._bestTranscript();
            const isEcho = text && this._looksLikeSelfEcho(normalizeVoiceText(text));
            this.stopListening();
            if (text && !isEcho) {
                this._dispatchAnswer(text);
                return;
            }
            this._play('No logré entenderle. ' + this.prompt, { listen: true });
        },

        _clearTimers() {
            this._clearNoResponseWatchdog();
            this._clearMaxListenCeiling();
            if (this._captureTimer) { clearTimeout(this._captureTimer); this._captureTimer = null; }
            if (this._restartTimer) { clearTimeout(this._restartTimer); this._restartTimer = null; }
        },

        // ---------------------------------------------------------------
        // Comunicación con el backend
        // ---------------------------------------------------------------
        async _get(url) {
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this._applyStep(await res.json());
            } catch (e) {
                this._serverError();
            }
        },

        async _post(url, body) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(body),
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this._applyStep(await res.json());
            } catch (e) {
                this._serverError();
            }
        },

        _serverError() {
            this.message = 'Hubo un error de comunicación con el servidor. Intentemos de nuevo.';
            this._play(this.message + ' ' + this.prompt, { listen: !!this.prompt });
        },

        sendTypedAnswer() {
            const value = (this.typedAnswer || '').trim();
            if (!value || !this.running) return;
            this.typedAnswer = '';
            this.stopListening();
            this._dispatchAnswer(value);
        },

        // ---------------------------------------------------------------
        // Síntesis de voz
        // ---------------------------------------------------------------
        _play(text, { listen = false, then = null } = {}) {
            if (!this._synth) {
                if (then) then();
                return;
            }
            this.stopSpeak();
            this._lastSpokenNormalized = normalizeVoiceText(String(text));
            const u = new SpeechSynthesisUtterance(String(text));
            const voice = pickBestSpanishVoice(this._synth, 'es');
            const prefs = readVoicePrefs();
            u.lang = (voice && voice.lang) || 'es-419';
            u.rate = prefs.rate;
            u.pitch = prefs.pitch;
            u.volume = prefs.volume;
            if (voice) u.voice = voice;
            const after = () => {
                if (listen) this.startListening();
                if (then) then();
            };
            u.onend = after;
            u.onerror = (e) => {
                // El navegador bloqueó la voz por falta de un gesto previo:
                // se espera la primera tecla o clic y se repite el mensaje.
                if (e && e.error === 'not-allowed') {
                    this._waitForGestureThenReplay(text, { listen, then });
                    return;
                }
                if (e && (e.error === 'interrupted' || e.error === 'canceled')) return;
                after();
            };
            // Referencia viva: Chrome puede descartar el enunciado y no
            // disparar nunca "onend", lo que dejaría el flujo detenido.
            this._utterance = u;
            this._synth.speak(u);
        },

        _waitForGestureThenReplay(text, options) {
            this.blocked = true;
            const trigger = () => {
                document.removeEventListener('keydown', trigger, true);
                document.removeEventListener('click', trigger, true);
                this.blocked = false;
                this._play(text, options);
            };
            document.addEventListener('keydown', trigger, true);
            document.addEventListener('click', trigger, true);
        },

        stopSpeak() {
            if (this._synth) this._synth.cancel();
        },

        // Botones: equivalentes a decir "repetir" / "atrás".
        repeat() {
            this.stopListening();
            this._play(this.program, { listen: true });
        },
        goBack() {
            this.stopListening();
            this._dispatchAnswer('atras');
        },

        destroy() {
            this.stopListening();
            this.stopSpeak();
        },
    }));
}
