<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    DeleteOutlined,
    EditOutlined,
    EnvironmentOutlined,
    FileAddOutlined,
    FileTextOutlined,
    PlusOutlined,
    RedoOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    sucursales: { type: Array, default: () => [] },
    sucursalSeleccionada: { type: [Number, String], default: null },
    kpis: { type: Object, default: () => ({}) },
    arbol: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
    tiposArea: { type: Array, default: () => [] },
    tiposLimpieza: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
});

const tarjetas = computed(() => [
    { label: 'Ubicaciones totales', valor: props.kpis.total ?? 0, icono: ApartmentOutlined, color: '#0d84c9' },
    { label: 'Con equipos asignados', valor: props.kpis.con_equipos ?? 0, icono: ToolOutlined, color: '#1f9e86' },
    { label: 'Solicitudes abiertas', valor: props.kpis.solicitudes_abiertas ?? 0, icono: FileAddOutlined, color: '#e08a1e' },
    { label: 'Órdenes abiertas', valor: props.kpis.ordenes_abiertas ?? 0, icono: FileTextOutlined, color: '#6b4bc9' },
]);

const { puede } = usePermisos();
const confirmar = ref(null);

const solicitarMantenimiento = (nodo) =>
    router.visit(route('solicitudes.create', { ubicacion_id: nodo.id }));
const verSolicitudes = (nodo) =>
    router.visit(route('solicitudes.index', { ubicacion_id: nodo.id }));
const verOrdenes = (nodo) =>
    router.visit(route('mantenimientos.index', { ubicacion_id: nodo.id }));

const modoTodas = computed(() => props.sucursalSeleccionada === 'todas');

const opcionesSucursal = computed(() => [
    { id: 'todas', nombre: 'Todas las sucursales' },
    ...props.sucursales,
]);

const sucursalActual = computed({
    get: () => (modoTodas.value ? 'todas' : props.sucursalSeleccionada ? Number(props.sucursalSeleccionada) : null),
    set: (v) => router.get(route('ubicaciones.index'), { sucursal_id: v, estado: filtroEstado.value }, { preserveState: false }),
});

const sucursalNombre = computed(() =>
    modoTodas.value ? 'Todas las sucursales' : props.sucursales.find((s) => s.id === Number(props.sucursalSeleccionada))?.nombre ?? '',
);

// --- Filtro de estado (Todos / Activas / Inactivas) -----------------
const filtroEstado = ref(props.filtros?.estado ?? 'todos');

const opcionesEstado = [
    { label: 'Todas', value: 'todos' },
    { label: 'Activas', value: 'activo' },
    { label: 'Inactivas', value: 'inactivo' },
];

const cambiarEstado = (valor) => {
    filtroEstado.value = valor;
    router.get(
        route('ubicaciones.index'),
        {
            sucursal_id: props.sucursalSeleccionada,
            estado: valor === 'todos' ? undefined : valor,
        },
        { preserveState: false, preserveScroll: true },
    );
};

// --- Árbol ----------------------------------------------------------
const aTreeData = (nodos) =>
    nodos.map((n) => ({
        key: n.id,
        nodo: n,
        children: n.hijas?.length ? aTreeData(n.hijas) : undefined,
    }));

const treeData = computed(() => aTreeData(props.arbol));
const totalUbicaciones = computed(() => {
    const contar = (ns) => ns.reduce((acc, n) => acc + 1 + (n.hijas ? contar(n.hijas) : 0), 0);
    return contar(props.arbol);
});

// --- Modal de alta / edición ---------------------------------------
const modal = reactive({ abierto: false, editando: false, tituloPadre: '' });

const form = useForm({
    id: null,
    sucursal_id: null,
    padre_id: null,
    tipo_id: undefined,
    tipo_area_id: undefined,
    tipo_limpieza_id: undefined,
    codigo: '',
    nombre: '',
    descripcion: '',
    estado: 'activo',
});

const cargarForm = (datos) => {
    form.clearErrors();
    form.id = datos.id ?? null;
    form.sucursal_id = datos.sucursal_id ?? Number(props.sucursalSeleccionada);
    form.padre_id = datos.padre_id ?? null;
    form.tipo_id = datos.tipo_id ?? undefined;
    form.tipo_area_id = datos.tipo_area_id ?? undefined;
    form.tipo_limpieza_id = datos.tipo_limpieza_id ?? undefined;
    form.codigo = datos.codigo ?? '';
    form.nombre = datos.nombre ?? '';
    form.descripcion = datos.descripcion ?? '';
    form.estado = datos.estado ?? 'activo';
};

