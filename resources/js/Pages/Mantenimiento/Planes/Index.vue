<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CalendarOutlined, EyeOutlined, FilterOutlined, PlusOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    planes: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('planes.index', {
    filtros: {
        equipo: props.filtros.equipo ?? '',
        nombre: props.filtros.nombre ?? '',
        tipo_mantenimiento_id: props.filtros.tipo_mantenimiento_id ?? undefined,
        tecnico_id: props.filtros.tecnico_id ?? undefined,
        frecuencia: props.filtros.frecuencia ?? undefined,
        vencidos: props.filtros.vencidos ? true : undefined,
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

const columns = [
    { title: 'Plan / equipo', key: 'equipo', filtro: 'texto', filtroClave: 'equipo', width: 280 },
    { title: 'Tipo', key: 'tipo', filtro: 'select', filtroClave: 'tipo_mantenimiento_id', width: 170 },
    { title: 'Frecuencia', key: 'frecuencia', filtro: 'select', filtroClave: 'frecuencia', width: 150 },
    { title: 'Próxima fecha', key: 'proxima_fecha', dataIndex: 'proxima_fecha', sorter: true, width: 150 },
    { title: 'Técnico', key: 'tecnico', filtro: 'select', filtroClave: 'tecnico_id', width: 170 },
    { title: 'Ocurrencias', key: 'ocurrencias_count', dataIndex: 'ocurrencias_count', sorter: true, align: 'right', width: 120 },
    { title: 'Estado', key: 'estado', width: 120 },
    { title: '', key: 'acciones', align: 'right', width: 70, fixed: 'right' },
];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const irA = (n, p) => router.visit(route(n, p));
</script>

<template>
    <Head title="Planes preventivos" />

    <AppLayout
        titulo="Planes de mantenimiento preventivo"
        descripcion="Programas periódicos por equipo que generan órdenes automáticamente según su frecuencia."
    >
        <template #acciones>
            <a-checkbox
                :checked="!!filtros.vencidos"
                @change="(e) => { filtros.vencidos = e.target.checked || undefined; aplicar(); }"
            >
                Solo vencidos
            </a-checkbox>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('mantenimientos.crear')" type="primary" @click="irA('planes.create')">
                <template #icon><PlusOutlined /></template>
                Nuevo plan
            </a-button>
        </template>

        <DataTableInertia :paginador="planes" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
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
                    <a class="font-medium" @click="irA('planes.show', record.id)">{{ record.nombre || 'Plan preventivo' }}</a>
                    <div class="text-xs opacity-60">{{ record.equipo || '—' }}</div>
                </template>
                <template v-else-if="column.key === 'tipo'">{{ record.tipo || '—' }}</template>
                <template v-else-if="column.key === 'frecuencia'">
                    {{ etiquetaFrecuencia(record.frecuencia) }}
                    <span v-if="record.frecuencia === 'dias'" class="opacity-60">({{ record.valor_frecuencia }})</span>
                </template>
                <template v-else-if="column.key === 'proxima_fecha'">
                    <a-tag :color="record.vencido ? 'error' : 'blue'">{{ fecha(record.proxima_fecha) }}</a-tag>
                </template>
                <template v-else-if="column.key === 'tecnico'">{{ record.tecnico || '—' }}</template>
                <template v-else-if="column.key === 'ocurrencias_count'">
                    <a-tag>{{ record.ocurrencias_count }}</a-tag>
                </template>
                <template v-else-if="column.key === 'estado'">
                    <a-tag :color="record.estado === 'activo' ? 'green' : 'default'">
                        {{ record.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                    </a-tag>
                </template>
                <template v-else-if="column.key === 'acciones'">
                    <a-button type="text" size="small" @click="irA('planes.show', record.id)">
                        <template #icon><EyeOutlined /></template>
                    </a-button>
                </template>
            </template>
        </DataTableInertia>
    </AppLayout>
</template>
