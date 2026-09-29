<script setup>
import { computed, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowLeftOutlined,
    BarChartOutlined,
    CheckCircleFilled,
    CloseCircleFilled,
    FilterOutlined,
    FileExcelOutlined,
    FilePdfOutlined,
    ReloadOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    clave: { type: String, required: true },
    nombre: { type: String, required: true },
    filtros: { type: Object, default: () => ({}) },
    filtrosDisponibles: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
    puedeExportar: { type: Boolean, default: false },
    generado_por: { type: Object, default: () => ({}) },
    generado_at: { type: String, default: null },
    columnas: { type: Array, default: () => [] },
    filas: { type: Array, default: () => [] },
    totales: { type: Object, default: () => ({}) },
    pendiente: { type: Boolean, default: false },
});

const DEFS = {
    sucursal_id: { label: 'Sucursal', tipo: 'select', catalogo: 'sucursales' },
    tipo_equipo_id: { label: 'Tipo de equipo', tipo: 'select', catalogo: 'tipos_equipo' },
    estado_equipo_id: { label: 'Estado del equipo', tipo: 'select', catalogo: 'estados_equipo' },
    tipo_mant_id: { label: 'Tipo de mantenimiento', tipo: 'select', catalogo: 'tipos_mant' },
    estado_mant_id: { label: 'Estado de la orden', tipo: 'select', catalogo: 'estados_mant' },
    desde: { label: 'Desde', tipo: 'date' },
    hasta: { label: 'Hasta', tipo: 'date' },
    dias: { label: 'Ventana (días)', tipo: 'number' },
};

const form = reactive(
    Object.fromEntries(
        props.filtrosDisponibles.map((k) => [k, props.filtros[k] ?? (DEFS[k]?.tipo === 'number' ? undefined : '')]),
    ),
);

const opciones = (catalogo) =>
    (props.catalogos[catalogo] ?? []).map((o) => ({ label: o.nombre, value: o.id }));

const generar = () => {
    const params = {};
    for (const [k, v] of Object.entries(form)) {
        if (v !== '' && v !== null && v !== undefined) params[k] = v;
    }
    router.get(route('reportes.generar', props.clave), params, { preserveScroll: true });
};

const exportar = (formato) => {
    const params = new URLSearchParams({ formato });
    for (const [k, v] of Object.entries(form)) {
        if (v !== '' && v !== null && v !== undefined) params.set(k, v);
    }
    window.location.href = `${route('reportes.exportar', props.clave)}?${params.toString()}`;
};

/* ==========================================================
   Tabla con colores
   ========================================================== */
const PALETA_COLUMNAS = [
    { h: '#0d84c9', h2: '#0f6fb0', soft: '#e6f2fb' },
    { h: '#1f9e86', h2: '#16806c', soft: '#e4f4ec' },
    { h: '#6b4bc9', h2: '#563a9e', soft: '#efe9fb' },
    { h: '#e08a1e', h2: '#a86717', soft: '#fdf3e6' },
    { h: '#0891b2', h2: '#0e7490', soft: '#e0f7fa' },
    { h: '#d64545', h2: '#b91c1c', soft: '#fdecec' },
];

const tablaColumns = computed(() =>
    props.columnas.map((titulo, i) => {
        const pal = PALETA_COLUMNAS[i % PALETA_COLUMNAS.length];
        return {
            title: titulo,
            dataIndex: String(i),
            key: String(i),
            ellipsis: true,
            _pal: pal,
        };
    }),
);

const tablaData = computed(() =>
    props.filas.map((fila, idx) => {
        const row = { key: idx };
        fila.forEach((valor, j) => (row[String(j)] = valor));
        return row;
    }),
);

const tipoCelda = (text) => {
    if (text === null || text === undefined || text === '') return 'vacio';
    if (typeof text === 'object' && text.__color) return 'tag';
    if (typeof text === 'boolean') return 'bool';
    if (typeof text === 'number') return 'num';
    if (typeof text === 'string' && /^\$?\s*-?\d[\d,]*\.\d{2}$/.test(text.trim())) return 'money';
    return 'text';
};

