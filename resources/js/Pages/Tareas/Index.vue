<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircleOutlined, ExclamationCircleOutlined, EyeOutlined, FilterOutlined, PlusOutlined, SyncOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ModalTarea from '@/Components/ModalTarea.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    tareas: { type: Object, required: true },
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
const colorEstado = (e) => ({ pendiente: 'default', en_proceso: 'processing', realizada: 'success', cancelada: 'error' })[e] ?? 'default';
const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));
const opcionesFiltro = {
    estado: computed(() => opcionesEstado),
    prioridad_id: computed(() => opciones(props.catalogos.prioridades)),
};

const columns = computed(() => [
    { title: 'Tarea', key: 'tarea', filtro: 'texto', filtroClave: 'descripcion', width: 300 },
    { title: 'Responsable(s)', key: 'responsables', filtro: 'texto', filtroClave: 'responsable', width: 190 },
    { title: 'Fecha límite', key: 'fecha_limite', dataIndex: 'fecha_limite', sorter: true, width: 140 },
    { title: 'Prioridad', key: 'prioridad', filtro: 'select', filtroClave: 'prioridad_id', width: 130 },
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
            <a-button v-if="puede('tareas.crear')" type="primary" @click="modalTarea.abrir()">
                <template #icon><PlusOutlined /></template>
                Nueva tarea
            </a-button>
        </template>

        <a-tabs :active-key="vista" class="tareas-tabs" @change="cambiarVista">
            <a-tab-pane key="activas">
                <template #tab>
                    <span class="tareas-tab"><SyncOutlined /> Activas</span>
                </template>
            </a-tab-pane>
            <a-tab-pane key="completadas">
                <template #tab>
                    <span class="tareas-tab"><CheckCircleOutlined /> Realizadas y canceladas</span>
                </template>
            </a-tab-pane>
        </a-tabs>

        <DataTableInertia :paginador="tareas" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambio">
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
                    <a class="tarea-titulo" @click="irA('tareas.show', record.id)">{{ record.titulo || record.descripcion }}</a>
                    <div v-if="record.titulo && record.descripcion" class="tarea-descripcion">{{ record.descripcion }}</div>
                </template>
                <template v-else-if="column.key === 'responsables'">{{ record.responsables || '—' }}</template>
                <template v-else-if="column.key === 'fecha_limite'">
                    <span :class="{ 'fecha-vencida': record.vencida }">
                        <ExclamationCircleOutlined v-if="record.vencida" />
                        {{ fecha(record.fecha_limite) }}
                    </span>
                </template>
                <template v-else-if="column.key === 'prioridad'">
                    <a-tag v-if="record.prioridad" :color="record.prioridad.color || 'default'">{{ record.prioridad.nombre }}</a-tag>
                    <span v-else>—</span>
                </template>
                <template v-else-if="column.key === 'estado'">
                    <a-tag :color="colorEstado(record.estado)">{{ opcionesEstado.find((o) => o.value === record.estado)?.label }}</a-tag>
                </template>
                <template v-else-if="column.key === 'registrado'">
                    <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                </template>
                <template v-else-if="column.key === 'acciones'">
                    <a-button type="text" size="small" @click="irA('tareas.show', record.id)">
                        <template #icon><EyeOutlined /></template>
                    </a-button>
                </template>
            </template>
        </DataTableInertia>

        <ModalTarea ref="modalTarea" :usuarios="catalogos.usuarios ?? []" :prioridades="catalogos.prioridades ?? []" />
    </AppLayout>
</template>

<style scoped>
.tareas-tabs {
    margin-bottom: 4px;
}
.tareas-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
}
.tarea-titulo {
    display: block;
    font-weight: 600;
    color: var(--sigam-navy, #173a5f);
}
.tarea-descripcion {
    color: #8c98a8;
    font-size: 12.5px;
    font-weight: 400;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 280px;
}
.fecha-vencida {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #d64545;
    font-weight: 700;
}
</style>
