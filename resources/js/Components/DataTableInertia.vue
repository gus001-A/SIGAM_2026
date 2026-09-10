<script setup>
import { computed, onBeforeUnmount, onMounted, ref, useSlots } from 'vue';
import { ClearOutlined } from '@ant-design/icons-vue';

/**
 * Tabla con paginación/orden en el servidor (paginador de Laravel).
 *
 * - El scroll vertical vive DENTRO del cuerpo de la tabla; el encabezado, la
 *   fila de filtros y la paginación quedan fijos.
 * - Los filtros van en una **fila al pie de la tabla** (estilo SAINS): usa el
 *   slot `#filtro="{ column }"` para pintar el control de cada columna.
 */
const props = defineProps({
    paginador: { type: Object, required: true },
    columns: { type: Array, required: true },
    orden: { type: Object, default: () => ({ campo: 'id', dir: 'asc' }) },
    cargando: { type: Boolean, default: false },
    rowKey: { type: String, default: 'id' },
    hayFiltros: { type: Boolean, default: false },
    expandable: { type: Object, default: undefined },
});

const emit = defineEmits(['cambio', 'limpiar']);
const slots = useSlots();

const CLAVES_ACCION = ['acciones', '__acciones'];

const columnasConOrden = computed(() =>
    props.columns.map((c) => {
        const esAccion = CLAVES_ACCION.includes(c.key);
        const base = esAccion
            ? {
                ...c,
                title: c.title || 'Acciones',
                align: 'center',
                className: `${c.className ?? ''} col-acciones`.trim(),
            }
            : { ...c };
        if (!c.sorter) return base;
        const activo = (c.key ?? c.dataIndex) === props.orden.campo;
        return {
            ...base,
            sortOrder: activo ? (props.orden.dir === 'desc' ? 'descend' : 'ascend') : null,
        };
    }),
);

const tieneFiltros = computed(
    () => !!slots.filtro && props.columns.some((c) => c.filtro),
);

const paginacion = computed(() => ({
    current: props.paginador.current_page,
    pageSize: props.paginador.per_page,
    total: props.paginador.total,
    showSizeChanger: false,
    hideOnSinglePage: false,
    showTotal: (total, range) => `${range[0]}–${range[1]} de ${total}`,
}));

const onChange = (pag, filtros, sorter) => emit('cambio', pag, filtros, sorter);

// --- Alto dinámico: el cuerpo de la tabla se ajusta al espacio disponible ---
const contenedor = ref(null);
const scrollY = ref(360);
let ro = null;

const recalcular = () => {
    const el = contenedor.value;
    if (!el) return;
    const header = el.querySelector('.ant-table-header')?.offsetHeight ?? 44;
    const resumen = el.querySelector('.ant-table-summary')?.offsetHeight ?? 0;
    const paginador = el.querySelector('.ant-pagination')?.offsetHeight ?? 0;
    const paginadorMargen = paginador ? 24 : 0;
    const alto = el.clientHeight - header - resumen - paginador - paginadorMargen - 2;
    scrollY.value = Math.max(140, Math.round(alto));
};

onMounted(() => {
    recalcular();
    ro = new ResizeObserver(recalcular);
    if (contenedor.value) ro.observe(contenedor.value);
    window.addEventListener('resize', recalcular);
    requestAnimationFrame(recalcular);
    setTimeout(recalcular, 120);
});

onBeforeUnmount(() => {
    ro?.disconnect();
    window.removeEventListener('resize', recalcular);
});
</script>

<template>
    <div ref="contenedor" class="dti">
        <a-table
            :columns="columnasConOrden"
            :data-source="paginador.data"
            :pagination="paginacion"
            :loading="cargando"
            :row-key="rowKey"
            :expandable="expandable"
            size="middle"
            :scroll="{ x: 'max-content', y: scrollY }"
            :show-sorter-tooltip="false"
            class="tabla-inertia"
            @change="onChange"
        >
            <template v-for="(_, name) in $slots" #[name]="slotData">
                <slot v-if="name !== 'filtro'" :name="name" v-bind="slotData ?? {}" />
            </template>

            <template v-if="tieneFiltros" #summary>
                <a-table-summary fixed="bottom">
                    <a-table-summary-row class="dti-filtros">
                        <a-table-summary-cell
                            v-for="(col, i) in columnasConOrden"
                            :key="col.key ?? col.dataIndex ?? i"
                            :index="i"
                        >
                            <div class="dti-filtros__cel" @click.stop>
                                <a-tooltip
                                    v-if="CLAVES_ACCION.includes(col.key)"
                                    title="Limpiar filtros"
                                >
                                    <button
                                        type="button"
                                        class="dti-filtros__limpiar"
                                        :class="{ 'is-activo': hayFiltros }"
                                        :disabled="!hayFiltros"
                                        @click="emit('limpiar')"
                                    >
                                        <ClearOutlined />
                                    </button>
                                </a-tooltip>
                                <slot v-else name="filtro" :column="col" />
                            </div>
                        </a-table-summary-cell>
                    </a-table-summary-row>
                </a-table-summary>
            </template>

            <template #emptyText>
                <a-empty description="Sin resultados para los filtros aplicados" />
            </template>
        </a-table>
    </div>
