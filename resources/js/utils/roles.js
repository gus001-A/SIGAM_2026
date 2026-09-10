/**
 * Etiquetas y colores legibles para los roles de spatie/permission.
 * Los slugs viven en RolesPermisosSeeder.
 */
const ETIQUETAS = {
    superadministrador: 'Superadministrador',
    supervisor: 'Supervisor',
    tecnico: 'Técnico',
    usuario_basico: 'Usuario básico',
    auditor: 'Auditor',
};

const COLORES = {
    superadministrador: 'red',
    supervisor: 'blue',
    tecnico: 'green',
    usuario_basico: 'default',
    auditor: 'purple',
};

const HEX = {
    superadministrador: '#d64545',
    supervisor: '#0d84c9',
    tecnico: '#1f9e86',
    usuario_basico: '#64748b',
    auditor: '#6b4bc9',
};

export const etiquetaRol = (slug) =>
    ETIQUETAS[slug] ?? String(slug ?? '').replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase());

export const colorRol = (slug) => COLORES[slug] ?? 'default';

/** Color hex del rol, alineado con la paleta del sistema. */
export const colorHexRol = (slug) => HEX[slug] ?? '#64748b';
