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
    sucursales: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('sucursales.index', {
    filtros: {
        codigo: props.filtros.codigo ?? '',
        nombre: props.filtros.nombre ?? '',
        direccion: props.filtros.direccion ?? '',
        responsable_id: props.filtros.responsable_id ?? undefined,
        estado: props.filtros.estado ?? undefined,
    },
    orden: { campo: props.orden.campo ?? 'nombre', dir: props.orden.dir ?? 'asc' },
});

const opcionesEstado = [
    { label: 'Activa', value: 'activo' },
    { label: 'Inactiva', value: 'inactivo' },
];

const opcionesFiltro = {
    responsable_id: computed(() => props.catalogos.responsables ?? []),
    estado: computed(() => opcionesEstado),
};

const columns = [
    { title: 'Código', key: 'codigo', dataIndex: 'codigo', sorter: true, filtro: 'texto', filtroClave: 'codigo', width: 130 },
    { title: 'Sucursal', key: 'nombre', dataIndex: 'nombre', sorter: true, filtro: 'texto', filtroClave: 'nombre', width: 220 },
    { title: 'Dirección', key: 'direccion', filtro: 'texto', filtroClave: 'direccion', width: 240 },
    { title: 'Contacto', key: 'contacto', width: 200 },
    { title: 'Responsable', key: 'responsable', filtro: 'select', filtroClave: 'responsable_id', width: 190 },
    { title: 'Equipos', key: 'equipos_count', dataIndex: 'equipos_count', sorter: true, align: 'right', width: 100 },
    { title: 'Valor activos', key: 'valor_activos', dataIndex: 'valor_activos', sorter: true, align: 'right', width: 150 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 130 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const moneda = (v) =>
    v == null ? '—' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(v);

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (sucursal) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${sucursal.nombre}`,
        mensaje: 'La sucursal se conservará en el historial pero dejará de aparecer en los listados activos.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('sucursales.destroy', sucursal.id), { preserveScroll: true });
};

const reactivar = (sucursal) =>
    router.put(route('sucursales.restore', sucursal.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Sucursales" />

    <AppLayout
        titulo="Sucursales"
        descripcion="Sedes de la organización con su responsable, contacto y equipos asignados."
    >
        <template #acciones>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('sucursales.crear')" type="primary" @click="irA('sucursales.create')">
                <template #icon><PlusOutlined /></template>
                Nueva sucursal
            </a-button>
        </template>

        <DataTableInertia :paginador="sucursales" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
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
                            :options="opcionesFiltro[column.filtroClave].value"
                            size="small"
                            allow-clear
                            show-search
                            option-filter-prop="label"
                            placeholder="Todos"
                            style="width: 100%"
                            @change="aplicar()"
                        />
            </template>

            <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'codigo'">
                    <a class="font-medium" @click="irA('sucursales.show', record.id)">{{ record.codigo }}</a>
                </template>

                <template v-else-if="column.key === 'nombre'">
                    <a @click="irA('sucursales.show', record.id)">{{ record.nombre }}</a>
                    <div class="text-xs opacity-60">
                        {{ record.ubicaciones_count }} ubicaciones · {{ record.usuarios_count }} usuarios
                    </div>
                </template>

                <template v-else-if="column.key === 'direccion'">{{ record.direccion || '—' }}</template>

                <template v-else-if="column.key === 'contacto'">
                    <div v-if="record.telefono || record.correo">
                        <div v-if="record.telefono">{{ record.telefono }}</div>
                        <div v-if="record.correo" class="text-xs opacity-60">{{ record.correo }}</div>
                    </div>
                    <span v-else class="opacity-50">—</span>
                </template>

                <template v-else-if="column.key === 'responsable'">{{ record.responsable || '—' }}</template>

                <template v-else-if="column.key === 'equipos_count'">
                    <a-tag>{{ record.equipos_count }}</a-tag>
                </template>

                <template v-else-if="column.key === 'valor_activos'">
                    <span class="whitespace-nowrap">{{ moneda(record.valor_activos) }}</span>
                </template>

                <template v-else-if="column.key === 'estado'">
                    <a-tag :color="record.estado === 'activo' ? 'green' : 'default'">
                        {{ record.estado === 'activo' ? 'Activa' : 'Inactiva' }}
                    </a-tag>
                </template>

                <template v-else-if="column.key === 'acciones'">
                    <a-space :size="2">
                        <a-tooltip title="Ver detalle">
                            <a-button type="text" size="small" @click="irA('sucursales.show', record.id)">
                                <template #icon><EyeOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="puede('sucursales.editar')" title="Editar">
                            <a-button type="text" size="small" @click="irA('sucursales.edit', record.id)">
                                <template #icon><EditOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('sucursales.desactivar')" title="Desactivar">
                            <a-button type="text" size="small" danger @click="desactivar(record)">
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-else-if="record.estado === 'inactivo' && puede('sucursales.editar')" title="Reactivar">
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

