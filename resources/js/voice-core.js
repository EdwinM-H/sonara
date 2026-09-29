/**
 * SONARA — Utilidades compartidas por los asistentes de voz.
 */

let audioCtx = null;

function getAudioContext() {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;
    if (!audioCtx) audioCtx = new Ctx();
    return audioCtx;
}

/**
 * Pitido corto y leve (~200 ms) que avisa que el micrófono se abre y ya
 * se puede hablar. Resuelve cuando el tono terminó de sonar, para abrir
 * el micrófono justo después y que no capte el propio pitido.
 */
export async function beep({ frequency = 880, duration = 0.2, volume = 0.08 } = {}) {
    const ctx = getAudioContext();
    if (!ctx) return;
    if (ctx.state === 'suspended') {
        try {
            await Promise.race([ctx.resume(), new Promise((r) => setTimeout(r, 300))]);
        } catch (e) { /* sin permiso de audio: se sigue sin pitido */ }
    }

    const now = ctx.currentTime;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.value = frequency;
    // Subida y bajada suaves: evita el "clic" de cortar la onda en seco.
    gain.gain.setValueAtTime(0, now);
    gain.gain.linearRampToValueAtTime(volume, now + 0.02);
    gain.gain.setValueAtTime(volume, now + duration - 0.05);
    gain.gain.linearRampToValueAtTime(0, now + duration);
    osc.connect(gain).connect(ctx.destination);
    osc.start(now);
    osc.stop(now + duration);

    return new Promise((resolve) => {
        osc.onended = resolve;
        setTimeout(resolve, duration * 1000 + 150); // por si onended no llega
    });
}

/**
 * ¿El navegador ya permite reproducir audio sin otro gesto del usuario?
 * Chrome exige una interacción (clic o tecla) antes de hablar o sonar;
 * tras navegar dentro del mismo sitio suele conservar ese permiso.
 */
export async function audioAllowed() {
    if (navigator.userActivation && navigator.userActivation.hasBeenActive) return true;
    const ctx = getAudioContext();
    if (!ctx) return true;
    if (ctx.state === 'running') return true;
    try {
        await Promise.race([ctx.resume(), new Promise((r) => setTimeout(r, 300))]);
    } catch (e) { /* bloqueado */ }
    return ctx.state === 'running';
}

/** Minúsculas, sin tildes y sin caracteres especiales (igual que VoiceText en PHP). */
export function normalizeVoiceText(text) {
    return (text || '')
        .toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
}
