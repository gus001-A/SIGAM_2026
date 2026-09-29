<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    FilterOutlined,
    PlusOutlined,
    StopOutlined,
    TeamOutlined,
    ToolOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    proveedores: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
});

const tarjetas = computed(() => [
    { label: 'Proveedores totales', valor: props.kpis.total ?? 0, icono: TeamOutlined, color: '#0d84c9' },
    { label: 'Activos', valor: props.kpis.activos ?? 0, icono: CheckCircleOutlined, color: '#1f9e86' },
    { label: 'Inactivos', valor: props.kpis.inactivos ?? 0, icono: StopOutlined, color: '#d64545' },
    { label: 'Con equipos asignados', valor: props.kpis.con_equipos ?? 0, icono: ToolOutlined, color: '#6b4bc9' },
]);

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('proveedores.index', {
    filtros: {
        razon_social: props.filtros.razon_social ?? '',
        rfc: props.filtros.rfc ?? '',
        contacto: props.filtros.contacto ?? '',
        especialidad: props.filtros.especialidad ?? '',
        estado: props.filtros.estado ?? undefined,
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'razon_social', dir: props.orden.dir ?? 'asc' },
});

const opcionesEstado = [
    { label: 'Todos', value: 'todos' },
    { label: 'Activos', value: 'activo' },
    { label: 'Inactivos', value: 'inactivo' },
];

const columns = [
    { title: 'Razón social', key: 'razon_social', dataIndex: 'razon_social', sorter: true, filtro: 'texto', filtroClave: 'razon_social', width: 250 },
    { title: 'RFC', key: 'rfc', dataIndex: 'rfc', sorter: true, filtro: 'texto', filtroClave: 'rfc', width: 150 },
    { title: 'Contacto', key: 'contacto', filtro: 'texto', filtroClave: 'contacto', width: 220 },
    { title: 'Especialidad', key: 'especialidad', dataIndex: 'especialidad', sorter: true, filtro: 'texto', filtroClave: 'especialidad', width: 190 },
    { title: 'Equipos', key: 'equipos_count', dataIndex: 'equipos_count', sorter: true, align: 'right', width: 100 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 130 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 150, fixed: 'right' },
];