const abrirAlta = (padre = null) => {
    cargarForm({ padre_id: padre?.id ?? null });
    modal.editando = false;
    modal.tituloPadre = padre?.nombre ?? '';
    modal.abierto = true;
};

const abrirEdicion = (nodo) => {
    cargarForm(nodo);
    modal.editando = true;
    modal.tituloPadre = '';
    modal.abierto = true;
};

const guardar = () => {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => (modal.abierto = false),
    };
    if (modal.editando) form.put(route('ubicaciones.update', form.id), opciones);
    else form.post(route('ubicaciones.store'), opciones);
};

const eliminar = async (nodo) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${nodo.nombre}`,
        mensaje: 'Solo se puede desactivar si no tiene sububicaciones ni equipos asignados.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('ubicaciones.destroy', nodo.id), { preserveScroll: true });
};

const reactivar = async (nodo) => {
    const ok = await confirmar.value.abrir({
        titulo: `Reactivar ${nodo.nombre}`,
        mensaje: 'La ubicación volverá a estar disponible para asignar equipos y registrar solicitudes.',
        confirmar: 'Reactivar',
    });
    if (ok) router.put(route('ubicaciones.restore', nodo.id), {}, { preserveScroll: true });
};

const est = (campo) => (form.errors[campo] ? 'error' : undefined);
</script>

<template>

    <Head title="Ubicaciones" />

    <AppLayout titulo="Ubicaciones"
        descripcion="Estructura jerárquica de áreas y espacios de cada sucursal donde se ubican los equipos.">
        <template #acciones>
            <a-select v-model:value="sucursalActual" :options="opcionesSucursal"
                :field-names="{ label: 'nombre', value: 'id' }" style="min-width: 220px"
                placeholder="Selecciona una sucursal" />

            <a-select v-if="sucursalSeleccionada" v-model:value="filtroEstado" :options="opcionesEstado"
                style="min-width: 140px" @change="cambiarEstado" />

            <a-button v-if="puede('ubicaciones.crear') && sucursalSeleccionada && !modoTodas" type="primary"
                @click="abrirAlta()">
                <template #icon>
                    <PlusOutlined />
                </template>
                Nueva ubicación
            </a-button>
        </template>

        <a-card v-if="!sucursalSeleccionada" size="small">
            <a-empty description="Selecciona una sucursal para ver y gestionar sus ubicaciones" />
        </a-card>

        <template v-else>
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

            <a-card :body-style="{ padding: 0 }" class="ubic-card">
            <div class="ubic-head">
                <div class="ubic-head__ico">
                    <ApartmentOutlined />
                </div>
                <div class="ubic-head__meta">
                    <div class="ubic-head__titulo">{{ sucursalNombre }}</div>
                    <div class="ubic-head__sub">
                        <span class="ubic-head__badge">{{ totalUbicaciones }}</span>
                        ubicación(es) registradas
                    </div>
                </div>
            </div>

            <div class="ubic-cuerpo">
                <a-tree v-if="treeData.length" :tree-data="treeData" default-expand-all :selectable="false" block-node
                    class="ubic-tree">
                    <template #title="{ nodo }">
                        <div class="nodo" :class="{ 'nodo--inactiva': nodo.estado !== 'activo' }">
                            <span class="nodo__pin">
                                <EnvironmentOutlined />
                            </span>

                            <!-- Acciones a la izquierda, pegadas al ícono -->
                            <div class="nodo__acciones">
                                <button v-if="puede('solicitudes.crear') && nodo.estado === 'activo'" type="button"
                                    class="nodo__btn nodo__btn--mant" title="Solicitar mantenimiento"
                                    @click.stop="solicitarMantenimiento(nodo)">
                                    <FileAddOutlined />
                                </button>
                                <button v-if="puede('ubicaciones.crear') && !modoTodas && nodo.estado === 'activo'"
                                    type="button" class="nodo__btn nodo__btn--add" title="Añadir sububicación"
                                    @click.stop="abrirAlta(nodo)">
                                    <PlusOutlined />
                                </button>
                                <button v-if="puede('ubicaciones.editar') && nodo.estado === 'activo'" type="button"
                                    class="nodo__btn nodo__btn--edit" title="Editar" @click.stop="abrirEdicion(nodo)">
                                    <EditOutlined />
                                </button>
                                <button v-if="puede('ubicaciones.desactivar') && nodo.estado === 'activo'" type="button"
                                    class="nodo__btn nodo__btn--del" title="Desactivar" @click.stop="eliminar(nodo)">
                                    <DeleteOutlined />
                                </button>
                                <button v-if="puede('ubicaciones.editar') && nodo.estado !== 'activo'" type="button"
                                    class="nodo__btn nodo__btn--reactivar" title="Reactivar"
                                    @click.stop="reactivar(nodo)">
                                    <RedoOutlined />
                                </button>
                            </div>

                            <div class="nodo__info">
                                <span class="nodo__nombre">{{ nodo.nombre }}</span>
                                <a-tooltip v-if="nodo.codigo" :title="nodo.ruta ? `Trazabilidad: ${nodo.ruta}` : ''">
                                    <a-tag class="nodo__cod">{{ nodo.codigo }}</a-tag>
                                </a-tooltip>
                                <span v-if="nodo.tipo" class="nodo__tipo">{{ nodo.tipo }}</span>
                                <a-tooltip v-if="nodo.tipo_area" title="Tipo de área (periodicidad de limpieza)">
                                    <a-tag color="cyan">{{ nodo.tipo_area }}</a-tag>
                                </a-tooltip>
                                <a-tag v-if="nodo.tipo_limpieza" color="geekblue">{{ nodo.tipo_limpieza }}</a-tag>
                                <a-tag v-if="modoTodas && !nodo.padre_id" color="purple">{{ nodo.sucursal }}</a-tag>
                                <a-tag v-if="nodo.equipos_count" color="blue">
                                    <ToolOutlined /> {{ nodo.equipos_count }}
                                </a-tag>
                                <a-tag v-if="nodo.solicitudes_count" color="orange" class="nodo__mant"
                                    @click.stop="verSolicitudes(nodo)">
                                    <FileAddOutlined /> {{ nodo.solicitudes_count }} solicitud(es)
                                </a-tag>
                                <a-tag v-if="nodo.mantenimientos_count" color="orange" class="nodo__mant"
                                    @click.stop="verOrdenes(nodo)">
                                    <ToolOutlined /> {{ nodo.mantenimientos_count }} orden(es)
                                </a-tag>
                                <a-tag v-if="nodo.estado !== 'activo'">Inactiva</a-tag>
                            </div>
                        </div>
                    </template>
                </a-tree>

                <a-empty v-else :description="filtroEstado === 'inactivo'
                    ? 'No hay ubicaciones inactivas en esta sucursal'
                    : 'Esta sucursal aún no tiene ubicaciones registradas'">
                    <a-button v-if="puede('ubicaciones.crear') && filtroEstado !== 'inactivo'" type="primary"
                        @click="abrirAlta()">
                        Crear la primera ubicación
                    </a-button>
                </a-empty>
            </div>
        </a-card>
        </template>

        <a-modal v-model:open="modal.abierto"
            :title="modal.editando ? 'Editar ubicación' : (modal.tituloPadre ? `Nueva sububicación en «${modal.tituloPadre}»` : 'Nueva ubicación')"
            :confirm-loading="form.processing" ok-text="Guardar" cancel-text="Cancelar" @ok="guardar">
            <a-form layout="vertical" class="mt-2" @submit.prevent="guardar">
                <a-row :gutter="12">
                    <a-col :span="10">
                        <a-form-item label="Código" extra="Vacío = automático (4 letras + consecutivo)."
                            :validate-status="est('codigo')" :help="form.errors.codigo">
                            <a-input v-model:value="form.codigo" placeholder="Automático" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="14">
                        <a-form-item label="Nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                            <a-input v-model:value="form.nombre" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Tipo de ubicación" :validate-status="est('tipo_id')"
                            :help="form.errors.tipo_id">
                            <SelectCatalogo v-model:value="form.tipo_id" :options="tipos"
                                ruta="catalogos.tipos_ubicacion" etiqueta="tipo de ubicación"
                                etiqueta-plural="tipos de ubicación" placeholder="Sin especificar" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Tipo de área" extra="Criticidad de limpieza — opcional."
                            :validate-status="est('tipo_area_id')" :help="form.errors.tipo_area_id">
                            <a-select v-model:value="form.tipo_area_id"
                                :options="tiposArea.map((t) => ({ value: t.id, label: `${t.nombre} (${t.dias_limpieza} días)` }))"
                                allow-clear placeholder="Sin especificar" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Tipo de limpieza" extra="Opcional."
                            :validate-status="est('tipo_limpieza_id')" :help="form.errors.tipo_limpieza_id">
                            <a-select v-model:value="form.tipo_limpieza_id"
                                :options="tiposLimpieza.map((t) => ({ value: t.id, label: t.nombre }))" allow-clear
                                placeholder="Sin especificar" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Descripción" :validate-status="est('descripcion')"
                            :help="form.errors.descripcion">
                            <a-textarea v-model:value="form.descripcion" :auto-size="{ minRows: 2, maxRows: 4 }" />
                        </a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>

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

.ubic-card {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(15, 37, 71, 0.06);
}

.ubic-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 22px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 70%);
}

.ubic-head__ico {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: var(--sigam-grad);
    box-shadow: 0 6px 14px rgba(15, 37, 71, 0.18);
}

.ubic-head__meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ubic-head__titulo {
    font-weight: 800;
    font-size: 16px;
    color: var(--sigam-navy);
    letter-spacing: -0.2px;
}

.ubic-head__sub {
    font-size: 12px;
    color: var(--sigam-tenue);
    display: flex;
    align-items: center;
    gap: 6px;
}

.ubic-head__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 20px;
    padding: 0 7px;
    border-radius: 999px;
    background: var(--sigam-teal);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
}

.ubic-cuerpo {
    padding: 14px 18px 20px;
}

.ubic-tree :deep(.ant-tree-treenode) {
    padding: 3px 0;
    width: 100%;
    align-items: center;
}

.ubic-tree :deep(.ant-tree-node-content-wrapper) {
    flex: 1;
    padding: 0;
    border-radius: 12px;
    transition: background 0.13s ease;
}

.ubic-tree :deep(.ant-tree-node-content-wrapper:hover) {
    background: var(--sigam-navy-050);
}

.ubic-tree :deep(.ant-tree-switcher) {
    color: var(--sigam-tenue);
}

.ubic-tree :deep(.ant-tree-indent-unit) {
    width: 22px;
}

/* Nodo ------------------------------------------------------------ */
.nodo {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 12px;
    border: 1px solid transparent;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.nodo:hover {
    border-color: var(--sigam-borde-suave);
    background: #fff;
}

.nodo--inactiva {
    opacity: 0.55;
}

.nodo__pin {
    color: var(--sigam-teal);
    font-size: 15px;
    display: inline-flex;
    flex-shrink: 0;
}

.nodo__acciones {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}

.nodo__btn {
    width: 26px;
    height: 26px;
    min-width: 26px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 7px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.12s ease, color 0.12s ease, transform 0.12s ease;
    outline: none;
}

.nodo__btn:active {
    transform: scale(0.92);
}

.nodo__btn--mant {
    background: #fdf3e6;
    color: #a86717;
}

.nodo__btn--mant:hover {
    background: #e08a1e;
    color: #fff;
}

.nodo__btn--add {
    background: #e4f4ee;
    color: var(--sigam-teal-700);
}

.nodo__btn--add:hover {
    background: var(--sigam-teal);
    color: #fff;
}

.nodo__btn--edit {
    background: #e7eefb;
    color: #23508c;
}

.nodo__btn--edit:hover {
    background: var(--sigam-navy);
    color: #fff;
}

.nodo__btn--del {
    background: #fbeaea;
    color: #c23b3b;
}

.nodo__btn--del:hover {
    background: #dc2626;
    color: #fff;
}

.nodo__btn--reactivar {
    background: #e4f4ee;
    color: var(--sigam-teal-700);
}

.nodo__btn--reactivar:hover {
    background: var(--sigam-teal);
    color: #fff;
}

/* Info ------------------------------------------------------------ */
.nodo__info {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
    flex: 1;
    min-width: 0;
}

.nodo__nombre {
    font-weight: 600;
    color: var(--sigam-texto);
}

.nodo__cod {
    font-family: ui-monospace, monospace;
    background: var(--sigam-navy-050);
    color: var(--sigam-navy);
    border: none;
    margin: 0;
}

.nodo__tipo {
    font-size: 12px;
    color: var(--sigam-tenue);
}

.nodo__mant {
    cursor: pointer;
    transition: transform 0.13s ease;
}

.nodo__mant:hover {
    transform: translateY(-1px);
}
</style>