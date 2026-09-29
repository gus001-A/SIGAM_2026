<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CheckCircleOutlined,
    CheckOutlined,
    ClearOutlined,
    EditOutlined,
    EnvironmentOutlined,
    FilterOutlined,
    FlagOutlined,
    InboxOutlined,
    PlusOutlined,
    SafetyCertificateOutlined,
    SaveOutlined,
    StopOutlined,
    SyncOutlined,
    TagsOutlined,
    ToolOutlined,
    UnorderedListOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';

/**
 * Tabla + modal genéricos para los catálogos configurables.
 * `rutaBase` es el prefijo de ruta Ziggy, p. ej. "catalogos.marcas".
 */
const props = defineProps({
    titulo: { type: String, required: true },
    descripcion: { type: String, default: 'Catálogo configurable usado en los formularios del sistema.' },
    registros: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    rutaBase: { type: String, required: true },
    columnas: { type: Array, required: true },
    campos: { type: Array, required: true },
    singular: { type: String, default: 'registro' },
});

const { puede } = usePermisos();

/* ==========================================================
   Navegación entre catálogos
   ========================================================== */
const CATALOGOS = [
    { ruta: 'catalogos.marcas', label: 'Marcas', icono: TagsOutlined, color: '#0d84c9' },
    { ruta: 'catalogos.tipos_equipo', label: 'Tipos de equipo', icono: ToolOutlined, color: '#1f9e86' },
    { ruta: 'catalogos.estados_equipo', label: 'Estados de equipo', icono: CheckCircleOutlined, color: '#6b4bc9' },
    { ruta: 'catalogos.tipos_ubicacion', label: 'Tipos de ubicación', icono: EnvironmentOutlined, color: '#e08a1e' },
    { ruta: 'catalogos.tipos_mantenimiento', label: 'Tipos de mantenimiento', icono: SafetyCertificateOutlined, color: '#d64545' },
    { ruta: 'catalogos.estados_mantenimiento', label: 'Estados de mantenimiento', icono: SyncOutlined, color: '#0ea5e9' },
    { ruta: 'catalogos.prioridades', label: 'Prioridades', icono: FlagOutlined, color: '#7c3aed' },
    { ruta: 'catalogos.materiales', label: 'Materiales', icono: InboxOutlined, color: '#16a34a' },
    { ruta: 'catalogos.tipos_area', label: 'Tipos de área', icono: ApartmentOutlined, color: '#a86717' },
    { ruta: 'catalogos.tipos_limpieza', label: 'Tipos de limpieza', icono: ClearOutlined, color: '#0d6fae' },
];
const irCatalogo = (ruta) => {
    if (ruta !== props.rutaBase) router.visit(route(`${ruta}.index`));
};

const clavesFiltro = props.columnas.filter((c) => c.filtro).map((c) => c.filtroClave ?? c.key);
const claveEnlace = (props.columnas.find((c) => c.enlace) ?? props.columnas.find((c) => c.filtro === 'texto') ?? props.columnas[0]).key;

const estadoInicial = {};
clavesFiltro.forEach((k) => (estadoInicial[k] = props.filtros[k] ?? ''));
estadoInicial.estado = props.filtros.estado ?? undefined;
estadoInicial.registrado_por = props.filtros.registrado_por ?? '';

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia(
    `${props.rutaBase}.index`,
    { filtros: estadoInicial, orden: { campo: props.orden.campo ?? props.columnas[0].key, dir: props.orden.dir ?? 'asc' } },
);

const opcionesEstado = [
    { label: 'Activo', value: 'activo' },
    { label: 'Inactivo', value: 'inactivo' },
];

