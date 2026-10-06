<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BankOutlined,
    CheckCircleOutlined,
    DeleteOutlined,
    DollarOutlined,
    EditOutlined,
    EyeOutlined,
    FilterOutlined,
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
    sucursales: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
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
        estado: props.filtros.estado ?? undefined,
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'nombre', dir: props.orden.dir ?? 'asc' },
});

const opcionesEstado = [
    { label: 'Activa', value: 'activo' },
    { label: 'Inactiva', value: 'inactivo' },
];

const opcionesFiltro = {
    estado: computed(() => opcionesEstado),
};

const columns = [
    { title: 'Código', key: 'codigo', dataIndex: 'codigo', sorter: true, filtro: 'texto', filtroClave: 'codigo', width: 130 },
    { title: 'Sucursal', key: 'nombre', dataIndex: 'nombre', sorter: true, filtro: 'texto', filtroClave: 'nombre', width: 220 },
    { title: 'Dirección', key: 'direccion', filtro: 'texto', filtroClave: 'direccion', width: 240 },
    { title: 'Contacto', key: 'contacto', width: 200 },
    { title: 'Equipos', key: 'equipos_count', dataIndex: 'equipos_count', sorter: true, align: 'right', width: 100 },
    { title: 'Valor activos', key: 'valor_activos', dataIndex: 'valor_activos', sorter: true, align: 'right', width: 150 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 130 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const moneda = (v) =>
    v == null ? '—' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(v);

const tarjetas = computed(() => [
    { label: 'Sucursales totales', valor: props.kpis.total ?? 0, icono: BankOutlined, color: '#0d84c9' },
    { label: 'Activas', valor: props.kpis.activas ?? 0, icono: CheckCircleOutlined, color: '#1f9e86' },
    { label: 'Inactivas', valor: props.kpis.inactivas ?? 0, icono: StopOutlined, color: '#d64545' },
    { label: 'Valor de inventario', valor: moneda(props.kpis.valor_total), icono: DollarOutlined, color: '#6b4bc9' },
]);

const totalPaginas = computed(() => {
    const p = props.sucursales;
    if (!p) return 1;
    return Number(p.last_page ?? 1);
});

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

    <AppLayout titulo="Sucursales"
        descripcion="Sedes de la organización con su responsable, contacto y equipos asignados.">
        <template #acciones>
            <a-button v-if="hayFiltros()" class="btn-limpiar" @click="limpiar">
                <template #icon>
                    <FilterOutlined />
                </template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('sucursales.crear')" type="primary" class="btn-nueva"
                @click="irA('sucursales.create')">
                <template #icon>
                    <PlusOutlined />
                </template>
                Nueva sucursal
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

        <div class="tabla-sucursales" :class="{ 'tabla-sucursales--sin-paginacion': totalPaginas <= 1 }">
            <DataTableInertia :paginador="sucursales" :columns="columns" :orden="orden" :cargando="cargando"
                @cambio="onCambioTabla" @limpiar="limpiar">
                <template #filtro="{ column }">
                    <a-input v-if="column.filtro === 'texto'" v-model:value="filtros[column.filtroClave]" size="small"
                        allow-clear placeholder="Filtrar" @update:value="filtrar()" />

                    <a-select v-else-if="column.filtro === 'select'" v-model:value="filtros[column.filtroClave]"
                        :options="opcionesFiltro[column.filtroClave].value" size="small" allow-clear placeholder="Todos"
                        style="width: 100%" @change="aplicar()" />
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'codigo'">
                        <a class="link-codigo" @click="irA('sucursales.show', record.id)">{{ record.codigo }}</a>
                    </template>

                    <template v-else-if="column.key === 'nombre'">
                        <a class="link-sucursal" @click="irA('sucursales.show', record.id)">{{ record.nombre }}</a>
                        <div class="sucursal-meta">
                            <span class="meta-chip meta-chip--blue">{{ record.ubicaciones_count }} ubicaciones</span>
                        </div>
                    </template>

                    <template v-else-if="column.key === 'direccion'">
                        <span class="texto-suave">{{ record.direccion || '—' }}</span>
                    </template>

                    <template v-else-if="column.key === 'contacto'">
                        <div v-if="record.telefono || record.correo" class="contacto">
                            <div v-if="record.telefono" class="contacto__tel">
                                <span class="dot dot--green"></span>{{ record.telefono }}
                            </div>
                            <div v-if="record.correo" class="contacto__mail">{{ record.correo }}</div>
                        </div>
                        <span v-else class="texto-suave">—</span>
                    </template>

                    <template v-else-if="column.key === 'equipos_count'">
                        <a-tag class="tag-equipos">{{ record.equipos_count }}</a-tag>
                    </template>

                    <template v-else-if="column.key === 'valor_activos'">
                        <span class="valor-activos">{{ moneda(record.valor_activos) }}</span>
                    </template>

                    <template v-else-if="column.key === 'estado'">
                        <a-tag :color="record.estado === 'activo' ? 'green' : 'default'" class="tag-estado">
                            <span class="dot" :class="record.estado === 'activo' ? 'dot--green' : 'dot--gray'"></span>
                            {{ record.estado === 'activo' ? 'Activa' : 'Inactiva' }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === 'registrado'">
                        <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                    </template>

                    <template v-else-if="column.key === 'acciones'">
                        <a-space :size="2">
                            <a-tooltip title="Ver detalle">
                                <a-button type="text" size="small" class="accion accion--ver"
                                    @click="irA('sucursales.show', record.id)">
                                    <template #icon>
                                        <EyeOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-if="puede('sucursales.editar')" title="Editar">
                                <a-button type="text" size="small" class="accion accion--editar"
                                    @click="irA('sucursales.edit', record.id)">
                                    <template #icon>
                                        <EditOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-if="record.estado === 'activo' && puede('sucursales.desactivar')"
                                title="Desactivar">
                                <a-button type="text" size="small" danger class="accion accion--peligro"
                                    @click="desactivar(record)">
                                    <template #icon>
                                        <DeleteOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-else-if="record.estado === 'inactivo' && puede('sucursales.editar')"
                                title="Reactivar">
                                <a-button type="text" size="small" class="accion accion--reactivar"
                                    @click="reactivar(record)">
                                    <template #icon>
                                        <UndoOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                        </a-space>
                    </template>
                </template>
            </DataTableInertia>
        </div>

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

/* ==========================================================
   Botones superiores (colores sólidos)
   ========================================================== */
.btn-limpiar {
    background: #fff;
    border: 1px solid #c9b8f0;
    color: #6b4bc9;
}

.btn-limpiar:hover {
    background: #faf7ff !important;
    border-color: #6b4bc9 !important;
    color: #563a9e !important;
}

.btn-nueva {
    background: #0d84c9 !important;
    border-color: #0d84c9 !important;
    color: #fff !important;
}

.btn-nueva:hover {
    background: #0f6fb0 !important;
    border-color: #0f6fb0 !important;
}

/* ==========================================================
   Tabla
   ========================================================== */
.tabla-sucursales {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--sigam-borde-suave);
    box-shadow: var(--sigam-sombra-sm);
    background: #fff;
}

/* Encabezado gris azulado claro (como Proveedores/Equipos) */
.tabla-sucursales :deep(.ant-table-thead > tr > th) {
    background: var(--sigam-navy-050) !important;
    color: var(--sigam-navy) !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-size: 11px;
    border-bottom: 1px solid var(--sigam-borde-suave) !important;
}

.tabla-sucursales :deep(.ant-table-thead > tr > th::before) {
    background-color: var(--sigam-borde-suave) !important;
}

/* Filas alternas */
.tabla-sucursales :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid var(--sigam-borde-suave) !important;
}

.tabla-sucursales :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

/* Hover de fila neutro */
.tabla-sucursales :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

/* Columna de acciones fija */
.tabla-sucursales :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid var(--sigam-borde-suave);
}

