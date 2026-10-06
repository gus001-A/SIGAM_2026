<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    ExclamationCircleOutlined,
    EyeOutlined,
    FilterOutlined,
    FolderOutlined,
    HourglassOutlined,
    PlusOutlined,
    SyncOutlined,
    UnorderedListOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ModalTarea from '@/Components/ModalTarea.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    tareas: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    vista: { type: String, default: 'activas' },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const vista = ref(props.vista === 'completadas' ? 'completadas' : 'activas');

const { filtros, orden, cargando, navegar, onCambioTabla } = useTablaInertia('tareas.index', {
    filtros: {
        descripcion: props.filtros.descripcion ?? '',
        estado: props.filtros.estado ?? undefined,
        responsable: props.filtros.responsable ?? '',
        prioridad_id: props.filtros.prioridad_id ?? undefined,
        clasificacion: props.filtros.clasificacion ?? undefined,
        desde: props.filtros.desde ?? '',
        hasta: props.filtros.hasta ?? '',
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'fecha_limite', dir: props.orden.dir ?? 'asc' },
});

let temporizador = null;
const conVista = (extra = {}) => navegar({ vista: vista.value, ...extra });
const filtrar = (ms = 350) => {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => conVista({ page: 1 }), ms);
};
const aplicar = () => {
    clearTimeout(temporizador);
    conVista({ page: 1 });
};
const limpiar = () => {
    Object.keys(filtros).forEach((k) => (filtros[k] = undefined));
    conVista({ page: 1 });
};
const hayFiltros = () => Object.values(filtros).some((v) => v !== '' && v !== null && v !== undefined);
const onCambio = (paginacion, f, sorter) => {
    clearTimeout(temporizador);
    onCambioTabla(paginacion, f, sorter);
};

const cambiarVista = (v) => {
    vista.value = v;
    Object.keys(filtros).forEach((k) => (filtros[k] = undefined));
    conVista({ page: 1 });
};

const opcionesEstado = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'en_proceso', label: 'En proceso' },
    { value: 'realizada', label: 'Realizada' },
    { value: 'cancelada', label: 'Cancelada' },
];

/* Paleta de estados más viva y consistente */
const ESTADO_META = {
    pendiente:  { color: 'gold',  hex: '#e08a1e', soft: '#fdf3e6' },
    en_proceso: { color: 'blue',  hex: '#0d84c9', soft: '#e6f2fb' },
    realizada:  { color: 'green', hex: '#1f9e86', soft: '#e4f4ec' },
    cancelada:  { color: 'red',   hex: '#d64545', soft: '#fdecec' },
};
const colorEstado = (e) => ESTADO_META[e]?.color ?? 'default';
const hexEstado = (e) => ESTADO_META[e]?.hex ?? '#64748b';

const opcionesClasificacion = [
    { value: 'general', label: 'General' },
    { value: 'proyecto', label: 'Por proyecto' },
    { value: 'categoria', label: 'Categoría' },
];
const ICONO_CLASIFICACION = { proyecto: FolderOutlined, categoria: ApartmentOutlined };

const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));
const opcionesFiltro = {
    estado: computed(() => opcionesEstado),
    prioridad_id: computed(() => opciones(props.catalogos.prioridades)),
    clasificacion: computed(() => opcionesClasificacion),
};

const columns = computed(() => [
    { title: 'Tarea', key: 'tarea', filtro: 'texto', filtroClave: 'descripcion', width: 300 },
    { title: 'Responsable(s)', key: 'responsables', filtro: 'texto', filtroClave: 'responsable', width: 190 },
    { title: 'Fecha límite', key: 'fecha_limite', dataIndex: 'fecha_limite', sorter: true, width: 140 },
    { title: 'Prioridad', key: 'prioridad', filtro: 'select', filtroClave: 'prioridad_id', width: 130 },
    { title: 'Clasificación', key: 'clasificacion', filtro: 'select', filtroClave: 'clasificacion', width: 170 },
    {
        title: 'Estado',
        key: 'estado',
        filtro: vista.value === 'activas' ? 'select' : undefined,
        filtroClave: 'estado',
        width: 140,
    },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 70, fixed: 'right' },
]);

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const irA = (n, p) => router.visit(route(n, p));

