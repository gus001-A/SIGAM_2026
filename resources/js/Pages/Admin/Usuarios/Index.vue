<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircleOutlined,
    CloseOutlined,
    DeleteOutlined,
    EditOutlined,
    EnvironmentOutlined,
    EyeOutlined,
    FilterOutlined,
    InfoCircleOutlined,
    PlusOutlined,
    StopOutlined,
    TeamOutlined,
    ToolOutlined,
    UndoOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';
import { etiquetaRol, colorRol, colorHexRol, listaRoles } from '@/utils/roles';

const props = defineProps({
    usuarios: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

/* ==========================================================
   KPIs vibrantes
   ========================================================== */
const tarjetas = computed(() => [
    {
        label: 'Usuarios totales',
        valor: props.kpis.total ?? 0,
        icono: TeamOutlined,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
    },
    {
        label: 'Activos',
        valor: props.kpis.activos ?? 0,
        icono: CheckCircleOutlined,
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e4f4ec',
    },
    {
        label: 'Técnicos',
        valor: props.kpis.tecnicos ?? 0,
        icono: ToolOutlined,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
    },
    {
        label: 'Inactivos',
        valor: props.kpis.inactivos ?? 0,
        icono: StopOutlined,
        color: '#d64545',
        color2: '#b91c1c',
        soft: '#fdecec',
    },
]);

const { puede, usuario: yo } = usePermisos();

const modalRoles = ref(false);
const roles = listaRoles();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('usuarios.index', {
    filtros: {
        nombre: props.filtros.nombre ?? '',
        email: props.filtros.email ?? '',
        sucursal_id: props.filtros.sucursal_id ?? undefined,
        rol: props.filtros.rol ?? undefined,
        estado: props.filtros.estado ?? undefined,
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'nombre', dir: props.orden.dir ?? 'asc' },
});

const opcionesFiltro = {
    sucursal_id: computed(() => (props.catalogos.sucursales ?? []).map((s) => ({ label: s.nombre, value: s.id }))),
    rol: computed(() => (props.catalogos.roles ?? []).map((r) => ({ label: etiquetaRol(r), value: r }))),
    estado: computed(() => [
        { label: 'Activo', value: 'activo' },
        { label: 'Inactivo', value: 'inactivo' },
    ]),
};

const columns = [
    { title: 'Usuario', key: 'nombre_completo', filtro: 'texto', filtroClave: 'nombre', sorter: true, width: 240 },
    { title: 'Correo', key: 'email', dataIndex: 'email', filtro: 'texto', filtroClave: 'email', sorter: true, width: 250 },
    { title: 'Sucursal', key: 'sucursal', filtro: 'select', filtroClave: 'sucursal_id', width: 180 },
    { title: 'Roles', key: 'roles', filtro: 'select', filtroClave: 'rol', width: 220 },
    { title: 'Último acceso', key: 'ultimo_acceso_at', dataIndex: 'ultimo_acceso_at', sorter: true, width: 150 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 120 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const fechaHora = (v) => (v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'Nunca');

/* ==========================================================
   Iniciales para el avatar
   ========================================================== */
const iniciales = (nombre = '') =>
    nombre
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');

/* ==========================================================
   Color del avatar (según hash del nombre)
   ========================================================== */
const PALETA_AVATAR = [
    { c: '#0d84c9', c2: '#0f6fb0' },
    { c: '#1f9e86', c2: '#16806c' },
    { c: '#6b4bc9', c2: '#563a9e' },
    { c: '#e08a1e', c2: '#a86717' },
    { c: '#d64545', c2: '#b91c1c' },
    { c: '#0891b2', c2: '#0e7490' },
];
const colorAvatar = (nombre = '') => {
    const i = nombre.split('').reduce((a, ch) => a + ch.charCodeAt(0), 0) % PALETA_AVATAR.length;
    return PALETA_AVATAR[i];
};

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (usuario) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar a ${usuario.nombre_completo}`,
        mensaje: 'El usuario no podrá iniciar sesión. Su historial y registros se conservan.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('usuarios.destroy', usuario.id), { preserveScroll: true });
};

const reactivar = (usuario) => router.put(route('usuarios.restore', usuario.id), {}, { preserveScroll: true });
</script>

<template>

    <Head title="Usuarios" />

    <AppLayout titulo="Usuarios" descripcion="Cuentas del personal con sus roles, permisos y sucursal asignada.">
        <!-- ==========================================================
             Acciones arriba: roles + nuevo usuario
             ========================================================== -->
        <template #acciones>
            <a-button class="btn-roles" @click="modalRoles = true">
                <template #icon>
                    <InfoCircleOutlined />
                </template>
                ¿Qué puede hacer cada rol?
            </a-button>

            <a-button v-if="puede('usuarios.crear')" type="primary" class="btn-nueva" @click="irA('usuarios.create')">
                <template #icon>
                    <PlusOutlined />
                </template>
                Nuevo usuario
            </a-button>
        </template>

        <!-- KPIs vibrantes -->
        <div class="kpis">
            <div v-for="k in tarjetas" :key="k.label" class="kpi" :style="{
                '--acc': k.color,
                '--acc2': k.color2,
                '--soft': k.soft,
            }">
                <div class="kpi__glow"></div>
                <div class="kpi__icono">
                    <component :is="k.icono" />
                </div>
                <div class="kpi__txt">
                    <div class="kpi__valor">{{ k.valor }}</div>
                    <div class="kpi__etq">{{ k.label }}</div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="tabla-usuarios">
            <DataTableInertia :paginador="usuarios" :columns="columns" :orden="orden" :cargando="cargando"
                @cambio="onCambioTabla"  @limpiar="limpiar">
                <template #filtro="{ column }">
                    <a-input v-if="column.filtro === 'texto'" v-model:value="filtros[column.filtroClave]" size="small"
                        allow-clear placeholder="Filtrar" @update:value="filtrar()" />
                    <a-select v-else-if="column.filtro === 'select'" v-model:value="filtros[column.filtroClave]"
                        :options="opcionesFiltro[column.filtroClave].value" size="small" allow-clear placeholder="Todos"
                        style="width: 100%" @change="aplicar()" />
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'nombre_completo'">
                        <a class="usuario" @click="irA('usuarios.show', record.id)">
                            <span class="usuario__av" :style="{
                                background: `linear-gradient(135deg, ${colorAvatar(record.nombre_completo).c} 0%, ${colorAvatar(record.nombre_completo).c2} 100%)`,
                            }">
                                {{ iniciales(record.nombre_completo) }}
                            </span>
                            <span class="usuario__info">
                                <span class="usuario__dot"
                                    :style="{ background: record.estado === 'activo' ? '#1f9e86' : '#94a3b8' }"></span>
                                <span class="usuario__nombre">{{ record.nombre_completo }}</span>
                            </span>
                        </a>
                        <div v-if="record.telefono" class="usuario__tel">{{ record.telefono }}</div>
                    </template>

                    <template v-else-if="column.key === 'email'">
                        <a v-if="record.email" class="email" :href="`mailto:${record.email}`">
                            {{ record.email }}
                        </a>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'sucursal'">
                        <span v-if="record.sucursal" class="sucursal">
                            <EnvironmentOutlined />
                            {{ record.sucursal }}
                        </span>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'roles'">
                        <a-space :size="4" wrap>
                            <a-tag v-for="r in record.roles" :key="r" :color="colorRol(r)" class="tag-rol">
                                {{ etiquetaRol(r) }}
                            </a-tag>
                            <span v-if="!record.roles.length" class="vacio">Sin rol</span>
                        </a-space>
                    </template>

                    <template v-else-if="column.key === 'ultimo_acceso_at'">
                        <span class="acceso" :class="{ 'acceso--nunca': !record.ultimo_acceso_at }">
                            {{ fechaHora(record.ultimo_acceso_at) }}
                        </span>
                    </template>

                    <template v-else-if="column.key === 'estado'">
                        <a-tag :color="record.estado === 'activo' ? 'green' : 'default'" class="tag-estado">
                            <span class="tag-estado__dot"
                                :style="{ background: record.estado === 'activo' ? '#1f9e86' : '#94a3b8' }"></span>
                            {{ record.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'registrado'">
                        <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                    </template>

                    <template v-else-if="column.key === 'acciones'">
                        <a-space :size="2">
                            <a-tooltip title="Ver detalle">
                                <a-button type="text" size="small" class="accion-ver"
                                    @click="irA('usuarios.show', record.id)">
                                    <template #icon>
                                        <EyeOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-if="record.estado === 'activo' && puede('usuarios.editar')" title="Editar">
                                <a-button type="text" size="small" class="accion-edit"
                                    @click="irA('usuarios.edit', record.id)">
                                    <template #icon>
                                        <EditOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip
                                v-if="record.estado === 'activo' && puede('usuarios.desactivar') && record.id !== yo?.id"
                                title="Desactivar">
                                <a-button type="text" size="small" class="accion-danger" @click="desactivar(record)">
                                    <template #icon>
                                        <DeleteOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-else-if="record.estado === 'inactivo' && puede('usuarios.editar')"
                                title="Reactivar">
                                <a-button type="text" size="small" class="accion-reactivar" @click="reactivar(record)">
                                    <template #icon>
                                        <UndoOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                        </a-space>
                    </template>
                </template>
            </DataTableInertia>
        </div>

        <ConfirmarDialog ref="confirmar" />

        <!-- ==========================================================
             Modal de roles
             ========================================================== -->
        <a-modal v-model:open="modalRoles" :footer="null" :closable="false" :width="640" centered class="modal-roles">
            <div class="modal-roles__wrap">
                <header class="modal-roles__head">
                    <div class="modal-roles__ico">
                        <TeamOutlined />
                    </div>
                    <div class="modal-roles__meta">
                        <span class="modal-roles__tipo">Leyenda</span>
                        <h3 class="modal-roles__titulo">¿Qué puede hacer cada rol?</h3>
                        <p class="modal-roles__sub">Principales facultades de cada rol dentro del sistema.</p>
                    </div>
                    <button type="button" class="modal-roles__close" title="Cerrar" @click="modalRoles = false">
                        <CloseOutlined />
                    </button>
                </header>

                <div class="modal-roles__body">
                    <div class="leyenda-roles">
                        <div v-for="r in roles" :key="r.slug" class="leyenda-roles__card"
                            :style="{ '--rol-color': colorHexRol(r.slug) }">
                            <div class="leyenda-roles__head">
                                <span class="leyenda-roles__punto"></span>
                                <span class="leyenda-roles__titulo">{{ r.etiqueta }}</span>
                            </div>
                            <span class="leyenda-roles__desc">{{ r.descripcion }}</span>
                        </div>
                    </div>
                </div>

                <footer class="modal-roles__footer">
                    <a-button size="large" @click="modalRoles = false">
                        Cerrar
                    </a-button>
                </footer>
            </div>
        </a-modal>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Botones del header
   ========================================================== */
.btn-nueva {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%) !important;
    border-color: #0d84c9 !important;
    box-shadow: 0 4px 12px rgba(13, 132, 201, 0.32);
    font-weight: 700;
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.btn-nueva:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 6px 16px rgba(13, 132, 201, 0.45);
}

.btn-roles {
    color: #6b4bc9;
    border-color: #e0d3f7;
    background: #f7f4fd;
    font-weight: 700;
    transition: background 0.14s ease, border-color 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.btn-roles:hover {
    background: #efe9fb !important;
    border-color: #6b4bc9 !important;
    color: #563a9e !important;
    transform: translateY(-1px);
}

/* ==========================================================
   KPIs vibrantes
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
    margin-bottom: 14px;
}

.kpi {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 2px 6px -3px rgba(15, 37, 71, 0.1);
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.kpi:hover {
    transform: translateY(-2px);
    border-color: var(--acc);
    box-shadow: 0 8px 20px -10px rgba(15, 37, 71, 0.35);
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, var(--acc) 0%, var(--acc2) 100%);
}

.kpi__glow {
    position: absolute;
    right: -30px;
    top: -30px;
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: radial-gradient(circle, var(--soft) 0%, transparent 70%);
    opacity: 0.9;
    pointer-events: none;
}

.kpi__icono {
    position: relative;
    z-index: 1;
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    background: linear-gradient(135deg, var(--acc) 0%, var(--acc2) 100%);
    flex-shrink: 0;
    box-shadow: 0 4px 10px -3px rgba(15, 37, 71, 0.35);
}

.kpi__txt {
    position: relative;
    z-index: 1;
    min-width: 0;
}

.kpi__valor {
    font-size: 20px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.05;
    letter-spacing: -0.5px;
}

.kpi__etq {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
    color: #7b8a9c;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}

@media (max-width: 767px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* ==========================================================
   Barra con "Filtrar por" + botón Limpiar debajo
   ========================================================== */
.barra-filtros {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 14px;
    padding: 12px 16px;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.05);
}

.barra-filtros__grupo {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.barra-filtros__ic {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    background: var(--c);
    box-shadow: 0 3px 8px -3px rgba(15, 37, 71, 0.35);
    flex-shrink: 0;
}

.barra-filtros__l {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #7b8a9c;
    white-space: nowrap;
}

.barra-filtros__limpiar {
    align-self: flex-start;
    color: #d64545;
    border-color: #f4dede;
    background: #fdf4f4;
    font-weight: 700;
    transition: background 0.14s ease, border-color 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.barra-filtros__limpiar:hover {
    background: #fdecec !important;
    border-color: #d64545 !important;
    color: #a83232 !important;
    transform: translateY(-1px);
}

/* ==========================================================
   Tabla
   ========================================================== */
.tabla-usuarios {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    background: #fff;
}

.tabla-usuarios :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(180deg, #f5f8fb 0%, #eef3f8 100%) !important;
    color: #173a5f !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 11px;
    border-bottom: 1px solid #dbe3ec !important;
}

.tabla-usuarios :deep(.ant-table-thead > tr > th::before) {
    background-color: #dbe3ec !important;
}

.tabla-usuarios :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid #eef2f7 !important;
}

.tabla-usuarios :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

.tabla-usuarios :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

.tabla-usuarios :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid #eef2f7;
}

.tabla-usuarios :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

.tabla-usuarios :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
    background: #e6f2fb;
}

.tabla-usuarios :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #0f6fb0;
    font-weight: 800;
}

/* ==========================================================
   Celda "Usuario"
   ========================================================== */
.usuario {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    color: #173a5f;
    cursor: pointer;
    text-decoration: none;
    border-bottom: 1px dashed transparent;
    transition: color 0.14s ease, border-color 0.14s ease;
}

.usuario:hover {
    color: #0d84c9;
    border-bottom-color: #0d84c9;
}

.usuario__av {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    flex-shrink: 0;
    letter-spacing: 0.5px;
    box-shadow: 0 3px 8px -3px rgba(15, 37, 71, 0.35);
}

.usuario__info {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.usuario__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9);
}

.usuario__nombre {
    font-size: 13px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

.usuario__tel {
    font-size: 11.5px;
    color: #7b8a9c;
    margin-top: 3px;
    margin-left: 42px;
}

/* ==========================================================
   Celdas auxiliares
   ========================================================== */
.email {
    color: #0d84c9;
    font-weight: 600;
    font-size: 12.5px;
    text-decoration: none;
    transition: color 0.14s ease;
}

.email:hover {
    color: #0f6fb0;
    text-decoration: underline;
}

.sucursal {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: #2b3a4f;
}

.sucursal .anticon {
    color: #1f9e86;
    font-size: 13px;
}

.acceso {
    font-size: 12.5px;
    font-weight: 600;
    color: #2b3a4f;
    font-variant-numeric: tabular-nums;
}

.acceso--nunca {
    color: #94a3b8;
    font-style: italic;
    font-weight: 500;
}

.vacio {
    color: #94a3b8;
    font-size: 12.5px;
}

/* ==========================================================
   Tags
   ========================================================== */
.tag-rol {
    display: inline-flex !important;
    align-items: center;
    margin: 0;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    line-height: 20px;
    border: none !important;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.08);
}

.tag-estado {
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    margin: 0;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    line-height: 20px;
    border: none !important;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.08);
}

.tag-estado__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9);
}

/* ==========================================================
   Botones de acción
   ========================================================== */
.accion-ver,
.accion-edit,
.accion-danger,
.accion-reactivar {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 9px;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.accion-ver {
    color: #0d84c9;
}

.accion-ver:hover {
    background: #e6f2fb !important;
    color: #0f6fb0 !important;
    transform: translateY(-1px);
}

.accion-edit {
    color: #1f9e86;
}

.accion-edit:hover {
    background: #e4f4ec !important;
    color: #16806c !important;
    transform: translateY(-1px);
}

.accion-danger {
    color: #d64545;
}

.accion-danger:hover {
    background: #fdecec !important;
    color: #b91c1c !important;
    transform: translateY(-1px);
}

.accion-reactivar {
    color: #6b4bc9;
}

.accion-reactivar:hover {
    background: #efe9fb !important;
    color: #563a9e !important;
    transform: translateY(-1px);
}

/* ==========================================================
   Modal de roles
   ========================================================== */
.modal-roles :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-roles :deep(.ant-modal-body) {
    padding: 0;
}

.modal-roles__wrap {
    display: flex;
    flex-direction: column;
    background: #fff;
}

.modal-roles__head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f8fb 100%);
    border-bottom: 1px solid #e2e8f0;
}

.modal-roles__ico {
    width: 46px;
    height: 46px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 6px 16px -6px rgba(107, 75, 201, 0.55);
}

.modal-roles__meta {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.modal-roles__tipo {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b4bc9;
}

.modal-roles__titulo {
    font-size: 16px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.25;
    letter-spacing: -0.2px;
}

.modal-roles__sub {
    margin: 2px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.modal-roles__close {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.14s ease, color 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
}

.modal-roles__close:hover {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.modal-roles__body {
    padding: 18px 20px;
    max-height: 60vh;
    overflow-y: auto;
    background: #fff;
    scrollbar-width: thin;
}

.modal-roles__body::-webkit-scrollbar {
    width: 6px;
}

.modal-roles__body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.leyenda-roles {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.leyenda-roles__card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 12px 14px 12px 16px;
    border-radius: 12px;
    background: linear-gradient(135deg,
            color-mix(in srgb, var(--rol-color) 8%, #fff) 0%,
            #fff 60%);
    border: 1px solid color-mix(in srgb, var(--rol-color) 22%, #e2e8f0);
    overflow: hidden;
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.leyenda-roles__card::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--rol-color);
}

.leyenda-roles__card:hover {
    transform: translateY(-1px);
    border-color: var(--rol-color);
    box-shadow: 0 6px 16px -8px color-mix(in srgb, var(--rol-color) 55%, transparent);
}

.leyenda-roles__head {
    display: flex;
    align-items: center;
    gap: 8px;
}

.leyenda-roles__punto {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--rol-color);
    flex-shrink: 0;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--rol-color) 20%, transparent);
}

.leyenda-roles__titulo {
    font-size: 13.5px;
    font-weight: 800;
    color: color-mix(in srgb, var(--rol-color) 85%, #173a5f);
}

.leyenda-roles__desc {
    font-size: 12.5px;
    color: #2b3a4f;
    line-height: 1.5;
    padding-left: 16px;
}

.modal-roles__footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}
</style>