const totalPaginas = computed(() => {
    const p = props.proveedores;
    if (!p) return 1;
    return Number(p.last_page ?? 1);
});

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (proveedor) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${proveedor.razon_social}`,
        mensaje: 'El proveedor se conservará en el historial pero dejará de aparecer en los listados activos.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('proveedores.destroy', proveedor.id), { preserveScroll: true });
};

const reactivar = async (proveedor) => {
    const ok = await confirmar.value.abrir({
        titulo: `Reactivar ${proveedor.razon_social}`,
        mensaje: 'El proveedor volverá a aparecer como opción en los listados y al registrar equipos.',
        confirmar: 'Reactivar',
    });
    if (ok) router.put(route('proveedores.restore', proveedor.id), {}, { preserveScroll: true });
};

const aplicarEstado = () => {
    if (filtros.estado === 'todos') filtros.estado = undefined;
    aplicar();
};
</script>

<template>

    <Head title="Proveedores" />

    <AppLayout titulo="Proveedores" descripcion="Empresas que suministran equipos o servicios de mantenimiento.">
        <template #acciones>
            <a-button v-if="hayFiltros()" class="btn-limpiar" @click="limpiar">
                <template #icon>
                    <FilterOutlined />
                </template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('proveedores.crear')" type="primary" class="btn-nueva"
                @click="irA('proveedores.create')">
                <template #icon>
                    <PlusOutlined />
                </template>
                Nuevo proveedor
            </a-button>
        </template>

        <!-- KPIs con colores sólidos -->
        <div class="kpis">
            <div v-for="k in tarjetas" :key="k.label" class="kpi" :style="{ '--acc': k.color }">
                <div class="kpi__icono">
                    <component :is="k.icono" />
                </div>
                <div class="kpi__txt">
                    <div class="kpi__valor">{{ k.valor }}</div>
                    <div class="kpi__etq">{{ k.label }}</div>
                </div>
            </div>
        </div>

        <div class="tabla-proveedores" :class="{ 'tabla-proveedores--sin-paginacion': totalPaginas <= 1 }">
            <DataTableInertia :paginador="proveedores" :columns="columns" :orden="orden" :cargando="cargando"
                @cambio="onCambioTabla" @limpiar="limpiar">
                <template #filtro="{ column }">
                    <a-input v-if="column.filtro === 'texto'" v-model:value="filtros[column.filtroClave]" size="small"
                        allow-clear placeholder="Filtrar" @update:value="filtrar()" />
                    <a-select v-else-if="column.filtro === 'select'" v-model:value="filtros[column.filtroClave]"
                        :options="opcionesEstado" size="small" allow-clear placeholder="Todos" style="width: 100%"
                        @change="aplicarEstado" />
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'razon_social'">
                        <a class="link-proveedor" @click="irA('proveedores.show', record.id)">{{ record.razon_social }}</a>
                        <div v-if="record.nombre_comercial" class="proveedor-comercial">{{ record.nombre_comercial }}</div>
                    </template>

                    <template v-else-if="column.key === 'rfc'">
                        <span v-if="record.rfc" class="rfc-chip">{{ record.rfc }}</span>
                        <span v-else class="texto-suave">—</span>
                    </template>

                    <template v-else-if="column.key === 'contacto'">
                        <div v-if="record.contacto || record.telefono || record.correo" class="contacto">
                            <div v-if="record.contacto" class="contacto__nombre">
                                <span class="contacto__av">{{ record.contacto.charAt(0) }}</span>
                                {{ record.contacto }}
                            </div>
                            <div class="contacto__meta">
                                <span v-if="record.telefono" class="contacto__tel">
                                    <span class="dot dot--green"></span>{{ record.telefono }}
                                </span>
                                <span v-if="record.telefono && record.correo" class="contacto__sep">·</span>
                                <span v-if="record.correo" class="contacto__mail">{{ record.correo }}</span>
                            </div>
                        </div>
                        <span v-else class="texto-suave">—</span>
                    </template>

                    <template v-else-if="column.key === 'especialidad'">
                        <span v-if="record.especialidad" class="especialidad-chip">{{ record.especialidad }}</span>
                        <span v-else class="texto-suave">—</span>
                    </template>

                    <template v-else-if="column.key === 'equipos_count'">
                        <a-tag class="tag-equipos">{{ record.equipos_count }}</a-tag>
                    </template>

                    <template v-else-if="column.key === 'estado'">
                        <a-tag :color="record.estado === 'activo' ? 'green' : 'default'" class="tag-estado">
                            <span class="dot" :class="record.estado === 'activo' ? 'dot--green' : 'dot--gray'"></span>
                            {{ record.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'registrado'">
                        <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                    </template>

                    <template v-else-if="column.key === 'acciones'">
                        <a-space :size="2">
                            <a-tooltip title="Ver detalle">
                                <a-button type="text" size="small" class="accion accion--ver"
                                    @click="irA('proveedores.show', record.id)">
                                    <template #icon>
                                        <EyeOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>

                            <template v-if="record.estado === 'activo'">
                                <a-tooltip v-if="puede('proveedores.editar')" title="Editar">
                                    <a-button type="text" size="small" class="accion accion--editar"
                                        @click="irA('proveedores.edit', record.id)">
                                        <template #icon>
                                            <EditOutlined />
                                        </template>
                                    </a-button>
                                </a-tooltip>
                                <a-tooltip v-if="puede('proveedores.desactivar')" title="Desactivar">
                                    <a-button type="text" size="small" danger class="accion accion--peligro"
                                        @click="desactivar(record)">
                                        <template #icon>
                                            <DeleteOutlined />
                                        </template>
                                    </a-button>
                                </a-tooltip>
                            </template>

                            <template v-else-if="record.estado === 'inactivo'">
                                <a-tooltip v-if="puede('proveedores.editar')" title="Reactivar">
                                    <a-button type="text" size="small" class="accion accion--reactivar"
                                        @click="reactivar(record)">
                                        <template #icon>
                                            <UndoOutlined />
                                        </template>
                                    </a-button>
                                </a-tooltip>
                            </template>
                        </a-space>
                    </template>
                </template>
            </DataTableInertia>
        </div>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   KPIs con colores sólidos
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
    margin-bottom: 14px;
}

.kpi {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    position: relative;
    overflow: hidden;
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}

.kpi__icono {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: var(--acc);
    flex-shrink: 0;
}

.kpi__valor {
    font-size: 18px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.1;
}

.kpi__etq {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 767px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* ==========================================================
   Botones superiores (colores sólidos)
   ========================================================== */
.btn-limpiar {
    background: #fff;
    border: 1px solid #c9b8f0;
    color: #6b4bc9;
}

.btn-limpiar:hover {
    background: #faf7ff !important;
    border-color: #6b4bc9 !important;
    color: #563a9e !important;
}

.btn-nueva {
    background: #0d84c9 !important;
    border-color: #0d84c9 !important;
    color: #fff !important;
}

.btn-nueva:hover {
    background: #0f6fb0 !important;
    border-color: #0f6fb0 !important;
}

/* ==========================================================
   Tabla
   ========================================================== */
.tabla-proveedores {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--sigam-borde-suave);
    box-shadow: var(--sigam-sombra-sm);
    background: #fff;
}

/* Encabezado gris azulado claro (como Equipos) */
.tabla-proveedores :deep(.ant-table-thead > tr > th) {
    background: var(--sigam-navy-050) !important;
    color: var(--sigam-navy) !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-size: 11px;
    border-bottom: 1px solid var(--sigam-borde-suave) !important;
}

.tabla-proveedores :deep(.ant-table-thead > tr > th::before) {
    background-color: var(--sigam-borde-suave) !important;
}

/* Filas alternas */
.tabla-proveedores :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid var(--sigam-borde-suave) !important;
}

.tabla-proveedores :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

/* Hover de fila neutro */
.tabla-proveedores :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

/* Columna de acciones fija */
.tabla-proveedores :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid var(--sigam-borde-suave);
}

.tabla-proveedores :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

/* ==========================================================
   Paginación
   ========================================================== */
.tabla-proveedores :deep(.ant-pagination-total-text),
.tabla-proveedores :deep(.ant-pagination-options-quick-jumper) {
    display: none !important;
}

.tabla-proveedores--sin-paginacion :deep(.ant-pagination) {
    display: none !important;
}

.tabla-proveedores :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
}

.tabla-proveedores :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #0d84c9;
    font-weight: 800;
}

/* ==========================================================
   Celdas
   ========================================================== */
.link-proveedor {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13px;
}

.link-proveedor:hover {
    color: #0d84c9;
    text-decoration: underline;
}

.proveedor-comercial {
    font-size: 11px;
    color: var(--sigam-tenue);
    margin-top: 2px;
    font-style: italic;
}

.rfc-chip {
    display: inline-flex;
    align-items: center;
    padding: 1px 10px;
    border-radius: 999px;
    background: #efe9fb;
    color: #6b4bc9;
    border: 1px solid #ddd2f3;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
    line-height: 18px;
}

.texto-suave {
    color: var(--sigam-tenue);
}

/* Contacto */
.contacto__nombre {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: var(--sigam-texto);
    font-size: 12.5px;
}

.contacto__av {
    width: 22px;
    height: 22px;
    flex: none;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10.5px;
    font-weight: 800;
    color: #fff;
    background: #0d84c9;
}

.contacto__meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--sigam-tenue);
    margin-top: 2px;
    flex-wrap: wrap;
}

.contacto__tel {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
}

.contacto__sep {
    opacity: 0.6;
}

/* Especialidad */
.especialidad-chip {
    display: inline-flex;
    align-items: center;
    padding: 1px 10px;
    border-radius: 999px;
    background: #e4f4ec;
    color: #16806c;
    border: 1px solid #cbeadd;
    font-size: 11px;
    font-weight: 700;
    line-height: 18px;
}

/* Tag equipos */
.tag-equipos {
    background: #e6f2fb;
    color: #0d6fae;
    border: 1px solid #cfe3f2;
    font-weight: 800;
    border-radius: 999px;
    padding: 0 10px;
    line-height: 20px;
}

/* Tag estado */
.tag-estado {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    border-radius: 999px;
    padding: 0 10px;
    line-height: 20px;
}

.dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
}

.dot--green {
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
}

.dot--gray {
    background: #94a3b8;
    box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.18);
}

/* ==========================================================
   Acciones
   ========================================================== */
.accion {
    border-radius: 8px;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.accion--ver {
    color: #0d84c9;
}

.accion--ver:hover {
    background: #e6f2fb !important;
    color: #0f6fb0 !important;
    transform: translateY(-1px);
}

.accion--editar {
    color: #6b4bc9;
}

.accion--editar:hover {
    background: #efe9fb !important;
    color: #563a9e !important;
    transform: translateY(-1px);
}

.accion--reactivar {
    color: #1f9e86;
}

.accion--reactivar:hover {
    background: #e4f4ec !important;
    color: #16806c !important;
    transform: translateY(-1px);
}

.accion--peligro:hover {
    background: #fdecec !important;
    transform: translateY(-1px);
}
</style>