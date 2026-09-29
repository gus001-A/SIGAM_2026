<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BankOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    CloseCircleOutlined,
    EnvironmentOutlined,
    EyeOutlined,
    FileTextOutlined,
    FilterOutlined,
    PlusOutlined,
    SyncOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ModalSolicitud from '@/Components/ModalSolicitud.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    solicitudes: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    sucursalId: { type: [Number, String], default: null },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

/* ==========================================================
   KPIs vibrantes con gradientes
   ========================================================== */
const tarjetas = computed(() => [
    {
        label: 'Solicitudes totales',
        valor: props.kpis.total ?? 0,
        icono: FileTextOutlined,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
    },
    {
        label: 'Pendientes de autorizar',
        valor: props.kpis.pendientes ?? 0,
        icono: ClockCircleOutlined,
        color: '#e08a1e',
        color2: '#a86717',
        soft: '#fdf3e6',
    },
    {
        label: 'Autorizadas',
        valor: props.kpis.autorizadas ?? 0,
        icono: CheckCircleOutlined,
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e4f4ec',
    },
    {
        label: 'Canceladas',
        valor: props.kpis.canceladas ?? 0,
        icono: CloseCircleOutlined,
        color: '#d64545',
        color2: '#b91c1c',
        soft: '#fdecec',
    },
]);

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('solicitudes.index', {
    filtros: {
        sucursal_id: props.sucursalId ?? 'todas',
        folio: props.filtros.folio ?? '',
        equipo: props.filtros.equipo ?? '',
        solicitante: props.filtros.solicitante ?? '',
        prioridad_id: props.filtros.prioridad_id ?? undefined,
        estado_id: props.filtros.estado_id ?? undefined,
        desde: props.filtros.desde ?? '',
        hasta: props.filtros.hasta ?? '',
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'id', dir: props.orden.dir ?? 'desc' },
});

const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));
const opcionesFiltro = {
    prioridad_id: computed(() => opciones(props.catalogos.prioridades)),
    estado_id: computed(() => opciones(props.catalogos.estados)),
};

const opcionesSucursal = computed(() => [
    { value: 'todas', label: 'Todas las sucursales' },
    ...opciones(props.catalogos.sucursales),
]);

const cambiarSucursal = (v) => {
    filtros.sucursal_id = v;
    aplicar();
};

