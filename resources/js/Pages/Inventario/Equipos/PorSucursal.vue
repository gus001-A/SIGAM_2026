<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BankOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    DollarOutlined,
    EditOutlined,
    EyeOutlined,
    FileTextOutlined,
    PlusOutlined,
    QrcodeOutlined,
    StopOutlined,
    ToolOutlined,
    UndoOutlined,
    UploadOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import ModalQr from '@/Components/ModalQr.vue';
import ModalImportarEquipos from '@/Components/ModalImportarEquipos.vue';
import ModalBajaEquipo from '@/Components/ModalBajaEquipo.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    resumen: { type: Array, default: () => [] },
    sucursalId: { type: [Number, String], default: null },
    kpis: { type: Object, default: null },
    equipos: { type: Object, default: null },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const moneda = (v) =>
    v == null ? 'No especificado' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(v);

const totalGeneral = computed(() => ({
    equipos: props.resumen.reduce((s, r) => s + Number(r.equipos_count), 0),
    valor: props.resumen.reduce((s, r) => s + Number(r.valor_total), 0),
}));

const opcionesSucursal = computed(() => [
    {
        value: 'todas',
        label: `Todas las sucursales — ${totalGeneral.value.equipos} equipo(s) · ${moneda(totalGeneral.value.valor)}`,
    },
    ...props.resumen.map((r) => ({
        value: r.id,
        label: `${r.nombre} — ${r.equipos_count} equipo(s) · ${moneda(r.valor_total)}`,
    })),
]);

const sucursalActual = computed(() => props.resumen.find((r) => r.id === props.sucursalId));
const valorSelector = computed(() => props.sucursalId ?? 'todas');

// --- Tabla de equipos de la sucursal seleccionada (embebida, sin salir de la vista) ---
const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('equipos.por_sucursal', {
    filtros: {
        sucursal_id: props.sucursalId ?? 'todas',
        codigo: props.filtros.codigo ?? '',
        descripcion: props.filtros.descripcion ?? '',
        marca: props.filtros.marca ?? '',
        serie: props.filtros.serie ?? '',
        tipo_id: props.filtros.tipo_id ?? undefined,
        ubicacion_id: props.filtros.ubicacion_id ?? undefined,
        estado_id: props.filtros.estado_id ?? undefined,
        valor: props.filtros.valor ?? '',
        registrado_por: props.filtros.registrado_por ?? '',
        bajas: props.filtros.bajas ? '1' : undefined,
    },
    orden: { campo: props.orden?.campo ?? 'codigo_activo', dir: props.orden?.dir ?? 'asc' },
});

const cambiarSucursal = (id) => {
    Object.keys(filtros).forEach((k) => (filtros[k] = k === 'sucursal_id' ? id : undefined));
    aplicar();
};

const opciones = (lista, label = 'nombre', value = 'id') => (lista ?? []).map((o) => ({ label: o[label], value: o[value] }));
const opcionesFiltro = {
    tipo_id: computed(() => opciones(props.catalogos.tipos)),
    ubicacion_id: computed(() => opciones(props.catalogos.ubicaciones)),
    estado_id: computed(() => opciones(props.catalogos.estados)),
};

const verBajas = computed(() => filtros.bajas === '1');

const alternarBajas = () => {
    filtros.bajas = verBajas.value ? undefined : '1';
    aplicar();
};

