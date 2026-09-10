<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    CheckOutlined,
    EditOutlined,
    FilterOutlined,
    PlusOutlined,
    StopOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
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
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    rutaBase: { type: String, required: true },
    // Columnas de datos (sin incluir estado ni acciones).
    columnas: { type: Array, required: true },
    // Campos del modal: { name, label, tipo, opciones?, required?, ayuda?, ancho? }
    campos: { type: Array, required: true },
    singular: { type: String, default: 'registro' },
});

const { puede } = usePermisos();

const CATALOGOS = [
    { ruta: 'catalogos.marcas', label: 'Marcas' },
    { ruta: 'catalogos.tipos_equipo', label: 'Tipos de equipo' },
    { ruta: 'catalogos.estados_equipo', label: 'Estados de equipo' },
    { ruta: 'catalogos.tipos_ubicacion', label: 'Tipos de ubicación' },
    { ruta: 'catalogos.tipos_mantenimiento', label: 'Tipos de mantenimiento' },
    { ruta: 'catalogos.estados_mantenimiento', label: 'Estados de mantenimiento' },
    { ruta: 'catalogos.prioridades', label: 'Prioridades' },
    { ruta: 'catalogos.materiales', label: 'Materiales' },
];
const irCatalogo = (ruta) => {
    if (ruta !== props.rutaBase) router.visit(route(`${ruta}.index`));
};

const clavesFiltro = props.columnas.filter((c) => c.filtro).map((c) => c.filtroClave ?? c.key);
const claveEnlace = (props.columnas.find((c) => c.enlace) ?? props.columnas.find((c) => c.filtro === 'texto') ?? props.columnas[0]).key;

const estadoInicial = {};
clavesFiltro.forEach((k) => (estadoInicial[k] = props.filtros[k] ?? ''));
estadoInicial.estado = props.filtros.estado ?? undefined;

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
    { title: '', key: '__acciones', align: 'right', width: 110, fixed: 'right' },
]);

// --- Modal ---------------------------------------------------------
const modal = reactive({ abierto: false, editando: false, id: null });

const valoresIniciales = () => {
    const v = {};
    props.campos.forEach((c) => (v[c.name] = c.tipo === 'switch' ? false : c.tipo === 'number' ? null : ''));
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
</script>

<template>
    <AppLayout :titulo="titulo" :descripcion="descripcion">
        <template #acciones>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('catalogos.crear')" type="primary" @click="abrirNuevo">
                <template #icon><PlusOutlined /></template>
                Nuevo
            </a-button>
        </template>

        <div class="cat-nav">
            <button
                v-for="c in CATALOGOS"
                :key="c.ruta"
                type="button"
                class="cat-nav__item"
                :class="{ 'cat-nav__item--activo': c.ruta === rutaBase }"
                @click="irCatalogo(c.ruta)"
            >
                {{ c.label }}
            </button>
        </div>

        <DataTableInertia :paginador="registros" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
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
                    :options="opcionesEstado"
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

                <template v-else-if="column.key === '__acciones'">
                    <a-space :size="2">
                        <a-tooltip v-if="puede('catalogos.editar')" title="Editar">
                            <a-button type="text" size="small" @click="abrirEdicion(record)">
                                <template #icon><EditOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('catalogos.desactivar')" title="Desactivar">
                            <a-button type="text" size="small" danger @click="desactivar(record)">
                                <template #icon><StopOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-else-if="record.estado === 'inactivo' && puede('catalogos.editar')" title="Reactivar">
                            <a-button type="text" size="small" @click="activar(record)">
                                <template #icon><CheckOutlined /></template>
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
                    <a class="font-medium" @click="abrirEdicion(record)">{{ record[column.key] || '—' }}</a>
                </template>

                <template v-else>{{ record[column.key] ?? '—' }}</template>
            </template>
        </DataTableInertia>

        <a-modal
            v-model:open="modal.abierto"
            :title="(modal.editando ? 'Editar ' : 'Agregar ') + singular"
            :confirm-loading="form.processing"
            ok-text="Guardar"
            cancel-text="Cancelar"
            @ok="guardar"
        >
            <a-form layout="vertical" class="mt-2" @submit.prevent="guardar">
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
                            />
                            <a-select
                                v-else-if="campo.tipo === 'select'"
                                v-model:value="form[campo.name]"
                                :options="campo.opciones"
                            />
                            <div v-else-if="campo.tipo === 'color'" class="flex items-center gap-2">
                                <input type="color" v-model="form[campo.name]" class="clr-input" />
                                <a-input v-model:value="form[campo.name]" placeholder="#1e5eb8" style="flex: 1" />
                            </div>
                            <a-switch v-else-if="campo.tipo === 'switch'" v-model:checked="form[campo.name]" />
                            <a-input v-else v-model:value="form[campo.name]" />
                        </a-form-item>
                    </a-col>

                    <a-col :span="24">
                        <a-form-item label="Estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activo</a-radio-button>
                                <a-radio-button value="inactivo">Inactivo</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
.cat-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    flex: none;
    margin-bottom: 14px;
    padding: 5px;
    background: var(--sigam-navy-050);
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 12px;
}
.cat-nav__item {
    border: 1px solid transparent;
    background: transparent;
    color: #52606d;
    font-size: 12.5px;
    font-weight: 600;
    padding: 6px 13px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.16s ease, color 0.16s ease, box-shadow 0.16s ease,
        transform 0.12s ease;
}
.cat-nav__item:hover {
    color: var(--sigam-navy);
    background: #fff;
}
.cat-nav__item--activo {
    background: var(--sigam-grad);
    color: #fff;
    box-shadow: 0 6px 14px -7px rgba(23, 58, 95, 0.55);
}
.cat-nav__item:active {
    transform: scale(0.97);
}
.clr { display: inline-flex; align-items: center; gap: 6px; font-variant-numeric: tabular-nums; }
.clr i { width: 14px; height: 14px; border-radius: 4px; border: 1px solid rgba(0, 0, 0, 0.1); display: inline-block; }
.clr-input { width: 42px; height: 32px; padding: 0; border: 1px solid #d9d9d9; border-radius: 6px; background: none; cursor: pointer; }
</style>
