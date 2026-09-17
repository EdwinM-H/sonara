/**
 * SONARA â€” Asistente de voz del sitio (todas las pÃ¡ginas).
 *
 * El micrÃ³fono NUNCA se activa solo: los navegadores exigen un gesto
 * explÃ­cito del usuario (clic o tecla) antes de conceder acceso al
 * micrÃ³fono, y abrirlo sin pedirlo interrumpe a quienes ya navegan con
 * su propio lector de pantalla (NVDA/JAWS/VoiceOver). Por eso la escucha
 * solo arranca cuando el usuario pulsa el botÃ³n flotante o Alt+V (un
 * gesto real). Al llegar por primera vez a la pestaÃ±a sÃ­ se anuncia, una
 * sola vez por sesiÃ³n de navegaciÃ³n (no en cada clic/pÃ¡gina), cÃ³mo
 * activar el asistente â€” sin abrir el micrÃ³fono.
 *
 * Comandos de navegaciÃ³n por voz: "inicio", "explorar", "categorÃ­as",
 * "registrarme", "iniciar sesiÃ³n", "registro por voz", "mi perfil",
 * "notificaciones", "mi panel", "leer pÃ¡gina", "ayuda", "detente".
 */
import { pickBestSpanishVoice, readVoicePrefs } from './voice-quality';

