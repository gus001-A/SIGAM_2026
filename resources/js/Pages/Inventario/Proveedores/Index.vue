<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    FilterOutlined,
    PlusOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    proveedores: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('proveedores.index', {
    filtros: {
        razon_social: props.filtros.razon_social ?? '',
        rfc: props.filtros.rfc ?? '',
        contacto: props.filtros.contacto ?? '',
        especialidad: props.filtros.especialidad ?? '',
        estado: props.filtros.estado ?? undefined,
    },
    orden: { campo: props.orden.campo ?? 'razon_social', dir: props.orden.dir ?? 'asc' },
});

const opcionesEstado = [
    { label: 'Activo', value: 'activo' },
    { label: 'Inactivo', value: 'inactivo' },
];

const columns = [
    { title: 'Razón social', key: 'razon_social', dataIndex: 'razon_social', sorter: true, filtro: 'texto', filtroClave: 'razon_social', width: 250 },
    { title: 'RFC', key: 'rfc', dataIndex: 'rfc', sorter: true, filtro: 'texto', filtroClave: 'rfc', width: 150 },
    { title: 'Contacto', key: 'contacto', filtro: 'texto', filtroClave: 'contacto', width: 220 },
    { title: 'Especialidad', key: 'especialidad', dataIndex: 'especialidad', sorter: true, filtro: 'texto', filtroClave: 'especialidad', width: 190 },
    { title: 'Equipos', key: 'equipos_count', dataIndex: 'equipos_count', sorter: true, align: 'right', width: 100 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 130 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (proveedor) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${proveedor.razon_social}`,
        mensaje: 'El proveedor se conservará en el historial pero dejará de aparecer en los listados activos.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('proveedores.destroy', proveedor.id), { preserveScroll: true });
};

const reactivar = (proveedor) =>
    router.put(route('proveedores.restore', proveedor.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Proveedores" />

    <AppLayout
        titulo="Proveedores"
        descripcion="Empresas que suministran equipos o servicios de mantenimiento."
    >
        <template #acciones>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('proveedores.crear')" type="primary" @click="irA('proveedores.create')">
                <template #icon><PlusOutlined /></template>
                Nuevo proveedor
            </a-button>
        </template>

        <DataTableInertia :paginador="proveedores" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
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
                            placeholder="Todos"
                            style="width: 100%"
                            @change="aplicar()"
                        />
            </template>

            <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'razon_social'">
                    <a class="font-medium" @click="irA('proveedores.show', record.id)">{{ record.razon_social }}</a>
                    <div v-if="record.nombre_comercial" class="text-xs opacity-60">{{ record.nombre_comercial }}</div>
                </template>

                <template v-else-if="column.key === 'rfc'">{{ record.rfc || '—' }}</template>

                <template v-else-if="column.key === 'contacto'">
                    <div v-if="record.contacto || record.telefono || record.correo">
                        <div v-if="record.contacto">{{ record.contacto }}</div>
                        <div class="text-xs opacity-60">
                            <span v-if="record.telefono">{{ record.telefono }}</span>
                            <span v-if="record.telefono && record.correo"> · </span>
                            <span v-if="record.correo">{{ record.correo }}</span>
                        </div>
                    </div>
                    <span v-else class="opacity-50">—</span>
                </template>

                <template v-else-if="column.key === 'especialidad'">{{ record.especialidad || '—' }}</template>

                <template v-else-if="column.key === 'equipos_count'">
                    <a-tag>{{ record.equipos_count }}</a-tag>
                </template>

                <template v-else-if="column.key === 'estado'">
                    <a-tag :color="record.estado === 'activo' ? 'green' : 'default'">
                        {{ record.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                    </a-tag>
                </template>

                <template v-else-if="column.key === 'acciones'">
                    <a-space :size="2">
                        <a-tooltip title="Ver detalle">
                            <a-button type="text" size="small" @click="irA('proveedores.show', record.id)">
                                <template #icon><EyeOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('proveedores.editar')" title="Editar">
                            <a-button type="text" size="small" @click="irA('proveedores.edit', record.id)">
                                <template #icon><EditOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('proveedores.desactivar')" title="Desactivar">
                            <a-button type="text" size="small" danger @click="desactivar(record)">
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-else-if="record.estado === 'inactivo' && puede('proveedores.editar')" title="Reactivar">
                            <a-button type="text" size="small" @click="reactivar(record)">
                                <template #icon><UndoOutlined /></template>
                            </a-button>
                        </a-tooltip>
                    </a-space>
                </template>
            </template>
        </DataTableInertia>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>
