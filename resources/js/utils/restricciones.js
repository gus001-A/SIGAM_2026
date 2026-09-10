/**
 * Restricciones de entrada + reglas de validación para formularios Ant.
 * Se combinan: `@keypress`/`@paste` bloquean caracteres inválidos en vivo,
 * y `regla*()` devuelve un objeto `:rules` para `<a-form-item name>`.
 */

const PATRONES = {
    // Texto libre: letras (con ñ/acentos), números, espacios y signos de
    // puntuación. Solo se bloquean < > { } \ (riesgo de inyección).
    texto: /^[^<>{}\\]*$/u,
    // Solo letras y espacios (con ñ/acentos y signos de nombres).
    letras: /^[\p{L}\s.'\-]*$/u,
    // Código: letras, números, espacios y . - _ / #
    codigo: /^[\p{L}\p{N}\s._/\-#]*$/u,
    // Solo dígitos.
    digitos: /^\d*$/,
    // Número con decimales.
    decimal: /^\d*(\.\d*)?$/,
    // Teléfono: dígitos, espacios, +, -, ()
    telefono: /^[\d\s+()\-]*$/,
};

const teclasControl = new Set([
    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'Home', 'End',
    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
]);

/** Handler `@keypress` que bloquea el caracter si no encaja con el patrón. */
export const soloPatron = (nombre) => (e) => {
    if (e.ctrlKey || e.metaKey || teclasControl.has(e.key)) return;
    const re = PATRONES[nombre];
    if (re && !re.test(e.key)) e.preventDefault();
};

export const soloLetras = soloPatron('letras');
export const soloDigitos = soloPatron('digitos');
export const soloDecimal = soloPatron('decimal');
export const soloCodigo = soloPatron('codigo');
export const soloTexto = soloPatron('texto');

/** Limpia el texto pegado según el patrón. */
export const limpiarPegado = (nombre) => (e) => {
    const re = PATRONES[nombre];
    if (!re) return;
    const texto = (e.clipboardData || window.clipboardData).getData('text');
    const limpio = [...texto].filter((c) => re.test(c)).join('');
    if (limpio !== texto) {
        e.preventDefault();
        document.execCommand('insertText', false, limpio);
    }
};

// --- Reglas para :rules de a-form-item -----------------------------
const patronRegla = (nombre, mensaje) => ({
    pattern: new RegExp(PATRONES[nombre].source.replace('*', '+'), PATRONES[nombre].flags),
    message: mensaje,
});

export const reglaRequerido = (msg = 'Este campo es obligatorio.') => ({
    required: true,
    message: msg,
});

export const reglaLetras = (msg = 'Solo se permiten letras.') => patronRegla('letras', msg);
export const reglaDigitos = (msg = 'Solo se permiten dígitos.') => patronRegla('digitos', msg);
export const reglaCodigo = (msg = 'Solo letras, números y . - _ / # (sin otros símbolos).') =>
    patronRegla('codigo', msg);
export const reglaTexto = (msg = 'No se permiten los caracteres < > { } \\.') => patronRegla('texto', msg);

export const reglaCorreo = (msg = 'Correo electrónico no válido.') => ({ type: 'email', message: msg });

/** Teléfono: exactamente `n` dígitos (por defecto 10, formato México). */
export const reglaTelefono = (n = 10, msg) => ({
    validator: (_r, v) =>
        !v || new RegExp(`^\\d{${n}}$`).test(String(v))
            ? Promise.resolve()
            : Promise.reject(msg ?? `El teléfono debe tener exactamente ${n} dígitos.`),
});

export const reglaNoFutura = (msg = 'La fecha no puede ser futura.') => ({
    validator: (_r, v) =>
        !v || new Date(v) <= new Date(new Date().toDateString())
            ? Promise.resolve()
            : Promise.reject(msg),
});

export const reglaDespuesDe = (obtener, msg = 'Debe ser posterior a la fecha anterior.') => ({
    validator: (_r, v) => {
        const otra = obtener();
        if (!v || !otra || new Date(v) >= new Date(otra)) return Promise.resolve();
        return Promise.reject(msg);
    },
});
