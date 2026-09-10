/**
 * Reglas de validación reutilizables para los formularios (Vuetify `:rules`).
 * Cada regla devuelve `true` o un mensaje en español.
 */

export const requerido =
    (etiqueta = 'Este') =>
    (v) =>
        (v !== null && v !== undefined && String(v).trim() !== '') ||
        `${etiqueta} es un campo obligatorio.`;

export const correo = () => (v) =>
    !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Ingresa un correo electrónico válido.';

export const minimo = (n, etiqueta = 'Este campo') => (v) =>
    !v || String(v).length >= n || `${etiqueta} debe tener al menos ${n} caracteres.`;

export const maximo = (n, etiqueta = 'Este campo') => (v) =>
    !v || String(v).length <= n || `${etiqueta} no debe exceder ${n} caracteres.`;

export const igualA =
    (obtener, mensaje = 'Los valores no coinciden.') =>
    (v) =>
        v === obtener() || mensaje;

export const numero = () => (v) =>
    v === '' || v === null || !isNaN(Number(v)) || 'Debe ser un número.';