const modalTarea = ref(null);

/* ==========================================================
   KPIs vibrantes — 5 tarjetas
   ========================================================== */
const tarjetas = computed(() => [
    {
        label: 'Totales',
        valor: props.kpis.total ?? 0,
        icono: UnorderedListOutlined,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
    },
    {
        label: 'Vencidas',
        valor: props.kpis.vencidas ?? 0,
        icono: ExclamationCircleOutlined,
        color: '#d64545',
        color2: '#b91c1c',
        soft: '#fdecec',
    },
    {
        label: 'Pendientes',
        valor: props.kpis.pendientes ?? 0,
        icono: HourglassOutlined,
        color: '#e08a1e',
        color2: '#a86717',
        soft: '#fdf3e6',
    },
    {
        label: 'En proceso',
        valor: props.kpis.en_proceso ?? 0,
        icono: ClockCircleOutlined,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
    },
    {
        label: 'Realizadas',
        valor: props.kpis.realizadas ?? 0,
        icono: CheckCircleOutlined,
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e4f4ec',
    },
]);
</script>

<template>
    <Head title="Tareas" />

    <AppLayout
        titulo="Tareas"
        descripcion="Seguimiento de tareas administrativas: responsables, fechas límite y su avance hasta el cierre."
    >
        <template #acciones>
            <a-range-picker
                :value="[filtros.desde || null, filtros.hasta || null]"
                value-format="YYYY-MM-DD"
                :allow-empty="[true, true]"
                placeholder="['Desde', 'Hasta']"
                @change="(_, s) => { filtros.desde = s[0] || ''; filtros.hasta = s[1] || ''; aplicar(); }"
            />
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('tareas.crear')" type="primary" class="btn-nueva" @click="modalTarea.abrir()">
                <template #icon><PlusOutlined /></template>
                Nueva tarea
            </a-button>
        </template>

        <!-- KPIs con gradientes y colores vivos -->
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

        <!-- Switch Activas / Completadas -->
        <div
            class="modo-switch"
            :class="{ 'modo-switch--completadas': vista === 'completadas' }"
            role="tablist"
            aria-label="Vista de tareas"
        >
            <span class="modo-switch__slider" aria-hidden="true"></span>
            <button
                type="button"
                role="tab"
                class="modo-switch__opt modo-switch__opt--activas"
                :class="{ 'modo-switch__opt--active': vista === 'activas' }"
                :aria-selected="vista === 'activas'"
                @click="cambiarVista('activas')"
            >
                <SyncOutlined />
                <span>Activas</span>
                <span class="modo-switch__badge modo-switch__badge--activas">
                    {{ (kpis.pendientes ?? 0) + (kpis.en_proceso ?? 0) }}
                </span>
            </button>
            <button
                type="button"
                role="tab"
                class="modo-switch__opt modo-switch__opt--completadas"
                :class="{ 'modo-switch__opt--active': vista === 'completadas' }"
                :aria-selected="vista === 'completadas'"
                @click="cambiarVista('completadas')"
            >
                <CheckCircleOutlined />
                <span>Realizadas y canceladas</span>
                <span class="modo-switch__badge modo-switch__badge--completadas">
                    {{ (kpis.realizadas ?? 0) + (kpis.canceladas ?? 0) }}
                </span>
            </button>
        </div>

        <div class="tabla-tareas">
            <DataTableInertia
                :paginador="tareas"
                :columns="columns"
                :orden="orden"
                :cargando="cargando"
                @cambio="onCambio"
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
                    <template v-if="column.key === 'tarea'">
                        <a class="tarea-titulo" @click="irA('tareas.show', record.id)">
                            <span
                                class="tarea-titulo__dot"
                                :style="{ background: hexEstado(record.estado) }"
                            ></span>
                            <span class="tarea-titulo__txt">{{ record.titulo || record.descripcion }}</span>
                        </a>
                        <div v-if="record.titulo && record.descripcion" class="tarea-descripcion">
                            {{ record.descripcion }}
                        </div>
                    </template>

                    <template v-else-if="column.key === 'responsables'">
                        <span v-if="record.responsables" class="responsables">
                            <span class="responsables__av">
                                {{ (record.responsables || 'S').charAt(0).toUpperCase() }}
                            </span>
                            {{ record.responsables }}
                        </span>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'fecha_limite'">
                        <span :class="{ 'fecha-vencida': record.vencida }">
                            <ExclamationCircleOutlined v-if="record.vencida" />
                            {{ fecha(record.fecha_limite) }}
                        </span>
                        <div v-if="record.retraso_dias > 0" class="retraso">
                            Realizada con {{ record.retraso_dias }} {{ record.retraso_dias === 1 ? 'día' : 'días' }} de retraso
                        </div>
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

                    <template v-else-if="column.key === 'clasificacion'">
                        <span v-if="record.clasificacion === 'general'" class="vacio">General</span>
                        <span v-else class="clasificacion-chip">
                            <component :is="ICONO_CLASIFICACION[record.clasificacion]" />
                            {{ record.proyecto?.nombre ?? record.categoria_tarea?.nombre ?? '—' }}
                        </span>
                    </template>

                    <template v-else-if="column.key === 'estado'">
                        <a-tag :color="colorEstado(record.estado)" class="tag-estado">
                            <span
                                class="tag-estado__dot"
                                :style="{ background: hexEstado(record.estado) }"
                            ></span>
                            {{ opcionesEstado.find((o) => o.value === record.estado)?.label }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'registrado'">
                        <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                    </template>

                    <template v-else-if="column.key === 'acciones'">
                        <a-button
                            type="text"
                            size="small"
                            class="accion accion--ver"
                            @click="irA('tareas.show', record.id)"
                        >
                            <template #icon><EyeOutlined /></template>
                        </a-button>
                    </template>
                </template>
            </DataTableInertia>
        </div>

        <ModalTarea
            ref="modalTarea"
            :usuarios="catalogos.usuarios ?? []"
            :prioridades="catalogos.prioridades ?? []"
            :proyectos="catalogos.proyectos ?? []"
            :categorias="catalogos.categorias ?? []"
        />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Botón Nueva tarea
   ========================================================== */
.btn-nueva {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%) !important;
    border-color: #0d84c9 !important;
    box-shadow: 0 4px 12px rgba(13, 132, 201, 0.28);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}
