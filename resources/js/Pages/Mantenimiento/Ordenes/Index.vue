<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BankOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    CloseCircleOutlined,
    EnvironmentOutlined,
    ExclamationCircleOutlined,
    EyeOutlined,
    FilterOutlined,
    PlusOutlined,
    SyncOutlined,
    ToolOutlined,
    UserOutlined,
    UserSwitchOutlined,
    WarningOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ModalOrden from '@/Components/ModalOrden.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    mantenimientos: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    sucursalId: { type: [Number, String], default: null },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const tarjetas = computed(() => [
    { label: 'Órdenes totales', valor: props.kpis.total ?? 0, icono: ToolOutlined, color: '#0d84c9' },
    { label: 'Abiertas', valor: props.kpis.abiertas ?? 0, icono: SyncOutlined, color: '#e08a1e' },
    { label: 'Vencidas', valor: props.kpis.vencidas ?? 0, icono: ExclamationCircleOutlined, color: '#d64545' },
    { label: 'Sin técnico', valor: props.kpis.sin_tecnico ?? 0, icono: UserSwitchOutlined, color: '#6b4bc9' },
]);

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia(
    'mantenimientos.index',
    {
        filtros: {
            sucursal_id: props.sucursalId ?? 'todas',
            folio: props.filtros.folio ?? '',
            equipo: props.filtros.equipo ?? '',
            tipo_id: props.filtros.tipo_id ?? undefined,
            prioridad_id: props.filtros.prioridad_id ?? undefined,
            estado_id: props.filtros.estado_id ?? undefined,
            tecnico_id: props.filtros.tecnico_id ?? undefined,
            desde: props.filtros.desde ?? '',
            hasta: props.filtros.hasta ?? '',
            registrado_por: props.filtros.registrado_por ?? '',
        },
        orden: { campo: props.orden.campo ?? 'id', dir: props.orden.dir ?? 'desc' },
    },
);

const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));
const opcionesFiltro = {
    tipo_id: computed(() => opciones(props.catalogos.tipos)),
    prioridad_id: computed(() => opciones(props.catalogos.prioridades)),
    estado_id: computed(() => opciones(props.catalogos.estados)),
    tecnico_id: computed(() => opciones(props.catalogos.tecnicos)),
};

const opcionesSucursal = computed(() => [
    { value: 'todas', label: 'Todas las sucursales' },
    ...opciones(props.catalogos.sucursales),
]);

const cambiarSucursal = (v) => {
    filtros.sucursal_id = v;
    aplicar();
};

