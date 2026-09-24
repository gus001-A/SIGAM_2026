<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CopyOutlined,
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
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    formatos: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('formatos.index', {
    filtros: {
        nombre: props.filtros.nombre ?? '',
        version: props.filtros.version ?? '',
        estado: props.filtros.estado ?? undefined,
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'nombre', dir: props.orden.dir ?? 'asc' },
});

const opcionesEstado = [
    { label: 'Activo', value: 'activo' },
    { label: 'Inactivo', value: 'inactivo' },
];

const columns = [
    { title: 'Nombre', key: 'nombre', dataIndex: 'nombre', sorter: true, filtro: 'texto', filtroClave: 'nombre', width: 280 },
    { title: 'Versión', key: 'version', dataIndex: 'version', sorter: true, filtro: 'texto', filtroClave: 'version', width: 110 },
    { title: 'Campos', key: 'campos_count', dataIndex: 'campos_count', sorter: true, align: 'right', width: 100 },
    { title: 'Respuestas', key: 'respuestas_count', dataIndex: 'respuestas_count', sorter: true, align: 'right', width: 120 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 120 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (formato) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${formato.nombre}`,
        mensaje: 'El formato dejará de estar disponible para las órdenes. Las respuestas ya capturadas se conservan.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('formatos.destroy', formato.id), { preserveScroll: true });
};

const reactivar = (formato) => router.put(route('formatos.restore', formato.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Formatos" />

    <AppLayout
        titulo="Formatos y checklists"
        descripcion="Plantillas de captura para inspecciones y mantenimientos, con sus campos configurables."
    >
        <template #acciones>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('formatos.crear')" type="primary" @click="irA('formatos.create')">
                <template #icon><PlusOutlined /></template>
                Nuevo formato
            </a-button>
        </template>

        <DataTableInertia :paginador="formatos" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
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
                <template v-if="column.key === 'nombre'">
                    <a class="font-medium" @click="irA('formatos.show', record.id)">{{ record.nombre }}</a>
                    <div v-if="record.descripcion" class="text-xs opacity-60">{{ record.descripcion }}</div>
                </template>

                <template v-else-if="column.key === 'version'">{{ record.version || '—' }}</template>

                <template v-else-if="column.key === 'campos_count'">
                    <a-tag>{{ record.campos_count }}</a-tag>
                </template>

                <template v-else-if="column.key === 'respuestas_count'">
                    <a-tag :color="record.respuestas_count ? 'blue' : 'default'">{{ record.respuestas_count }}</a-tag>
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
                    <a-space :size="2">
                        <a-tooltip title="Vista previa">
                            <a-button type="text" size="small" @click="irA('formatos.show', record.id)">
                                <template #icon><EyeOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('formatos.editar')" title="Editar">
                            <a-button type="text" size="small" @click="irA('formatos.edit', record.id)">
                                <template #icon><EditOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('formatos.desactivar')" title="Desactivar">
                            <a-button type="text" size="small" danger @click="desactivar(record)">
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-else-if="record.estado === 'inactivo' && puede('formatos.editar')" title="Reactivar">
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
