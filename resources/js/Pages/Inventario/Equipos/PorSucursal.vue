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
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import ModalQr from '@/Components/ModalQr.vue';
import ModalImportarEquipos from '@/Components/ModalImportarEquipos.vue';
import ModalBajaEquipo from '@/Components/ModalBajaEquipo.vue';
import ModalRestaurarEquipo from '@/Components/ModalRestaurarEquipo.vue';
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
    v == null
        ? 'No especificado'
        : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(v);

const formatoFechaHora = (fecha) => {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

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

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia(
    'equipos.por_sucursal',
    {
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
            bajas: props.filtros.bajas ?? 0,
        },
        orden: { campo: props.orden?.campo ?? 'codigo_activo', dir: props.orden?.dir ?? 'asc' },
    },
);

const modoBajas = computed(() => Number(filtros.bajas) === 1);

// 👇 Total de páginas para ocultar la paginación si solo hay 1
const totalPaginas = computed(() => {
    const p = props.equipos;
    if (!p) return 1;
    return Number(p.last_page ?? 1);
});

const cambiarSucursal = (id) => {
    Object.keys(filtros).forEach((k) => (filtros[k] = k === 'sucursal_id' ? id : undefined));
    filtros.bajas = 0;
    aplicar();
};

const cambiarModo = (esBajas) => {
    if (modoBajas.value === esBajas) return;
    filtros.bajas = esBajas ? 1 : 0;
    filtros.codigo = '';
    filtros.descripcion = '';
    filtros.marca = '';
    filtros.serie = '';
    filtros.tipo_id = undefined;
    filtros.ubicacion_id = undefined;
    filtros.estado_id = undefined;
    filtros.valor = '';
    filtros.registrado_por = '';
    aplicar();
};

const opciones = (lista, label = 'nombre', value = 'id') => (lista ?? []).map((o) => ({ label: o[label], value: o[value] }));
const opcionesFiltro = {
    tipo_id: computed(() => opciones(props.catalogos.tipos)),
    ubicacion_id: computed(() => opciones(props.catalogos.ubicaciones)),
    estado_id: computed(() => opciones(props.catalogos.estados)),
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
        { title: 'Próx. mant.', key: 'proximo_mantenimiento', width: 100 },
    ];

    if (modoBajas.value) {
        base.push({ title: 'Baja', key: 'baja', width: 230 });
    }

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
        { label: 'Operativos', valor: k.operativos, icono: CheckCircleOutlined, color: '#16a34a' },
        { label: 'Fuera de operación', valor: k.fuera_operacion, icono: StopOutlined, color: '#dc2626' },
        { label: 'Dados de baja', valor: k.dados_de_baja, icono: DeleteOutlined, color: '#7c3aed', accion: 'bajas' },
        { label: 'Mantenimientos pendientes', valor: k.mantenimientos_pendientes, icono: ClockCircleOutlined, color: '#6b4bc9', ruta: 'mantenimientos.index' },
        { label: 'Solicitudes abiertas', valor: k.solicitudes_abiertas, icono: FileTextOutlined, color: '#e08a1e', ruta: 'solicitudes.index' },
    ];
});

const ir = (ruta) => ruta && router.visit(route(ruta, { sucursal_id: sucursalActual.value?.id }));
const irA = (ruta, params) => router.visit(route(ruta, params));

const onKpiClick = (t) => {
    if (t.accion === 'bajas') {
        cambiarModo(!modoBajas.value);
        return;
    }
    ir(t.ruta);
};

const confirmar = ref(null);
const modalQr = ref(null);
const modalImportar = ref(null);
const modalBaja = ref(null);
const modalRestaurar = ref(null);

const reactivar = (equipo) => modalRestaurar.value.abrir(equipo);
</script>