const columns = [
    { title: 'Folio', key: 'folio', dataIndex: 'folio', sorter: true, filtro: 'texto', filtroClave: 'folio', width: 150 },
    { title: 'Equipo / instalación', key: 'objetivo', filtro: 'texto', filtroClave: 'equipo', width: 230 },
    { title: 'Tipo', key: 'tipo', filtro: 'select', filtroClave: 'tipo_id', width: 160 },
    { title: 'Sucursal', key: 'sucursal', width: 150 },
    { title: 'Prioridad', key: 'prioridad', filtro: 'select', filtroClave: 'prioridad_id', width: 140 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado_id', width: 160 },
    { title: 'Técnico(s)', key: 'tecnicos', filtro: 'select', filtroClave: 'tecnico_id', width: 180 },
    { title: 'Programado', key: 'programado_inicio', dataIndex: 'programado_inicio', sorter: true, width: 130 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 70, fixed: 'right' },
];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const irA = (n, p) => router.visit(route(n, p));

// --- Mapa de estados → color + icono -------------------------
const ESTADOS = {
    autorizado: { color: 'cyan', icono: CheckCircleOutlined },
    asignado: { color: 'blue', icono: UserOutlined },
    en_proceso: { color: 'processing', icono: SyncOutlined },
    en_espera_refaccion: { color: 'orange', icono: ClockCircleOutlined },
    fuera_de_servicio: { color: 'volcano', icono: WarningOutlined },
    realizado: { color: 'lime', icono: CheckCircleOutlined },
    supervisado: { color: 'geekblue', icono: CheckCircleOutlined },
    cerrado: { color: 'green', icono: CheckCircleOutlined },
    reprogramado: { color: 'purple', icono: SyncOutlined },
    cancelado: { color: 'red', icono: CloseCircleOutlined },
};

const estadoInfo = (record) => {
    const clave = record?.estado?.clave;
    return ESTADOS[clave] ?? { color: 'default', icono: ClockCircleOutlined };
};

// --- Mapa de tipos de mantenimiento → color + icono ----------
const TIPOS = {
    preventivo: { color: 'green', icono: ToolOutlined },
    correctivo: { color: 'blue', icono: ToolOutlined },
    urgente: { color: 'red', icono: WarningOutlined },
    predictivo: { color: 'purple', icono: ToolOutlined },
    emergencia: { color: 'volcano', icono: WarningOutlined },
    mejora: { color: 'cyan', icono: ToolOutlined },
};

const tipoInfo = (record) => {
    const cat = (record?.tipo?.categoria ?? '').toLowerCase();
    return TIPOS[cat] ?? { color: 'default', icono: ToolOutlined };
};

const modalOrden = ref(null);
</script>

<template>
    <Head title="Órdenes de mantenimiento" />

    <AppLayout titulo="Órdenes de mantenimiento"
        descripcion="Trabajos correctivos y preventivos con su asignación de técnicos, materiales y estado.">
        <template #acciones>
            <a-button v-if="puede('mantenimientos.crear')" type="primary" @click="modalOrden.abrir()">
                <template #icon>
                    <PlusOutlined />
                </template>
                Nueva orden
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

        <!-- Barra unificada: Sucursal + Rango de fechas + Limpiar filtros -->
        <div class="barra-filtros">
            <div class="barra-filtros__grupo">
                <span class="barra-filtros__ic">
                    <BankOutlined />
                </span>
                <span class="barra-filtros__l">Sucursal</span>
                <a-select :value="filtros.sucursal_id" :options="opcionesSucursal" class="barra-filtros__select"
                    @update:value="cambiarSucursal" />
            </div>

            <div class="barra-filtros__sep"></div>

            <div class="barra-filtros__grupo">
                <a-range-picker :value="[filtros.desde || null, filtros.hasta || null]" value-format="YYYY-MM-DD"
                    :allow-empty="[true, true]" placeholder="['Desde', 'Hasta']" class="barra-filtros__fechas"
                    @change="(_, s) => { filtros.desde = s[0] || ''; filtros.hasta = s[1] || ''; aplicar(); }" />
            </div>

            <a-button v-if="hayFiltros()" class="barra-filtros__limpiar" @click="limpiar">
                <template #icon>
                    <FilterOutlined />
                </template>
                Limpiar filtros
            </a-button>
        </div>

        <DataTableInertia :paginador="mantenimientos" :columns="columns" :orden="orden" :cargando="cargando"
            @cambio="onCambioTabla">
            <template #filtro="{ column }">
                <a-input v-if="column.filtro === 'texto'" v-model:value="filtros[column.filtroClave]" size="small"
                    allow-clear placeholder="Filtrar" @update:value="filtrar()" />
                <a-select v-else-if="column.filtro === 'select' && opcionesFiltro[column.filtroClave]"
                    v-model:value="filtros[column.filtroClave]" :options="opcionesFiltro[column.filtroClave].value"
                    size="small" allow-clear placeholder="Todos" style="width: 100%" @change="aplicar()" />
            </template>

            <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'folio'">
                    <a class="folio" @click="irA('mantenimientos.show', record.id)">
                        {{ record.folio }}
                    </a>
                </template>

                <template v-else-if="column.key === 'objetivo'">
                    <span v-if="record.objetivo" class="obj" :class="`obj--${record.objetivo.tipo}`">
                        <span class="obj__ic">
                            <ToolOutlined v-if="record.objetivo.tipo === 'equipo'" />
                            <EnvironmentOutlined v-else />
                        </span>
                        <span class="obj__t">{{ record.objetivo.texto }}</span>
                    </span>
                    <span v-else class="vacio">—</span>
                </template>

                <template v-else-if="column.key === 'tipo'">
                    <a-tag v-if="record.tipo" :color="tipoInfo(record).color" class="tipo-tag">
                        <component :is="tipoInfo(record).icono" />
                        <span>{{ record.tipo.nombre }}</span>
                    </a-tag>
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
                    <a-tag v-if="record.prioridad" :color="record.prioridad.color || 'default'" class="prio">
                        {{ record.prioridad.nombre }}
                    </a-tag>
                    <span v-else class="vacio">—</span>
                </template>

                <template v-else-if="column.key === 'estado'">
                    <a-tag :color="estadoInfo(record).color" class="estado-tag">
                        <component :is="estadoInfo(record).icono" />
                        <span>{{ record.estado?.nombre }}</span>
                    </a-tag>
                </template>

                <template v-else-if="column.key === 'tecnicos'">
                    <span v-if="record.tecnicos" class="tecnicos">
                        <span class="tecnicos__ic">
                            <UserOutlined />
                        </span>
                        {{ record.tecnicos }}
                    </span>
                    <span v-else class="vacio">—</span>
                </template>

                <template v-else-if="column.key === 'programado_inicio'">
                    <span class="fecha">{{ fecha(record.programado_inicio) }}</span>
                </template>

                <template v-else-if="column.key === 'registrado'">
                    <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                </template>

                <template v-else-if="column.key === 'acciones'">
                    <a-button type="text" size="small" class="accion-ver"
                        @click="irA('mantenimientos.show', record.id)">
                        <template #icon>
                            <EyeOutlined />
                        </template>
                    </a-button>
                </template>
            </template>
        </DataTableInertia>

        <ModalOrden ref="modalOrden" :equipos="catalogos.equipos ?? []" :ubicaciones="catalogos.ubicaciones ?? []"
            :catalogos="catalogos" />
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
   Barra unificada: Sucursal + Rango de fechas + Limpiar filtros
   ========================================================== */
.barra-filtros {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 12px;
}

.barra-filtros__grupo {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.barra-filtros__ic {
    color: var(--sigam-teal);
    font-size: 16px;
}

.barra-filtros__l {
    font-size: 12.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sigam-tenue);
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
    height: 22px;
    background: var(--sigam-borde-suave);
    flex: none;
}

.barra-filtros__limpiar {
    margin-left: auto;
    color: #d64545;
    border-color: #f4dede;
    background: #fdf4f4;
}

.barra-filtros__limpiar:hover {
    background: #fdecec !important;
    border-color: #d64545 !important;
    color: #a83232 !important;
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

/* ---------- Folio clicable ---------- */
.folio {
    font-weight: 700;
    color: var(--sigam-navy);
    cursor: pointer;
    text-decoration: none;
    border-bottom: 1px dashed transparent;
    transition: color 0.14s ease, border-color 0.14s ease;
}

.folio:hover {
    color: #0d84c9;
    border-bottom-color: #0d84c9;
}

/* ---------- Objetivo (equipo / instalación) ---------- */
.obj {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
    max-width: 100%;
}

.obj__ic {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    flex-shrink: 0;
}

.obj--equipo .obj__ic {
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 2px 6px rgba(13, 132, 201, 0.25);
}

.obj--ubicacion .obj__ic {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 2px 6px rgba(31, 158, 134, 0.25);
}

.obj__t {
    font-weight: 600;
    color: var(--sigam-texto);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 13px;
}

/* ---------- Sucursal ---------- */
.sucursal {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    color: var(--sigam-texto);
}

.sucursal .anticon {
    color: var(--sigam-teal);
    font-size: 13px;
}

/* ---------- Prioridad ---------- */
.prio {
    margin: 0;
    font-weight: 700;
    border: none;
}

/* ---------- Tipo de mantenimiento ---------- */
.tipo-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 12px;
    line-height: 20px;
    border: none;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
}

.tipo-tag .anticon {
    font-size: 12px;
}

/* ---------- Estado (tag con ícono) ---------- */
.estado-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 12px;
    line-height: 20px;
    border: none;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
}

.estado-tag .anticon {
    font-size: 12px;
}

/* ---------- Técnicos ---------- */
.tecnicos {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    color: var(--sigam-texto);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.tecnicos__ic {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    background: #e8f3fb;
    color: #0d6ca6;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    flex-shrink: 0;
}

/* ---------- Fecha ---------- */
.fecha {
    font-size: 12.5px;
    color: var(--sigam-texto);
    font-variant-numeric: tabular-nums;
}

/* ---------- Vacío ---------- */
.vacio {
    color: var(--sigam-tenue);
    font-size: 12.5px;
}

/* ---------- Botón ver ---------- */
.accion-ver {
    width: 30px;
    height: 30px;
    min-width: 30px;
    border-radius: 8px;
    color: #0d84c9;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.accion-ver:hover {
    background: #e8f3fb !important;
    color: #0a6ba6 !important;
    transform: translateY(-1px);
}
</style>