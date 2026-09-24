<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BankOutlined, EnvironmentOutlined, EyeOutlined, FilterOutlined, PlusOutlined, ToolOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    planes: { type: Object, required: true },
    sucursalId: { type: [Number, String], default: null },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

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
    { value: 'todas', label: 'Todas las sucursales' },
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
        <template #acciones>
            <a-range-picker
                :value="[filtros.desde || null, filtros.hasta || null]"
                value-format="YYYY-MM-DD"
                :allow-empty="[true, true]"
                placeholder="['Desde', 'Hasta']"
                @change="(_, s) => { filtros.desde = s[0] || ''; filtros.hasta = s[1] || ''; aplicar(); }"
            />
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

        <div class="selector-sucursal">
            <span class="selector-sucursal__ic"><BankOutlined /></span>
            <span class="selector-sucursal__l">Sucursal</span>
            <a-select
                :value="filtros.sucursal_id"
                :options="opcionesSucursal"
                style="min-width: 260px"
                @update:value="cambiarSucursal"
            />
        </div>

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
                    <div v-if="record.objetivo" class="text-xs opacity-60 obj">
                        <ToolOutlined v-if="record.objetivo.tipo === 'equipo'" />
                        <EnvironmentOutlined v-else />
                        {{ record.objetivo.texto }}
                    </div>
                </template>
                <template v-else-if="column.key === 'sucursal'">{{ record.sucursal || '—' }}</template>
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
                <template v-else-if="column.key === 'registrado'">
                    <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
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

<style scoped>
.selector-sucursal {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 12px;
}
.selector-sucursal__ic {
    color: var(--sigam-teal);
    font-size: 16px;
}
.selector-sucursal__l {
    font-size: 12.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sigam-tenue);
}
.obj {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 2px;
}
</style>
