<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { DownloadOutlined, FilterOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

const props = defineProps({
    registros: { type: Object, required: true },
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
                <template v-else-if="column.key === 'usuario'">{{ record.usuario || 'Sistema' }}</template>
                <template v-else-if="column.key === 'accion'">
                    <a-tag :color="colorAccion(record.accion)">{{ record.accion }}</a-tag>
                </template>
                <template v-else-if="column.key === 'modulo'">
                    <a-tag :bordered="false" color="blue">{{ record.modulo }}</a-tag>
                </template>
                <template v-else-if="column.key === 'entidad'">{{ record.entidad || 'No especificado' }}</template>
                <template v-else-if="column.key === 'sucursal'">{{ record.sucursal || 'No especificado' }}</template>
                <template v-else-if="column.key === 'ip'">
                    <span class="aud-mono">{{ record.ip || '—' }}</span>
                </template>
            </template>

            <template #expandedRowRender="{ record }">
                <div class="aud-det">
                    <div class="aud-det__meta">
                        <div><span>Entidad</span>{{ dato(record.entidad) }}</div>
                        <div><span>IP</span><code>{{ dato(record.ip) }}</code></div>
                        <div class="aud-det__nav"><span>Navegador</span>{{ dato(record.navegador) }}</div>
                    </div>

                    <template v-if="diff(record).length">
                        <div class="aud-det__tit">Cambios</div>
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
                        <div class="aud-det__tit">Metadatos</div>
                        <pre class="aud-json">{{ JSON.stringify(record.metadatos, null, 2) }}</pre>
                    </template>

                    <div v-if="!diff(record).length && !(record.metadatos && Object.keys(record.metadatos).length)" class="aud-det__sin">
                        Sin cambios de datos registrados en esta acción.
                    </div>
                </div>
            </template>
        </DataTableInertia>
    </AppLayout>
</template>

<style scoped>
.aud-fh {
    font-size: 12.5px;
    color: var(--sigam-texto);
}
.aud-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 12px;
    color: var(--sigam-tenue);
}
.aud-det {
    padding: 6px 8px 10px;
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
    font-weight: 700;
    font-size: 12px;
    color: var(--sigam-navy);
    margin: 10px 0 6px;
    padding-left: 8px;
    border-left: 3px solid var(--sigam-teal);
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