.btn-nueva:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 6px 16px rgba(13, 132, 201, 0.4);
}

/* ==========================================================
   KPIs vibrantes con gradientes — 5 tarjetas
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
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

/* Responsive KPIs */
@media (max-width: 1199px) {
    .kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 767px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 420px) {
    .kpis {
        grid-template-columns: 1fr;
    }
}

/* ==========================================================
   Switch Activas / Completadas
   ========================================================== */
.modo-switch {
    position: relative;
    display: inline-flex;
    padding: 3px;
    background: linear-gradient(180deg, #eef2f7 0%, #e6ecf3 100%);
    border: 1px solid #dbe3ec;
    border-radius: 999px;
    box-shadow: inset 0 1px 2px rgba(15, 45, 80, 0.08);
    user-select: none;
    transition: border-color 0.3s ease, background 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 14px;
}

.modo-switch--completadas {
    background: linear-gradient(180deg, #f3eefe 0%, #ece2fc 100%);
    border-color: #d6c7f0;
    box-shadow: inset 0 1px 2px rgba(107, 75, 201, 0.12);
}

.modo-switch__slider {
    position: absolute;
    top: 3px;
    bottom: 3px;
    left: 3px;
    width: calc((100% - 6px) / 2);
    border-radius: 999px;
    background: linear-gradient(180deg, #ffffff, #f6f9fc);
    box-shadow: 0 2px 6px rgba(15, 45, 80, 0.12), 0 1px 2px rgba(15, 45, 80, 0.08);
    transform: translateX(0);
    transition:
        transform 0.36s cubic-bezier(0.34, 1.4, 0.4, 1),
        background 0.3s ease,
        box-shadow 0.3s ease;
    z-index: 0;
}

.modo-switch--completadas .modo-switch__slider {
    transform: translateX(100%);
    background: linear-gradient(180deg, #ffffff, #f8f3ff);
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.28), 0 1px 2px rgba(124, 58, 237, 0.15);
}

.modo-switch__opt {
    position: relative;
    z-index: 1;
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 6px 16px;
    min-width: 130px;
    border: 0;
    background: transparent;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: color 0.28s ease, transform 0.2s ease;
}

.modo-switch__opt .anticon {
    font-size: 13px;
    transition: transform 0.28s ease;
}

.modo-switch__opt:hover {
    color: #0f2d50;
}

.modo-switch__opt:hover .anticon {
    transform: scale(1.15);
}

.modo-switch__opt--activas.modo-switch__opt--active {
    color: #0d84c9;
}

.modo-switch__opt--completadas.modo-switch__opt--active {
    color: #6b21a8;
}

.modo-switch__opt--active .anticon {
    transform: scale(1.08);
}

/* Badge de conteo dentro del switch */
.modo-switch__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    color: #fff;
}

.modo-switch__badge--activas {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 2px 5px rgba(13, 132, 201, 0.35);
}

.modo-switch__badge--completadas {
    background: linear-gradient(135deg, #6b21a8 0%, #563a9e 100%);
    box-shadow: 0 2px 5px rgba(107, 75, 201, 0.35);
}

/* ==========================================================
   Tabla
   ========================================================== */
.tabla-tareas {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    background: #fff;
}

.tabla-tareas :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(180deg, #f5f8fb 0%, #eef3f8 100%) !important;
    color: #173a5f !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 11px;
    border-bottom: 1px solid #dbe3ec !important;
}

.tabla-tareas :deep(.ant-table-thead > tr > th::before) {
    background-color: #dbe3ec !important;
}

.tabla-tareas :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid #eef2f7 !important;
}

.tabla-tareas :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

.tabla-tareas :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

.tabla-tareas :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid #eef2f7;
}

.tabla-tareas :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

.tabla-tareas :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
    background: #e6f2fb;
}