const totalesLegibles = computed(() =>
    Object.entries(props.totales ?? {}).map(([k, v]) => ({
        etiqueta: k.replace(/_/g, ' '),
        valor: typeof v === 'number' && !Number.isInteger(v)
            ? new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 }).format(v)
            : v,
    })),
);

const PALETA_TOTALES = [
    { color: '#0d84c9', color2: '#0f6fb0', soft: '#e6f2fb' },
    { color: '#1f9e86', color2: '#16806c', soft: '#e4f4ec' },
    { color: '#6b4bc9', color2: '#563a9e', soft: '#efe9fb' },
    { color: '#e08a1e', color2: '#a86717', soft: '#fdf3e6' },
    { color: '#d64545', color2: '#b91c1c', soft: '#fdecec' },
    { color: '#0891b2', color2: '#0e7490', soft: '#e0f7fa' },
];

const colorTotal = (i) => PALETA_TOTALES[i % PALETA_TOTALES.length];

const fechaHora = (v) => (v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : '');
</script>

<template>
    <Head :title="nombre" />

    <AppLayout>
        <!-- Header -->
        <div class="rpt-head">
            <div class="rpt-head__l">
                <button type="button" class="rpt-volver" @click="router.visit(route('reportes.index'))">
                    <ArrowLeftOutlined />
                </button>
                <span class="rpt-head__ic">
                    <BarChartOutlined />
                </span>
                <div class="rpt-head__info">
                    <h1 class="rpt-head__t">{{ nombre }}</h1>
                    <div class="rpt-head__s">
                        Generado por <strong>{{ generado_por.nombre }}</strong>
                        · {{ fechaHora(generado_at) }}
                        · <strong>{{ tablaData.length }}</strong> registro{{ tablaData.length === 1 ? '' : 's' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra única: filtros + exportar -->
        <div class="rpt-barra">
            <div class="rpt-barra__filtros">
                <span class="rpt-barra__ic">
                    <FilterOutlined />
                </span>
                <span class="rpt-barra__l">Filtros</span>

                <template v-if="filtrosDisponibles.length">
                    <a-select
                        v-for="k in filtrosDisponibles"
                        :key="k"
                        v-show="DEFS[k]?.tipo === 'select'"
                        v-model:value="form[k]"
                        :options="opciones(DEFS[k].catalogo)"
                        :placeholder="DEFS[k]?.label ?? k"
                        allow-clear
                        size="middle"
                        class="rpt-barra__select"
                    />
                    <a-input
                        v-for="k in filtrosDisponibles"
                        :key="`d-${k}`"
                        v-show="DEFS[k]?.tipo === 'date'"
                        v-model:value="form[k]"
                        type="date"
                        size="middle"
                        class="rpt-barra__date"
                        :placeholder="DEFS[k]?.label ?? k"
                    />
                    <a-input-number
                        v-for="k in filtrosDisponibles"
                        :key="`n-${k}`"
                        v-show="DEFS[k]?.tipo === 'number'"
                        v-model:value="form[k]"
                        :min="1"
                        :max="365"
                        size="middle"
                        class="rpt-barra__number"
                        :placeholder="DEFS[k]?.label ?? k"
                    />
                </template>
                <span v-else class="rpt-barra__hint">Este reporte no tiene filtros configurables.</span>
            </div>

            <div class="rpt-barra__acciones">
                <a-button
                    v-if="filtrosDisponibles.length"
                    type="primary"
                    class="btn-generar"
                    @click="generar"
                >
                    <template #icon><ReloadOutlined /></template>
                    Generar
                </a-button>

                <template v-if="!pendiente && puedeExportar">
                    <button type="button" class="btn-export btn-export--excel" @click="exportar('xlsx')">
                        <span class="btn-export__ic">
                            <FileExcelOutlined />
                        </span>
                        <span class="btn-export__t">
                            <span class="btn-export__l">Excel</span>
                            <span class="btn-export__s">.xlsx</span>
                        </span>
                    </button>
                    <button type="button" class="btn-export btn-export--pdf" @click="exportar('pdf')">
                        <span class="btn-export__ic">
                            <FilePdfOutlined />
                        </span>
                        <span class="btn-export__t">
                            <span class="btn-export__l">PDF</span>
                            <span class="btn-export__s">documento</span>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Alerta -->
        <div v-if="pendiente" class="rpt-alerta">
            <div class="rpt-alerta__ico">
                <BarChartOutlined />
            </div>
            <div class="rpt-alerta__txt">
                <div class="rpt-alerta__t">Reporte en construcción</div>
                <div class="rpt-alerta__s">
                    Este reporte forma parte del catálogo de la especificación pero su consulta aún no está implementada.
                </div>
            </div>
        </div>

        <template v-else>
            <!-- KPIs de totales -->
            <div v-if="totalesLegibles.length" class="rpt-tot">
                <div
                    v-for="(t, i) in totalesLegibles"
                    :key="t.etiqueta"
                    class="rpt-tot__c"
                    :style="{
                        '--acc': colorTotal(i).color,
                        '--acc2': colorTotal(i).color2,
                        '--soft': colorTotal(i).soft,
                    }"
                >
                    <div class="rpt-tot__glow"></div>
                    <span class="rpt-tot__v">{{ t.valor }}</span>
                    <span class="rpt-tot__l">{{ t.etiqueta }}</span>
                </div>
            </div>

            <!-- Tabla -->
            <div class="rpt-tabla-wrap">
                <a-table
                    :columns="tablaColumns"
                    :data-source="tablaData"
                    :pagination="{
                        pageSize: 25,
                        showSizeChanger: false,
                        showTotal: (t) => `${t} filas`,
                    }"
                    size="middle"
                    class="rpt-tabla"
                    :scroll="{ x: 'max-content' }"
                    row-class-name="rpt-row"
                >
                    <template #headerCell="{ column }">
                        <span class="th-cell">
                            <span
                                class="th-dot"
                                :style="{ background: column._pal?.h ?? '#0d84c9' }"
                            ></span>
                            <span class="th-txt">{{ column.title }}</span>
                        </span>
                    </template>

                    <template #bodyCell="{ text, column }">
                        <a-tag
                            v-if="tipoCelda(text) === 'tag'"
                            :color="text.color || 'default'"
                            class="cell-tag"
                        >
                            {{ text.texto }}
                        </a-tag>

                        <span
                            v-else-if="tipoCelda(text) === 'bool'"
                            class="cell-bool"
                            :class="text ? 'cell-bool--si' : 'cell-bool--no'"
                        >
                            <CheckCircleFilled v-if="text" />
                            <CloseCircleFilled v-else />
                            {{ text ? 'Sí' : 'No' }}
                        </span>

                        <span
                            v-else-if="tipoCelda(text) === 'num'"
                            class="cell-num"
                        >
                            {{ new Intl.NumberFormat('es-MX').format(text) }}
                        </span>

                        <span
                            v-else-if="tipoCelda(text) === 'money'"
                            class="cell-money"
                        >
                            {{ text }}
                        </span>

                        <span
                            v-else-if="tipoCelda(text) === 'vacio'"
                            class="cell-vacio"
                        >
                            —
                        </span>

                        <span
                            v-else
                            class="cell-text"
                            :style="{ '--cell-c': column._pal?.h }"
                        >
                            {{ text }}
                        </span>
                    </template>

                    <template #emptyText>
                        <a-empty description="Sin datos para los filtros aplicados" />
                    </template>
                </a-table>
            </div>
        </template>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Header
   ========================================================== */
