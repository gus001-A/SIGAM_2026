<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircleOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    ExclamationCircleOutlined,
    EyeOutlined,
    FileProtectOutlined,
    FilterOutlined,
    PaperClipOutlined,
    PlusOutlined,
    StopOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    normas: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
});

/* ==========================================================
   KPIs vibrantes con gradientes
   ========================================================== */
const tarjetas = computed(() => [
    {
        label: 'Normas totales',
        valor: props.kpis.total ?? 0,
        icono: FileProtectOutlined,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
    },
    {
        label: 'Vigentes',
        valor: props.kpis.vigentes ?? 0,
        icono: CheckCircleOutlined,
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e4f4ec',
    },
    {
        label: 'Por revisar',
        valor: props.kpis.por_revisar ?? 0,
        icono: ExclamationCircleOutlined,
        color: '#e08a1e',
        color2: '#a86717',
        soft: '#fdf3e6',
    },
    {
        label: 'Inactivas',
        valor: props.kpis.inactivas ?? 0,
        icono: StopOutlined,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
    },
]);

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('normas.index', {
    filtros: {
        codigo: props.filtros.codigo ?? '',
        nombre: props.filtros.nombre ?? '',
        version: props.filtros.version ?? '',
        estado: props.filtros.estado ?? undefined,
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'codigo', dir: props.orden.dir ?? 'asc' },
});

const opcionesEstado = [
    { label: 'Vigente', value: 'activo' },
    { label: 'Inactiva', value: 'inactivo' },
];

