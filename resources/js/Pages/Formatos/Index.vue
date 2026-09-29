<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircleOutlined,
    CopyOutlined,
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    FileTextOutlined,
    FilterOutlined,
    PlusOutlined,
    SnippetsOutlined,
    StopOutlined,
    UnorderedListOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    formatos: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

/* ==========================================================
   KPIs vibrantes con gradientes
   ========================================================== */
const tarjetas = computed(() => [
    {
        label: 'Formatos totales',
        valor: props.kpis?.total ?? props.formatos?.total ?? 0,
        icono: SnippetsOutlined,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
    },
    {
        label: 'Activos',
        valor: props.kpis?.activos ?? 0,
        icono: CheckCircleOutlined,
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e4f4ec',
    },
    {
        label: 'Inactivos',
        valor: props.kpis?.inactivos ?? 0,
        icono: StopOutlined,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
    },
    {
        label: 'Con respuestas',
        valor: props.kpis?.con_respuestas ?? 0,
        icono: UnorderedListOutlined,
        color: '#e08a1e',
        color2: '#a86717',
        soft: '#fdf3e6',
    },
]);

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('formatos.index', {
    filtros: {
        nombre: props.filtros.nombre ?? '',
        version: props.filtros.version ?? '',
        estado: props.filtros.estado ?? undefined,
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'nombre', dir: props.orden.dir ?? 'asc' },
});

const opcionesEstado = [
    { label: 'Activo', value: 'activo' },
    { label: 'Inactivo', value: 'inactivo' },
];

const columns = [
    { title: 'Nombre', key: 'nombre', dataIndex: 'nombre', sorter: true, filtro: 'texto', filtroClave: 'nombre', width: 280 },
    { title: 'Versión', key: 'version', dataIndex: 'version', sorter: true, filtro: 'texto', filtroClave: 'version', width: 110 },
    { title: 'Campos', key: 'campos_count', dataIndex: 'campos_count', sorter: true, align: 'right', width: 100 },
    { title: 'Respuestas', key: 'respuestas_count', dataIndex: 'respuestas_count', sorter: true, align: 'right', width: 120 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 120 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (formato) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${formato.nombre}`,
        mensaje: 'El formato dejará de estar disponible para las órdenes. Las respuestas ya capturadas se conservan.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('formatos.destroy', formato.id), { preserveScroll: true });
};

const reactivar = (formato) => router.put(route('formatos.restore', formato.id), {}, { preserveScroll: true });
</script>

<template>

    <Head title="Formatos" />

    <AppLayout titulo="Formatos y checklists"
        descripcion="Plantillas de captura para inspecciones y mantenimientos, con sus campos configurables.">
        <!-- ==========================================================
             Botón "Nuevo formato" arriba, al mismo nivel que el título
             ========================================================== -->
        <template #acciones>
            <a-button v-if="puede('formatos.crear')" type="primary" class="btn-nueva" @click="irA('formatos.create')">
                <template #icon>
                    <PlusOutlined />
                </template>
                Nuevo formato
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
        <div class="tabla-formatos">
            <DataTableInertia :paginador="formatos" :columns="columns" :orden="orden" :cargando="cargando"
                @cambio="onCambioTabla" @limpiar="limpiar">
                <template #filtro="{ column }">
                    <a-input v-if="column.filtro === 'texto'" v-model:value="filtros[column.filtroClave]" size="small"
                        allow-clear placeholder="Filtrar" @update:value="filtrar()" />
                    <a-select v-else-if="column.filtro === 'select'" v-model:value="filtros[column.filtroClave]"
                        :options="opcionesEstado" size="small" allow-clear placeholder="Todos" style="width: 100%"
                        @change="aplicar()" />
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'nombre'">
                        <a class="formato" @click="irA('formatos.show', record.id)">
                            <span class="formato__dot"
                                :style="{ background: record.estado === 'activo' ? '#1f9e86' : '#94a3b8' }"></span>
                            {{ record.nombre }}
                        </a>
                        <div v-if="record.descripcion" class="formato__desc">
                            {{ record.descripcion }}
                        </div>
                    </template>

                    <template v-else-if="column.key === 'version'">
                        <a-tag v-if="record.version" class="tag-version">v{{ record.version }}</a-tag>
                        <span v-else class="vacio">—</span>
                    </template>

                    <template v-else-if="column.key === 'campos_count'">
                        <a-tag class="tag-campos">
                            <UnorderedListOutlined class="tag-campos__ic" />
                            {{ record.campos_count }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'respuestas_count'">
                        <a-tag :color="record.respuestas_count ? 'blue' : 'default'" class="tag-respuestas">
                            {{ record.respuestas_count }}
                        </a-tag>
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
                            <a-tooltip title="Vista previa">
                                <a-button type="text" size="small" class="accion-ver"
                                    @click="irA('formatos.show', record.id)">
                                    <template #icon>
                                        <EyeOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-if="record.estado === 'activo' && puede('formatos.editar')" title="Editar">
                                <a-button type="text" size="small" class="accion-edit"
                                    @click="irA('formatos.edit', record.id)">
                                    <template #icon>
                                        <EditOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-if="record.estado === 'activo' && puede('formatos.desactivar')"
                                title="Desactivar">
                                <a-button type="text" size="small" class="accion-danger" @click="desactivar(record)">
                                    <template #icon>
                                        <DeleteOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-else-if="record.estado === 'inactivo' && puede('formatos.editar')"
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
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Botón Nuevo formato
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
.tabla-formatos {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    background: #fff;
}

.tabla-formatos :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(180deg, #f5f8fb 0%, #eef3f8 100%) !important;
    color: #173a5f !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-size: 11px;
    border-bottom: 1px solid #dbe3ec !important;
}

.tabla-formatos :deep(.ant-table-thead > tr > th::before) {
    background-color: #dbe3ec !important;
}

.tabla-formatos :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid #eef2f7 !important;
}

.tabla-formatos :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

.tabla-formatos :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

.tabla-formatos :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid #eef2f7;
}

.tabla-formatos :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

.tabla-formatos :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
    background: #e6f2fb;
}

.tabla-formatos :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #0f6fb0;
    font-weight: 800;
}

/* ==========================================================
   Celda "Formato"
   ========================================================== */
.formato {
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

.formato:hover {
    color: #0d84c9;
    border-bottom-color: #0d84c9;
}

.formato__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(15, 37, 71, 0.06);
}

.formato__desc {
    font-size: 11.5px;
    color: #7b8a9c;
    margin-top: 3px;
    line-height: 1.3;
    max-width: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
}

/* ==========================================================
   Celdas auxiliares
   ========================================================== */
.vacio {
    color: #94a3b8;
    font-size: 12.5px;
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
    font-size: 11px;
    line-height: 20px;
    border: none !important;
    background: #efe9fb !important;
    color: #563a9e !important;
    box-shadow: 0 1px 3px rgba(107, 75, 201, 0.15);
}

.tag-campos {
    display: inline-flex !important;
    align-items: center;
    gap: 6px;
    margin: 0;
    font-weight: 800;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    line-height: 20px;
    border: none !important;
    background: #fdf3e6 !important;
    color: #a86717 !important;
    box-shadow: 0 1px 3px rgba(224, 138, 30, 0.15);
}

.tag-campos__ic {
    font-size: 11px;
    opacity: 0.85;
}

.tag-respuestas {
    display: inline-flex !important;
    align-items: center;
    margin: 0;
    font-weight: 800;
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
</style>