<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BankOutlined, EnvironmentOutlined, EyeOutlined, FilterOutlined, PlusOutlined, ToolOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ModalOrden from '@/Components/ModalOrden.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    mantenimientos: { type: Object, required: true },
    sucursalId: { type: [Number, String], default: null },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('mantenimientos.index', {
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
});

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
    { title: 'Tipo', key: 'tipo', filtro: 'select', filtroClave: 'tipo_id', width: 140 },
    { title: 'Sucursal', key: 'sucursal', width: 150 },
    { title: 'Prioridad', key: 'prioridad', filtro: 'select', filtroClave: 'prioridad_id', width: 140 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado_id', width: 150 },
    { title: 'Técnico(s)', key: 'tecnicos', filtro: 'select', filtroClave: 'tecnico_id', width: 180 },
    { title: 'Programado', key: 'programado_inicio', dataIndex: 'programado_inicio', sorter: true, width: 130 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 70, fixed: 'right' },
];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const irA = (n, p) => router.visit(route(n, p));

const modalOrden = ref(null);
</script>

<template>
    <Head title="Órdenes de mantenimiento" />

    <AppLayout
        titulo="Órdenes de mantenimiento"
        descripcion="Trabajos correctivos y preventivos con su asignación de técnicos, materiales y estado."
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
            <a-button v-if="puede('mantenimientos.crear')" type="primary" @click="modalOrden.abrir()">
                <template #icon><PlusOutlined /></template>
                Nueva orden
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

        <DataTableInertia :paginador="mantenimientos" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
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
                <template v-if="column.key === 'folio'">
                    <a class="font-medium" @click="irA('mantenimientos.show', record.id)">{{ record.folio }}</a>
                </template>
                <template v-else-if="column.key === 'objetivo'">
                    <span v-if="record.objetivo" class="obj">
                        <ToolOutlined v-if="record.objetivo.tipo === 'equipo'" />
                        <EnvironmentOutlined v-else />
                        {{ record.objetivo.texto }}
                    </span>
                    <span v-else>—</span>
                </template>
                <template v-else-if="column.key === 'tipo'">
                    <a-tag v-if="record.tipo" :color="record.tipo.categoria === 'urgente' ? 'red' : record.tipo.categoria === 'preventivo' ? 'green' : 'blue'">
                        {{ record.tipo.nombre }}
                    </a-tag>
                    <span v-else>—</span>
                </template>
                <template v-else-if="column.key === 'sucursal'">{{ record.sucursal || '—' }}</template>
                <template v-else-if="column.key === 'prioridad'">
                    <a-tag v-if="record.prioridad" :color="record.prioridad.color || 'default'">{{ record.prioridad.nombre }}</a-tag>
                    <span v-else>—</span>
                </template>
                <template v-else-if="column.key === 'estado'">
                    <a-tag>{{ record.estado?.nombre }}</a-tag>
                </template>
                <template v-else-if="column.key === 'tecnicos'">{{ record.tecnicos || '—' }}</template>
                <template v-else-if="column.key === 'programado_inicio'">{{ fecha(record.programado_inicio) }}</template>
                <template v-else-if="column.key === 'registrado'">
                    <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                </template>
                <template v-else-if="column.key === 'acciones'">
                    <a-button type="text" size="small" @click="irA('mantenimientos.show', record.id)">
                        <template #icon><EyeOutlined /></template>
                    </a-button>
                </template>
            </template>
        </DataTableInertia>

        <ModalOrden ref="modalOrden" :equipos="catalogos.equipos ?? []" :ubicaciones="catalogos.ubicaciones ?? []" :catalogos="catalogos" />
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
    gap: 6px;
}
</style>
