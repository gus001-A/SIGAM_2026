<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    ExclamationCircleOutlined,
    EyeOutlined,
    FileProtectOutlined,
    FilterOutlined,
    PaperClipOutlined,
    PlusOutlined,
    StopOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    normas: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
});

const tarjetas = computed(() => [
    { label: 'Normas totales', valor: props.kpis.total ?? 0, icono: FileProtectOutlined, color: '#0d84c9' },
    { label: 'Vigentes', valor: props.kpis.vigentes ?? 0, icono: CheckCircleOutlined, color: '#1f9e86' },
    { label: 'Inactivas', valor: props.kpis.inactivas ?? 0, icono: StopOutlined, color: '#d64545' },
    { label: 'Por revisar', valor: props.kpis.por_revisar ?? 0, icono: ExclamationCircleOutlined, color: '#e08a1e' },
]);

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('normas.index', {
    filtros: {
        codigo: props.filtros.codigo ?? '',
        nombre: props.filtros.nombre ?? '',
        version: props.filtros.version ?? '',
        estado: props.filtros.estado ?? undefined,
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'codigo', dir: props.orden.dir ?? 'asc' },
});

const opcionesEstado = [
    { label: 'Vigente', value: 'activo' },
    { label: 'Inactiva', value: 'inactivo' },
];

const columns = [
    { title: 'Código', key: 'codigo', dataIndex: 'codigo', sorter: true, filtro: 'texto', filtroClave: 'codigo', width: 170 },
    { title: 'Nombre', key: 'nombre', dataIndex: 'nombre', sorter: true, filtro: 'texto', filtroClave: 'nombre', width: 280 },
    { title: 'Versión', key: 'version', dataIndex: 'version', sorter: true, filtro: 'texto', filtroClave: 'version', width: 110 },
    { title: 'Vigencia', key: 'fecha_vigencia', dataIndex: 'fecha_vigencia', sorter: true, width: 120 },
    { title: 'Revisión', key: 'fecha_revision', dataIndex: 'fecha_revision', sorter: true, width: 130 },
    { title: 'Uso', key: 'uso', width: 130 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 120 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (norma) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${norma.codigo}`,
        mensaje: 'La norma se conservará en el historial pero dejará de estar disponible para asociar a equipos y planes.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('normas.destroy', norma.id), { preserveScroll: true });
};

const reactivar = (norma) => router.put(route('normas.restore', norma.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Normas" />

    <AppLayout
        titulo="Normas y procedimientos"
        descripcion="Normativa aplicable a los equipos, con su versión vigente y fecha de revisión."
    >
        <template #acciones>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('normas.crear')" type="primary" @click="irA('normas.create')">
                <template #icon><PlusOutlined /></template>
                Nueva norma
            </a-button>
        </template>

        <!-- KPIs con colores sólidos -->
        <div class="kpis">
            <div v-for="k in tarjetas" :key="k.label" class="kpi" :style="{ '--acc': k.color }">
                <div class="kpi__icono">
                    <component :is="k.icono" />
                </div>
                <div class="kpi__txt">
                    <div class="kpi__valor">{{ k.valor }}</div>
                    <div class="kpi__etq">{{ k.label }}</div>
                </div>
            </div>
        </div>

        <DataTableInertia :paginador="normas" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
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
                            placeholder="Todas"
                            style="width: 100%"
                            @change="aplicar()"
                        />
            </template>

            <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'codigo'">
                    <a class="font-medium" @click="irA('normas.show', record.id)">{{ record.codigo }}</a>
                    <PaperClipOutlined v-if="record.tiene_documento" class="ml-1 opacity-50" />
                </template>

                <template v-else-if="column.key === 'version'">{{ record.version || '—' }}</template>
                <template v-else-if="column.key === 'fecha_vigencia'">{{ fecha(record.fecha_vigencia) }}</template>

                <template v-else-if="column.key === 'fecha_revision'">
                    <a-tag v-if="record.fecha_revision" :color="record.revision_vencida ? 'error' : 'default'">
                        {{ fecha(record.fecha_revision) }}
                    </a-tag>
                    <span v-else class="opacity-50">—</span>
                </template>

                <template v-else-if="column.key === 'uso'">
                    <a-space :size="4">
                        <a-tooltip title="Equipos"><a-tag>{{ record.equipos_count }} eq.</a-tag></a-tooltip>
                        <a-tooltip title="Planes preventivos"><a-tag>{{ record.planes_count }} pl.</a-tag></a-tooltip>
                    </a-space>
                </template>

                <template v-else-if="column.key === 'estado'">
                    <a-tag :color="record.estado === 'activo' ? 'green' : 'default'">
                        {{ record.estado === 'activo' ? 'Vigente' : 'Inactiva' }}
                    </a-tag>
                </template>

                <template v-else-if="column.key === 'registrado'">
                    <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                </template>

                <template v-else-if="column.key === 'acciones'">
                    <a-space :size="2">
                        <a-tooltip title="Ver detalle">
                            <a-button type="text" size="small" @click="irA('normas.show', record.id)">
                                <template #icon><EyeOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('normas.editar')" title="Editar">
                            <a-button type="text" size="small" @click="irA('normas.edit', record.id)">
                                <template #icon><EditOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('normas.desactivar')" title="Desactivar">
                            <a-button type="text" size="small" danger @click="desactivar(record)">
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-else-if="record.estado === 'inactivo' && puede('normas.editar')" title="Reactivar">
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

<style scoped>
/* ==========================================================
   KPIs con colores sólidos
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
    margin-bottom: 14px;
}

.kpi {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    position: relative;
    overflow: hidden;
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}

.kpi__icono {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: var(--acc);
    flex-shrink: 0;
}

.kpi__valor {
    font-size: 18px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.1;
}

.kpi__etq {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 767px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
