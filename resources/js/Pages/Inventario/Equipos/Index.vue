<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    PlusOutlined,
    QrcodeOutlined,
    UploadOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import ModalQr from '@/Components/ModalQr.vue';
import ModalImportarEquipos from '@/Components/ModalImportarEquipos.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    equipos: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('equipos.index', {
    filtros: {
        codigo: props.filtros.codigo ?? '',
        descripcion: props.filtros.descripcion ?? '',
        marca: props.filtros.marca ?? '',
        serie: props.filtros.serie ?? '',
        tipo_id: props.filtros.tipo_id ?? undefined,
        sucursal_id: props.filtros.sucursal_id ?? undefined,
        ubicacion_id: props.filtros.ubicacion_id ?? undefined,
        estado_id: props.filtros.estado_id ?? undefined,
        valor_min: props.filtros.valor_min ?? '',
        valor_max: props.filtros.valor_max ?? '',
    },
    orden: { campo: props.orden.campo ?? 'codigo_activo', dir: props.orden.dir ?? 'asc' },
});

const ubicacionesDeSucursal = computed(() => {
    const base = props.catalogos.ubicaciones ?? [];
    if (!filtros.sucursal_id) return base;
    return base.filter((u) => u.sucursal_id === Number(filtros.sucursal_id));
});

const opciones = (lista, label = 'nombre', value = 'id') =>
    (lista ?? []).map((o) => ({ label: o[label], value: o[value] }));

const columns = [
    { title: 'Código', key: 'codigo_activo', dataIndex: 'codigo_activo', sorter: true, filtro: 'texto', filtroClave: 'codigo', width: 118 },
    { title: 'Equipo', key: 'descripcion', dataIndex: 'descripcion', sorter: true, filtro: 'texto', filtroClave: 'descripcion', width: 190, ellipsis: true },
    { title: 'Tipo', key: 'tipo', filtro: 'select', filtroClave: 'tipo_id', width: 110, ellipsis: true },
    { title: 'Marca / modelo', key: 'marca', filtro: 'texto', filtroClave: 'marca', width: 130, ellipsis: true },
    { title: 'Serie', key: 'numero_serie', dataIndex: 'numero_serie', sorter: true, filtro: 'texto', filtroClave: 'serie', width: 108 },
    { title: 'Sucursal', key: 'sucursal', filtro: 'select', filtroClave: 'sucursal_id', width: 124, ellipsis: true },
    { title: 'Ubicación', key: 'ubicacion', filtro: 'select', filtroClave: 'ubicacion_id', width: 124, ellipsis: true },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado_id', width: 96 },
    { title: 'Valor', key: 'valor_adquisicion', dataIndex: 'valor_adquisicion', sorter: true, filtro: 'rango', align: 'right', width: 108 },
    { title: 'Próx. mant.', key: 'proximo_mantenimiento', width: 100 },
    { title: 'Acciones', key: 'acciones', width: 128, fixed: 'right' },
];

const opcionesFiltro = {
    tipo_id: computed(() => opciones(props.catalogos.tipos)),
    sucursal_id: computed(() => opciones(props.catalogos.sucursales)),
    ubicacion_id: computed(() => opciones(ubicacionesDeSucursal.value)),
    estado_id: computed(() => opciones(props.catalogos.estados)),
};

const moneda = (v) =>
    v == null ? '—' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v);

const confirmar = ref(null);
const modalQr = ref(null);
const modalImportar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const onSucursalCambio = () => {
    filtros.ubicacion_id = undefined;
    aplicar();
};

