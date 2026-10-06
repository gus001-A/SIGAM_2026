<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BankOutlined,
    CalendarOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    EnvironmentOutlined,
    ExclamationCircleOutlined,
    EyeOutlined,
    FilterOutlined,
    PlusOutlined,
    StopOutlined,
    ThunderboltOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    planes: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    sucursalId: { type: [Number, String], default: null },
    puedeVerTodas: { type: Boolean, default: false },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

/* ==========================================================
   KPIs vibrantes con gradientes
   ========================================================== */
const tarjetas = computed(() => [
    {
        label: 'Planes totales',
        valor: props.kpis.total ?? 0,
        icono: CalendarOutlined,
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
        soft: '#e7f7f2',
    },
    {
        label: 'Vencidos',
        valor: props.kpis.vencidos ?? 0,
        icono: ExclamationCircleOutlined,
        color: '#d64545',
        color2: '#b91c1c',
        soft: '#fdecec',
    },
    {
        label: 'Inactivos',
        valor: props.kpis.inactivos ?? 0,
        icono: StopOutlined,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
    },
]);

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('planes.index', {
    filtros: {
        sucursal_id: props.sucursalId ?? 'todas',
        equipo: props.filtros.equipo ?? '',
        nombre: props.filtros.nombre ?? '',
        tipo_mantenimiento_id: props.filtros.tipo_mantenimiento_id ?? undefined,
        tecnico_id: props.filtros.tecnico_id ?? undefined,
        frecuencia: props.filtros.frecuencia ?? undefined,
        vencidos: props.filtros.vencidos ? true : undefined,
        desde: props.filtros.desde ?? '',
        hasta: props.filtros.hasta ?? '',
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'proxima_fecha', dir: props.orden.dir ?? 'asc' },
});

const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));
const opcionesFrecuencia = computed(() =>
    (props.catalogos.frecuencias ?? []).map((f) => ({ label: etiquetaFrecuencia(f), value: f })),
);
const opcionesFiltro = {
    tipo_mantenimiento_id: computed(() => opciones(props.catalogos.tipos)),
    tecnico_id: computed(() => opciones(props.catalogos.tecnicos)),
    frecuencia: opcionesFrecuencia,
};

const opcionesSucursal = computed(() => [
    ...(props.puedeVerTodas ? [{ value: 'todas', label: 'Todas las sucursales' }] : []),
    ...opciones(props.catalogos.sucursales),
]);

const cambiarSucursal = (v) => {
    filtros.sucursal_id = v;
    aplicar();
};

function etiquetaFrecuencia(f) {
    return {
        dias: 'Cada N días',
        semanal: 'Semanal',
        mensual: 'Mensual',
        bimestral: 'Bimestral',
        trimestral: 'Trimestral',
        semestral: 'Semestral',
        anual: 'Anual',
        personalizada: 'Personalizada',
    }[f] ?? f;
}

/* Etiqueta de frecuencia ya con el valor real cuando aplica */
const etiquetaFrecuenciaCompleta = (record) => {
    if (record.frecuencia === 'dias') {
        const v = Math.max(1, Number(record.valor_frecuencia) || 1);
        return `Cada ${v} día${v === 1 ? '' : 's'}`;
    }
    return etiquetaFrecuencia(record.frecuencia);
};

const columns = [
    { title: 'Plan', key: 'equipo', filtro: 'texto', filtroClave: 'equipo', width: 260 },
    { title: 'Sucursal', key: 'sucursal', width: 150 },
    { title: 'Tipo', key: 'tipo', filtro: 'select', filtroClave: 'tipo_mantenimiento_id', width: 170 },
    { title: 'Frecuencia', key: 'frecuencia', filtro: 'select', filtroClave: 'frecuencia', width: 150 },
    { title: 'Próxima fecha', key: 'proxima_fecha', dataIndex: 'proxima_fecha', sorter: true, width: 150 },
    { title: 'Técnico', key: 'tecnico', filtro: 'select', filtroClave: 'tecnico_id', width: 170 },
    { title: 'Ocurrencias', key: 'ocurrencias_count', dataIndex: 'ocurrencias_count', sorter: true, align: 'right', width: 120 },
    { title: 'Estado', key: 'estado', width: 120 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 70, fixed: 'right' },
];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const irA = (n, p) => router.visit(route(n, p));
</script>

