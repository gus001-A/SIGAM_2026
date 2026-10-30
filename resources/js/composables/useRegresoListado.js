/**
 * Recuerda la URL completa de un listado (filtros, orden y página van en el
 * query string) para que, al volver desde el expediente o la edición, el
 * usuario regrese exactamente a la búsqueda que tenía.
 *
 * Se guarda en sessionStorage: vive solo en la pestaña actual.
 */
const PREFIJO = 'sigam:listado:';

export function recordarListado(clave) {
    try {
        sessionStorage.setItem(PREFIJO + clave, window.location.pathname + window.location.search);
    } catch {
        // Sin almacenamiento disponible: se vuelve a la ruta por defecto.
    }
}

/** URL guardada del listado, o `porDefecto` si no hay (p. ej. se entró directo). */
export function urlListado(clave, porDefecto) {
    try {
        return sessionStorage.getItem(PREFIJO + clave) || porDefecto;
    } catch {
        return porDefecto;
    }
}
