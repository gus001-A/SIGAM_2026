import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Estado de filtros + navegación server-side para listados con Inertia + a-table.
 * El tamaño de página lo define el backend (fijo).
 *
 * @param {string} rutaIndex  nombre de ruta Ziggy del listado (p.ej. 'equipos.index')
 * @param {object} inicial    { filtros, orden: { campo, dir } }
 */
export function useTablaInertia(rutaIndex, inicial = {}) {
    const filtros = reactive({ ...(inicial.filtros ?? {}) });
    const orden = reactive({
        campo: inicial.orden?.campo ?? 'id',
        dir: inicial.orden?.dir ?? 'asc',
    });
    const cargando = ref(false);

    let temporizador = null;

    const limpiarVacios = (obj) =>
        Object.fromEntries(
            Object.entries(obj).filter(([, v]) => v !== '' && v !== null && v !== undefined),
        );

    const navegar = (extra = {}) => {
        const params = limpiarVacios({
            ...filtros,
            orden: orden.campo,
            dir: orden.dir,
            ...extra,
        });

        router.get(route(rutaIndex), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => (cargando.value = true),
            onFinish: () => (cargando.value = false),
        });
    };

    /** Aplica filtros con retraso (inputs de texto). */
    const filtrar = (ms = 350) => {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => navegar({ page: 1 }), ms);
    };

    /** Aplica filtros de inmediato (selects, botón "Aplicar"). */
    const aplicar = () => {
        clearTimeout(temporizador);
        navegar({ page: 1 });
    };

    const limpiar = () => {
        Object.keys(filtros).forEach((k) => (filtros[k] = undefined));
        navegar({ page: 1 });
    };

    const hayFiltros = () =>
        Object.values(filtros).some((v) => v !== '' && v !== null && v !== undefined);

    /** Handler para `@change` de a-table: (pagination, _filters, sorter). */
    const onCambioTabla = (paginacion, _filtros, sorter) => {
        const s = Array.isArray(sorter) ? sorter[0] : sorter;

        if (s?.order) {
            orden.campo = s.field ?? s.columnKey ?? orden.campo;
            orden.dir = s.order === 'descend' ? 'desc' : 'asc';
        } else if (s && s.column === undefined) {
            // se quitó el orden
            orden.campo = inicial.orden?.campo ?? 'id';
            orden.dir = 'asc';
        }

        navegar({ page: paginacion?.current ?? 1 });
    };

    return { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, navegar, onCambioTabla };
}