export function registerSiteVoice() {
    window.Alpine.data('siteVoiceAssistant', () => ({
        open: false,
        listening: false,
        status: '',
        supported: true,
        speechRecognition: null,
        _synth: null,
        _commands: [],
        _wantsListen: false,
        _restartTimer: null,
        _listenTimeout: null,

        init() {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) {
                this.supported = false;
                this.status = 'Tu navegador no soporta reconocimiento de voz.';
                return;
            }
            this.speechRecognition = new SR();
            this.speechRecognition.lang = 'es-PE';
            this.speechRecognition.interimResults = true;
            this.speechRecognition.maxAlternatives = 5;
            this.speechRecognition.continuous = true;
            this.speechRecognition.onresult = (event) => this._onResult(event);
            this.speechRecognition.onerror = (event) => this._onError(event);
            this.speechRecognition.onend = () => this._onEnd();
            this._synth = window.speechSynthesis || null;
            if (this._synth && this._synth.addEventListener) {
                this._synth.addEventListener('voiceschanged', () => this._synth.getVoices());
            }

            this._buildCommands();
            this._bindKeys();
            window.addEventListener('sonara-read-page', () => this.readPage());

            // Anuncio de bienvenida UNA sola vez por sesión de navegación
            // (no en cada página), y solo si el usuario no desactivó la
            // lectura automática en su perfil de accesibilidad. Nunca abre
            // el micrófono por sí mismo: solo informa cómo activarlo. La
            // página de registro por voz gestiona su propio saludo y no
            // debe duplicarse aquí.
            const alreadyGreeted = window.sessionStorage?.getItem('sonara_greeted') === '1';
            if (!document.querySelector('[data-standalone-voice]') && readVoicePrefs().autoRead && !alreadyGreeted) {
                setTimeout(() => this.greet(), 900);
                try { window.sessionStorage?.setItem('sonara_greeted', '1'); } catch (e) { /* almacenamiento no disponible */ }
            }
        },

        _buildCommands() {
            const add = (keys, label, url) => {
                if (url) this._commands.push({ keys, label, url });
            };
            const d = this.$root.dataset;
            add(['inicio', 'menu principal', 'pagina principal', 'home', 'volver al inicio', 'presentacion'], 'Ir al inicio', d.navHome);
            add(['explorar', 'catalogo', 'buscar', 'emprendimientos', 'servicios'], 'Explorar emprendimientos', d.navExplore);
            add(['categorias', 'categorias por voz'], 'Ver categorÃ­as', d.navCategories);
            add(['registrarme', 'crear cuenta', 'registro', 'ser emprendedor'], 'Crear cuenta', d.navRegister);
            add(['iniciar sesion', 'ingresar', 'entrar', 'login'], 'Iniciar sesiÃ³n', d.navLogin);
            add(['registro por voz', 'por voz', 'hablar', 'registro hablado'], 'Registro guiado por voz', d.navVoice);
            add(['mi perfil', 'perfil'], 'Mi perfil', d.navProfile);
            add(['notificaciones', 'avisos'], 'Mis notificaciones', d.navNotifications);
            add(['mi panel', 'panel', 'mi cuenta', 'dashboard'], 'Mi panel', d.navPanel);
        },

        _bindKeys() {
            window.addEventListener('keydown', (e) => {
                if (e.altKey && (e.key === 'v' || e.key === 'V')) {
                    e.preventDefault();
                    this.activate();
                }
            });
        },

        // ---------------------------------------------------------------
        // Saludo (una vez por sesión): informa cómo activar el asistente,
        // pero NUNCA abre el micrófono por sí solo — eso requiere que el
        // usuario pulse el botón flotante o presione Alt+V.
        // ---------------------------------------------------------------
        greet() {
            if (document.visibilityState === 'hidden') return;
            const msg = 'Bienvenido a Sonara, una plataforma accesible. ' +
                'Para activar el asistente de voz en cualquier momento, presiona el botón de micrófono ' +
                'en la esquina inferior derecha, o la tecla Alt más V.';
            this.say(msg);
        },

        activate() {
            this.open = true;
            this.say('Asistente activado. SeÃ±ala una opciÃ³n: ' + this.commandList() +
                '. O di ayuda para repetir las opciones.');
            this.startListen(12000);
        },

        toggle() {
            if (this.open) {
                this.close();
            } else {
                this.activate();
            }
        },

        close() {
            this.open = false;
            this.stopListen();
        },

        // ---------------------------------------------------------------
        // Escucha activa
        // ---------------------------------------------------------------
        startListen(duration = null) {
            if (!this.speechRecognition) {
                this.status = 'Tu navegador no soporta reconocimiento de voz.';
                return;
            }
            this.stopSpeak();
            this._wantsListen = true;
            if (this._listenTimeout) { clearTimeout(this._listenTimeout); this._listenTimeout = null; }
            if (duration) {
                this._listenTimeout = setTimeout(() => this.stopListenAndAnnounce(), duration);
            }
            if (this._restartTimer) { clearTimeout(this._restartTimer); this._restartTimer = null; }
            setTimeout(() => {
                if (!this._wantsListen) return;
                try {
                    this.speechRecognition.start();
                    this.listening = true;
                    this.status = 'Escuchandoâ€¦ diga su opciÃ³n.';
                } catch (e) {
                    if (String(e).indexOf('already') === -1) {
                        this.status = 'No se pudo activar el micrÃ³fono. Verifique el permiso.';
                    } else {
                        this.listening = true;
                    }
                }
            }, 350);
        },

        stopListen() {
            this._wantsListen = false;
            this.listening = false;
            if (this._listenTimeout) { clearTimeout(this._listenTimeout); this._listenTimeout = null; }
            if (this._restartTimer) { clearTimeout(this._restartTimer); this._restartTimer = null; }
            if (this.speechRecognition) {
                try { this.speechRecognition.stop(); } catch (e) { /* ignore */ }
            }
        },

        stopListenAndAnnounce() {
            this.stopListen();
            this.status = 'Asistente en pausa. Presione Alt+V para activarlo de nuevo.';
        },

        _onResult(event) {
            let finalText = '';
            const results = event.results;
            for (let i = 0; i < results.length; i++) {
                if (results[i].isFinal) {
                    finalText += results[i][0].transcript;
                }
            }
            const interim = this._getInterim(results);
            if (finalText.trim()) {
                this.status = 'He escuchado: "' + finalText.trim() + '".';
                this.stopListen();
                this._process(finalText.trim());
            } else if (interim.trim()) {
                this.status = 'â€¦' + interim.trim();
            }
        },

        _getInterim(results) {
            for (let i = results.length - 1; i >= 0; i--) {
                if (!results[i].isFinal) return results[i][0].transcript;
            }
            return '';
        },

        _onError(event) {
            const err = (event && event.error) || '';
            if (err === 'no-speech') {
                this.listening = false;
                this._scheduleRestart();
                return;
            }
            if (err === 'not-allowed' || err === 'service-not-allowed') {
                this._wantsListen = false;
                this.listening = false;
                this.status = 'Permiso de micrÃ³fono denegado. ActÃ­velo en el navegador.';
                return;
            }
            if (err === 'network' || err === 'aborted') {
                this._scheduleRestart();
                return;
            }
            this.listening = false;
            this.status = 'No pude escuchar (error: ' + err + ').';
        },

        _onEnd() {
            this.listening = false;
            if (this._wantsListen) this._scheduleRestart();
        },

        _scheduleRestart() {
            if (!this._wantsListen || this.listening) return;
            if (this._restartTimer) clearTimeout(this._restartTimer);
            this._restartTimer = setTimeout(() => {
                if (!this._wantsListen || this.listening) return;
                try {
                    this.speechRecognition.start();
                    this.listening = true;
                } catch (e) { this._restartTimer = null; }
            }, 500);
        },

        // ---------------------------------------------------------------
        // InterpretaciÃ³n de comandos
        // ---------------------------------------------------------------
        _process(raw) {
            const text = this._normalize(raw);
            if (!text) { this.say('No te he entendido. Para repetir las opciones, di ayuda.'); return; }

            if (text.includes('leer la pagina') || text.includes('leer pagina') || text.includes('leeme la pagina')) {
                this.readPage();
                return;
            }
            if (text.includes('ayuda') || text.includes('opciones')) {
                this.say('Puedes decir: ' + this.commandList() + '. O leer pÃ¡gina.');
                this.startListen(10000);
                return;
            }
            if (text.includes('detente') || text.includes('callate') || text.includes('callate la boca') ||
                text.includes('silencio') || text.includes('parar')) {
                this.stopSpeak();
                this.stopListen();
                this.status = 'Asistente en pausa.';
                return;
            }
            if (text.includes('escuchame') || text.includes('escucha') || text.includes('oyeme')) {
                this.startListen(12000);
                return;
            }

            for (const cmd of this._commands) {
                for (const key of cmd.keys) {
                    if (text === key || text.includes('ir a ' + key) || text.includes('abrir ' + key) ||
                        text.includes(key + ' por favor') || text.includes('quiero ' + key)) {
                        this.say('Abriendo ' + cmd.label + '.');
                        const target = cmd.url;
                        setTimeout(() => { window.location.href = target; }, 1800);
                        return;
                    }
                }
            }

            this.say('No encontrÃ© la opciÃ³n "' + raw + '". Para repetir las opciones, di ayuda.');
            this.startListen(10000);
        },

        _normalize(text) {
            return (text || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, ' ')
                .trim();
        },

        commandList() {
            const labels = this._commands.map((c) => c.label.toLowerCase());
            return labels.slice(0, 6).join(', ') + (labels.length > 6 ? ', entre otras.' : '.');
        },

        readPage() {
            const main = document.getElementById('main-content');
            const text = main ? main.innerText : document.body.innerText;
            const clean = text.replace(/\s+/g, ' ').trim().slice(0, 4000);
            this.status = 'Leyendo la pÃ¡ginaâ€¦';
            this.say(clean || 'La pÃ¡gina no tiene contenido legible.');
        },

        // ---------------------------------------------------------------
        // SÃ­ntesis
        // ---------------------------------------------------------------
        say(text) {
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
            this._synth.speak(u);
        },

        _pickVoice(langPrefix) {
            return pickBestSpanishVoice(this._synth, langPrefix);
        },

        stopSpeak() {
            if (this._synth) this._synth.cancel();
        },

        destroy() {
            this.stopListen();
            this.stopSpeak();
        },
    }));
}
