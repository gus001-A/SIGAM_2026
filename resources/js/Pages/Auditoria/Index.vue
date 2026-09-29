<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    DatabaseOutlined,
    DeleteOutlined,
    DownloadOutlined,
    EditOutlined,
    ExportOutlined,
    FilterOutlined,
    HistoryOutlined,
    LoginOutlined,
    LogoutOutlined,
    PlusOutlined,
    StopOutlined,
    SwapOutlined,
    UndoOutlined,
    UserOutlined,
    WarningOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    registros: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('auditoria.index', {
    filtros: {
        usuario_id: props.filtros.usuario_id ?? undefined,
        accion: props.filtros.accion ?? undefined,
        modulo: props.filtros.modulo ?? undefined,
        ip: props.filtros.ip ?? '',
        registro: props.filtros.registro ?? '',
        desde: props.filtros.desde ?? '',
        hasta: props.filtros.hasta ?? '',
    },
    orden: { campo: props.orden.campo ?? 'created_at', dir: props.orden.dir ?? 'desc' },
});

const opcionesFiltro = {
    usuario_id: computed(() => (props.catalogos.usuarios ?? []).map((u) => ({ label: u.nombre, value: u.id }))),
    accion: computed(() => (props.catalogos.acciones ?? []).map((a) => ({ label: a, value: a }))),
    modulo: computed(() => (props.catalogos.modulos ?? []).map((m) => ({ label: m, value: m }))),
};

const columns = [
    { title: 'Fecha y hora', key: 'created_at', dataIndex: 'created_at', sorter: true, width: 180 },
    { title: 'Usuario', key: 'usuario', filtro: 'select', filtroClave: 'usuario_id', width: 200 },
    { title: 'Acción', key: 'accion', filtro: 'select', filtroClave: 'accion', sorter: true, width: 140 },
    { title: 'Módulo', key: 'modulo', filtro: 'select', filtroClave: 'modulo', sorter: true, width: 150 },
    { title: 'Entidad', key: 'entidad', width: 150 },
    { title: 'Sucursal', key: 'sucursal', width: 140 },
    { title: 'IP', key: 'ip', filtro: 'texto', filtroClave: 'ip', width: 130 },
];

const expandable = { expandRowByClick: true, columnWidth: 42 };

const COLOR_ACCION = {
    crear: 'green', actualizar: 'blue', desactivar: 'orange', eliminar: 'red',
    reactivar: 'cyan', acceso: 'default', salida: 'default', cambio_estado: 'purple',
    asignar: 'geekblue', reprogramar: 'gold', exportar: 'default', descargar: 'default',
};
const colorAccion = (a) => COLOR_ACCION[a] ?? 'default';

const ICONO_ACCION = {
    crear: PlusOutlined, actualizar: EditOutlined, desactivar: StopOutlined, eliminar: DeleteOutlined,
    reactivar: UndoOutlined, acceso: LoginOutlined, salida: LogoutOutlined, cambio_estado: SwapOutlined,
    asignar: UserOutlined, reprogramar: HistoryOutlined, exportar: ExportOutlined, descargar: DownloadOutlined,
};
const iconoAccion = (a) => ICONO_ACCION[a] ?? SwapOutlined;

const HEX_ACCION = {
    crear: '#16a34a', actualizar: '#0d84c9', desactivar: '#e08a1e', eliminar: '#d64545',
    reactivar: '#0ea5e9', acceso: '#64748b', salida: '#64748b', cambio_estado: '#6b4bc9',
    asignar: '#0d84c9', reprogramar: '#a86717', exportar: '#64748b', descargar: '#64748b',
};
const hexAccion = (a) => HEX_ACCION[a] ?? '#64748b';

// Paleta estable por nombre de módulo (no hay un catálogo fijo de módulos).
const PALETA_MODULO = ['#0d84c9', '#1f9e86', '#6b4bc9', '#e08a1e', '#d64545', '#0ea5e9', '#a86717', '#7c3aed'];
const colorModulo = (m) => {
    if (!m) return '#64748b';
    let hash = 0;
    for (let i = 0; i < m.length; i++) hash = (hash * 31 + m.charCodeAt(i)) >>> 0;
    return PALETA_MODULO[hash % PALETA_MODULO.length];
};