const columns = computed(() => [
    ...props.columnas.map((c) => ({ align: c.align, width: c.width ?? 160, sorter: c.sorter, ...c })),
    { title: 'Estado', key: '__estado', filtro: 'select', filtroClave: 'estado', width: 120 },
    { title: 'Registrado por', key: '__registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 160 },
    { title: '', key: '__acciones', align: 'right', width: 110, fixed: 'right' },
]);

/* ==========================================================
   Modal
   ========================================================== */
const modal = reactive({ abierto: false, editando: false, id: null });

const valoresIniciales = () => {
    const v = {};
    props.campos.forEach((c) => (v[c.name] = c.tipo === 'switch' ? false : c.tipo === 'number' ? null : ''));
    // El estado ya no se edita, siempre se conserva o se crea como activo
    v.estado = 'activo';
    return v;
};

const form = useForm(valoresIniciales());

const abrirNuevo = () => {
    Object.assign(form, valoresIniciales());
    form.clearErrors();
    modal.editando = false;
    modal.id = null;
    modal.abierto = true;
};

const abrirEdicion = (registro) => {
    const v = valoresIniciales();
    props.campos.forEach((c) => {
        v[c.name] = registro[c.name] ?? v[c.name];
    });
    v.estado = registro.estado ?? 'activo';
    Object.assign(form, v);
    form.clearErrors();
    modal.editando = true;
    modal.id = registro.id;
    modal.abierto = true;
};

const cerrar = () => {
    modal.abierto = false;
};

const guardar = () => {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => (modal.abierto = false),
        onFinish: () => {
            if (!form.hasErrors) modal.abierto = false;
        },
    };
    if (modal.editando) form.put(route(`${props.rutaBase}.update`, modal.id), opciones);
    else form.post(route(`${props.rutaBase}.store`), opciones);
};

const confirmar = ref(null);