/* Columnas */
const columns = [
    { title: 'Folio', key: 'folio', dataIndex: 'folio', sorter: true, filtro: 'texto', filtroClave: 'folio', width: 150 },
    { title: 'Equipo / instalación', key: 'objetivo', filtro: 'texto', filtroClave: 'equipo', width: 280 },
    { title: 'Sucursal', key: 'sucursal', width: 170 },
    { title: 'Prioridad', key: 'prioridad', filtro: 'select', filtroClave: 'prioridad_id', width: 150 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado_id', width: 160 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 170 },
    { title: '', key: 'acciones', align: 'right', width: 70, fixed: 'right' },
];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const irA = (n, p) => router.visit(route(n, p));

/* ==========================================================
   Estados: color + hex para el dot
   ========================================================== */
const ESTADOS = {
    solicitado: { color: 'gold', hex: '#e08a1e', icono: ClockCircleOutlined },
    autorizado: { color: 'green', hex: '#1f9e86', icono: CheckCircleOutlined },
    asignado: { color: 'cyan', hex: '#0d84c9', icono: SyncOutlined },
    en_proceso: { color: 'blue', hex: '#0d84c9', icono: SyncOutlined },
    en_espera_refaccion: { color: 'orange', hex: '#a86717', icono: ClockCircleOutlined },
    fuera_de_servicio: { color: 'volcano', hex: '#d64545', icono: CloseCircleOutlined },
    realizado: { color: 'lime', hex: '#16a34a', icono: CheckCircleOutlined },
    supervisado: { color: 'geekblue', hex: '#6b4bc9', icono: CheckCircleOutlined },
    cerrado: { color: 'default', hex: '#64748b', icono: CheckCircleOutlined },
    reprogramado: { color: 'purple', hex: '#6b4bc9', icono: SyncOutlined },
    cancelado: { color: 'red', hex: '#d64545', icono: CloseCircleOutlined },
};

const estadoInfo = (record) => {
    const clave = record?.estado?.clave;
    return ESTADOS[clave] ?? { color: 'default', hex: '#64748b', icono: ClockCircleOutlined };
};

const modalSolicitud = ref(null);
</script>

<template>
    <Head title="Solicitudes" />

    <AppLayout
        titulo="Solicitudes de servicio"
        descripcion="Reportes de falla o necesidades de servicio de cualquier área, pendientes de autorizar y convertir en órdenes."
    >
        <template #acciones>
            <a-button
                v-if="puede('solicitudes.crear')"
                type="primary"
                class="btn-nueva"
                @click="modalSolicitud.abrir()"
            >
                <template #icon>
                    <PlusOutlined />
                </template>
                Nueva solicitud
            </a-button>
        </template>

        <!-- ==========================================================
             KPIs vibrantes con gradientes
             ========================================================== -->
        <div class="kpis">
            <div
                v-for="k in tarjetas"
                :key="k.label"
                class="kpi"
                :style="{
                    '--acc': k.color,
                    '--acc2': k.color2,
                    '--soft': k.soft,
                }"
            >
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

        <!-- ==========================================================
             Barra unificada de filtros
             ========================================================== -->
        <div class="barra-filtros">
            <div class="barra-filtros__grupo">
                <span class="barra-filtros__ic" style="--c: #0d84c9">
                    <BankOutlined />
                </span>
                <span class="barra-filtros__l">Sucursal</span>
                <a-select
                    :value="filtros.sucursal_id"
                    :options="opcionesSucursal"
                    class="barra-filtros__select"
                    @update:value="cambiarSucursal"
                />
            </div>

            <div class="barra-filtros__sep"></div>

            <div class="barra-filtros__grupo">
                <span class="barra-filtros__ic" style="--c: #6b4bc9">
                    <ClockCircleOutlined />
                </span>
                <a-range-picker
                    :value="[filtros.desde || null, filtros.hasta || null]"
                    value-format="YYYY-MM-DD"
                    :allow-empty="[true, true]"
                    placeholder="['Desde', 'Hasta']"
                    class="barra-filtros__fechas"
                    @change="(_, s) => { filtros.desde = s[0] || ''; filtros.hasta = s[1] || ''; aplicar(); }"
                />
            </div>

            <a-button v-if="hayFiltros()" class="barra-filtros__limpiar" @click="limpiar">
                <template #icon>
                    <FilterOutlined />
                </template>
                Limpiar filtros
            </a-button>
        </div>

        <!-- ==========================================================
             Tabla
             ========================================================== -->
        <div class="tabla-solicitudes">
            <DataTableInertia
                :paginador="solicitudes"
                :columns="columns"
                :orden="orden"
                :cargando="cargando"
                @cambio="onCambioTabla"
                @limpiar="limpiar"
            >
                <template #filtro="{ column }">
                    <a-input
                        v-if="column.filtro === 'texto'"
                        v-model:value="filtros[column.filtroClave]"
                        size="small"
                        allow-clear
                        placeholder="Filtrar"
                        @update:value="filtrar()"
                    />
                    <a-select
                        v-else-if="column.filtro === 'select' && opcionesFiltro[column.filtroClave]"
                        v-model:value="filtros[column.filtroClave]"
                        :options="opcionesFiltro[column.filtroClave].value"
                        size="small"
                        allow-clear
                        placeholder="Todos"
                        style="width: 100%"
                        @change="aplicar()"
                    />
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'folio'">
                        <a class="folio" @click="irA('solicitudes.show', record.id)">
                            <span
                                class="folio__dot"
                                :style="{ background: estadoInfo(record).hex }"
                            ></span>
                            {{ record.folio }}
                        </a>
                    </template>

                    <template v-else-if="column.key === 'objetivo'">
                        <span
                            v-if="record.objetivo"
                            class="obj"
                            :class="`obj--${record.objetivo.tipo}`"
                        >
                            <span class="obj__ic">
                                <ToolOutlined v-if="record.objetivo.tipo === 'equipo'" />
                                <EnvironmentOutlined v-else />
                            </span>
                            <span class="obj__t">{{ record.objetivo.texto }}</span>
                        </span>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'sucursal'">
                        <span v-if="record.sucursal" class="sucursal">
                            <BankOutlined />
                            {{ record.sucursal }}
                        </span>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'prioridad'">
                        <a-tag
                            v-if="record.prioridad"
                            :color="record.prioridad.color || 'default'"
                            class="tag-prioridad"
                        >
                            {{ record.prioridad.nombre }}
                        </a-tag>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'estado'">
                        <a-tag :color="estadoInfo(record).color" class="tag-estado">
                            <span
                                class="tag-estado__dot"
                                :style="{ background: estadoInfo(record).hex }"
                            ></span>
                            <component :is="estadoInfo(record).icono" class="tag-estado__ic" />
                            <span>{{ record.estado?.nombre }}</span>
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'registrado'">
                        <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                    </template>

                    <template v-else-if="column.key === 'acciones'">
                        <a-button
                            type="text"
                            size="small"
                            class="accion-ver"
                            @click="irA('solicitudes.show', record.id)"
                        >
                            <template #icon>
                                <EyeOutlined />
                            </template>
                        </a-button>
                    </template>
                </template>
            </DataTableInertia>
        </div>

        <ModalSolicitud
            ref="modalSolicitud"
            :equipos="catalogos.equipos ?? []"
            :ubicaciones="catalogos.ubicaciones ?? []"
            :prioridades="catalogos.prioridades ?? []"
        />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Botón Nueva solicitud
   ========================================================== */