.tabla-sucursales :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

/* ==========================================================
   Paginación
   ========================================================== */
.tabla-sucursales :deep(.ant-pagination-total-text),
.tabla-sucursales :deep(.ant-pagination-options-quick-jumper) {
    display: none !important;
}

.tabla-sucursales--sin-paginacion :deep(.ant-pagination) {
    display: none !important;
}

.tabla-sucursales :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
}

.tabla-sucursales :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #0d84c9;
    font-weight: 800;
}

/* ==========================================================
   Celdas con color
   ========================================================== */
.link-codigo {
    font-weight: 800;
    color: #0d84c9;
    letter-spacing: 0.02em;
}

.link-codigo:hover {
    color: #0d84c9;
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 4px;
}

.link-sucursal {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13px;
}

.link-sucursal:hover {
    color: #0d84c9;
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 4px;
}

.sucursal-meta {
    display: flex;
    gap: 6px;
    margin-top: 3px;
    flex-wrap: wrap;
}

.meta-chip {
    display: inline-flex;
    align-items: center;
    padding: 1px 8px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    line-height: 16px;
    border: 1px solid transparent;
}

.meta-chip--blue {
    background: #e6f2fb;
    color: #0d6fae;
    border-color: #cfe3f2;
}

.texto-suave {
    color: var(--sigam-tenue);
}

/* Contacto */
.contacto__tel {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: var(--sigam-texto);
    font-size: 12.5px;
}

.contacto__mail {
    font-size: 11px;
    color: var(--sigam-tenue);
    margin-top: 1px;
}

/* Tag equipos (color sólido) */
.tag-equipos {
    background: #e6f2fb;
    color: #0d6fae;
    border: 1px solid #cfe3f2;
    font-weight: 800;
    border-radius: 999px;
    padding: 0 10px;
    line-height: 20px;
}

/* Valor de activos */
.valor-activos {
    font-weight: 800;
    color: #16806c;
    letter-spacing: -0.1px;
    white-space: nowrap;
}

/* Tag estado con dot */
.tag-estado {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    border-radius: 999px;
    padding: 0 10px;
    line-height: 20px;
}

.dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
}

.dot--green {
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
}

.dot--gray {
    background: #94a3b8;
    box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.18);
}

/* ==========================================================
   Acciones con color al hover
   ========================================================== */
.accion {
    border-radius: 8px;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.accion--ver {
    color: #0d84c9;
}

.accion--ver:hover {
    background: #e6f2fb !important;
    color: #0f6fb0 !important;
    transform: translateY(-1px);
}

.accion--editar {
    color: #6b4bc9;
}

.accion--editar:hover {
    background: #efe9fb !important;
    color: #563a9e !important;
    transform: translateY(-1px);
}

.accion--reactivar {
    color: #1f9e86;
}

.accion--reactivar:hover {
    background: #e4f4ec !important;
    color: #16806c !important;
    transform: translateY(-1px);
}

.accion--peligro:hover {
    background: #fdecec !important;
    transform: translateY(-1px);
}
</style>