</template>

<style scoped>
.dti {
    flex: 1;
    min-height: 0;
    animation: sigam-fade-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.dti :deep(.ant-table-pagination) {
    margin: 14px 2px 2px;
}
</style>

<style>
/* ================= Estilo global de las tablas SIGAM ================= */
.tabla-inertia .ant-table {
    border: 1px solid var(--sigam-borde);
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    box-shadow: var(--sigam-sombra-sm);
}
.tabla-inertia .ant-table-container {
    border-radius: 14px;
}

/* --- Encabezado --- */
.tabla-inertia .ant-table-thead > tr > th {
    background: var(--sigam-navy-050);
    border-bottom: 1px solid var(--sigam-borde);
    color: #3c5064;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 12px 13px !important;
    white-space: nowrap;
}
.tabla-inertia .ant-table-thead > tr > th::before {
    display: none !important;
}
.tabla-inertia .ant-table-column-sorter {
    color: #9aa7b4;
    margin-inline-start: 5px;
}
.tabla-inertia .ant-table-column-sorter-up.active,
.tabla-inertia .ant-table-column-sorter-down.active {
    color: var(--sigam-navy);
}
.tabla-inertia .ant-table-header {
    box-shadow: 0 4px 10px -8px rgba(17, 34, 51, 0.3);
    position: relative;
    z-index: 3;
}

/* --- Fila de filtros al pie (estilo SAINS) --- */
.tabla-inertia .ant-table-summary {
    background: #eef3f9;
}
.tabla-inertia .dti-filtros > .ant-table-cell {
    background: #eef3f9 !important;
    border-top: 2px solid var(--sigam-navy-100) !important;
    border-bottom: none !important;
    padding: 8px 9px !important;
    vertical-align: middle;
}
.tabla-inertia .dti-filtros__cel .ant-input,
.tabla-inertia .dti-filtros__cel .ant-input-affix-wrapper,
.tabla-inertia .dti-filtros__cel .ant-select,
.tabla-inertia .dti-filtros__cel .ant-select-selector,
.tabla-inertia .dti-filtros__cel .ant-picker,
.tabla-inertia .dti-filtros__cel .ant-input-number {
    width: 100%;
    border-radius: 8px !important;
    font-size: 12.5px;
    background: #fff !important;
    box-shadow: 0 1px 2px rgba(17, 34, 51, 0.06);
}
.tabla-inertia .dti-filtros__cel .th__rango {
    display: flex;
    gap: 5px;
}
.tabla-inertia .dti-filtros__cel .th__rango .ant-input {
    width: 100%;
}

/* --- Cuerpo: filas uniformes --- */
.tabla-inertia .ant-table-tbody > tr > td {
    padding: 0 14px;
    height: 52px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    font-size: 13px;
    color: var(--sigam-texto);
    vertical-align: middle;
}
.tabla-inertia .ant-table-tbody > tr:last-child > td {
    border-bottom: none;
}
.tabla-inertia .ant-table-tbody > tr.ant-table-row {
    transition: background 0.12s ease;
    animation: sigam-fade-up 0.28s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.tabla-inertia .ant-table-tbody > tr.ant-table-row:nth-child(1) { animation-delay: 0.02s; }
.tabla-inertia .ant-table-tbody > tr.ant-table-row:nth-child(2) { animation-delay: 0.04s; }
.tabla-inertia .ant-table-tbody > tr.ant-table-row:nth-child(3) { animation-delay: 0.06s; }
.tabla-inertia .ant-table-tbody > tr.ant-table-row:nth-child(4) { animation-delay: 0.08s; }
.tabla-inertia .ant-table-tbody > tr.ant-table-row:nth-child(5) { animation-delay: 0.1s; }
.tabla-inertia .ant-table-tbody > tr.ant-table-row:nth-child(n + 6) { animation-delay: 0.12s; }
.tabla-inertia .ant-table-tbody > tr.ant-table-row:nth-child(even) > td {
    background: #fafbfd;
}
.tabla-inertia .ant-table-tbody > tr.ant-table-row:hover > td {
    background: var(--sigam-navy-050);
}
.tabla-inertia .ant-table-tbody > tr > td a:not(.ant-btn):not(.acc) {
    color: var(--sigam-navy);
    font-weight: 600;
    transition: color 0.14s ease;
}
.tabla-inertia .ant-table-tbody > tr > td a:not(.ant-btn):not(.acc):hover {
    color: var(--sigam-teal-700);
    text-decoration: underline;
}
.tabla-inertia .ant-table-tbody .ant-tag {
    margin-inline-end: 4px;
}

/* --- Columna de acciones: botones uniformes, color por ícono --- */
.tabla-inertia th.col-acciones,
.tabla-inertia td.col-acciones {
    text-align: center;
}
.tabla-inertia td.col-acciones {
    background: #fff;
}
.tabla-inertia .ant-table-tbody > tr.ant-table-row:nth-child(even) > td.col-acciones {
    background: #fafbfd;
}
.tabla-inertia .ant-table-row:hover td.col-acciones,
.tabla-inertia .ant-table-row:hover .ant-table-cell-fix-right {
    background: var(--sigam-navy-050);
}
.tabla-inertia td.col-acciones .ant-space,
.tabla-inertia td.col-acciones .acc-grupo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px !important;
}
.tabla-inertia td.col-acciones .ant-space-item {
    display: inline-flex;
}
.tabla-inertia td.col-acciones .ant-btn {
    width: 30px;
    height: 30px;
    min-width: 30px;
    padding: 0;
    border: 1px solid transparent;
    border-radius: 8px;
    background: #f1f4f8;
    color: #5a6b7d;
    box-shadow: none;
    transition: transform 0.13s cubic-bezier(0.16, 1, 0.3, 1),
        color 0.13s ease, background 0.13s ease, border-color 0.13s ease;
}
.tabla-inertia td.col-acciones .ant-btn .anticon {
    font-size: 14px;
}
.tabla-inertia td.col-acciones .ant-btn:hover {
    transform: translateY(-1px);
    color: #fff;
}
.tabla-inertia td.col-acciones .ant-btn:active {
    transform: translateY(0);
}

/* Color en reposo (tinte) + relleno al hover, según el ícono */
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-eye) {
    background: #e8f3fb;
    color: #0d6ca6;
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-eye):hover {
    background: #0d84c9;
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-qrcode) {
    background: #efecfb;
    color: #6b4bc9;
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-qrcode):hover {
    background: #6b4bc9;
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-edit) {
    background: #e7eefb;
    color: #23508c;
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-edit):hover {
    background: var(--sigam-navy);
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-delete),
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-stop),
.tabla-inertia td.col-acciones .ant-btn.ant-btn-dangerous {
    background: #fbeaea;
    color: #c23b3b;
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-delete):hover,
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-stop):hover,
.tabla-inertia td.col-acciones .ant-btn.ant-btn-dangerous:hover {
    background: #dc2626;
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-undo),
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-check) {
    background: #e4f4ee;
    color: var(--sigam-teal-700);
}
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-undo):hover,
.tabla-inertia td.col-acciones .ant-btn:has(.anticon-check):hover {
    background: var(--sigam-teal);
}