<template>

    <Head title="Equipos" />

    <AppLayout titulo="Equipos por sucursal"
        descripcion="Elige una sucursal para ver de inmediato sus equipos, el valor de su inventario y los indicadores clave.">
        <template #acciones>
            <a-button v-if="puede('equipos.crear')" class="btn-importar" @click="modalImportar.abrir()">
                <template #icon>
                    <UploadOutlined />
                </template>
                Cargar desde Excel
            </a-button>
            <a-button v-if="puede('equipos.crear')" type="primary" class="btn-nuevo" @click="irA('equipos.create')">
                <template #icon>
                    <PlusOutlined />
                </template>
                Nuevo equipo
            </a-button>
        </template>

        <div v-if="!resumen.length" class="ps-vacio">
            <a-empty description="No hay sucursales activas registradas todavía." />
        </div>

        <template v-else>
            <!-- Selector de sucursal + Switch en la MISMA línea -->
            <div class="card card--selector">
                <div class="ps-selector">
                    <label class="ps-selector__label">
                        <BankOutlined /> Sucursal
                    </label>
                    <a-select :value="valorSelector" :options="opcionesSucursal" class="ps-selector__select"
                        placeholder="Selecciona una sucursal" @update:value="cambiarSucursal" />

                    <div class="modo-switch" :class="{ 'modo-switch--bajas': modoBajas }" role="tablist"
                        aria-label="Modo de listado">
                        <span class="modo-switch__slider" aria-hidden="true"></span>
                        <button type="button" role="tab" class="modo-switch__opt"
                            :class="{ 'modo-switch__opt--active': !modoBajas }"
                            :aria-selected="!modoBajas" @click="cambiarModo(false)">
                            <CheckCircleOutlined />
                            <span>Activos</span>
                        </button>
                        <button type="button" role="tab" class="modo-switch__opt"
                            :class="{ 'modo-switch__opt--active': modoBajas }"
                            :aria-selected="modoBajas" @click="cambiarModo(true)">
                            <DeleteOutlined />
                            <span>Dados de baja</span>
                        </button>
                    </div>
                </div>
            </div>

            <template v-if="kpis">
                <!-- KPIs -->
                <div class="kpis mb-3">
                    <button v-for="t in tarjetas" :key="t.label" type="button" class="kpi"
                        :class="{ 'kpi--link': !!t.ruta || !!t.accion, 'kpi--activo': t.accion === 'bajas' && modoBajas }"
                        :style="{ '--acc': t.color }" @click="onKpiClick(t)">
                        <span class="kpi__ic">
                            <component :is="t.icono" />
                        </span>
                        <span class="kpi__t">
                            <span class="kpi__v">{{ t.valor }}</span>
                            <span class="kpi__l">{{ t.label }}</span>
                        </span>
                    </button>
                </div>

                <!-- 👇 Tabla con paginación estilo iOS -->
                <div class="card card--tabla"
                    :class="{ 'card--tabla--sin-paginacion': totalPaginas <= 1 }">
                    <DataTableInertia v-if="equipos" :paginador="equipos" :columns="columnas" :orden="orden"
                        :cargando="cargando" :hay-filtros="hayFiltros()" @cambio="onCambioTabla" @limpiar="limpiar">
                        <template #filtro="{ column }">
                            <a-input v-if="column.filtro === 'texto'" v-model:value="filtros[column.filtroClave]"
                                size="small" allow-clear placeholder="Filtrar" @update:value="filtrar()" />
                            <a-select v-else-if="column.filtro === 'select'" v-model:value="filtros[column.filtroClave]"
                                :options="opcionesFiltro[column.filtroClave]?.value" size="small" allow-clear
                                placeholder="Todos" style="width: 100%" @change="aplicar()" />
                        </template>

                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'codigo_activo'">
                                <a v-if="!record.deleted_at" class="link-equipo"
                                    @click="irA('equipos.show', record.id)">{{ record.codigo_activo }}</a>
                                <span v-else class="link-equipo link-equipo--baja">{{ record.codigo_activo }}</span>
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
                                <a-tag v-if="record.proximo_mantenimiento"
                                    :color="new Date(record.proximo_mantenimiento) < new Date() ? 'error' : 'blue'">
                                    {{ record.proximo_mantenimiento }}
                                </a-tag>
                                <span v-else class="opacity-50">No especificado</span>
                            </template>

                            <template v-else-if="column.key === 'baja'">
                                <div class="baja-cell">
                                    <a-tooltip :title="record.motivo_baja || 'Sin motivo registrado'">
                                        <div class="baja-cell__motivo">{{ record.motivo_baja || 'Sin motivo registrado' }}</div>
                                    </a-tooltip>
                                    <div class="baja-cell__meta">
                                        <span class="baja-cell__user">
                                            <UserOutlined /> {{ record.baja_por || 'Sistema' }}
                                        </span>
                                        <span class="baja-cell__fecha">
                                            <ClockCircleOutlined /> {{ formatoFechaHora(record.baja_en) }}
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="column.key === 'registrado'">
                                <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                            </template>
                            <template v-else-if="column.key === 'acciones'">
                                <a-space :size="2">
                                    <template v-if="!record.deleted_at">
                                        <a-tooltip title="Ver expediente">
                                            <a-button type="text" size="small" class="accion accion--ver"
                                                @click="irA('equipos.show', record.id)">
                                                <template #icon>
                                                    <EyeOutlined />
                                                </template>
                                            </a-button>
                                        </a-tooltip>
                                        <a-tooltip title="Código QR">
                                            <a-button type="text" size="small" class="accion accion--ver"
                                                @click="modalQr.abrir(record.id)">
                                                <template #icon>
                                                    <QrcodeOutlined />
                                                </template>
                                            </a-button>
                                        </a-tooltip>
                                        <a-tooltip v-if="puede('equipos.editar')" title="Editar">
                                            <a-button type="text" size="small" class="accion accion--editar"
                                                @click="irA('equipos.edit', record.id)">
                                                <template #icon>
                                                    <EditOutlined />
                                                </template>
                                            </a-button>
                                        </a-tooltip>
                                        <a-tooltip v-if="puede('equipos.desactivar')" title="Dar de baja">
                                            <a-button type="text" size="small" danger class="accion accion--peligro"
                                                @click="modalBaja.abrir(record)">
                                                <template #icon>
                                                    <DeleteOutlined />
                                                </template>
                                            </a-button>
                                        </a-tooltip>
                                    </template>

                                    <template v-else>
                                        <a-tooltip title="Ver registro">
                                            <a-button type="text" size="small" class="accion accion--ver"
                                                @click="irA('equipos.show', record.id)">
                                                <template #icon>
                                                    <EyeOutlined />
                                                </template>
                                            </a-button>
                                        </a-tooltip>
                                        <a-tooltip v-if="puede('equipos.editar')" title="Restaurar equipo">
                                            <a-button type="text" size="small" class="accion accion--reactivar"
                                                @click="reactivar(record)">
                                                <template #icon>
                                                    <UndoOutlined />
                                                </template>
                                            </a-button>
                                        </a-tooltip>
                                    </template>
                                </a-space>
                            </template>
                        </template>
                    </DataTableInertia>
                </div>
            </template>
        </template>

        <ConfirmarDialog ref="confirmar" />
        <ModalQr ref="modalQr" />
        <ModalImportarEquipos ref="modalImportar" />
        <ModalBajaEquipo ref="modalBaja" />
        <ModalRestaurarEquipo ref="modalRestaurar" :estados="catalogos.estados ?? []" />
    </AppLayout>
