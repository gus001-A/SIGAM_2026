<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { EyeOutlined, FilterOutlined, PlusOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ModalSolicitud from '@/Components/ModalSolicitud.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    solicitudes: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('solicitudes.index', {
    filtros: {
        folio: props.filtros.folio ?? '',
        equipo: props.filtros.equipo ?? '',
        solicitante: props.filtros.solicitante ?? '',
        sucursal_id: props.filtros.sucursal_id ?? undefined,
        prioridad_id: props.filtros.prioridad_id ?? undefined,
        estado_id: props.filtros.estado_id ?? undefined,
    },
    orden: { campo: props.orden.campo ?? 'id', dir: props.orden.dir ?? 'desc' },
});

const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));
const opcionesFiltro = {
    sucursal_id: computed(() => opciones(props.catalogos.sucursales)),
    prioridad_id: computed(() => opciones(props.catalogos.prioridades)),
    estado_id: computed(() => opciones(props.catalogos.estados)),
};

const columns = [
    { title: 'Folio', key: 'folio', dataIndex: 'folio', sorter: true, filtro: 'texto', filtroClave: 'folio', width: 150 },
    { title: 'Equipo', key: 'equipo', filtro: 'texto', filtroClave: 'equipo', width: 260 },
    { title: 'Sucursal', key: 'sucursal', filtro: 'select', filtroClave: 'sucursal_id', width: 160 },
    { title: 'Prioridad', key: 'prioridad', filtro: 'select', filtroClave: 'prioridad_id', width: 150 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado_id', width: 150 },
    { title: 'Solicitante', key: 'solicitante', filtro: 'texto', filtroClave: 'solicitante', width: 170 },
    { title: 'Fecha', key: 'solicitado_at', dataIndex: 'solicitado_at', sorter: true, width: 120 },
    { title: '', key: 'acciones', align: 'right', width: 70, fixed: 'right' },
];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const irA = (n, p) => router.visit(route(n, p));

const modalSolicitud = ref(null);
</script>

<template>
    <Head title="Solicitudes" />

    <AppLayout
        titulo="Solicitudes de mantenimiento"
        descripcion="Reportes de falla o necesidades de servicio pendientes de autorizar y convertir en órdenes."
    >
        <template #acciones>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('solicitudes.crear')" type="primary" @click="modalSolicitud.abrir()">
                <template #icon><PlusOutlined /></template>
                Nueva solicitud
            </a-button>
        </template>

        <DataTableInertia :paginador="solicitudes" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
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
                    <a class="font-medium" @click="irA('solicitudes.show', record.id)">{{ record.folio }}</a>
                </template>
                <template v-else-if="column.key === 'equipo'">{{ record.equipo || '—' }}</template>
                <template v-else-if="column.key === 'sucursal'">{{ record.sucursal || '—' }}</template>
                <template v-else-if="column.key === 'prioridad'">
                    <a-tag v-if="record.prioridad" :color="record.prioridad.color || 'default'">{{ record.prioridad.nombre }}</a-tag>
                    <span v-else>—</span>
                </template>
                <template v-else-if="column.key === 'estado'">
                    <a-tag>{{ record.estado?.nombre }}</a-tag>
                </template>
                <template v-else-if="column.key === 'solicitante'">{{ record.solicitante || '—' }}</template>
                <template v-else-if="column.key === 'solicitado_at'">{{ fecha(record.solicitado_at) }}</template>
                <template v-else-if="column.key === 'acciones'">
                    <a-button type="text" size="small" @click="irA('solicitudes.show', record.id)">
                        <template #icon><EyeOutlined /></template>
                    </a-button>
                </template>
            </template>
        </DataTableInertia>

        <ModalSolicitud
            ref="modalSolicitud"
            :equipos="catalogos.equipos ?? []"
            :prioridades="catalogos.prioridades ?? []"
        />
    </AppLayout>
</template>