const desactivar = async (registro) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar «${registro.nombre}»`,
        mensaje: `Dejará de aparecer al asignarlo, pero se conserva en los registros existentes.`,
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route(`${props.rutaBase}.destroy`, registro.id), { preserveScroll: true });
};

const activar = (registro) =>
    router.put(route(`${props.rutaBase}.activar`, registro.id), {}, { preserveScroll: true });

const est = (campo) => (form.errors[campo] ? 'error' : undefined);
const moneda = (v) =>
    v == null ? '—' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v);

const tarjetas = computed(() => [
    { label: 'Total', valor: props.kpis.total ?? 0, icono: UnorderedListOutlined, color: '#0d84c9' },
    { label: 'Activos', valor: props.kpis.activos ?? 0, icono: CheckCircleOutlined, color: '#1f9e86' },
    { label: 'Inactivos', valor: props.kpis.inactivos ?? 0, icono: StopOutlined, color: '#d64545' },
]);
</script>

<template>
    <AppLayout :titulo="titulo" :descripcion="descripcion">
        <template #acciones>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon>
                    <FilterOutlined />
                </template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('catalogos.crear')" type="primary" @click="abrirNuevo">
                <template #icon>
                    <PlusOutlined />
                </template>
                Nuevo
            </a-button>
        </template>

        <nav class="cat-nav">
            <button
                v-for="c in CATALOGOS"
                :key="c.ruta"
                type="button"
                class="cat-nav__item"
                :class="{ 'cat-nav__item--activo': c.ruta === rutaBase }"
                :style="{ '--c': c.color }"
                @click="irCatalogo(c.ruta)"
            >
                <span class="cat-nav__ic">
                    <component :is="c.icono" />
                </span>
                <span class="cat-nav__label">{{ c.label }}</span>
            </button>
        </nav>

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

        <!-- Tabla -->
        <div class="tabla-catalogo">
            <DataTableInertia
                :paginador="registros"
                :columns="columns"
                :orden="orden"
                :cargando="cargando"
                @cambio="onCambioTabla"
                @limpiar="limpiar"
            >
                <template #filtro="{ column }">
                    <a-input
                        v-if="column.filtro === 'texto'"
                        v-model:value="filtros[column.filtroClave ?? column.key]"
                        size="small"
                        allow-clear
                        placeholder="Filtrar"
                        @update:value="filtrar()"
                    />
                    <a-select
                        v-else-if="column.filtro === 'select'"
                        v-model:value="filtros[column.filtroClave ?? column.key]"
                        :options="column.opciones ?? opcionesEstado"
                        size="small"
                        allow-clear
                        placeholder="Todos"
                        style="width: 100%"
                        @change="aplicar()"
                    />
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === '__estado'">
                        <a-tag :color="record.estado === 'activo' ? 'green' : 'default'">
                            {{ record.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                        </a-tag>
                    </template>

                    <template v-else-if="column.key === '__registrado'">
                        <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                    </template>

                    <template v-else-if="column.key === '__acciones'">
                        <a-space :size="2">
                            <a-tooltip v-if="puede('catalogos.editar')" title="Editar">
                                <a-button type="text" size="small" class="accion accion--editar" @click="abrirEdicion(record)">
                                    <template #icon>
                                        <EditOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-if="record.estado === 'activo' && puede('catalogos.desactivar')" title="Desactivar">
                                <a-button type="text" size="small" danger class="accion accion--peligro" @click="desactivar(record)">
                                    <template #icon>
                                        <StopOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                            <a-tooltip v-else-if="record.estado === 'inactivo' && puede('catalogos.editar')" title="Reactivar">
                                <a-button type="text" size="small" class="accion accion--reactivar" @click="activar(record)">
                                    <template #icon>
                                        <CheckOutlined />
                                    </template>
                                </a-button>
                            </a-tooltip>
                        </a-space>
                    </template>

                    <template v-else-if="column.tipo === 'color'">
                        <span v-if="record[column.key]" class="clr">
                            <i :style="{ background: record[column.key] }" />{{ record[column.key] }}
                        </span>
                        <span v-else class="opacity-50">—</span>
                    </template>

                    <template v-else-if="column.tipo === 'tag'">
                        <a-tag v-if="record[column.key]">{{ record[column.key] }}</a-tag>
                        <span v-else class="opacity-50">—</span>
                    </template>

                    <template v-else-if="column.tipo === 'bool'">
                        <a-tag :color="record[column.key] ? 'blue' : 'default'">{{ record[column.key] ? 'Sí' : 'No' }}</a-tag>
                    </template>

                    <template v-else-if="column.tipo === 'count'">
                        <a-tag>{{ record[column.key] ?? 0 }}</a-tag>
                    </template>

                    <template v-else-if="column.tipo === 'money'">
                        <span class="whitespace-nowrap">{{ moneda(record[column.key]) }}</span>
                    </template>

                    <template v-else-if="column.key === claveEnlace">
                        <a class="cat-nombre" @click="abrirEdicion(record)">
                            <span v-if="record.color" class="cat-nombre__dot" :style="{ background: record.color }" />
                            <span class="cat-nombre__t">{{ record[column.key] || '—' }}</span>
                        </a>
                        <div v-if="record.clave" class="cat-nombre__clave">{{ record.clave }}</div>
                    </template>

                    <template v-else>{{ record[column.key] ?? '—' }}</template>
                </template>
            </DataTableInertia>
        </div>

        <!-- ==========================================================
             Modal: rediseñado (mismo look que los otros modales)
             ========================================================== -->
        <a-modal
            v-model:open="modal.abierto"
            :footer="null"
            :closable="false"
            :width="620"
            centered
            class="modal-catalogo"
            :mask-closable="!form.processing"
        >
            <div class="modal-catalogo__wrap">
                <!-- Header -->
                <header class="modal-catalogo__head">
                    <div class="modal-catalogo__ico">
                        <component :is="modal.editando ? EditOutlined : PlusOutlined" />
                    </div>
                    <div class="modal-catalogo__meta">
                        <span class="modal-catalogo__tipo">
                            {{ modal.editando ? 'Editar registro' : 'Nuevo registro' }}
                        </span>
                        <h3 class="modal-catalogo__titulo">
                            {{ (modal.editando ? 'Editar ' : 'Agregar ') + singular }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="modal-catalogo__close"
                        title="Cerrar"
                        :disabled="form.processing"
                        @click="cerrar"
                    >
                        ✕
                    </button>
                </header>

                <!-- Body -->
                <div class="modal-catalogo__body">
                    <a-form :model="form" layout="vertical" @submit.prevent="guardar">
                        <a-row :gutter="12">
                            <a-col v-for="campo in campos" :key="campo.name" :span="campo.ancho ?? 24">
                                <a-form-item
                                    :label="campo.label"
                                    :extra="campo.ayuda"
                                    :validate-status="est(campo.name)"
                                    :help="form.errors[campo.name]"
                                >
                                    <a-textarea
                                        v-if="campo.tipo === 'textarea'"
                                        v-model:value="form[campo.name]"
                                        :auto-size="{ minRows: 2, maxRows: 5 }"
                                    />
                                    <a-input-number
                                        v-else-if="campo.tipo === 'number'"
                                        v-model:value="form[campo.name]"
                                        :min="campo.min ?? 0"
                                        :max="campo.max"
                                        style="width: 100%"
                                        size="large"
                                    />
                                    <a-select
                                        v-else-if="campo.tipo === 'select'"
                                        v-model:value="form[campo.name]"
                                        :options="campo.opciones"
                                        size="large"
                                    />
                                    <div v-else-if="campo.tipo === 'color'" class="color-field">
                                        <input
                                            type="color"
                                            v-model="form[campo.name]"
                                            class="color-field__picker"
                                        />
                                        <a-input
                                            v-model:value="form[campo.name]"
                                            placeholder="#1e5eb8"
                                            class="color-field__text"
                                        />
                                    </div>
                                    <a-switch
                                        v-else-if="campo.tipo === 'switch'"
                                        v-model:checked="form[campo.name]"
                                    />
                                    <a-input
                                        v-else
                                        v-model:value="form[campo.name]"
                                        size="large"
                                        :placeholder="campo.placeholder"
                                    />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-form>
                </div>

                <!-- Footer -->
                <footer class="modal-catalogo__footer">
                    <a-button size="large" :disabled="form.processing" @click="cerrar">
                        Cancelar
                    </a-button>
                    <a-button
                        type="primary"
                        size="large"
                        class="modal-catalogo__submit"
                        :loading="form.processing"
                        :disabled="form.processing"
                        @click="guardar"
                    >
                        <template #icon><SaveOutlined /></template>
                        {{ modal.editando ? 'Guardar cambios' : 'Crear registro' }}
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   KPIs
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
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

@media (max-width: 640px) {
    .kpis {
        grid-template-columns: 1fr;
    }
}

/* ==========================================================
   Tabla
   ========================================================== */
.tabla-catalogo {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--sigam-borde-suave);
    box-shadow: var(--sigam-sombra-sm);
    background: #fff;
}

.tabla-catalogo :deep(.ant-table-thead > tr > th) {
    background: var(--sigam-navy-050) !important;
    color: var(--sigam-navy) !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-size: 11px;
    border-bottom: 1px solid var(--sigam-borde-suave) !important;
}

.tabla-catalogo :deep(.ant-table-thead > tr > th::before) {
    background-color: var(--sigam-borde-suave) !important;
}

.tabla-catalogo :deep(.ant-table-tbody > tr > td) {
    border-bottom: 1px solid var(--sigam-borde-suave) !important;
}

.tabla-catalogo :deep(.ant-table-tbody > tr:nth-child(even) > td) {
    background: #fafbfd;
}

.tabla-catalogo :deep(.ant-table-tbody > tr:hover > td) {
    background: #eef4fb !important;
    transition: background 0.14s ease;
}

.tabla-catalogo :deep(.ant-table-cell-fix-right) {
    background: inherit;
    border-left: 1px solid var(--sigam-borde-suave);
}

.tabla-catalogo :deep(.ant-table-tbody > tr:hover > td.ant-table-cell-fix-right) {
    background: #eef4fb !important;
}

.tabla-catalogo :deep(.ant-pagination-total-text),
.tabla-catalogo :deep(.ant-pagination-options-quick-jumper) {
    display: none !important;
}

.tabla-catalogo :deep(.ant-pagination .ant-pagination-item-active) {
    border-color: #0d84c9;
}

.tabla-catalogo :deep(.ant-pagination .ant-pagination-item-active a) {
    color: #0d84c9;
    font-weight: 800;
}

/* Acciones */
.accion {
    border-radius: 8px;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
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
   Navegación entre catálogos
   ========================================================== */
.cat-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-bottom: 14px;
}

.cat-nav__item {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    border: 1px solid var(--sigam-borde-suave);
    background: #fff;
    color: #52606d;
    font-size: 12.5px;
    font-weight: 700;
    padding: 6px 16px 6px 6px;
    border-radius: 999px;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.05);
    transition: background 0.16s ease, color 0.16s ease, border-color 0.16s ease,
        box-shadow 0.16s ease, transform 0.14s ease;
}

.cat-nav__ic {
    width: 28px;
    height: 28px;
    flex: none;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 13px;
    color: #fff;
    background: var(--c);
    transition: background 0.16s ease;
}

.cat-nav__label {
    white-space: nowrap;
}

.cat-nav__item:hover {
    color: var(--sigam-navy);
    border-color: var(--c);
    transform: translateY(-1px);
    box-shadow: 0 6px 14px -8px var(--c);
}

.cat-nav__item--activo {
    background: var(--c);
    border-color: var(--c);
    color: #fff;
    box-shadow: 0 8px 16px -8px var(--c);
}

.cat-nav__item--activo .cat-nav__ic {
    background: rgba(255, 255, 255, 0.28);
}

.cat-nav__item:active {
    transform: scale(0.97);
}

.cat-nombre {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: var(--sigam-navy);
}

.cat-nombre:hover {
    color: #0d84c9;
}

.cat-nombre__dot {
    width: 11px;
    height: 11px;
    flex: none;
    border-radius: 50%;
    border: 1px solid rgba(0, 0, 0, 0.08);
}

.cat-nombre__clave {
    font-family: ui-monospace, monospace;
    font-size: 10.5px;
    color: var(--sigam-tenue);
    margin-top: 2px;
}

.clr {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-variant-numeric: tabular-nums;
}

.clr i {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    display: inline-block;
}

/* ==========================================================
   Modal rediseñado (mismo look que los otros modales)
   ========================================================== */
.modal-catalogo :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-catalogo :deep(.ant-modal-body) {
    padding: 0;
}

.modal-catalogo__wrap {
    display: flex;
    flex-direction: column;
    background: #fff;
}

/* Header */
.modal-catalogo__head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f8fb 100%);
    border-bottom: 1px solid #e2e8f0;
}

.modal-catalogo__ico {
    width: 46px;
    height: 46px;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.55);
}

.modal-catalogo__meta {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.modal-catalogo__tipo {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #0d84c9;
}

.modal-catalogo__titulo {
    font-size: 16px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.25;
    letter-spacing: -0.2px;
}

.modal-catalogo__close {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.14s ease, color 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
}

.modal-catalogo__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.modal-catalogo__close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Body */
.modal-catalogo__body {
    padding: 18px 20px 6px;
    background: #fff;
}

/* Campo color */
.color-field {
    display: flex;
    align-items: center;
    gap: 10px;
}

.color-field__picker {
    width: 48px;
    height: 40px;
    padding: 0;
    border: 1px solid #d9d9d9;
    border-radius: 10px;
    background: none;
    cursor: pointer;
    flex-shrink: 0;
}

.color-field__text {
    flex: 1;
}

/* Footer */
.modal-catalogo__footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.modal-catalogo__submit {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%) !important;
    border-color: #0d84c9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.modal-catalogo__submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(13, 132, 201, 0.75);
}

/* Responsive */
@media (max-width: 575px) {
    .modal-catalogo__head {
        padding: 14px;
    }

    .modal-catalogo__titulo {
        font-size: 15px;
    }

    .modal-catalogo__body {
        padding: 14px 14px 6px;
    }

    .modal-catalogo__footer {
        padding: 12px 14px;
        flex-direction: column-reverse;
    }

    .modal-catalogo__footer .ant-btn {
        width: 100%;
    }
}
</style>