.rpt-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.rpt-head__l {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.rpt-volver {
    width: 36px;
    height: 36px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.14s ease;
    flex-shrink: 0;
}

.rpt-volver:hover {
    background: #eef4fb;
    color: #0d84c9;
    border-color: #cfe4f5;
    transform: translateY(-1px);
}

.rpt-head__ic {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 19px;
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.55);
    flex-shrink: 0;
}

.rpt-head__info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.rpt-head__t {
    font-size: 18px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    letter-spacing: -0.2px;
    line-height: 1.2;
}

.rpt-head__s {
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.rpt-head__s strong {
    color: #173a5f;
    font-weight: 700;
}

/* ==========================================================
   Barra
   ========================================================== */
.rpt-barra {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    padding: 12px 16px;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.05);
}

.rpt-barra__filtros {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    min-width: 0;
    flex: 1;
}

.rpt-barra__ic {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    background: #6b4bc9;
    box-shadow: 0 3px 8px -3px rgba(107, 75, 201, 0.5);
    flex-shrink: 0;
}

.rpt-barra__l {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #7b8a9c;
    white-space: nowrap;
    margin-right: 4px;
}

.rpt-barra__hint {
    font-size: 12px;
    color: #7b8a9c;
    font-style: italic;
}

.rpt-barra__select { min-width: 170px; }
.rpt-barra__date { min-width: 150px; }
.rpt-barra__number { min-width: 130px; }

