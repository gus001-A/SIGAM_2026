/**
 * URL de un archivo de /public respetando la ruta base de la aplicación.
 *
 * Una ruta fija como "/images/logo.png" apunta a la raíz del dominio y falla
 * cuando el sistema vive en una subcarpeta (p. ej. http://servidor/sigam).
 * La directiva @routes declara `const Ziggy = {...}` con la URL raíz real de la
 * petición (url('/')). Es una variable global, pero no queda en `window`.
 */
export function urlPublica(ruta) {
    // eslint-disable-next-line no-undef
    const ziggy = typeof Ziggy !== 'undefined' ? Ziggy : globalThis.Ziggy;
    const base = (ziggy?.url ?? '').replace(/\/+$/, '');

    return `${base}/${String(ruta).replace(/^\/+/, '')}`;
}