.tabla-tareas :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #0d84c9;
    font-weight: 800;
}

/* ==========================================================
   Acción ver
   ========================================================== */
.accion {
    border-radius: 8px;
    transition:
        background 0.14s ease,
        color 0.14s ease,
        transform 0.14s ease;
}

.accion--ver {
    color: #0d84c9;
}

.accion--ver:hover {
    background: #e6f2fb !important;
    color: #0f6fb0 !important;
    transform: translateY(-1px);
}

/* ==========================================================
   Celda Tarea
   ========================================================== */
.tarea-titulo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #173a5f;
    transition: color 0.14s ease;
}

.tarea-titulo:hover .tarea-titulo__txt {
    color: #0d84c9;
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 4px;
}

.tarea-titulo__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(13, 132, 201, 0.12);
}

.tarea-descripcion {
    color: #8c98a8;
    font-size: 12.5px;
    font-weight: 400;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 280px;
    margin-top: 2px;
    margin-left: 16px;
}

/* ==========================================================
   Fecha vencida
   ========================================================== */
.retraso {
    margin-top: 4px;
    font-size: 11.5px;
    font-weight: 700;
    color: #b45309;
}
.fecha-vencida {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #d64545;
    font-weight: 800;
    background: #fdecec;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11.5px;
}

/* ==========================================================
   Tags
   ========================================================== */
.tag-estado {
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
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

.tag-prioridad {
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    line-height: 20px;
    border: none !important;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.08);
}

/* ==========================================================
   Responsables con avatar
   ========================================================== */
.responsables {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-weight: 600;
    color: #2b3a4f;
    font-size: 12.5px;
}

.responsables__av {
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
}

.clasificacion-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 12.5px;
    color: #0f6fb0;
}
</style>