</template>

<style scoped>
.ps-vacio {
    padding: 40px 0;
}

/* ==========================================================
   Botones de acciones
   ========================================================== */
.btn-importar {
    background: #fff;
    border: 1px solid #c9b8f0;
    color: #6b4bc9;
}

.btn-importar:hover {
    background: #faf7ff !important;
    border-color: #6b4bc9 !important;
    color: #563a9e !important;
}

.btn-nuevo {
    background: #0d84c9 !important;
    border-color: #0d84c9 !important;
    color: #fff !important;
}

.btn-nuevo:hover {
    background: #0f6fb0 !important;
    border-color: #0f6fb0 !important;
}

/* ==========================================================
   Switch Activos / Dados de baja
   ========================================================== */
.modo-switch {
    position: relative;
    display: inline-flex;
    padding: 3px;
    background: #eef2f7;
    border: 1px solid #dbe3ec;
    border-radius: 999px;
    box-shadow: inset 0 1px 2px rgba(15, 45, 80, 0.08);
    user-select: none;
    transition: border-color 0.3s ease, background 0.3s ease;
    flex: none;
}

.modo-switch--bajas {
    background: #f3eefe;
    border-color: #ddd2f3;
}

.modo-switch__slider {
    position: absolute;
    top: 3px;
    bottom: 3px;
    left: 3px;
    width: calc((100% - 6px) / 2);
    border-radius: 999px;
    background: linear-gradient(180deg, #ffffff, #f6f9fc);
    box-shadow: 0 2px 6px rgba(15, 45, 80, 0.12), 0 1px 2px rgba(15, 45, 80, 0.08);
    transform: translateX(0);
    transition: transform 0.36s cubic-bezier(0.34, 1.4, 0.4, 1),
                background 0.3s ease,
                box-shadow 0.3s ease;
    z-index: 0;
}

.modo-switch--bajas .modo-switch__slider {
    transform: translateX(100%);
    background: linear-gradient(180deg, #ffffff, #f8f3ff);
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.25), 0 1px 2px rgba(124, 58, 237, 0.15);
}

.modo-switch__opt {
    position: relative;
    z-index: 1;
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 16px;
    min-width: 130px;
    border: 0;
    background: transparent;
    border-radius: 999px;
    font-size: 12.5px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: color 0.28s ease, transform 0.2s ease;
}

.modo-switch__opt .anticon {
    font-size: 13px;
    transition: transform 0.28s ease;
}

.modo-switch__opt:hover {
    color: #0f2d50;
}

.modo-switch__opt:hover .anticon {
    transform: scale(1.15);
}

.modo-switch__opt--active {
    color: #0f2d50;
}

.modo-switch__opt--active .anticon {
    transform: scale(1.05);
}

.modo-switch--bajas .modo-switch__opt--active {
    color: #6b21a8;
}

/* ==========================================================
   Card del selector (sucursal + switch en la misma fila)
   ========================================================== */
.card {
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 14px;
    box-shadow: var(--sigam-sombra-sm);
    margin-bottom: 14px;
}

.card--selector {
    padding: 12px 14px;
}

.card--tabla {
    padding: 0;
    overflow: hidden;
}

.card--tabla :deep(.ant-card-body) {
    padding: 0;
}

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
    font-weight: 800;
    color: #0d6fae;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    flex: none;
}