.rpt-barra__acciones {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

/* Botón Generar */
.btn-generar {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%) !important;
    border-color: #0d84c9 !important;
    font-weight: 700;
    box-shadow: 0 4px 12px -4px rgba(13, 132, 201, 0.5);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.btn-generar:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 6px 16px -4px rgba(13, 132, 201, 0.65);
}

/* Botones exportar */
.btn-export {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    height: 38px;
    padding: 0 14px 0 10px;
    border-radius: 11px;
    cursor: pointer;
    font-family: inherit;
    text-align: left;
    overflow: hidden;
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.btn-export::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.22) 50%, transparent 100%);
    transform: translateX(-100%);
    transition: transform 0.5s ease;
    pointer-events: none;
}

.btn-export:hover::after { transform: translateX(100%); }
.btn-export:hover { transform: translateY(-1px); }

.btn-export__ic {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
}

.btn-export__t {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
    color: #fff;
}

.btn-export__l { font-size: 12.5px; font-weight: 800; letter-spacing: -0.1px; }
.btn-export__s { font-size: 10px; font-weight: 600; opacity: 0.85; }

.btn-export--excel {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    border: 1px solid #16806c;
    box-shadow: 0 4px 12px -4px rgba(31, 158, 134, 0.55);
}

.btn-export--excel:hover {
    filter: brightness(1.08);
    box-shadow: 0 8px 20px -6px rgba(31, 158, 134, 0.75);
}