const tarjetas = computed(() => [
    { label: 'Eventos totales', valor: props.kpis.total ?? 0, icono: DatabaseOutlined, color: '#0d84c9' },
    { label: 'Eventos hoy', valor: props.kpis.hoy ?? 0, icono: HistoryOutlined, color: '#1f9e86' },
    { label: 'Usuarios activos hoy', valor: props.kpis.usuarios_hoy ?? 0, icono: UserOutlined, color: '#6b4bc9' },
    { label: 'Acciones críticas hoy', valor: props.kpis.criticas_hoy ?? 0, icono: WarningOutlined, color: '#d64545' },
]);

const fechaHora = (v) =>
    v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'medium' }) : 'No especificado';

const dato = (v) => (v === null || v === undefined || v === '' ? 'No especificado' : v);

// --- Diff antes/después ------------------------------------------------
const diff = (record) => {
    const ant = record.valores_anteriores ?? {};
    const nue = record.valores_nuevos ?? {};
    const claves = [...new Set([...Object.keys(ant), ...Object.keys(nue)])].sort();
    return claves.map((k) => ({
        campo: k,
        antes: ant[k] ?? null,
        despues: nue[k] ?? null,
        cambio: JSON.stringify(ant[k]) !== JSON.stringify(nue[k]),
    }));
};
const fmt = (v) => {
    if (v === null || v === undefined) return '—';
    if (typeof v === 'boolean') return v ? 'Sí' : 'No';
    if (typeof v === 'object') return JSON.stringify(v);
    return String(v);
};

const exportarCsv = () => {
    const params = new URLSearchParams();
    for (const [k, v] of Object.entries(filtros)) {
        if (v !== '' && v !== null && v !== undefined) params.set(k, v);
    }
    window.location.href = `${route('auditoria.exportar')}?${params.toString()}`;
};
</script>

<template>
    <Head title="Auditoría" />

    <AppLayout
        titulo="Bitácora de auditoría"
        descripcion="Registro de todas las acciones del sistema: quién, qué, cuándo y desde dónde. Haz clic en una fila para ver el detalle."
    >
        <template #acciones>
            <a-range-picker
                :value="[filtros.desde || null, filtros.hasta || null]"
                value-format="YYYY-MM-DD"
                :allow-empty="[true, true]"
                @change="(_, s) => { filtros.desde = s[0] || ''; filtros.hasta = s[1] || ''; aplicar(); }"
            />
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar
            </a-button>
            <a-button v-if="puede('auditoria.exportar')" type="primary" @click="exportarCsv">
                <template #icon><DownloadOutlined /></template>
                Exportar CSV
            </a-button>
        </template>

        <!-- KPIs -->
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

        <div class="tabla-auditoria">
            <DataTableInertia
                :paginador="registros"
                :columns="columns"
                :orden="orden"
                :cargando="cargando"
                :hay-filtros="hayFiltros()"
                :expandable="expandable"
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
                    <template v-if="column.key === 'created_at'">
                        <span class="aud-fh">{{ fechaHora(record.created_at) }}</span>
                    </template>
                    <template v-else-if="column.key === 'usuario'">
                        <span class="aud-usuario">
                            <span class="aud-usuario__av">{{ (record.usuario || 'S')[0] }}</span>
                            {{ record.usuario || 'Sistema' }}
                        </span>
                    </template>
                    <template v-else-if="column.key === 'accion'">
                        <a-tag :color="colorAccion(record.accion)" class="aud-tag-ic">
                            <component :is="iconoAccion(record.accion)" />
                            {{ record.accion }}
                        </a-tag>
                    </template>
                    <template v-else-if="column.key === 'modulo'">
                        <span class="aud-modulo" :style="{ '--c': colorModulo(record.modulo) }">{{ record.modulo }}</span>
                    </template>
                    <template v-else-if="column.key === 'entidad'">{{ record.entidad || 'No especificado' }}</template>
                    <template v-else-if="column.key === 'sucursal'">{{ record.sucursal || 'No especificado' }}</template>
                    <template v-else-if="column.key === 'ip'">
                        <span class="aud-mono">{{ record.ip || '—' }}</span>
                    </template>
                </template>

                <template #expandedRowRender="{ record }">
                    <div class="aud-det" :style="{ '--acc': hexAccion(record.accion) }">
                        <div class="aud-det__meta">
                            <div><span>Entidad</span>{{ dato(record.entidad) }}</div>
                            <div><span>IP</span><code>{{ dato(record.ip) }}</code></div>
                            <div class="aud-det__nav"><span>Navegador</span>{{ dato(record.navegador) }}</div>
                        </div>

                        <template v-if="diff(record).length">
                            <div class="aud-det__tit">
                                <EditOutlined /> Cambios
                            </div>
                            <table class="aud-diff">
                                <thead>
                                    <tr><th>Campo</th><th>Antes</th><th>Después</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="d in diff(record)" :key="d.campo" :class="{ 'is-cambio': d.cambio }">
                                        <td class="aud-diff__c">{{ d.campo }}</td>
                                        <td class="aud-diff__a">{{ fmt(d.antes) }}</td>
                                        <td class="aud-diff__d">{{ fmt(d.despues) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>

                        <template v-if="record.metadatos && Object.keys(record.metadatos).length">
                            <div class="aud-det__tit">
                                <DatabaseOutlined /> Metadatos
                            </div>
                            <pre class="aud-json">{{ JSON.stringify(record.metadatos, null, 2) }}</pre>
                        </template>

                        <div v-if="!diff(record).length && !(record.metadatos && Object.keys(record.metadatos).length)" class="aud-det__sin">
                            Sin cambios de datos registrados en esta acción.
                        </div>
                    </div>
                </template>
            </DataTableInertia>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ---------- KPIs ---------- */
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

/* ---------- Tabla ---------- */
.tabla-auditoria {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--sigam-borde-suave);
    box-shadow: var(--sigam-sombra-sm);
    background: #fff;
}
.tabla-auditoria :deep(.ant-table-thead > tr > th) {
    background: var(--sigam-navy-050) !important;
    color: var(--sigam-navy) !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-size: 11px;
    border-bottom: 1px solid var(--sigam-borde-suave) !important;
}
.tabla-auditoria :deep(.ant-table-thead > tr > th::before) {
    background-color: var(--sigam-borde-suave) !important;
}
.tabla-auditoria :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid var(--sigam-borde-suave) !important;
}
.tabla-auditoria :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}
.tabla-auditoria :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}
.tabla-auditoria :deep(.ant-pagination .ant-pagination-item-active) { border-color: #0d84c9; }
.tabla-auditoria :deep(.ant-pagination .ant-pagination-item-active a) { color: #0d84c9; font-weight: 800; }

/* ---------- Celdas ---------- */
.aud-fh {
    font-size: 12.5px;
    color: var(--sigam-texto);
}
.aud-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 12px;
    color: var(--sigam-tenue);
}
.aud-usuario {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: var(--sigam-texto);
}
.aud-usuario__av {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #fff;
    background: var(--sigam-grad);
}
.aud-tag-ic {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-weight: 700;
    text-transform: capitalize;
}
.aud-modulo {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--c);
}
.aud-modulo::before {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--c);
    flex-shrink: 0;
}

