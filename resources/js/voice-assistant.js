/**
 * SONARA — Motor del Asistente de Voz (registro guiado).
 *
 * Capa cliente de Speech-to-Text y Text-to-Speech sobre la Web Speech API.
 * El backend (VoiceAssistantService) define la máquina de estados; este
 * módulo orquesta la conversación, la captura de voz y la confirmación.
 *
 * Mejoras frente a la primera versión:
 *  - Reconocimiento continuo con resultados provisionales (live).
 *  - Reinicio automático cuando la escucha termina sin reconocer.
 *  - Espera real a que la síntesis termine antes de abrir el micrófono.
 *  - Auto-escucha tras cada pregunta (manos libres "desde que se habla").
 *  - Selección de una voz española de calidad para la síntesis.
 *  - Manejo granular de errores (no-speech, permisos, red).
 */
import { pickBestSpanishVoice, readVoicePrefs } from './voice-quality';

export function registerVoiceAssistant() {
    window.Alpine.data('voiceAssistant', () => ({
        running: false,
        listening: false,
        phase: 'idle', // idle | question | confirm | review | blocked | exited
        state: 'welcome',
        program: '',
        message: '',
        typedAnswer: '',
        password: '',
        current: null,
        history: [],
        speechRecognition: null,
        _synth: null,
        _lastFinalIndex: 0,
        _wantsListen: false,
        _restartTimer: null,
        _silenceTimer: null,

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

            // No se autoarranca ni se abre el micrófono al cargar la página:
            // los navegadores exigen un gesto real del usuario (clic o
            // tecla) antes de conceder el micrófono, así que el flujo
            // empieza únicamente cuando el usuario presiona "Comenzar /
            // Retomar" (ver botón @click="autoStart()" en la vista).
        },

        // ---------------------------------------------------------------
        // Arranque / reanudación
        // ---------------------------------------------------------------
        async autoStart() {
            this.running = true;
            this.typedAnswer = '';
            this.phase = 'question';
            // Retomar progreso guardado; si no existe, iniciar de cero.
            try {
                const res = await fetch(this.$root.dataset.resumeRoute, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('sin progreso');
                const data = await res.json();
                this._applyStep(data);
            } catch (e) {
                await this.start();
            }
        },

        async start() {
            this.running = true;
            this.typedAnswer = '';
            this.phase = 'question';
            try {
                const res = await fetch(this.$root.dataset.startRoute, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json();
                this._applyStep(data);
            } catch (e) {
                this.message = 'Ocurrió un error al iniciar. Intenta de nuevo.';
            }
        },

        _applyStep(data) {
            this.current = data;
            this.phase = data.type;
            this.state = data.state || this.state;
            this.program = data.ssml || data.message || '';
            this.message = data.message || '';
            if (data.type === 'question' || data.type === 'confirm') {
                this._play(this.program, { listen: true });
            } else if (data.type === 'review' || data.type === 'blocked') {
                this.showReview();
            } else {
                this._play(this.program, { listen: false });
            }
        },

        // ---------------------------------------------------------------
        // Captura de voz (robusta)
        // ---------------------------------------------------------------
        startListening() {
            if (!this.speechRecognition) {
                this.message = 'Tu navegador no soporta reconocimiento de voz. Escribe tu respuesta con el teclado.';
                return;
            }
            this.stopSpeak();
            this._lastFinalIndex = 0;
            this._wantsListen = true;
            // Retardo breve: Chrome falla si se abre el micrófono mientras
            // todavía suena la síntesis.
            setTimeout(() => {
                if (!this._wantsListen) return;
                try {
                    this.speechRecognition.lang = 'es-PE';
                    this.speechRecognition.start();
                    this.listening = true;
                    this.message = 'Escuchando… di tu respuesta.';
                    this._startSilenceWatchdog();
                } catch (e) {
                    // Ya estaba activo: se ignora.
                    if (String(e).indexOf('already') === -1) {
                        this.listening = true;
                    }
                }
            }, 350);
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
            let finalText = '';
            const results = event.results;
            for (let i = this._lastFinalIndex; i < results.length; i++) {
                const result = results[i];
                if (result.isFinal) {
                    finalText += result[0].transcript;
                    this._lastConfidence = result[0].confidence ?? 1;
                }
            }
            this._lastFinalIndex = Math.max(this._lastFinalIndex, results.length - 1);

            const interim = this._getInterim(results);
            if (finalText.trim()) {
                this.message = 'He escuchado: "' + finalText.trim() + '".';
                this._sendOrRetry(finalText.trim());
                this.stopListening();
            } else if (interim.trim()) {
                this.message = '…' + interim.trim();
            }
        },

        _getInterim(results) {
            for (let i = results.length - 1; i >= 0; i--) {
                if (!results[i].isFinal) {
                    return results[i][0].transcript;
                }
            }
            return '';
        },

        _onError(event) {
            const err = (event && event.error) || '';
            if (err === 'no-speech') {
                // Silencio: se reinicia la escucha sin molestar.
                this.listening = false;
                this._scheduleRestart();
                return;
            }
            if (err === 'not-allowed' || err === 'service-not-allowed') {
                this._wantsListen = false;
                this.listening = false;
                this.message = 'No tengo permiso para usar el micrófono. Actívalo en el navegador o escribe tu respuesta.';
                return;
            }
            if (err === 'network' || err === 'aborted') {
                this._scheduleRestart();
                return;
            }
            this.listening = false;
            this.message = 'No pude escuchar (error: ' + err + '). Escribe tu respuesta o inténtalo de nuevo.';
        },

        _onEnd() {
            this.listening = false;
            this._clearSilence();
            if (this._wantsListen) {
                // Terminó sin reconocer (silencio o corte): reintenta.
                this._scheduleRestart();
            }
        },

        _scheduleRestart() {
            if (!this._wantsListen) return;
            this._clearTimers();
            this._restartTimer = setTimeout(() => {
                if (!this._wantsListen || this.listening) return;
                try {
                    this.speechRecognition.start();
                    this.listening = true;
                    this.message = 'Escuchando…';
                } catch (e) {
                    this._restartTimer = null;
                }
            }, 500);
        },

        _startSilenceWatchdog() {
            this._clearSilence();
            this._silenceTimer = setTimeout(() => {
                // Sin reconocimiento en 15 s: ciclo de reinicio.
                if (this._wantsListen && this.speechRecognition) {
                    try { this.speechRecognition.stop(); } catch (e) { /* ignore */ }
                }
            }, 15000);
        },

        _clearSilence() {
            if (this._silenceTimer) { clearTimeout(this._silenceTimer); this._silenceTimer = null; }
        },

        _clearTimers() {
            if (this._silenceTimer) { clearTimeout(this._silenceTimer); this._silenceTimer = null; }
            if (this._restartTimer) { clearTimeout(this._restartTimer); this._restartTimer = null; }
        },

        _sendOrRetry(text) {
            const final = text.trim().replace(/\s+/g, ' ').toLowerCase();
            const confidence = this._lastConfidence ?? 1;
            if (final && confidence < 0.45) {
                this.message = 'No estoy muy seguro de haber entendido. Repítelo, por favor.';
                this._scheduleRestart();
                return;
            }
            this.send(text);
        },

        // ---------------------------------------------------------------
        // Envío al backend
        // ---------------------------------------------------------------
        async send(text) {
            if (!this.running) return;
            this.message = '';
            try {
                const res = await fetch(this.$root.dataset.processRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.$root.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ transcript: text }),
                });
                const data = await res.json();
                if (data.type === 'exited') {
                    this.running = false;
                    this.phase = 'exited';
                    this.program = data.message;
                    this._play(data.message, { listen: false });
                    return;
                }
                this._applyStep(data);
            } catch (e) {
                this.message = 'Hubo un error de comunicación con el servidor.';
            }
        },

        sendTypeAnswer() {
            const value = (this.typedAnswer || '').trim();
            if (!value || !this.running) return;
            this.typedAnswer = '';
            this.send(value);
        },

        // ---------------------------------------------------------------
        // Revisión y confirmación
        // ---------------------------------------------------------------
        async showReview() {
            try {
                const res = await fetch(this.$root.dataset.reviewRoute, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.phase = 'review';
                const list = this.$root.querySelector('#review-list');
                list.innerHTML = '';
                for (const [key, value] of Object.entries(data.summary || {})) {
                    const dt = document.createElement('dt');
                    dt.className = 'font-semibold';
                    dt.textContent = key + ':';
                    const dd = document.createElement('dd');
                    dd.textContent = value || '—';
                    const div = document.createElement('div');
                    div.className = 'flex gap-2';
                    div.append(dt, dd);
                    list.appendChild(div);
                }
                this.program = 'Revisa tus datos en pantalla o escucha el resumen. Luego crea tu contraseña y confirma.';
                this._play('Revisa tus datos. Crea una contraseña de al menos 8 caracteres y presiona confirmar y crear cuenta.', { listen: false });
            } catch (e) {
                this.message = 'No se pudo cargar el resumen.';
            }
        },

        async confirm() {
            if (this.password.length < 8) {
                this.message = 'La contraseña debe tener al menos 8 caracteres.';
                this._play(this.message, { listen: false });
                return;
            }
            try {
                const res = await fetch(this.$root.dataset.confirmRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.$root.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ password: this.password }),
                });
                const data = await res.json();
                if (data.success) {
                    this.message = '¡Tu cuenta fue creada!';
                    this._play('Tu cuenta fue creada correctamente. Bienvenida o bienvenido a SONARA.', { listen: false });
                    setTimeout(() => { window.location.href = data.redirect; }, 2500);
                } else {
                    this.message = data.message || 'Ocurrió un error al crear la cuenta.';
                    this._play(this.message, { listen: false });
                }
            } catch (e) {
                this.message = 'Ocurrió un error al crear la cuenta.';
            }
        },

        // ---------------------------------------------------------------
        // Síntesis de voz
        // ---------------------------------------------------------------
        _play(text, { listen = false } = {}) {
            if (typeof window === 'undefined' || !this._synth) return;
            this.stopSpeak();
            const u = new SpeechSynthesisUtterance(String(text));
            const voice = this._pickVoice('es');
            const prefs = readVoicePrefs();
            u.lang = (voice && voice.lang) || 'es-419';
            u.rate = prefs.rate;
            u.pitch = prefs.pitch;
            u.volume = prefs.volume;
            if (voice) u.voice = voice;
            if (listen) {
                u.onend = () => this.startListening();
                u.onerror = () => this.startListening();
            }
            this._synth.speak(u);
        },

        speak(text) {
            this._play(text, { listen: false });
        },

        _pickVoice(langPrefix) {
            return pickBestSpanishVoice(this._synth, langPrefix);
        },

        stopSpeak() {
            if (this._synth) this._synth.cancel();
        },

        // ---------------------------------------------------------------
        // Comandos del usuario
        // ---------------------------------------------------------------
        goBack() { this.send('volver'); },
        help() {
            this.message = 'Puedes decir: repetir, volver, corregir, continuar, ayuda, salir o cancelar. O escribe tu respuesta directamente.';
            this._play(this.message, { listen: false });
        },
        exit() { this.send('salir'); },

        destroy() {
            this.stopListening();
            this.stopSpeak();
        },
    }));
}