const darBaja = async (equipo) => {
    const ok = await confirmar.value.abrir({
        titulo: `Dar de baja ${equipo.codigo_activo}`,
        mensaje: 'El equipo se conservará en el historial pero dejará de aparecer en el inventario activo.',
        confirmar: 'Dar de baja',
        peligro: true,
    });
    if (ok) router.delete(route('equipos.destroy', equipo.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Equipos" />

    <AppLayout
        titulo="Equipos"
        descripcion="Inventario de activos con su expediente digital, ubicación, responsable y código QR."
    >
        <template #acciones>
            <a-button v-if="puede('equipos.crear')" @click="modalImportar.abrir()">
                <template #icon><UploadOutlined /></template>
                Cargar desde Excel
            </a-button>
            <a-button v-if="puede('equipos.crear')" type="primary" @click="irA('equipos.create')">
                <template #icon><PlusOutlined /></template>
                Nuevo equipo
            </a-button>
        </template>

        <DataTableInertia
            :paginador="equipos"
            :columns="columns"
            :orden="orden"
            :cargando="cargando"
            :hay-filtros="hayFiltros()"
            @cambio="onCambioTabla"
            @limpiar="limpiar"
        >
            <!-- Fila de filtros bajo el título de cada columna -->
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
                            placeholder="Todos"
                            style="width: 100%"
                            @change="column.filtroClave === 'sucursal_id' ? onSucursalCambio() : aplicar()"
                        />

                        <div v-else-if="column.filtro === 'rango'" class="th__rango">
                            <a-input v-model:value="filtros.valor_min" size="small" placeholder="mín" @update:value="filtrar(600)" />
                            <a-input v-model:value="filtros.valor_max" size="small" placeholder="máx" @update:value="filtrar(600)" />
                        </div>
            </template>

            <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'codigo_activo'">
                    <a class="font-medium" @click="irA('equipos.show', record.id)">{{ record.codigo_activo }}</a>
                </template>

                <template v-else-if="column.key === 'tipo'">{{ record.tipo || '—' }}</template>

                <template v-else-if="column.key === 'marca'">
                    <span>{{ record.marca || '—' }}</span>
                    <span v-if="record.modelo" class="opacity-60"> · {{ record.modelo }}</span>
                </template>

                <template v-else-if="column.key === 'numero_serie'">{{ record.numero_serie || '—' }}</template>
                <template v-else-if="column.key === 'sucursal'">{{ record.sucursal || '—' }}</template>
                <template v-else-if="column.key === 'ubicacion'">{{ record.ubicacion || '—' }}</template>

                <template v-else-if="column.key === 'estado'">
                    <a-tag v-if="record.estado" :color="record.estado.color || 'default'">{{ record.estado.nombre }}</a-tag>
                    <span v-else>—</span>
                </template>

                <template v-else-if="column.key === 'valor_adquisicion'">
                    <span class="whitespace-nowrap">{{ moneda(record.valor_adquisicion) }}</span>
                </template>

                <template v-else-if="column.key === 'proximo_mantenimiento'">
                    <a-tag
                        v-if="record.proximo_mantenimiento"
                        :color="new Date(record.proximo_mantenimiento) < new Date() ? 'error' : 'blue'"
                    >
                        {{ record.proximo_mantenimiento }}
                    </a-tag>
                    <span v-else class="opacity-50">—</span>
                </template>

                <template v-else-if="column.key === 'acciones'">
                    <a-space :size="2">
                        <a-tooltip title="Ver expediente">
                            <a-button type="text" size="small" @click="irA('equipos.show', record.id)">
                                <template #icon><EyeOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip title="Código QR">
                            <a-button type="text" size="small" @click="modalQr.abrir(record.id)">
                                <template #icon><QrcodeOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="puede('equipos.editar')" title="Editar">
                            <a-button type="text" size="small" @click="irA('equipos.edit', record.id)">
                                <template #icon><EditOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="puede('equipos.desactivar')" title="Dar de baja">
                            <a-button type="text" size="small" danger @click="darBaja(record)">
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-tooltip>
                    </a-space>
                </template>
            </template>
        </DataTableInertia>

        <ConfirmarDialog ref="confirmar" />
        <ModalQr ref="modalQr" />
        <ModalImportarEquipos ref="modalImportar" />
    </AppLayout>
</template>

