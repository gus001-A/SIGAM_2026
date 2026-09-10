import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Acceso a roles y permisos del usuario autenticado, compartidos por
 * HandleInertiaRequests como `auth.roles` y `auth.permisos`.
 */
export function usePermisos() {
    const page = usePage();

    const roles = computed(() => page.props.auth?.roles ?? []);
    const permisos = computed(() => page.props.auth?.permisos ?? []);
    const usuario = computed(() => page.props.auth?.user ?? null);

    /** @param {string|string[]} permiso */
    const puede = (permiso) => {
        const lista = Array.isArray(permiso) ? permiso : [permiso];
        return lista.some((p) => permisos.value.includes(p));
    };

    /** @param {string|string[]} rol */
    const tieneRol = (rol) => {
        const lista = Array.isArray(rol) ? rol : [rol];
        return lista.some((r) => roles.value.includes(r));
    };

    return { roles, permisos, usuario, puede, tieneRol };
}