/* ---------- Detalle expandido ---------- */
.aud-det {
    padding: 10px 12px 12px;
    border-left: 3px solid var(--acc, var(--sigam-teal));
    background: color-mix(in srgb, var(--acc, var(--sigam-teal)) 4%, #fff);
    border-radius: 0 10px 10px 0;
}
.aud-det__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 26px;
    margin-bottom: 12px;
}
.aud-det__meta > div {
    display: flex;
    flex-direction: column;
    font-size: 13px;
    color: var(--sigam-texto);
}
.aud-det__meta span {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 700;
    color: var(--sigam-tenue);
}
.aud-det__nav {
    max-width: 100%;
    word-break: break-word;
}
.aud-det__tit {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 800;
    font-size: 12px;
    color: var(--sigam-navy);
    margin: 10px 0 6px;
}
.aud-diff {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 10px;
    overflow: hidden;
}
.aud-diff th {
    background: var(--sigam-navy-050);
    color: #42566b;
    text-align: left;
    padding: 6px 10px;
    font-size: 11px;
    text-transform: uppercase;
}
.aud-diff td {
    padding: 6px 10px;
    border-top: 1px solid var(--sigam-borde-suave);
    vertical-align: top;
}
.aud-diff tr.is-cambio .aud-diff__a {
    color: #c23b3b;
    text-decoration: line-through;
    background: #fdf0f0;
}
.aud-diff tr.is-cambio .aud-diff__d {
    color: var(--sigam-teal-700);
    background: #eafaf4;
    font-weight: 600;
}
.aud-diff__c {
    font-weight: 600;
    color: var(--sigam-navy);
}
.aud-json {
    background: #0f2c4a;
    color: #d7e6f5;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 11.5px;
    overflow-x: auto;
    margin: 0;
}
.aud-det__sin {
    font-size: 12.5px;
    color: var(--sigam-tenue);
    font-style: italic;
}
</style>