.ps-selector__label .anticon {
    color: #0d84c9;
    font-size: 14px;
}

.ps-selector__select {
    min-width: 260px;
    flex: 1 1 320px;
    max-width: 520px;
}

.ps-selector .modo-switch {
    margin-left: auto;
}

/* ==========================================================
   KPIs
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 10px;
}

.kpi {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 12px;
    text-align: left;
    cursor: default;
    position: relative;
    overflow: hidden;
    min-width: 0;
    box-shadow: var(--sigam-sombra-sm);
    transition: box-shadow 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
    border-radius: 12px 0 0 12px;
}

.kpi--link {
    cursor: pointer;
}

.kpi--link:hover {
    box-shadow: var(--sigam-sombra-md);
    transform: translateY(-2px);
    border-color: var(--acc);
}

.kpi--activo {
    border-color: var(--acc);
    box-shadow: 0 0 0 2px var(--acc), var(--sigam-sombra-md);
}

.kpi__ic {
    width: 34px;
    height: 34px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: var(--acc);
    position: relative;
    z-index: 1;
}

.kpi__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    width: 100%;
    position: relative;
    z-index: 1;
}

.kpi__v {
    font-size: 15px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.15;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    letter-spacing: -0.2px;
}

.kpi__l {
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--sigam-tenue);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 1px;
}

/* ==========================================================
   Enlaces y celdas
   ========================================================== */
.link-equipo {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13px;
}

.link-equipo:hover {
    color: #0d84c9;
    text-decoration: underline;
}

.link-equipo--baja {
    color: #9ca3af;
    text-decoration: line-through;
    cursor: not-allowed;
}

.baja-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.baja-cell__motivo {
    font-size: 12.5px;
    font-weight: 600;
    color: #7c3aed;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.baja-cell__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    font-size: 11px;
    color: var(--sigam-tenue);
}