<template>
    <Head title="Planes preventivos" />

    <AppLayout
        titulo="Planes de mantenimiento preventivo"
        descripcion="Programas periódicos por equipo o instalación que generan órdenes automáticamente según su frecuencia."
    >
        <!-- ==========================================================
             Botón "Nuevo plan" arriba, al mismo nivel que el título
             ========================================================== -->
        <template #acciones>
            <a-button
                v-if="puede('mantenimientos.crear')"
                type="primary"
                class="btn-nueva"
                @click="irA('planes.create')"
            >
                <template #icon>
                    <PlusOutlined />
                </template>
                Nuevo plan
            </a-button>
        </template>

        <!-- KPIs vibrantes -->
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
             Barra unificada de filtros (sucursal + fecha + vencidos + limpiar)
             ========================================================== -->
        <div class="barra-filtros">
            <div class="barra-filtros__grupo">
                <span class="barra-filtros__ic" style="--c: #1f9e86">
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
                <span class="barra-filtros__l">Próxima fecha</span>
                <a-range-picker
                    :value="[filtros.desde || null, filtros.hasta || null]"
                    value-format="YYYY-MM-DD"
                    :allow-empty="[true, true]"
                    placeholder="['Desde', 'Hasta']"
                    class="barra-filtros__fechas"
                    @change="(_, s) => { filtros.desde = s[0] || ''; filtros.hasta = s[1] || ''; aplicar(); }"
                />
            </div>

            <div class="barra-filtros__sep"></div>

            <label class="barra-filtros__check" :class="{ 'barra-filtros__check--active': !!filtros.vencidos }">
                <a-checkbox
                    :checked="!!filtros.vencidos"
                    @change="(e) => { filtros.vencidos = e.target.checked || undefined; aplicar(); }"
                />
                <span class="barra-filtros__check-txt">
                    <ExclamationCircleOutlined /> Solo vencidos
                </span>
            </label>

            <a-button v-if="hayFiltros()" class="barra-filtros__limpiar" @click="limpiar">
                <template #icon>
                    <FilterOutlined />
                </template>
                Limpiar filtros
            </a-button>
        </div>

        <!-- Tabla -->
        <div class="tabla-planes">
            <DataTableInertia
                :paginador="planes"
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
                    <template v-if="column.key === 'equipo'">
                        <a class="plan" @click="irA('planes.show', record.id)">
                            <span
                                class="plan__dot"
                                :style="{ background: record.estado === 'activo' ? (record.vencido ? '#d64545' : '#1f9e86') : '#94a3b8' }"
                            ></span>
                            {{ record.nombre || 'Plan preventivo' }}
                        </a>
                        <div v-if="record.objetivo" class="obj" :class="`obj--${record.objetivo.tipo}`">
                            <span class="obj__ic">
                                <ToolOutlined v-if="record.objetivo.tipo === 'equipo'" />
                                <EnvironmentOutlined v-else />
                            </span>
                            <span class="obj__t">{{ record.objetivo.texto }}</span>
                        </div>
                    </template>

                    <template v-else-if="column.key === 'sucursal'">
                        <span v-if="record.sucursal" class="sucursal">
                            <BankOutlined /> {{ record.sucursal }}
                        </span>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'tipo'">
                        <span v-if="record.tipo" class="tipo">{{ record.tipo }}</span>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'frecuencia'">
                        <a-tag class="tag-frecuencia">
                            <ThunderboltOutlined class="tag-frecuencia__ic" />
                            {{ etiquetaFrecuenciaCompleta(record) }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'proxima_fecha'">
                        <a-tag
                            :color="record.vencido ? 'error' : 'blue'"
                            class="tag-fecha"
                        >
                            <CalendarOutlined class="tag-fecha__ic" />
                            {{ fecha(record.proxima_fecha) }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'tecnico'">
                        <span v-if="record.tecnico" class="tecnico">
                            <span class="tecnico__av">{{ record.tecnico.charAt(0).toUpperCase() }}</span>
                            {{ record.tecnico }}
                        </span>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'ocurrencias_count'">
                        <a-tag class="tag-ocurrencias">{{ record.ocurrencias_count }}</a-tag>
                    </template>

                    <template v-else-if="column.key === 'estado'">
                        <a-tag
                            :color="record.estado === 'activo' ? 'green' : 'default'"
                            class="tag-estado"
                        >
                            <span
                                class="tag-estado__dot"
                                :style="{ background: record.estado === 'activo' ? '#1f9e86' : '#94a3b8' }"
                            ></span>
                            {{ record.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'registrado'">
                        <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                    </template>

                    <template v-else-if="column.key === 'acciones'">
                        <a-button type="text" size="small" class="accion-ver" @click="irA('planes.show', record.id)">
                            <template #icon><EyeOutlined /></template>
                        </a-button>
                    </template>
                </template>
            </DataTableInertia>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Botón Nueva plan (arriba, en #acciones)
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
   Barra unificada de filtros
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
    min-width: 220px;
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

/* Check "Solo vencidos" pill */
.barra-filtros__check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px 6px 10px;
    border-radius: 999px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    user-select: none;
    transition: border-color 0.16s ease, background 0.16s ease;
}

.barra-filtros__check:hover {
    border-color: #f4dede;
    background: #fdf4f4;
}

.barra-filtros__check--active {
    border-color: #f4b9b9;
    background: #fdecec;
}

.barra-filtros__check-txt {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 800;
    color: #64748b;
    white-space: nowrap;
    transition: color 0.16s ease;
}

.barra-filtros__check--active .barra-filtros__check-txt {
    color: #b91c1c;
}

.barra-filtros__check-txt .anticon {
    font-size: 12px;
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
.tabla-planes {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    background: #fff;
}

.tabla-planes :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(180deg, #f5f8fb 0%, #eef3f8 100%) !important;
    color: #173a5f !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 11px;
    border-bottom: 1px solid #dbe3ec !important;
}

.tabla-planes :deep(.ant-table-thead > tr > th::before) {
    background-color: #dbe3ec !important;
}

.tabla-planes :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid #eef2f7 !important;
}

.tabla-planes :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

.tabla-planes :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

.tabla-planes :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid #eef2f7;
}

.tabla-planes :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

.tabla-planes :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
    background: #e6f2fb;
}

.tabla-planes :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #0f6fb0;
    font-weight: 800;
}

/* ==========================================================
   Celda "Plan"
   ========================================================== */
.plan {
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

.plan:hover {
    color: #0d84c9;
    border-bottom-color: #0d84c9;
}

.plan__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(15, 37, 71, 0.06);
}

/* Objetivo */
.obj {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-top: 3px;
    min-width: 0;
    max-width: 100%;
}

.obj__ic {
    width: 22px;
    height: 22px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #fff;
    flex-shrink: 0;
}

.obj--equipo .obj__ic {
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 2px 5px rgba(13, 132, 201, 0.28);
}

.obj--ubicacion .obj__ic {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 2px 5px rgba(31, 158, 134, 0.28);
}

.obj__t {
    font-size: 12px;
    color: #7b8a9c;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ==========================================================
   Celdas auxiliares
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

.tipo {
    font-size: 12.5px;
    font-weight: 600;
    color: #2b3a4f;
}

.tecnico {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-weight: 600;
    color: #2b3a4f;
    font-size: 12.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.tecnico__av {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(31, 158, 134, 0.35);
}

.vacio {
    color: #94a3b8;
    font-size: 12.5px;
}

/* ==========================================================
   Tags
   ========================================================== */
.tag-frecuencia {
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
    background: #fdf3e6 !important;
    color: #a86717 !important;
    box-shadow: 0 1px 3px rgba(224, 138, 30, 0.15);
}

.tag-frecuencia__ic {
    font-size: 11px;
    color: #e08a1e;
}

.tag-fecha {
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

.tag-fecha__ic {
    font-size: 11px;
    opacity: 0.85;
}

.tag-ocurrencias {
    font-weight: 800;
    font-size: 12px;
    padding: 2px 10px;
    border-radius: 999px;
    line-height: 20px;
    border: none !important;
    background: #eef4fb !important;
    color: #0f6fb0 !important;
    box-shadow: 0 1px 3px rgba(13, 132, 201, 0.12);
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
   Botón ver
   ========================================================== */
.accion-ver {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 9px;
    color: #0d84c9;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.accion-ver:hover {
    background: #e6f2fb !important;
    color: #0f6fb0 !important;
    transform: translateY(-1px);
}
</style>