.btn-export--pdf {
    background: linear-gradient(135deg, #d64545 0%, #b91c1c 100%);
    border: 1px solid #b91c1c;
    box-shadow: 0 4px 12px -4px rgba(214, 69, 69, 0.55);
}

.btn-export--pdf:hover {
    filter: brightness(1.08);
    box-shadow: 0 8px 20px -6px rgba(214, 69, 69, 0.75);
}

@media (max-width: 767px) {
    .rpt-barra {
        flex-direction: column;
        align-items: stretch;
    }
    .rpt-barra__filtros { width: 100%; }
    .rpt-barra__acciones {
        width: 100%;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .btn-export { flex: 1 1 auto; justify-content: center; }
}

/* Alerta */
.rpt-alerta {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 14px;
    background: linear-gradient(135deg, #fdf7ec 0%, #fbeed6 100%);
    border: 1px dashed #f0d49a;
    margin-bottom: 14px;
}

.rpt-alerta__ico {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%);
    box-shadow: 0 4px 10px -4px rgba(224, 138, 30, 0.55);
}

.rpt-alerta__t { font-size: 13.5px; font-weight: 800; color: #8a5414; line-height: 1.2; }
.rpt-alerta__s { font-size: 12px; color: #a86717; line-height: 1.35; }

/* KPIs totales */
.rpt-tot {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 11px;
    margin-bottom: 14px;
}

.rpt-tot__c {
    position: relative;
    padding: 13px 15px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    box-shadow: 0 2px 6px -3px rgba(15, 37, 71, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 2px;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.rpt-tot__c:hover {
    transform: translateY(-2px);
    border-color: var(--acc);
    box-shadow: 0 8px 20px -10px rgba(15, 37, 71, 0.35);
}

.rpt-tot__c::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, var(--acc) 0%, var(--acc2) 100%);
}

.rpt-tot__glow {
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

.rpt-tot__v {
    position: relative;
    z-index: 1;
    font-size: 22px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.05;
    letter-spacing: -0.5px;
}

.rpt-tot__l {
    position: relative;
    z-index: 1;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #7b8a9c;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ==========================================================
   Tabla
   ========================================================== */
.rpt-tabla-wrap {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
}

/* Encabezados */
.rpt-tabla :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(180deg, #eef4fb 0%, #dde9f5 100%) !important;
    color: #173a5f !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-size: 11px;
    border-bottom: 2px solid #cfe0f2 !important;
    padding: 12px 14px !important;
}

.rpt-tabla :deep(.ant-table-thead > tr > th::before) {
    display: none !important;
}

.th-cell {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.th-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.85);
}

.th-txt {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
}

/* Filas — sin borde al hacer hover */
.rpt-tabla :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid #eef2f7 !important;
    font-size: 13px;
    padding: 11px 14px !important;
    transition: background 0.14s ease;
}

.rpt-tabla :deep(.ant-table-tbody > tr:nth-child(odd) > td) {
    background: #ffffff;
}

.rpt-tabla :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #f8fbfe;
}

.rpt-tabla :deep(.ant-table-tbody > tr:hover > td) {
    background: #e6f2fb !important;
    /* Sin borde ni sombra al hacer hover */
    box-shadow: none !important;
    border-color: #eef2f7 !important;
}

/* Aseguramos que no aparezca el borde animado por defecto de Ant */
.rpt-tabla :deep(.ant-table-tbody > tr > td.ant-table-cell-row-hover) {
    box-shadow: none !important;
    border-color: #eef2f7 !important;
}

/* Celda con color según tipo */
.cell-tag {
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

.cell-bool {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
    line-height: 1.3;
}

.cell-bool--si {
    color: #16806c;
    background: #e4f4ec;
    border: 1px solid #b9e4d5;
}

.cell-bool--no {
    color: #b91c1c;
    background: #fdecec;
    border: 1px solid #fecaca;
}

.cell-bool .anticon {
    font-size: 11px;
}

.cell-num {
    font-variant-numeric: tabular-nums;
    font-weight: 700;
    color: #173a5f;
    font-size: 13px;
}

.cell-money {
    font-variant-numeric: tabular-nums;
    font-weight: 800;
    color: #1f9e86;
    font-size: 13px;
    background: #e4f4ec;
    padding: 2px 8px;
    border-radius: 6px;
    display: inline-block;
}

.cell-vacio {
    color: #cbd5e1;
    font-style: italic;
}

.cell-text {
    color: #2b3a4f;
    font-weight: 500;
    font-size: 13px;
    line-height: 1.35;
}

/* Paginación */
.rpt-tabla :deep(.ant-pagination .ant-pagination-item) {
    border-radius: 8px;
    border-color: #dbe3ec;
    transition: all 0.14s ease;
}

.rpt-tabla :deep(.ant-pagination .ant-pagination-item:hover) {
    border-color: #0d84c9;
}

.rpt-tabla :deep(.ant-pagination .ant-pagination-item:hover a) {
    color: #0d84c9;
}

.rpt-tabla :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 4px 10px -4px rgba(13, 132, 201, 0.55);
}

.rpt-tabla :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #fff !important;
    font-weight: 800;
}

.rpt-tabla :deep(.ant-pagination .ant-pagination-total-text) {
    color: #7b8a9c;
    font-weight: 600;
    font-size: 12.5px;
}
</style>