/* Botón "limpiar filtros" en la fila de filtros */
.dti-filtros__limpiar {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--sigam-borde);
    border-radius: 8px;
    background: #fff;
    color: #94a3b8;
    cursor: not-allowed;
    transition: all 0.14s ease;
}
.dti-filtros__limpiar.is-activo {
    color: #c23b3b;
    border-color: #f0c9c9;
    background: #fdf0f0;
    cursor: pointer;
}
.dti-filtros__limpiar.is-activo:hover {
    background: #d64545;
    color: #fff;
    border-color: #d64545;
    transform: translateY(-1px);
}
.tabla-inertia .ant-table-cell-fix-right {
    background: #fff;
}
/* Sin sombra/línea marcada entre la columna fija y el resto */
.tabla-inertia .ant-table-cell-fix-right-first::after,
.tabla-inertia .ant-table-cell-fix-left-last::after,
.tabla-inertia .ant-table-ping-right .ant-table-cell-fix-right-first::after,
.tabla-inertia .ant-table-ping-left .ant-table-cell-fix-left-last::after {
    box-shadow: none !important;
}

/* --- Scroll interno --- */
.tabla-inertia .ant-table-body {
    scrollbar-width: thin;
}
.tabla-inertia .ant-table-body::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}
.tabla-inertia .ant-table-body::-webkit-scrollbar-thumb {
    background: #cdd7e1;
    border: 3px solid transparent;
    border-radius: 999px;
    background-clip: content-box;
}
.tabla-inertia .ant-table-body::-webkit-scrollbar-thumb:hover {
    background: #b3c0cd;
    background-clip: content-box;
}

/* --- Estado vacío / paginación --- */
.tabla-inertia .ant-empty {
    padding: 30px 0;
}
.tabla-inertia .ant-pagination-total-text {
    color: var(--sigam-tenue);
    font-size: 12.5px;
    margin-inline-end: auto;
}
.tabla-inertia .ant-table-pagination.ant-pagination {
    align-items: center;
}
</style>
