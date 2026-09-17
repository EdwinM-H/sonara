/**
 * SONARA — Selección de voz de síntesis de alta calidad.
 *
 * El navegador suele exponer varias voces por idioma: motores nativos del
 * sistema operativo (a menudo robóticos, tipo espeak) y motores en la nube
 * (Google, Microsoft) que suenan notablemente más naturales. Esta utilidad
 * puntúa las voces disponibles y elige la más natural en español, con
 * preferencia por acento latinoamericano/neutro sobre el de España.
 */
export function pickBestSpanishVoice(synth, langPrefix = 'es') {
    if (!synth || !synth.getVoices) return null;
    const voices = synth.getVoices();
    if (!voices || !voices.length) return null;

    const spanish = voices.filter((v) => (v.lang || '').toLowerCase().startsWith(langPrefix));
    if (!spanish.length) return null;

    const score = (v) => {
        const name = (v.name || '').toLowerCase();
        const lang = (v.lang || '').toLowerCase();
        let s = 0;

        // Motores en la nube conocidos por sonar más humanos.
        if (name.includes('google')) s += 50;
        if (name.includes('microsoft') && (name.includes('online') || name.includes('natural'))) s += 45;
        if (name.includes('neural')) s += 40;

        // Acento latinoamericano / neutro preferido sobre castellano de España.
        if (lang.includes('es-419') || lang.includes('es-us')) s += 20;
        if (lang.includes('es-pe') || lang.includes('es-mx') || lang.includes('es-co') || lang.includes('es-ar')) s += 15;
        if (lang.includes('es-es')) s += 2;

        // Voces locales de baja calidad (espeak, compact) al final.
        if (name.includes('espeak') || name.includes('compact')) s -= 30;

        // Preferir voces remotas (suelen ser de mayor calidad) si el dato existe.
        if (v.localService === false) s += 10;

        return s;
    };

    return spanish.slice().sort((a, b) => score(b) - score(a))[0] || spanish[0];
}

/**
 * Lee las preferencias de accesibilidad del usuario (compartidas por el
 * backend como data-attributes en <body>) y las convierte en parámetros
 * reales de síntesis de voz. Así "velocidad lenta/normal/rápida" y
 * "volumen bajo/normal/alto" configurados en el perfil de accesibilidad
 * afectan de verdad al motor de voz, no solo quedan guardados en la BD.
 */
export function readVoicePrefs() {
    const body = typeof document !== 'undefined' ? document.body : null;
    const rateKey = body?.dataset.speechRate || 'normal';
    const volumeKey = body?.dataset.speechVolume || 'normal';

    const rates = { lenta: 0.78, normal: 0.94, rapida: 1.18 };
    const volumes = { bajo: 0.55, normal: 1, alto: 1 };

    return {
        rate: rates[rateKey] ?? rates.normal,
        volume: volumes[volumeKey] ?? volumes.normal,
        pitch: 1.04,
        autoRead: body ? body.dataset.autoRead !== '0' : true,
        repeatPrompts: body ? body.dataset.repeatPrompts !== '0' : true,
    };
}