.baja-cell__user,
.baja-cell__fecha {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.baja-cell__user .anticon {
    color: #6b4bc9;
}

.baja-cell__fecha .anticon {
    color: #94a3b8;
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

/* ==========================================================
   👇 PAGINACIÓN — estilo iOS moderno (igual que Proveedores)
   ========================================================== */

/* Contenedor */
.card--tabla :deep(.ant-pagination) {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    padding: 14px 18px;
    margin: 0 !important;
    background: #fff;
    border-top: 1px solid var(--sigam-borde-suave);
    flex-wrap: wrap;
}

/* Ocultar total y quick jumper */
.card--tabla :deep(.ant-pagination-total-text),
.card--tabla :deep(.ant-pagination-options-quick-jumper) {
    display: none !important;
}

/* Si solo hay 1 página, ocultar toda la paginación */
.card--tabla--sin-paginacion :deep(.ant-pagination) {
    display: none !important;
}

/* Botones (números, prev, next, jump) */
.card--tabla :deep(.ant-pagination-item),
.card--tabla :deep(.ant-pagination-prev),
.card--tabla :deep(.ant-pagination-next),
.card--tabla :deep(.ant-pagination-jump-prev),
.card--tabla :deep(.ant-pagination-jump-next) {
    min-width: 34px;
    height: 34px;
    line-height: 32px;
    border-radius: 10px !important;
    border: 1px solid transparent !important;
    background: #f3f6fa;
    margin: 0 !important;
    transition: all 0.18s cubic-bezier(0.34, 1.3, 0.4, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Links dentro de los botones */
.card--tabla :deep(.ant-pagination-item a),
.card--tabla :deep(.ant-pagination-prev .ant-pagination-item-link),
.card--tabla :deep(.ant-pagination-next .ant-pagination-item-link) {
    color: var(--sigam-navy) !important;
    font-weight: 700;
    font-size: 13px;
    transition: color 0.15s ease;
}

/* Hover general */
.card--tabla :deep(.ant-pagination-item:hover),
.card--tabla :deep(.ant-pagination-prev:hover),
.card--tabla :deep(.ant-pagination-next:hover),
.card--tabla :deep(.ant-pagination-jump-prev:hover),
.card--tabla :deep(.ant-pagination-jump-next:hover) {
    background: #e6f2fb !important;
    border-color: #cfe3f2 !important;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px -4px rgba(13, 132, 201, 0.35);
}

.card--tabla :deep(.ant-pagination-item:hover a),
.card--tabla :deep(.ant-pagination-prev:hover .ant-pagination-item-link),
.card--tabla :deep(.ant-pagination-next:hover .ant-pagination-item-link) {
    color: #0f6fb0 !important;
}

/* Página activa */
.card--tabla :deep(.ant-pagination-item-active) {
    background: linear-gradient(180deg, #0d84c9 0%, #0a6fae 100%) !important;
    border-color: #0d84c9 !important;
    box-shadow: 0 4px 10px -4px rgba(13, 132, 201, 0.55);
}

.card--tabla :deep(.ant-pagination-item-active a) {
    color: #fff !important;
    font-weight: 800 !important;
}

.card--tabla :deep(.ant-pagination-item-active:hover) {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px -4px rgba(13, 132, 201, 0.65);
}

/* Botones deshabilitados */
.card--tabla :deep(.ant-pagination-disabled),
.card--tabla :deep(.ant-pagination-disabled:hover) {
    background: #f3f6fa !important;
    border-color: transparent !important;
    transform: none !important;
    box-shadow: none !important;
    opacity: 0.45;
    cursor: not-allowed;
}

.card--tabla :deep(.ant-pagination-disabled .ant-pagination-item-link) {
    color: var(--sigam-tenue) !important;
}

/* Iconos prev/next/jump */
.card--tabla :deep(.ant-pagination-prev .anticon),
.card--tabla :deep(.ant-pagination-next .anticon),
.card--tabla :deep(.ant-pagination-jump-prev .anticon),
.card--tabla :deep(.ant-pagination-jump-next .anticon) {
    font-size: 12px;
    color: inherit;
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 1400px) {
    .kpis {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .kpis {
        grid-template-columns: 1fr;
    }

    .ps-selector .modo-switch {
        margin-left: 0;
        width: 100%;
    }

    .modo-switch__opt {
        min-width: 0;
    }

    .card--tabla :deep(.ant-pagination) {
        justify-content: center;
        padding: 12px;
        gap: 4px;
    }
}
</style>