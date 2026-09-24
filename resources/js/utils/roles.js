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

/** Resumen en español de qué puede hacer cada rol — leyenda de permisos (sin exponer los slugs técnicos). */
const DESCRIPCIONES = {
    superadministrador: 'Acceso total; configuración, usuarios, catálogos, inventario, mantenimiento, reportes y auditoría.',
    supervisor: 'Consulta y gestión operativa; programación, asignación, supervisión, autorización de cierre y reportes.',
    tecnico: 'Agenda propia, órdenes asignadas, diagnóstico, actividades, evidencias, refacciones y cambio a realizado.',
    usuario_basico: 'Consulta autorizada y creación/seguimiento de solicitudes de mantenimiento.',
    auditor: 'Solo consulta en todos los módulos, para revisión; puede exportar auditoría y reportes.',
};

export const etiquetaRol = (slug) =>
    ETIQUETAS[slug] ?? String(slug ?? '').replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase());

export const colorRol = (slug) => COLORES[slug] ?? 'default';

/** Color hex del rol, alineado con la paleta del sistema. */
export const colorHexRol = (slug) => HEX[slug] ?? '#64748b';

/** Qué puede hacer este rol, en una frase — leyenda para quien asigna o revisa usuarios. */
export const descripcionRol = (slug) => DESCRIPCIONES[slug] ?? 'Sin descripción registrada.';

/** Lista completa {slug, etiqueta, color, descripcion} — para tablas de referencia de permisos. */
export const listaRoles = () =>
    Object.keys(ETIQUETAS).map((slug) => ({
        slug,
        etiqueta: etiquetaRol(slug),
        color: colorRol(slug),
        descripcion: descripcionRol(slug),
    }));