const columnas = computed(() => {
    const base = [
        { title: 'Código', key: 'codigo_activo', dataIndex: 'codigo_activo', sorter: true, filtro: 'texto', filtroClave: 'codigo', width: 118 },
        { title: 'Equipo', key: 'descripcion', dataIndex: 'descripcion', sorter: true, filtro: 'texto', filtroClave: 'descripcion', width: 190, ellipsis: true },
        { title: 'Tipo', key: 'tipo', filtro: 'select', filtroClave: 'tipo_id', width: 120, ellipsis: true },
        { title: 'Marca / modelo', key: 'marca', filtro: 'texto', filtroClave: 'marca', width: 130, ellipsis: true },
        { title: 'Serie', key: 'numero_serie', dataIndex: 'numero_serie', sorter: true, filtro: 'texto', filtroClave: 'serie', width: 108 },
        { title: 'Ubicación', key: 'ubicacion', filtro: 'select', filtroClave: 'ubicacion_id', width: 130, ellipsis: true },
        { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado_id', width: 110 },
        { title: 'Valor', key: 'valor_adquisicion', dataIndex: 'valor_adquisicion', sorter: true, filtro: 'texto', filtroClave: 'valor', align: 'right', width: 110 },
    ];
    base.push(
        verBajas.value
            ? { title: 'Motivo de baja', key: 'baja', width: 220, ellipsis: true }
            : { title: 'Próx. mant.', key: 'proximo_mantenimiento', width: 100 },
    );
    base.push({ title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 });
    base.push({ title: 'Acciones', key: 'acciones', width: 128, fixed: 'right' });
    return base;
});

const tarjetas = computed(() => {
    if (!props.kpis) return [];
    const k = props.kpis;
    return [
        { label: 'Equipos totales', valor: k.total_equipos, icono: ToolOutlined, color: '#0d84c9' },
        { label: 'Valor del inventario', valor: moneda(k.valor_total), icono: DollarOutlined, color: '#1f9e86' },
        { label: 'Operativos', valor: k.operativos, icono: CheckCircleOutlined, color: '#1f9e86' },
        { label: 'Fuera de operación', valor: k.fuera_operacion, icono: StopOutlined, color: '#d64545' },
        { label: 'Mantenimientos pendientes', valor: k.mantenimientos_pendientes, icono: ClockCircleOutlined, color: '#173a5f', ruta: 'mantenimientos.index' },
        { label: 'Solicitudes abiertas', valor: k.solicitudes_abiertas, icono: FileTextOutlined, color: '#e08a1e', ruta: 'solicitudes.index' },
    ];
});

const ir = (ruta) => ruta && router.visit(route(ruta, { sucursal_id: sucursalActual.value?.id }));
const irA = (ruta, params) => router.visit(route(ruta, params));

const confirmar = ref(null);
const modalQr = ref(null);
const modalImportar = ref(null);
const modalBaja = ref(null);

const reactivar = async (equipo) => {
    const ok = await confirmar.value.abrir({
        titulo: `Reactivar ${equipo.codigo_activo}`,
        mensaje: 'El equipo volverá a aparecer en el inventario activo.',
        confirmar: 'Reactivar',
    });
    if (ok) router.put(route('equipos.restore', equipo.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Equipos" />

    <AppLayout
        titulo="Equipos por sucursal"
        descripcion="Elige una sucursal para ver de inmediato sus equipos, el valor de su inventario y los indicadores clave."
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

        <div v-if="!resumen.length" class="ps-vacio">
            <a-empty description="No hay sucursales activas registradas todavía." />
        </div>

        <template v-else>
            <!-- Selector -->
            <a-card size="small" class="mb-3 sec sec--compacta">
                <div class="ps-selector">
                    <label class="ps-selector__label"><BankOutlined /> Sucursal</label>
                    <a-select
                        :value="valorSelector"
                        :options="opcionesSucursal"
                        class="ps-selector__select"
                        placeholder="Selecciona una sucursal"
                        @update:value="cambiarSucursal"
                    />
                    <div class="ps-total">
                        <span class="ps-total__item"><ToolOutlined /> <b>{{ totalGeneral.equipos }}</b> equipos</span>
                        <span class="ps-total__item"><DollarOutlined /> <b>{{ moneda(totalGeneral.valor) }}</b></span>
                        <span class="ps-total__item"><BankOutlined /> {{ resumen.length }} sucursal(es)</span>
                    </div>
                </div>
            </a-card>

            <template v-if="kpis">
                <div class="kpis mb-3">
                    <button
                        v-for="t in tarjetas"
                        :key="t.label"
                        type="button"
                        class="kpi"
                        :class="{ 'kpi--link': !!t.ruta }"
                        :style="{ '--acc': t.color }"
                        @click="ir(t.ruta)"
                    >
                        <span class="kpi__ic"><component :is="t.icono" /></span>
                        <span class="kpi__t">
                            <span class="kpi__v">{{ t.valor }}</span>
                            <span class="kpi__l">{{ t.label }}</span>
                        </span>
                    </button>
                </div>

                <!-- Desglose de equipos de la sucursal, en la misma vista -->
                <a-card size="small" class="sec">
                    <SeccionFicha :titulo="`Equipos de ${sucursalActual?.nombre ?? 'todas las sucursales'} (${equipos?.total ?? 0})`" :icono="ToolOutlined" color="#173a5f">
                        <template #extra>
                            <a-button size="small" :type="verBajas ? 'primary' : 'default'" :ghost="verBajas" @click="alternarBajas">
                                <template #icon><DeleteOutlined /></template>
                                {{ verBajas ? 'Ver equipos activos' : 'Ver dados de baja' }}
                            </a-button>
                        </template>
                    </SeccionFicha>

                    <DataTableInertia
                        v-if="equipos"
                        :paginador="equipos"
                        :columns="columnas"
                        :orden="orden"
                        :cargando="cargando"
                        :hay-filtros="hayFiltros()"
                        @cambio="onCambioTabla"
                        @limpiar="limpiar"
                    >
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
                                :options="opcionesFiltro[column.filtroClave]?.value"
                                size="small"
                                allow-clear
                                placeholder="Todos"
                                style="width: 100%"
                                @change="aplicar()"
                            />
                        </template>

                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'codigo_activo'">
                                <a class="font-medium" @click="irA('equipos.show', record.id)">{{ record.codigo_activo }}</a>
                            </template>
                            <template v-else-if="column.key === 'tipo'">{{ record.tipo || 'No especificado' }}</template>
                            <template v-else-if="column.key === 'marca'">
                                <span>{{ record.marca || 'No especificado' }}</span>
                                <span v-if="record.modelo" class="opacity-60"> · {{ record.modelo }}</span>
                            </template>
                            <template v-else-if="column.key === 'numero_serie'">{{ record.numero_serie || 'No especificado' }}</template>
                            <template v-else-if="column.key === 'ubicacion'">{{ record.ubicacion || 'No especificado' }}</template>
                            <template v-else-if="column.key === 'estado'">
                                <a-tag v-if="record.estado" :color="record.estado.color || 'default'">{{ record.estado.nombre }}</a-tag>
                                <span v-else>No especificado</span>
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
                                <span v-else class="opacity-50">No especificado</span>
                            </template>
                            <template v-else-if="column.key === 'baja'">
                                <a-tooltip :title="record.motivo_baja || 'Sin motivo registrado'">
                                    <div class="baja-motivo">{{ record.motivo_baja || 'Sin motivo registrado' }}</div>
                                </a-tooltip>
                                <div class="baja-meta">
                                    {{ record.baja_por || 'Sistema' }}
                                    <template v-if="record.baja_en"> · {{ new Date(record.baja_en).toLocaleDateString('es-MX') }}</template>
                                </div>
                            </template>
                            <template v-else-if="column.key === 'registrado'">
                                <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                            </template>
                            <template v-else-if="column.key === 'acciones'">
                                <a-space :size="2">
                                    <a-tooltip title="Ver expediente">
                                        <a-button type="text" size="small" @click="irA('equipos.show', record.id)">
                                            <template #icon><EyeOutlined /></template>
                                        </a-button>
                                    </a-tooltip>
                                    <template v-if="!verBajas">
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
                                            <a-button type="text" size="small" danger @click="modalBaja.abrir(record)">
                                                <template #icon><DeleteOutlined /></template>
                                            </a-button>
                                        </a-tooltip>
                                    </template>
                                    <a-tooltip v-else-if="puede('equipos.editar')" title="Reactivar">
                                        <a-button type="text" size="small" @click="reactivar(record)">
                                            <template #icon><UndoOutlined /></template>
                                        </a-button>
                                    </a-tooltip>
                                </a-space>
                            </template>
                        </template>
                    </DataTableInertia>
                </a-card>
            </template>
        </template>

        <ConfirmarDialog ref="confirmar" />
        <ModalQr ref="modalQr" />
        <ModalImportarEquipos ref="modalImportar" />
        <ModalBajaEquipo ref="modalBaja" />
    </AppLayout>
</template>

<style scoped>
.ps-vacio {
    padding: 40px 0;
}

/* ---------- Selector de sucursal ---------- */
.ps-selector {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 14px;
}
.ps-selector__label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--sigam-navy);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.ps-selector__select {
    min-width: 320px;
    flex: 1 1 320px;
    max-width: 520px;
}
.sec--compacta :deep(.ant-card-body) {
    padding: 10px 14px;
}
.ps-total {
    margin-left: auto;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.ps-total__item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    background: var(--sigam-navy-050);
    color: var(--sigam-tenue);
    font-size: 12px;
    white-space: nowrap;
}
.ps-total__item .anticon {
    color: var(--sigam-teal);
    font-size: 12px;
}
.ps-total__item b {
    color: var(--sigam-navy);
    font-weight: 800;
}

/* ---------- KPIs: compactos, en una sola línea, priorizando la tabla ---------- */
.kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 8px;
}
.kpi {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 10px;
    text-align: left;
    cursor: default;
    position: relative;
    overflow: hidden;
    min-width: 0;
    transition: box-shadow 0.14s ease, transform 0.14s ease, border-color 0.14s ease;
}
.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 3px;
    background: var(--acc);
}
.kpi--link {
    cursor: pointer;
}
.kpi--link:hover {
    box-shadow: 0 10px 20px -14px color-mix(in srgb, var(--acc) 55%, transparent);
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--acc) 40%, var(--sigam-borde));
}
.kpi__ic {
    width: 24px;
    height: 24px;
    flex: none;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: var(--acc);
    background: color-mix(in srgb, var(--acc) 13%, #fff);
}
.kpi__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    width: 100%;
}
.kpi__v {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.15;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.kpi__l {
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--sigam-tenue);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ---------- Columna "Motivo de baja" ---------- */
.baja-motivo {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--sigam-texto);
}
.baja-meta {
    font-size: 11px;
    color: var(--sigam-tenue);
    margin-top: 2px;
}

@media (max-width: 1280px) {
    .kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 640px) {
    .kpis {
        grid-template-columns: 1fr;
    }
}

</style>