const columns = [
    { title: 'Código', key: 'codigo', dataIndex: 'codigo', sorter: true, filtro: 'texto', filtroClave: 'codigo', width: 170 },
    { title: 'Nombre', key: 'nombre', dataIndex: 'nombre', sorter: true, filtro: 'texto', filtroClave: 'nombre', width: 280 },
    { title: 'Versión', key: 'version', dataIndex: 'version', sorter: true, filtro: 'texto', filtroClave: 'version', width: 110 },
    { title: 'Vigencia', key: 'fecha_vigencia', dataIndex: 'fecha_vigencia', sorter: true, width: 120 },
    { title: 'Revisión', key: 'fecha_revision', dataIndex: 'fecha_revision', sorter: true, width: 130 },
    { title: 'Uso', key: 'uso', width: 130 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 120 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (norma) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${norma.codigo}`,
        mensaje: 'La norma se conservará en el historial pero dejará de estar disponible para asociar a equipos y planes.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('normas.destroy', norma.id), { preserveScroll: true });
};

const reactivar = (norma) => router.put(route('normas.restore', norma.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Normas" />

    <AppLayout
        titulo="Normas y procedimientos"
        descripcion="Normativa aplicable a los equipos, con su versión vigente y fecha de revisión."
    >
        <!-- ==========================================================
             Botón "Nueva norma" arriba, al mismo nivel que el título
             ========================================================== -->
        <template #acciones>
            <a-button
                v-if="puede('normas.crear')"
                type="primary"
                class="btn-nueva"
                @click="irA('normas.create')"
            >
                <template #icon>
                    <PlusOutlined />
                </template>
                Nueva norma
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
             Barra unificada de filtros (limpiar aquí dentro)
             ========================================================== -->
        <div class="barra-filtros">
            <div class="barra-filtros__grupo">
                <span class="barra-filtros__ic" style="--c: #0d84c9">
                    <FileProtectOutlined />
                </span>
                <span class="barra-filtros__l">Filtrar por</span>
                <span class="barra-filtros__hint">
                    Usa los campos debajo de cada columna para acotar los resultados.
                </span>
            </div>

            <a-button v-if="hayFiltros()" class="barra-filtros__limpiar" @click="limpiar">
                <template #icon>
                    <FilterOutlined />
                </template>
                Limpiar filtros
            </a-button>
        </div>

        <!-- Tabla -->
        <div class="tabla-normas">
            <DataTableInertia
                :paginador="normas"
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
                        v-else-if="column.filtro === 'select'"
                        v-model:value="filtros[column.filtroClave]"
                        :options="opcionesEstado"
                        size="small"
                        allow-clear
                        placeholder="Todas"
                        style="width: 100%"
                        @change="aplicar()"
                    />
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'codigo'">
                        <a class="norma" @click="irA('normas.show', record.id)">
                            <span
                                class="norma__dot"
                                :style="{ background: record.estado === 'activo' ? (record.revision_vencida ? '#d64545' : '#1f9e86') : '#94a3b8' }"
                            ></span>
                            {{ record.codigo }}
                            <PaperClipOutlined v-if="record.tiene_documento" class="norma__clip" />
                        </a>
                    </template>

                    <template v-else-if="column.key === 'version'">
                        <a-tag v-if="record.version" class="tag-version">{{ record.version }}</a-tag>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'fecha_vigencia'">
                        <span class="fecha">{{ fecha(record.fecha_vigencia) }}</span>
                    </template>

                    <template v-else-if="column.key === 'fecha_revision'">
                        <a-tag
                            v-if="record.fecha_revision"
                            :color="record.revision_vencida ? 'error' : 'blue'"
                            class="tag-fecha"
                        >
                            <ClockCircleOutlined class="tag-fecha__ic" />
                            {{ fecha(record.fecha_revision) }}
                        </a-tag>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'uso'">
                        <div class="uso">
                            <a-tooltip title="Equipos asociados">
                                <span class="uso__chip uso__chip--equipo">
                                    {{ record.equipos_count }} eq.
                                </span>
                            </a-tooltip>
                            <a-tooltip title="Planes preventivos asociados">
                                <span class="uso__chip uso__chip--plan">
                                    {{ record.planes_count }} pl.
                                </span>
                            </a-tooltip>
                        </div>
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
                            {{ record.estado === 'activo' ? 'Vigente' : 'Inactiva' }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'registrado'">
                        <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                    </template>

                    <template v-else-if="column.key === 'acciones'">
                        <a-space :size="2">
                            <a-tooltip title="Ver detalle">
                                <a-button type="text" size="small" class="accion-ver" @click="irA('normas.show', record.id)">
                                    <template #icon><EyeOutlined /></template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-if="record.estado === 'activo' && puede('normas.editar')" title="Editar">
                                <a-button type="text" size="small" class="accion-edit" @click="irA('normas.edit', record.id)">
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-if="record.estado === 'activo' && puede('normas.desactivar')" title="Desactivar">
                                <a-button type="text" size="small" class="accion-danger" @click="desactivar(record)">
                                    <template #icon><DeleteOutlined /></template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-else-if="record.estado === 'inactivo' && puede('normas.editar')" title="Reactivar">
                                <a-button type="text" size="small" class="accion-reactivar" @click="reactivar(record)">
                                    <template #icon><UndoOutlined /></template>
                                </a-button>
                            </a-tooltip>
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
   Botón Nueva norma (arriba, en #acciones)
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

.barra-filtros__hint {
    font-size: 12px;
    color: #7b8a9c;
    font-weight: 500;
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
    .barra-filtros__limpiar {
        margin-left: 0;
        width: 100%;
        justify-content: center;
    }
}

/* ==========================================================
   Tabla
   ========================================================== */
.tabla-normas {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    background: #fff;
}

.tabla-normas :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(180deg, #f5f8fb 0%, #eef3f8 100%) !important;
    color: #173a5f !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 11px;
    border-bottom: 1px solid #dbe3ec !important;
}

.tabla-normas :deep(.ant-table-thead > tr > th::before) {
    background-color: #dbe3ec !important;
}

.tabla-normas :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid #eef2f7 !important;
}

.tabla-normas :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

.tabla-normas :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

.tabla-normas :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid #eef2f7;
}

.tabla-normas :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

.tabla-normas :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
    background: #e6f2fb;
}

.tabla-normas :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #0f6fb0;
    font-weight: 800;
}

/* ==========================================================
   Celda "Norma"
   ========================================================== */
.norma {
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

.norma:hover {
    color: #0d84c9;
    border-bottom-color: #0d84c9;
}

.norma__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(15, 37, 71, 0.06);
}

.norma__clip {
    font-size: 11px;
    opacity: 0.55;
    margin-left: 2px;
}

/* ==========================================================
   Celdas auxiliares
   ========================================================== */
.fecha {
    font-size: 12.5px;
    font-weight: 600;
    color: #2b3a4f;
    font-variant-numeric: tabular-nums;
}

.vacio {
    color: #94a3b8;
    font-size: 12.5px;
}

/* Uso */
.uso {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.uso__chip {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    line-height: 20px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.08);
}

.uso__chip--equipo {
    background: #eef4fb;
    color: #0f6fb0;
}

.uso__chip--plan {
    background: #efe9fb;
    color: #563a9e;
}

/* ==========================================================
   Tags
   ========================================================== */
.tag-version {
    display: inline-flex !important;
    align-items: center;
    margin: 0;
    font-weight: 800;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    line-height: 20px;
    border: none !important;
    background: #eef4fb !important;
    color: #0f6fb0 !important;
    box-shadow: 0 1px 3px rgba(13, 132, 201, 0.12);
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
</style>