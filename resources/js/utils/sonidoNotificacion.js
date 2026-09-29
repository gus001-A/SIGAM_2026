let contexto = null;

const obtenerContexto = () => {
    if (contexto) return contexto;
    const AudioContextCls = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextCls) return null;
    contexto = new AudioContextCls();
    return contexto;
};

/** Campanilla corta de dos tonos para avisar que llegó una notificación nueva. */
export const reproducirSonidoNotificacion = () => {
    try {
        const ctx = obtenerContexto();
        if (!ctx) return;
        if (ctx.state === 'suspended') ctx.resume();

        const ahora = ctx.currentTime;
        [
            { frecuencia: 880, inicio: 0 },
            { frecuencia: 1174.66, inicio: 0.09 },
        ].forEach(({ frecuencia, inicio }) => {
            const osc = ctx.createOscillator();
            const ganancia = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = frecuencia;
            ganancia.gain.setValueAtTime(0, ahora + inicio);
            ganancia.gain.linearRampToValueAtTime(0.18, ahora + inicio + 0.015);
            ganancia.gain.exponentialRampToValueAtTime(0.0001, ahora + inicio + 0.28);
            osc.connect(ganancia);
            ganancia.connect(ctx.destination);
            osc.start(ahora + inicio);
            osc.stop(ahora + inicio + 0.3);
        });
    } catch (e) {
        /* algunos navegadores bloquean audio sin interacción previa del usuario */
    }
};