.btn-nueva {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%) !important;
    border-color: #1f9e86 !important;
    box-shadow: 0 4px 12px rgba(31, 158, 134, 0.32);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}
.btn-nueva:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 6px 16px rgba(31, 158, 134, 0.45);
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
   Barra de filtros
   ========================================================== */
.barra-filtros {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
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

.barra-filtros__select {
    min-width: 260px;
}

.barra-filtros__fechas {
    min-width: 260px;
}

.barra-filtros__sep {
    width: 1px;
    height: 24px;
    background: #e2e8f0;
    flex: none;
}

.barra-filtros__limpiar {
    margin-left: auto;
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

@media (max-width: 767px) {
    .barra-filtros__sep {
        display: none;
    }
    .barra-filtros__limpiar {
        margin-left: 0;
        width: 100%;
        justify-content: center;
    }
    .barra-filtros__select,
    .barra-filtros__fechas {
        min-width: 0;
        flex: 1;
    }
}

/* ==========================================================
   Tabla
   ========================================================== */
.tabla-solicitudes {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    background: #fff;
}

.tabla-solicitudes :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(180deg, #f5f8fb 0%, #eef3f8 100%) !important;
    color: #173a5f !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 11px;
    border-bottom: 1px solid #dbe3ec !important;
}

.tabla-solicitudes :deep(.ant-table-thead > tr > th::before) {
    background-color: #dbe3ec !important;
}

.tabla-solicitudes :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid #eef2f7 !important;
}

.tabla-solicitudes :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

.tabla-solicitudes :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

.tabla-solicitudes :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid #eef2f7;
}

.tabla-solicitudes :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

.tabla-solicitudes :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #1f9e86;
    background: #e4f4ec;
}

.tabla-solicitudes :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #16806c;
    font-weight: 800;
}

:deep(.ant-pagination-total-text) {
    display: none !important;
}

/* ==========================================================
   Folio (con dot de estado)
   ========================================================== */
.folio {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #173a5f;
    cursor: pointer;
    text-decoration: none;
    border-bottom: 1px dashed transparent;
    transition: color 0.14s ease, border-color 0.14s ease;
}

.folio:hover {
    color: #1f9e86;
    border-bottom-color: #1f9e86;
}

.folio__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(15, 37, 71, 0.08);
}

/* ==========================================================
   Objetivo (equipo / instalación)
   ========================================================== */
.obj {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    max-width: 100%;
}

.obj__ic {
    width: 28px;
    height: 28px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    flex-shrink: 0;
}

.obj--equipo .obj__ic {
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 2px 6px rgba(13, 132, 201, 0.3);
}

.obj--ubicacion .obj__ic {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 2px 6px rgba(31, 158, 134, 0.3);
}

.obj__t {
    font-weight: 600;
    color: #2b3a4f;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 13px;
}

/* ==========================================================
   Sucursal
   ========================================================== */
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

/* ==========================================================
   Tag de prioridad
   ========================================================== */
.tag-prioridad {
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    line-height: 20px;
    border: none !important;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.08);
    margin: 0;
}

/* ==========================================================
   Tag de estado (con dot + ícono)
   ========================================================== */
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

.tag-estado__ic {
    font-size: 11px;
    opacity: 0.85;
}

/* ==========================================================
   Vacío
   ========================================================== */
.vacio {
    color: #94a3b8;
    font-size: 12.5px;
}

/* ==========================================================
   Botón ver
   ========================================================== */
.accion-ver {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 9px;
    color: #1f9e86;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.accion-ver:hover {
    background: #e4f4ec !important;
    color: #16806c !important;
    transform: translateY(-1px);
}
</style>