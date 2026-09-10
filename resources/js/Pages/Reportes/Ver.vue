<script setup>
import { computed, h, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowLeftOutlined,
    BarChartOutlined,
    DownOutlined,
    FileExcelOutlined,
    FilePdfOutlined,
    FileTextOutlined,
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

// --- Definición de los controles de filtro -------------------------
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

const exportar = ({ key }) => {
    const params = new URLSearchParams({ formato: key });
    for (const [k, v] of Object.entries(form)) {
        if (v !== '' && v !== null && v !== undefined) params.set(k, v);
    }
    window.location.href = `${route('reportes.exportar', props.clave)}?${params.toString()}`;
};

const menuExportar = [
    { key: 'xlsx', icon: () => h(FileExcelOutlined), label: 'Excel (.xlsx)' },
    { key: 'pdf', icon: () => h(FilePdfOutlined), label: 'PDF' },
    { key: 'csv', icon: () => h(FileTextOutlined), label: 'CSV' },
];

// --- Tabla --------------------------------------------------------
const tablaColumns = computed(() =>
    props.columnas.map((titulo, i) => ({
        title: titulo,
        dataIndex: String(i),
        key: String(i),
        ellipsis: true,
    })),
);

const tablaData = computed(() =>
    props.filas.map((fila, idx) => {
        const row = { key: idx };
        fila.forEach((valor, j) => (row[String(j)] = valor));
        return row;
    }),
);

const totalesLegibles = computed(() =>
    Object.entries(props.totales ?? {}).map(([k, v]) => ({
        etiqueta: k.replace(/_/g, ' '),
        valor: typeof v === 'number' && !Number.isInteger(v)
            ? new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 }).format(v)
            : v,
    })),
);

const fechaHora = (v) => (v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : '');
</script>

<template>
    <Head :title="nombre" />

    <AppLayout>
        <div class="rpt-head">
            <div class="rpt-head__l">
                <button type="button" class="rpt-volver" @click="router.visit(route('reportes.index'))">
                    <ArrowLeftOutlined />
                </button>
                <span class="rpt-head__ic"><BarChartOutlined /></span>
                <div>
                    <h1 class="rpt-head__t">{{ nombre }}</h1>
                    <div class="rpt-head__s">
                        Generado por {{ generado_por.nombre }} · {{ fechaHora(generado_at) }} ·
                        {{ tablaData.length }} registro(s)
                    </div>
                </div>
            </div>

            <a-dropdown v-if="!pendiente && puedeExportar">
                <a-button type="primary">
                    Exportar <DownOutlined />
                </a-button>
                <template #overlay>
                    <a-menu :items="menuExportar" @click="exportar" />
                </template>
            </a-dropdown>
        </div>

        <a-card v-if="filtrosDisponibles.length" size="small" class="mb-4 rpt-filtros">
            <a-form layout="inline" @submit.prevent="generar">
                <a-form-item v-for="k in filtrosDisponibles" :key="k" :label="DEFS[k]?.label ?? k">
                    <a-select
                        v-if="DEFS[k]?.tipo === 'select'"
                        v-model:value="form[k]"
                        :options="opciones(DEFS[k].catalogo)"
                        allow-clear
                        style="min-width: 180px"
                        placeholder="Todos"
                    />
                    <a-input v-else-if="DEFS[k]?.tipo === 'date'" v-model:value="form[k]" type="date" />
                    <a-input-number v-else-if="DEFS[k]?.tipo === 'number'" v-model:value="form[k]" :min="1" :max="365" />
                    <a-input v-else v-model:value="form[k]" />
                </a-form-item>
                <a-form-item>
                    <a-button type="primary" @click="generar">
                        <template #icon><ReloadOutlined /></template>
                        Generar
                    </a-button>
                </a-form-item>
            </a-form>
        </a-card>

        <a-alert
            v-if="pendiente"
            type="info"
            show-icon
            message="Reporte en construcción"
            description="Este reporte forma parte del catálogo de la especificación pero su consulta aún no está implementada."
        />

        <template v-else>
            <div v-if="totalesLegibles.length" class="rpt-tot">
                <div v-for="(t, i) in totalesLegibles" :key="t.etiqueta" class="rpt-tot__c" :style="{ '--acc': ['#0d84c9', '#1f9e86', '#6b4bc9', '#e08a1e'][i % 4] }">
                    <span class="rpt-tot__v">{{ t.valor }}</span>
                    <span class="rpt-tot__l">{{ t.etiqueta }}</span>
                </div>
            </div>

            <a-card :body-style="{ padding: 0 }">
                <a-table
                    :columns="tablaColumns"
                    :data-source="tablaData"
                    :pagination="{ pageSize: 25, showSizeChanger: false, showTotal: (t) => `${t} filas` }"
                    size="small"
                    class="rpt-tabla"
                    :scroll="{ x: 'max-content' }"
                >
                    <template #emptyText>
                        <a-empty description="Sin datos para los filtros aplicados" />
                    </template>
                </a-table>
            </a-card>
        </template>
    </AppLayout>
</template>

<style scoped>
.rpt-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.rpt-head__l {
    display: flex;
    align-items: center;
    gap: 12px;
}
.rpt-volver {
    width: 34px;
    height: 34px;
    border: 1px solid var(--sigam-borde);
    border-radius: 10px;
    background: #fff;
    color: var(--sigam-tenue);
    cursor: pointer;
    transition: all 0.14s ease;
}
.rpt-volver:hover {
    background: var(--sigam-navy-050);
    color: var(--sigam-navy);
    border-color: var(--sigam-navy-100);
}
.rpt-head__ic {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    background: var(--sigam-grad);
}
.rpt-head__t {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
}
.rpt-head__s {
    font-size: 12.5px;
    color: var(--sigam-tenue);
}
.rpt-filtros :deep(.ant-card-body) {
    display: flex;
    align-items: flex-end;
}
.rpt-tot {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.rpt-tot__c {
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    padding: 13px 15px;
    box-shadow: var(--sigam-sombra-sm);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.rpt-tot__c::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}
.rpt-tot__v {
    font-size: 20px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.1;
}
.rpt-tot__l {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--sigam-tenue);
}
.rpt-tabla :deep(.ant-table-thead > tr > th) {
    background: var(--sigam-navy-050);
    color: #42566b;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
</style>
