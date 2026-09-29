<script setup>
import { computed, h, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckCircleOutlined,
    CheckSquareOutlined,
    CloseCircleOutlined,
    DeleteOutlined,
    DollarOutlined,
    EditOutlined,
    EyeOutlined,
    FileTextOutlined,
    FlagOutlined,
    InfoCircleOutlined,
    InboxOutlined,
    PlayCircleOutlined,
    PlusOutlined,
    SendOutlined,
    StarFilled,
    SwapOutlined,
    TeamOutlined,
    ToolOutlined,
    UserOutlined,
    WhatsAppOutlined,
    EllipsisOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoEvidencia from '@/Components/CampoEvidencia.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ModalFicha from '@/Components/ModalFicha.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import { usePermisos } from '@/composables/usePermisos';
import { hoyISO } from '@/utils/restricciones';

const props = defineProps({
    tarea: { type: Object, default: () => ({}) },
    transicionesPosibles: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const t = computed(() => props.tarea ?? {});
const confirmar = ref(null);

const ETIQUETA_ESTADO = {
    en_proceso: 'Iniciar tarea',
    realizada: 'Marcar como realizada',
    cancelada: 'Cancelar tarea',
};
const ICONO_ESTADO = {
    en_proceso: PlayCircleOutlined,
    realizada: CheckCircleOutlined,
    cancelada: CloseCircleOutlined,
};
const COLOR_ESTADO = {
    pendiente: 'gold',
    en_proceso: 'blue',
    realizada: 'green',
    cancelada: 'red',
};
const ETIQUETA_ESTADO_TAG = {
    pendiente: 'Pendiente',
    en_proceso: 'En proceso',
    realizada: 'Realizada',
    cancelada: 'Cancelada',
};
const COLOR_TIMELINE = {
    pendiente: '#d4a017',
    en_proceso: '#0d84c9',
    realizada: '#1f9e86',
    cancelada: '#d64545',
};

/* Mapa de colores hero para cada estado */
const HERO_COLORES = {
    en_proceso: { c1: '#0d84c9', c2: '#0a6ba6', variante: 'primary' },
    realizada: { c1: '#1f9e86', c2: '#16806c', variante: 'primary' },
    cancelada: { c1: '#d64545', c2: '#b91c1c', variante: 'danger' },
};

const fecha = (v) =>
    v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado';
const soloFecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const fechaHora = (v) =>
    v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado';
const moneda = (v) =>
    v == null
        ? 'No especificado'
        : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v);

const vencida = computed(
    () =>
        ['pendiente', 'en_proceso'].includes(t.value?.estado) &&
        t.value?.fecha_limite &&
        new Date(t.value.fecha_limite) < new Date().setHours(0, 0, 0, 0),
);
const esFinal = computed(() => ['realizada', 'cancelada'].includes(t.value?.estado));

/* ---------- Datos de "Información de la tarea" ---------- */
const datos = computed(() => [
    {
        icono: CalendarOutlined,
        label: 'Fecha límite',
        valor: soloFecha(t.value?.fecha_limite),
        color: vencida.value ? '#d64545' : '#173a5f',
    },
    {
        icono: FlagOutlined,
        label: 'Prioridad',
        valor: t.value?.prioridad?.nombre ?? 'No especificado',
        color: t.value?.prioridad?.color || '#d64545',
    },
    {
        icono: DollarOutlined,
        label: 'Costo',
        valor: t.value?.costo != null ? moneda(t.value.costo) : 'No especificado',
        color: '#6b4bc9',
    },
]);

const responsablesActivos = computed(() =>
    (t.value?.asignaciones ?? []).filter((a) => !a.desasignado_at),
);
const responsablesInactivos = computed(() =>
    (t.value?.asignaciones ?? []).filter((a) => a.desasignado_at),
);

/* ---------- Cambiar estado ---------- */
const modalEstado = ref(false);
const formEstado = useForm({ estado: null, nota: '', evidencia: null });
const abrirEstado = (slug) => {
    formEstado.reset();
    formEstado.clearErrors();
    formEstado.estado = slug;
    modalEstado.value = true;
};
const confirmarEstado = () => {
    formEstado.clearErrors();
    if (!formEstado.evidencia) {
        formEstado.setError('evidencia', 'Adjunta una foto del avance para este movimiento.');
        return;
    }
    formEstado.post(route('tareas.transicion', t.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (modalEstado.value = false),
    });
};
const botonesTransicion = computed(() =>
    props.transicionesPosibles.map((slug, i) => {
        const hero = HERO_COLORES[slug] ?? { c1: '#0d84c9', c2: '#0a6ba6', variante: 'default' };
        return {
            slug,
            label: ETIQUETA_ESTADO[slug] ?? slug,
            icono: ICONO_ESTADO[slug] ?? CheckCircleOutlined,
            variante: hero.variante,
            color: hero.c1,
            color2: hero.c2,
        };
    }),
);

/* ==========================================================
   RESPONSABLES — modal mejorado estilo "Nueva tarea"
   ========================================================== */
const modalResponsable = ref(false);
const formResponsable = useForm({
    usuario_id: undefined,
    es_principal: false,
    notas: '',
});

const usuariosDisponibles = computed(() => {
    const activos = new Set(responsablesActivos.value.map((a) => a.usuario_id));
    return (props.catalogos?.usuarios ?? []).filter((u) => !activos.has(u.id));
});

const opcionesUsuarios = computed(() =>
    usuariosDisponibles.value.map((u) => ({
        value: u.id,
        label: `${u.nombre} ${u.apellidos ?? ''}`.trim(),
    })),
);

const usuarioSeleccionado = computed(() =>
    usuariosDisponibles.value.find((u) => u.id === formResponsable.usuario_id),
);

const tieneResponsablesActivos = computed(() => responsablesActivos.value.length > 0);

const abrirResponsable = () => {
    formResponsable.reset();
    formResponsable.clearErrors();
    formResponsable.es_principal = !tieneResponsablesActivos.value;
    modalResponsable.value = true;
};

const cerrarResponsable = () => {
    modalResponsable.value = false;
    formResponsable.reset();
    formResponsable.clearErrors();
};

const asignarResponsable = () => {
    formResponsable.post(route('tareas.responsables.store', t.value.id), {
        preserveScroll: true,
        onSuccess: () => cerrarResponsable(),
    });
};

const quitarResponsable = async (asig) => {
    const ok = await confirmar.value.abrir({
        titulo: `Retirar a ${asig.usuario?.nombre}`,
        confirmar: 'Retirar',
        peligro: true,
    });
    if (ok)
        router.delete(route('tareas.responsables.destroy', [t.value.id, asig.id]), {
            preserveScroll: true,
        });
};

/** Enlace de wa.me para avisar por WhatsApp que se le asignó la tarea. */
const waLink = (usuario) => {
    if (!usuario?.telefono) return null;
    const mensaje =
        `Hola ${usuario.nombre}, se te asignó la tarea "${t.value?.titulo ?? t.value?.descripcion}" ` +
        `con fecha límite ${soloFecha(t.value?.fecha_limite)}. Ver detalle: ${route('tareas.show', t.value.id)}`;
    return `https://wa.me/52${usuario.telefono}?text=${encodeURIComponent(mensaje)}`;
};

/* ==========================================================
   MATERIALES — modal mejorado
   ========================================================== */
const modalMaterial = ref(false);
const formMaterial = useForm({
    material_id: undefined,
    descripcion: '',
    cantidad: 1,
    unidad: 'pza',
    costo_unitario: '',
    notas: '',
});
const reglasMaterial = reactive({
    cantidad: [{ required: true, message: 'Indica la cantidad.' }],
});

const materialSeleccionado = computed(() =>
    (props.catalogos?.materiales ?? []).find((x) => x.id === formMaterial.material_id),
);

const totalMaterialForm = computed(() => {
    const cant = Number(formMaterial.cantidad) || 0;
    const costo = Number(formMaterial.costo_unitario) || 0;
    return cant * costo;
});

const abrirMaterial = () => {
    formMaterial.reset();
    formMaterial.clearErrors();
    modalMaterial.value = true;
};

const cerrarMaterial = () => {
    modalMaterial.value = false;
    formMaterial.reset();
    formMaterial.clearErrors();
};

const agregarMaterial = () => {
    formMaterial.post(route('tareas.materiales.store', t.value.id), {
        preserveScroll: true,
        onSuccess: () => cerrarMaterial(),
    });
};

const quitarMaterial = (mat) =>
    router.delete(route('tareas.materiales.destroy', [t.value.id, mat.id]), { preserveScroll: true });

const onMaterialSel = (id) => {
    const mat = (props.catalogos?.materiales ?? []).find((x) => x.id === id);
    if (mat) {
        formMaterial.unidad = mat.unidad;
        if (mat.costo_referencia) formMaterial.costo_unitario = mat.costo_referencia;
    }
};

const totalMateriales = computed(() =>
    (t.value?.materiales ?? []).reduce((s, mat) => {
        const cant = Number(mat.cantidad) || 0;
        const costo = Number(mat.costo_unitario) || 0;
        return s + cant * costo;
    }, 0),
);

/* ==========================================================
   EDITAR TAREA — modal mejorado estilo "Nueva tarea"
   ========================================================== */
const modalEditar = ref(false);
const formEditar = useForm({
    titulo: '',
    descripcion: '',
    fecha_limite: '',
    prioridad_id: undefined,
});

const prioridades = computed(() => props.catalogos?.prioridades ?? []);

const prioridadSeleccionada = computed(() =>
    prioridades.value.find((p) => p.id === formEditar.prioridad_id),
);

const previewFecha = computed(() => {
    if (!formEditar.fecha_limite) return null;
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const f = new Date(formEditar.fecha_limite);
    f.setHours(0, 0, 0, 0);
    const dias = Math.round((f - hoy) / 86400000);
    if (dias < 0) return { texto: 'Fecha vencida', color: '#d64545' };
    if (dias === 0) return { texto: 'Vence hoy', color: '#e08a1e' };
    if (dias === 1) return { texto: 'Vence mañana', color: '#e08a1e' };
    if (dias <= 7) return { texto: `Vence en ${dias} días`, color: '#0d84c9' };
    return { texto: `Vence en ${dias} días`, color: '#1f9e86' };
});

const abrirEditar = () => {
    formEditar.reset();
    formEditar.clearErrors();
    formEditar.titulo = t.value?.titulo ?? '';
    formEditar.descripcion = t.value?.descripcion ?? '';
    formEditar.fecha_limite = t.value?.fecha_limite?.slice(0, 10) ?? '';
    formEditar.prioridad_id = t.value?.prioridad_id ?? undefined;
    modalEditar.value = true;
};

const cerrarEditar = () => {
    modalEditar.value = false;
    formEditar.reset();
    formEditar.clearErrors();
};

const guardarEdicion = () => {
    formEditar
        .transform((datos) => ({ ...datos, _method: 'put' }))
        .post(route('tareas.update', t.value.id), {
            preserveScroll: true,
            onSuccess: () => cerrarEditar(),
        });
};

/* Paleta de prioridades */
const PRIORIDAD_FALLBACK = ['#1f9e86', '#0d84c9', '#e08a1e', '#d64545'];
const prioridadColor = (p, i) =>
    p?.color || PRIORIDAD_FALLBACK[(i ?? 0) % PRIORIDAD_FALLBACK.length];

/* ---------- Eliminar ---------- */
const eliminar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: 'Eliminar tarea',
        mensaje: 'La tarea se quitará de los listados; su historial se conserva.',
        confirmar: 'Eliminar',
        peligro: true,
    });
    if (ok) router.delete(route('tareas.destroy', t.value.id));
};

/* ---------- Historial: helpers ---------- */
const colorEstadoHistorial = (hh) => {
    const clave = hh?.estado_destino?.clave ?? hh?.estado_destino;
    return COLOR_TIMELINE[clave] || '#0d84c9';
};
const etiquetaEstadoHistorial = (hh) => {
    const clave = hh?.estado_destino?.clave ?? hh?.estado_destino;
    return ETIQUETA_ESTADO_TAG[clave] ?? hh?.estado_destino?.nombre ?? clave ?? '—';
};
const etiquetaEstadoOrigen = (hh) => {
    const clave = hh?.estado_origen?.clave ?? hh?.estado_origen;
    return ETIQUETA_ESTADO_TAG[clave] ?? hh?.estado_origen?.nombre ?? clave ?? null;
};

/* ---------- Foto adjunta a un movimiento del historial ---------- */
const fotoHistorial = ref(null);
const modalFotoHistorial = ref(false);
const abrirFotoHistorial = (doc) => {
    fotoHistorial.value = doc;
    modalFotoHistorial.value = true;
};

/* ---------- Menú de acciones (dropdown) ---------- */
const menuAcciones = computed(() => {
    const items = [];
    if (!esFinal.value && puede('tareas.desactivar')) {
        items.push({
            key: 'eliminar-bloqueado',
            label: 'Eliminar (no disponible)',
            disabled: true,
            icon: () => h(DeleteOutlined),
        });
    } else if (esFinal.value && puede('tareas.desactivar')) {
        items.push({
            key: 'eliminar',
            label: 'Eliminar tarea',
            danger: true,
            icon: () => h(DeleteOutlined),
        });
    }
    return items;
});

const onMenuAccion = ({ key }) => {
    if (key === 'eliminar') eliminar();
};
</script>

<template>
    <Head :title="`Tarea ${t?.titulo ?? ''}`" />

    <AppLayout>
        <FichaEncabezado :titulo="t?.titulo ?? 'Tarea'" :subtitulo="`Fecha límite: ${soloFecha(t?.fecha_limite)}`"
            :icono="CheckSquareOutlined" volver="tareas.index" :sello="sello">
            <template #tags>
                <a-tag :color="COLOR_ESTADO[t?.estado]">
                    {{ ETIQUETA_ESTADO_TAG[t?.estado] ?? t?.estado }}
                </a-tag>
                <a-tag v-if="t?.prioridad" :color="t.prioridad.color || 'default'">
                    {{ t.prioridad.nombre }}
                </a-tag>
                <a-tag v-if="vencida" color="error">Vencida</a-tag>
            </template>
            <template #acciones>
                <!-- Botón Editar -->
                <button
                    v-if="!esFinal && puede('tareas.editar')"
                    type="button"
                    class="btn-hero btn-hero--default"
                    style="--hc: #0d84c9; --hc2: #0a6ba6"
                    @click="abrirEditar"
                >
                    <span class="btn-hero__ic">
                        <EditOutlined />
                    </span>
                    <span class="btn-hero__txt">
                        <span class="btn-hero__l">Editar</span>
                        <span class="btn-hero__s">Tarea</span>
                    </span>
                </button>

                <!-- Botones de transición de estado -->
                <template v-if="puede('tareas.editar')">
                    <button
                        v-for="b in botonesTransicion"
                        :key="b.slug"
                        type="button"
                        class="btn-hero"
                        :class="{
                            'btn-hero--primary': b.variante === 'primary',
                            'btn-hero--danger': b.variante === 'danger',
                            'btn-hero--default': b.variante === 'default',
                        }"
                        :style="{ '--hc': b.color, '--hc2': b.color2 }"
                        @click="abrirEstado(b.slug)"
                    >
                        <span class="btn-hero__ic">
                            <component :is="b.icono" />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">{{ b.label }}</span>
                            <span class="btn-hero__s">Cambiar estado</span>
                        </span>
                    </button>
                </template>

                <!-- Dropdown de acciones (eliminar) -->
                <a-dropdown v-if="menuAcciones.length">
                    <button type="button" class="btn-more">
                        <EllipsisOutlined />
                    </button>
                    <template #overlay>
                        <a-menu :items="menuAcciones" @click="onMenuAccion" />
                    </template>
                </a-dropdown>
            </template>
        </FichaEncabezado>

        <div class="grid-ficha">
            <!-- COLUMNA IZQUIERDA -->
            <div class="col-izq">
                <!-- Información de la tarea -->
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #0d84c9">
                            <CheckSquareOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Información de la tarea</div>
                            <div class="card__sub">Datos generales</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="descripcion">
                            <span class="descripcion__label">Descripción</span>
                            <p class="descripcion__texto">
                                {{ t?.descripcion || 'Sin descripción' }}
                            </p>
                        </div>

                        <div class="mini-grid mini-grid--3">
                            <div v-for="d in datos" :key="d.label" class="mini" :style="{ '--c': d.color }">
                                <span class="mini__ic">
                                    <component :is="d.icono" />
                                </span>
                                <span class="mini__t">
                                    <span class="mini__l">{{ d.label }}</span>
                                    <span class="mini__v">{{ d.valor }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Materiales -->
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #e08a1e">
                            <ToolOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">
                                Materiales
                                <span v-if="t?.materiales?.length" class="badge badge--orange">
                                    {{ t.materiales.length }}
                                </span>
                            </div>
                            <div class="card__sub">Insumos utilizados · el costo se calcula con esto</div>
                        </div>
                        <a-button v-if="puede('tareas.editar') && !esFinal" class="card__extra btn-card-add" size="small"
                            type="text" @click="abrirMaterial">
                            <template #icon>
                                <PlusOutlined />
                            </template>
                            Agregar
                        </a-button>
                    </div>
                    <div class="card__body">
                        <template v-if="t?.materiales?.length">
                            <div class="mat-grid">
                                <div v-for="mat in t.materiales" :key="mat.id" class="mat-card">
                                    <span class="mat-card__head">
                                        <span class="mat-card__ic">
                                            <ToolOutlined />
                                        </span>
                                        <span class="mat-card__l">
                                            {{ mat.material?.nombre || mat.descripcion }}
                                        </span>
                                        <button v-if="puede('tareas.editar') && !esFinal" type="button"
                                            class="mat-card__x" title="Eliminar" @click.stop="quitarMaterial(mat)">
                                            <DeleteOutlined />
                                        </button>
                                    </span>
                                    <span class="mat-card__sub">
                                        {{ mat.cantidad }} {{ mat.unidad }} × {{ moneda(mat.costo_unitario) }}
                                    </span>
                                    <span class="mat-card__total">
                                        {{ moneda(mat.costo_unitario ? mat.costo_unitario * mat.cantidad : null) }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="t.materiales.length > 1" class="mat-total">
                                Total: {{ moneda(totalMateriales) }}
                            </div>
                        </template>
                        <div v-else class="vacio-box">
                            <ToolOutlined />
                            <span>Sin materiales registrados</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA -->
            <div class="col-der">
                <!-- Responsables -->
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #6b4bc9">
                            <TeamOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">
                                Responsables
                                <span v-if="responsablesActivos.length" class="badge badge--purple">
                                    {{ responsablesActivos.length }}
                                </span>
                            </div>
                            <div class="card__sub">Personal asignado</div>
                        </div>
                        <a-tooltip v-if="esFinal"
                            title="No se pueden modificar los responsables de una tarea finalizada.">
                            <a-button size="small" type="text" disabled>
                                <template #icon>
                                    <PlusOutlined />
                                </template>
                                Asignar
                            </a-button>
                        </a-tooltip>
                        <a-button v-else-if="puede('tareas.asignar')" class="card__extra btn-card-add" size="small"
                            type="text" @click="abrirResponsable">
                            <template #icon>
                                <PlusOutlined />
                            </template>
                            Asignar
                        </a-button>
                    </div>
                    <div class="card__body">
                        <a-list :data-source="responsablesActivos"
                            :locale="{ emptyText: 'Sin responsables asignados' }">
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta :title="item.usuario?.nombre"
                                        :description="`Asignado por ${item.asignado_por?.nombre ?? 'No especificado'} · ${fecha(item.asignado_at)}`" />
                                    <template #actions>
                                        <a-tag v-if="item.es_principal" color="blue">
                                            Principal
                                        </a-tag>
                                        <a-tooltip v-if="waLink(item.usuario)" title="Avisar por WhatsApp">
                                            <a class="wa-link" :href="waLink(item.usuario)" target="_blank"
                                                rel="noopener">
                                                <WhatsAppOutlined />
                                            </a>
                                        </a-tooltip>
                                        <a v-if="!esFinal && puede('tareas.asignar')" class="text-red-500"
                                            @click="quitarResponsable(item)">
                                            <DeleteOutlined />
                                        </a>
                                    </template>
                                </a-list-item>
                            </template>
                        </a-list>

                        <template v-if="responsablesInactivos.length">
                            <div class="seccion-mini">Retirados</div>
                            <a-list :data-source="responsablesInactivos" size="small">
                                <template #renderItem="{ item }">
                                    <a-list-item>
                                        <a-list-item-meta :title="item.usuario?.nombre"
                                            :description="`Retirado el ${fecha(item.desasignado_at)}`" />
                                    </a-list-item>
                                </template>
                            </a-list>
                        </template>
                    </div>
                </div>

                <!-- Historial de estados -->
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #e08a1e">
                            <SwapOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Historial de estados</div>
                            <div class="card__sub">
                                {{ t?.historial_estados?.length ?? 0 }} movimiento(s)
                            </div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div v-if="t?.historial_estados?.length" class="historial-estados">
                            <article v-for="hh in t.historial_estados" :key="hh.id" class="hist-item"
                                :style="{ '--hc': colorEstadoHistorial(hh) }">
                                <div class="hist-item__main">
                                    <p v-if="hh.nota" class="hist-item__comentario">{{ hh.nota }}</p>
                                    <p v-else class="hist-item__comentario hist-item__comentario--vacio">
                                        Sin comentario
                                    </p>

                                    <div class="hist-item__meta">
                                        <span class="hist-item__avatar">
                                            {{ (hh.cambiado_por?.nombre ?? 'S').charAt(0).toUpperCase() }}
                                        </span>
                                        <span class="hist-item__usuario">
                                            {{ hh.cambiado_por?.nombre ?? 'Sistema' }}
                                        </span>
                                        <span class="hist-item__sep">·</span>
                                        <span class="hist-item__fecha">
                                            {{ fechaHora(hh.cambiado_at) }}
                                        </span>
                                    </div>
                                </div>

                                <button v-if="hh.documento_evidencia" type="button" class="hist-item__foto"
                                    title="Ver foto del avance" @click="abrirFotoHistorial(hh.documento_evidencia)">
                                    <img :src="hh.documento_evidencia.url" alt="Foto del avance" />
                                </button>

                                <div class="hist-item__lado">
                                    <span class="hist-item__badge">
                                        {{ etiquetaEstadoHistorial(hh) }}
                                    </span>
                                    <span v-if="etiquetaEstadoOrigen(hh)" class="hist-item__origen">
                                        desde <em>{{ etiquetaEstadoOrigen(hh) }}</em>
                                    </span>
                                </div>
                            </article>
                        </div>
                        <div v-else class="vacio-box">
                            <SwapOutlined />
                            <span>Sin movimientos registrados</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================================
             MODAL CAMBIAR ESTADO
             ========================================================== -->
        <a-modal v-model:open="modalEstado" :title="ETIQUETA_ESTADO[formEstado.estado] || 'Cambiar estado'"
            :ok-text="formEstado.estado === 'cancelada' ? 'Cancelar tarea' : 'Confirmar'"
            :ok-button-props="{ danger: formEstado.estado === 'cancelada' }" cancel-text="Cerrar"
            :confirm-loading="formEstado.processing" @ok="confirmarEstado">
            <a-form layout="vertical" class="pt-2">
                <a-form-item :label="formEstado.estado === 'realizada'
                    ? 'Nota de cierre'
                    : formEstado.estado === 'cancelada'
                        ? 'Motivo de la cancelación'
                        : 'Nota de avance (opcional)'
                    " :required="formEstado.estado !== 'en_proceso'"
                    :validate-status="formEstado.errors.nota ? 'error' : undefined" :help="formEstado.errors.nota">
                    <a-textarea v-model:value="formEstado.nota" :rows="3" />
                </a-form-item>
                <a-form-item label="Foto del avance" required extra="Toma o adjunta una foto que respalde este movimiento."
                    :validate-status="formEstado.errors.evidencia ? 'error' : undefined"
                    :help="formEstado.errors.evidencia">
                    <CampoEvidencia v-model="formEstado.evidencia" />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- ==========================================================
             MODAL EDITAR TAREA — estilo "Nueva tarea"
             ========================================================== -->
        <a-modal v-model:open="modalEditar" :footer="null" :closable="false" :width="720" centered
            class="modal-tarea modal-editar" :mask-closable="!formEditar.processing">
            <div class="modal-tarea__wrap">
                <header class="modal-head">
                    <div class="modal-head__ico" style="--mh1: #6b4bc9; --mh2: #563a9e">
                        <EditOutlined />
                    </div>
                    <div class="modal-head__meta">
                        <h2 class="modal-head__titulo">Editar tarea</h2>
                        <p class="modal-head__sub">
                            Actualiza los datos generales de <strong>{{ t?.titulo }}</strong>.
                        </p>
                    </div>
                    <button type="button" class="modal-head__close" :disabled="formEditar.processing" title="Cerrar"
                        @click="cerrarEditar">✕</button>
                </header>

                <form class="modal-body" @submit.prevent="guardarEdicion">
                    <!-- Título -->
                    <div class="row">
                        <label class="label"><FileTextOutlined /> Título</label>
                        <a-form :model="formEditar" layout="vertical">
                            <a-form-item name="titulo"
                                :validate-status="formEditar.errors.titulo ? 'error' : undefined"
                                :help="formEditar.errors.titulo" class="mb-0">
                                <a-input v-model:value="formEditar.titulo" :maxlength="150" show-count size="large" />
                            </a-form-item>
                        </a-form>
                    </div>

                    <!-- Descripción -->
                    <div class="row">
                        <label class="label"><FileTextOutlined /> Descripción</label>
                        <a-form :model="formEditar" layout="vertical">
                            <a-form-item name="descripcion"
                                :validate-status="formEditar.errors.descripcion ? 'error' : undefined"
                                :help="formEditar.errors.descripcion" class="mb-0">
                                <a-textarea v-model:value="formEditar.descripcion" :rows="4" show-count :maxlength="1000" />
                            </a-form-item>
                        </a-form>
                    </div>

                    <!-- Fecha límite + Prioridad -->
                    <div class="grid-2">
                        <div class="row">
                            <label class="label"><CalendarOutlined /> Fecha límite</label>
                            <a-form :model="formEditar" layout="vertical">
                                <a-form-item name="fecha_limite"
                                    :validate-status="formEditar.errors.fecha_limite ? 'error' : undefined"
                                    :help="formEditar.errors.fecha_limite" class="mb-0">
                                    <CampoFechaHora v-model="formEditar.fecha_limite" solo-fecha :min-fecha="hoyISO()" />
                                </a-form-item>
                            </a-form>
                            <div v-if="previewFecha" class="preview-fecha" :style="{ '--pc': previewFecha.color }">
                                <CalendarOutlined />
                                <span>{{ previewFecha.texto }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <label class="label"><FlagOutlined /> Prioridad</label>
                            <div v-if="!prioridades.length" class="sin-prioridades">
                                <InfoCircleOutlined />
                                <span>Sin prioridades configuradas.</span>
                            </div>
                            <div v-else class="prioridades">
                                <label v-for="(p, i) in prioridades" :key="p.id" class="prio-card"
                                    :class="{ 'prio-card--active': formEditar.prioridad_id === p.id }"
                                    :style="{ '--pc': prioridadColor(p, i) }">
                                    <input type="radio" class="prio-card__input" :value="p.id"
                                        v-model="formEditar.prioridad_id" />
                                    <span class="prio-card__dot"></span>
                                    <span class="prio-card__label">{{ p.nombre }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Preview-bar -->
                    <div v-if="formEditar.fecha_limite || prioridadSeleccionada" class="preview-bar">
                        <span class="preview-bar__lbl">
                            <CheckCircleOutlined /> Vista previa
                        </span>
                        <div class="preview-bar__chips">
                            <span v-if="formEditar.fecha_limite" class="preview__chip"
                                :style="{ '--pc': previewFecha?.color || '#0d84c9' }">
                                <CalendarOutlined /> {{ formEditar.fecha_limite }}
                            </span>
                            <span v-if="prioridadSeleccionada" class="preview__chip"
                                :style="{ '--pc': prioridadColor(prioridadSeleccionada, 0) }">
                                <FlagOutlined /> {{ prioridadSeleccionada.nombre }}
                            </span>
                        </div>
                    </div>
                </form>

                <footer class="modal-footer">
                    <a-button size="large" :disabled="formEditar.processing" @click="cerrarEditar">
                        Cancelar
                    </a-button>
                    <a-button type="primary" size="large" class="btn-submit btn-submit--purple"
                        :loading="formEditar.processing" :disabled="formEditar.processing" @click="guardarEdicion">
                        <template #icon><CheckCircleOutlined /></template>
                        Guardar cambios
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <!-- ==========================================================
             MODAL ASIGNAR RESPONSABLE — estilo "Nueva tarea"
             ========================================================== -->
        <a-modal v-model:open="modalResponsable" :footer="null" :closable="false" :width="720" centered
            class="modal-tarea modal-responsable" :mask-closable="!formResponsable.processing">
            <div class="modal-tarea__wrap">
                <header class="modal-head">
                    <div class="modal-head__ico" style="--mh1: #1f9e86; --mh2: #16806c">
                        <TeamOutlined />
                    </div>
                    <div class="modal-head__meta">
                        <h2 class="modal-head__titulo">Asignar responsable</h2>
                        <p class="modal-head__sub">
                            Se notificará al responsable al guardar la asignación.
                        </p>
                    </div>
                    <button type="button" class="modal-head__close" :disabled="formResponsable.processing"
                        title="Cerrar" @click="cerrarResponsable">✕</button>
                </header>

                <form class="modal-body" @submit.prevent="asignarResponsable">
                    <!-- Usuario -->
                    <div class="row">
                        <label class="label"><UserOutlined /> Usuario</label>
                        <a-form :model="formResponsable" layout="vertical">
                            <a-form-item name="usuario_id"
                                :validate-status="formResponsable.errors.usuario_id ? 'error' : undefined"
                                :help="formResponsable.errors.usuario_id" class="mb-0">
                                <a-select v-model:value="formResponsable.usuario_id"
                                    :options="opcionesUsuarios"
                                    placeholder="Selecciona un responsable" size="large" show-search
                                    option-filter-prop="label" :disabled="!opcionesUsuarios.length">
                                    <template #option="{ label }">
                                        <div class="usuario-op">
                                            <span class="usuario-op__av">
                                                {{ label.charAt(0).toUpperCase() }}
                                            </span>
                                            <span>{{ label }}</span>
                                        </div>
                                    </template>
                                </a-select>
                            </a-form-item>
                        </a-form>
                        <div v-if="!opcionesUsuarios.length" class="sin-prioridades" style="margin-top: 6px">
                            <InfoCircleOutlined />
                            <span>Todos los usuarios disponibles ya están asignados.</span>
                        </div>
                    </div>

                    <!-- Vista previa del usuario -->
                    <div v-if="usuarioSeleccionado" class="usuario-preview">
                        <div class="usuario-preview__av">
                            {{ (usuarioSeleccionado.nombre ?? '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="usuario-preview__info">
                            <div class="usuario-preview__nombre">
                                {{ usuarioSeleccionado.nombre }} {{ usuarioSeleccionado.apellidos ?? '' }}
                            </div>
                            <div class="usuario-preview__sub">
                                <template v-if="usuarioSeleccionado.telefono">
                                    <WhatsAppOutlined /> {{ usuarioSeleccionado.telefono }}
                                </template>
                                <template v-else>Sin teléfono registrado</template>
                            </div>
                        </div>
                        <a-tag v-if="formResponsable.es_principal" color="blue">
                            <StarFilled /> Principal
                        </a-tag>
                    </div>

                    <!-- Rol principal -->
                    <div class="grid-2">
                        <div class="row">
                            <label class="label"><FlagOutlined /> Rol</label>
                            <label class="check-principal">
                                <input type="checkbox" v-model="formResponsable.es_principal"
                                    class="check-principal__input" />
                                <span class="check-principal__box">
                                    <CheckCircleOutlined />
                                </span>
                                <span class="check-principal__txt">
                                    <strong>Responsable principal</strong>
                                    <small>Aparece primero en la lista</small>
                                </span>
                            </label>
                            <div v-if="!tieneResponsablesActivos" class="hint-principal">
                                <InfoCircleOutlined />
                                <span>Es el primer responsable de la tarea, se marcará como principal.</span>
                            </div>
                        </div>

                        <div class="row">
                            <label class="label"><FileTextOutlined /> Notas</label>
                            <a-form :model="formResponsable" layout="vertical">
                                <a-form-item name="notas" class="mb-0">
                                    <a-input v-model:value="formResponsable.notas"
                                        placeholder="Ej. Encargado del área de urgencias" size="large" />
                                </a-form-item>
                            </a-form>
                        </div>
                    </div>
                </form>

                <footer class="modal-footer">
                    <a-button size="large" :disabled="formResponsable.processing" @click="cerrarResponsable">
                        Cancelar
                    </a-button>
                    <a-button type="primary" size="large" class="btn-submit btn-submit--green"
                        :loading="formResponsable.processing"
                        :disabled="formResponsable.processing || !formResponsable.usuario_id" @click="asignarResponsable">
                        <template #icon><SendOutlined /></template>
                        Asignar responsable
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <!-- ==========================================================
             MODAL AGREGAR MATERIAL — estilo "Nueva tarea"
             ========================================================== -->
        <a-modal v-model:open="modalMaterial" :footer="null" :closable="false" :width="720" centered
            class="modal-tarea modal-material" :mask-closable="!formMaterial.processing">
            <div class="modal-tarea__wrap">
                <header class="modal-head">
                    <div class="modal-head__ico" style="--mh1: #e08a1e; --mh2: #a86717">
                        <InboxOutlined />
                    </div>
                    <div class="modal-head__meta">
                        <h2 class="modal-head__titulo">Agregar material</h2>
                        <p class="modal-head__sub">
                            Selecciona del catálogo o captura manualmente.
                        </p>
                    </div>
                    <button type="button" class="modal-head__close" :disabled="formMaterial.processing"
                        title="Cerrar" @click="cerrarMaterial">✕</button>
                </header>

                <form class="modal-body" @submit.prevent="agregarMaterial">
                    <!-- Catálogo -->
                    <div class="row">
                        <label class="label"><InboxOutlined /> Del catálogo</label>
                        <a-form :model="formMaterial" layout="vertical">
                            <a-form-item name="material_id" class="mb-0">
                                <SelectCatalogo v-model:value="formMaterial.material_id"
                                    :options="catalogos.materiales" ruta="catalogos.materiales"
                                    etiqueta="material" etiqueta-plural="materiales"
                                    placeholder="Opcional — o captura la descripción abajo"
                                    :campos="[
                                        { name: 'unidad', label: 'Unidad (pza, m, lt…)', ancho: 12 },
                                        { name: 'costo_referencia', label: 'Costo referencia', tipo: 'number', min: 0 },
                                    ]" @update:value="onMaterialSel" />
                            </a-form-item>
                        </a-form>
                    </div>

                    <!-- Vista previa del material del catálogo -->
                    <div v-if="materialSeleccionado" class="mat-preview-card">
                        <span class="mat-preview-card__ic"><ToolOutlined /></span>
                        <div class="mat-preview-card__info">
                            <span class="mat-preview-card__nombre">{{ materialSeleccionado.nombre }}</span>
                            <span class="mat-preview-card__sub">
                                {{ materialSeleccionado.unidad || 'unidad' }}
                                <template v-if="materialSeleccionado.costo_referencia">
                                    · Costo ref. {{ moneda(materialSeleccionado.costo_referencia) }}
                                </template>
                            </span>
                        </div>
                    </div>

                    <!-- Descripción libre (si no hay catálogo) -->
                    <div v-if="!formMaterial.material_id" class="row">
                        <label class="label"><FileTextOutlined /> Descripción</label>
                        <a-form :model="formMaterial" layout="vertical">
                            <a-form-item name="descripcion"
                                :validate-status="formMaterial.errors.descripcion ? 'error' : undefined"
                                :help="formMaterial.errors.descripcion" class="mb-0">
                                <a-input v-model:value="formMaterial.descripcion"
                                    placeholder="Ej. Cinta aislante 3M" size="large" />
                            </a-form-item>
                        </a-form>
                    </div>

                    <!-- Cantidad / Unidad / Costo -->
                    <div class="grid-3">
                        <div class="row">
                            <label class="label">Cantidad</label>
                            <a-form :model="formMaterial" :rules="reglasMaterial" layout="vertical">
                                <a-form-item name="cantidad"
                                    :validate-status="formMaterial.errors.cantidad ? 'error' : undefined"
                                    :help="formMaterial.errors.cantidad" class="mb-0">
                                    <a-input-number v-model:value="formMaterial.cantidad" :min="0.01" size="large"
                                        style="width: 100%" />
                                </a-form-item>
                            </a-form>
                        </div>
                        <div class="row">
                            <label class="label">Unidad</label>
                            <a-input v-model:value="formMaterial.unidad" size="large" placeholder="pza, m, lt…" />
                        </div>
                        <div class="row">
                            <label class="label">Costo unitario</label>
                            <a-input-number v-model:value="formMaterial.costo_unitario" :min="0" :precision="2"
                                prefix="$" size="large" style="width: 100%" />
                        </div>
                    </div>

                    <!-- Total preview -->
                    <div v-if="totalMaterialForm > 0" class="total-preview">
                        <span class="total-preview__l"><DollarOutlined /> Total a registrar</span>
                        <span class="total-preview__v">{{ moneda(totalMaterialForm) }}</span>
                    </div>
                </form>

                <footer class="modal-footer">
                    <a-button size="large" :disabled="formMaterial.processing" @click="cerrarMaterial">
                        Cancelar
                    </a-button>
                    <a-button type="primary" size="large" class="btn-submit btn-submit--orange"
                        :loading="formMaterial.processing" :disabled="formMaterial.processing" @click="agregarMaterial">
                        <template #icon><PlusOutlined /></template>
                        Agregar material
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />

        <!-- Foto del historial -->
        <ModalFicha :show="modalFotoHistorial" :titulo="fotoHistorial?.titulo ?? 'Evidencia'"
            :subtitulo="fotoHistorial?.nombre_original" :icono="SwapOutlined" color="#e08a1e" max-width="lg"
            @close="modalFotoHistorial = false">
            <div v-if="fotoHistorial?.url" class="foto-modal">
                <img :src="fotoHistorial.url" alt="Evidencia" />
            </div>
            <template #footer>
                <a v-if="fotoHistorial" :href="route('documentos.download', fotoHistorial.id)"
                    class="ant-btn ant-btn-default" target="_blank">Descargar</a>
                <a v-if="fotoHistorial" :href="fotoHistorial.url"
                    class="ant-btn ant-btn-primary" target="_blank">Abrir en nueva pestaña</a>
            </template>
        </ModalFicha>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   BOTONES HERO (acciones del header)
   ========================================================== */
.btn-hero {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    height: 42px;
    padding: 0 14px 0 8px;
    border-radius: 11px;
    border: 1px solid transparent;
    font-family: inherit;
    font-weight: 800;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease, background 0.16s ease;
    flex-shrink: 0;
}

.btn-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.24) 50%, transparent 100%);
    transform: translateX(-100%) skewX(-20deg);
    transition: transform 0.6s ease;
    pointer-events: none;
}

.btn-hero:hover:not(:disabled)::after {
    transform: translateX(200%) skewX(-20deg);
}

.btn-hero:active:not(:disabled) {
    transform: translateY(0) scale(0.98);
}

.btn-hero:disabled {
    cursor: not-allowed;
    opacity: 0.55;
    filter: grayscale(0.4);
}

.btn-hero__ic {
    width: 28px;
    height: 28px;
    flex: none;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    background: rgba(255, 255, 255, 0.24);
    color: #fff;
    transition: transform 0.2s ease;
    position: relative;
    z-index: 1;
}

.btn-hero:hover:not(:disabled) .btn-hero__ic {
    transform: scale(1.1) rotate(-6deg);
}

.btn-hero__txt {
    display: flex;
    flex-direction: column;
    gap: 1px;
    line-height: 1.1;
    text-align: left;
    color: #fff;
    position: relative;
    z-index: 1;
}

.btn-hero__l {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    white-space: nowrap;
}

.btn-hero__s {
    font-size: 9.5px;
    font-weight: 600;
    opacity: 0.82;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.btn-hero--primary {
    background: linear-gradient(135deg, var(--hc, #1f9e86) 0%, var(--hc2, #16806c) 100%);
    box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--hc, #1f9e86) 65%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--primary:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px color-mix(in srgb, var(--hc, #1f9e86) 75%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-hero--default {
    background: linear-gradient(135deg, var(--hc, #0d84c9) 0%, var(--hc2, #0f6fb0) 100%);
    box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--hc, #0d84c9) 55%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--default:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px color-mix(in srgb, var(--hc, #0d84c9) 70%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-hero--danger {
    background: linear-gradient(135deg, #d64545 0%, #b91c1c 100%);
    box-shadow: 0 6px 16px -6px rgba(214, 69, 69, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--danger:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(214, 69, 69, 0.75), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-more {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    border: 1px solid #e6ecf3;
    background: #fff;
    color: #7b8a9c;
    cursor: pointer;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.14s ease, color 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
    flex-shrink: 0;
}

.btn-more:hover {
    background: #eef4fb;
    border-color: #cfe4f5;
    color: #0d84c9;
    transform: translateY(-1px);
}

/* ==========================================================
   Modal foto (historial de estados)
   ========================================================== */
.foto-modal {
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    padding: 0;
    margin: 0;
    max-height: 65vh;
    overflow: hidden;
}
.foto-modal img {
    max-width: 100%;
    max-height: 65vh;
    object-fit: contain;
    display: block;
    border: none;
    outline: none;
    border-radius: 8px;
}

/* ==========================================================
   Grid principal
   ========================================================== */
.grid-ficha {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 14px;
    align-items: start;
    margin-top: 14px;
}

.col-izq,
.col-der {
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-width: 0;
    padding-bottom: 4px;
}

/* ==========================================================
   Cards
   ========================================================== */
.card {
    background: #fff;
    border: 1px solid #e6ecf3;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 14px -10px rgba(15, 37, 71, 0.18);
    transition: box-shadow 0.18s ease;
}
.card:hover {
    box-shadow: 0 8px 22px -14px rgba(15, 37, 71, 0.28);
}
.card__head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 16px;
    border-bottom: 1px solid #e6ecf3;
    background: #f5f8fb;
}
.card__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 12%, #fff);
    flex-shrink: 0;
}
.card__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    flex: 1;
}
.card__titulo {
    font-weight: 800;
    font-size: 13.5px;
    color: #173a5f;
    letter-spacing: -0.1px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
.card__sub {
    font-size: 11px;
    color: #7b8a9c;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.card__extra {
    flex-shrink: 0;
}
.card__body {
    padding: 13px 16px;
}

/* Botón "+ Agregar/Asignar" en el header de card — con acento */
.btn-card-add {
    color: #fff !important;
    font-weight: 700;
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%) !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 0 12px !important;
    height: 28px !important;
    box-shadow: 0 3px 8px -3px rgba(13, 132, 201, 0.55);
    transition: transform 0.14s ease, filter 0.14s ease !important;
}
.btn-card-add:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.08);
}
.btn-card-add:disabled,
.btn-card-add[disabled] {
    color: #fff !important;
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%) !important;
    opacity: 0.85;
    cursor: not-allowed;
    box-shadow: none;
}
.btn-card-add :deep(.anticon) {
    color: #fff !important;
}

/* ==========================================================
   Badges
   ========================================================== */
.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    background: #efe9fb;
    color: #6b4bc9;
    font-size: 10.5px;
    font-weight: 800;
}
.badge--purple { background: #efe9fb; color: #6b4bc9; }
.badge--orange { background: #fdf3e6; color: #a86717; }

/* ==========================================================
   WhatsApp
   ========================================================== */
.wa-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #25d366;
    font-size: 15px;
    transition: transform 0.14s ease;
}
.wa-link:hover { transform: scale(1.15); color: #1da851; }

/* ==========================================================
   Materiales
   ========================================================== */
.mat-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}
.mat-card {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 9px 10px;
    border-radius: 11px;
    background: linear-gradient(180deg, #fffaf1 0%, #fff 70%);
    border: 1px solid #f7e4c4;
    border-left: 3px solid #e08a1e;
    min-width: 0;
}
.mat-card__head { display: flex; align-items: center; gap: 6px; min-width: 0; }
.mat-card__ic {
    width: 20px; height: 20px; flex-shrink: 0; border-radius: 6px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 10px; color: #fff; background: #e08a1e;
}
.mat-card__l {
    flex: 1; font-size: 11px; font-weight: 800; color: #173a5f;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.mat-card__x {
    width: 20px; height: 20px; flex-shrink: 0; border: none; background: transparent;
    color: #c23b3b; cursor: pointer; border-radius: 6px;
    display: inline-flex; align-items: center; justify-content: center;
}
.mat-card__x:hover { background: #fbeaea; }
.mat-card__sub {
    font-size: 10px; color: #7b8a9c; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap;
}
.mat-card__total {
    font-size: 13px; font-weight: 800; color: #a86717;
    letter-spacing: -0.15px; margin-top: auto;
}
.mat-total {
    display: flex; justify-content: flex-end; margin-top: 10px;
    padding-top: 10px; border-top: 1px dashed #e6ecf3;
    font-size: 12.5px; font-weight: 800; color: #173a5f;
}
@media (max-width: 767px) {
    .mat-grid { grid-template-columns: 1fr; }
}

/* ==========================================================
   Descripción
   ========================================================== */
.descripcion {
    margin-bottom: 14px;
    padding: 12px 14px;
    border-radius: 11px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e6ecf3;
}
.descripcion__label {
    display: inline-block; font-size: 10px; font-weight: 800;
    letter-spacing: 0.06em; text-transform: uppercase; color: #7b8a9c;
    margin-bottom: 4px;
}
.descripcion__texto {
    margin: 0; font-size: 13.5px; line-height: 1.5;
    color: #2b3a4f; white-space: pre-line;
}

/* ==========================================================
   Mini-cards
   ========================================================== */
.mini-grid { display: grid; gap: 9px; }
.mini-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.mini {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 11px; border-radius: 10px; background: #fff;
    border: 1px solid #e6ecf3;
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}
.mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px -6px rgba(15, 37, 71, 0.22);
    border-color: var(--c);
}
.mini__ic {
    width: 28px; height: 28px; flex-shrink: 0; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; color: #fff; background: var(--c);
}
.mini__t { display: flex; flex-direction: column; min-width: 0; gap: 0; }
.mini__l {
    font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em;
    font-weight: 700; color: #7b8a9c; line-height: 1.1;
}
.mini__v {
    font-size: 13px; font-weight: 700; color: #2b3a4f;
    word-break: break-word; line-height: 1.25;
}

/* ==========================================================
   Sección mini
   ========================================================== */
.seccion-mini {
    font-weight: 700; font-size: 11px; text-transform: uppercase;
    letter-spacing: 0.05em; color: #7b8a9c;
    margin-top: 16px; margin-bottom: 6px;
}

/* ==========================================================
   Historial de estados
   ========================================================== */
.historial-estados {
    display: flex; flex-direction: column; gap: 10px;
    max-height: 60vh; overflow-y: auto; padding-right: 4px;
    scrollbar-width: thin;
}
.historial-estados::-webkit-scrollbar { width: 6px; }
.historial-estados::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

.hist-item {
    position: relative; display: flex; align-items: flex-start;
    justify-content: space-between; gap: 14px;
    padding: 12px 14px; border-radius: 12px;
    background: #fbfdff; border: 1px solid #e6ecf3;
    border-left: 4px solid var(--hc);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    min-width: 0;
}
.hist-item:hover {
    transform: translateX(2px);
    box-shadow: 0 6px 18px -10px rgba(15, 37, 71, 0.35);
}
.hist-item__main {
    flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px;
}
.hist-item__foto {
    flex-shrink: 0; width: 46px; height: 46px; padding: 0;
    border-radius: 9px; overflow: hidden; border: 1px solid #e6ecf3;
    cursor: pointer; transition: transform 0.14s ease, box-shadow 0.14s ease;
}
.hist-item__foto img { display: block; width: 100%; height: 100%; object-fit: cover; }
.hist-item__foto:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(13, 132, 201, 0.25);
}
.hist-item__comentario {
    margin: 0; font-size: 13px; line-height: 1.5;
    color: #2b3a4f; white-space: pre-line; word-break: break-word;
}
.hist-item__comentario--vacio { color: #7b8a9c; font-style: italic; }
.hist-item__meta {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; color: #7b8a9c; flex-wrap: wrap;
}
.hist-item__avatar {
    width: 20px; height: 20px; border-radius: 50%;
    background: var(--hc); color: #fff; font-size: 10px; font-weight: 800;
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0; box-shadow: 0 1px 3px rgba(15, 37, 71, 0.15);
}
.hist-item__usuario { font-weight: 700; color: #173a5f; font-size: 11.5px; }
.hist-item__sep { opacity: 0.5; }
.hist-item__fecha { font-variant-numeric: tabular-nums; font-size: 11px; }
.hist-item__lado {
    display: flex; flex-direction: column; align-items: flex-end;
    gap: 4px; flex-shrink: 0; max-width: 45%;
}
.hist-item__badge {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 4px 12px; border-radius: 999px; color: #fff;
    background: var(--hc); font-size: 11px; font-weight: 800;
    letter-spacing: 0.03em; text-transform: uppercase; line-height: 1.3;
    box-shadow: 0 2px 6px rgba(15, 37, 71, 0.18);
    white-space: nowrap; text-align: center;
}
.hist-item__origen {
    font-size: 10.5px; color: #7b8a9c;
    white-space: nowrap; text-align: right;
}
.hist-item__origen em {
    font-style: normal; font-weight: 700; color: #2b3a4f;
}

/* ==========================================================
   Caja vacía
   ========================================================== */
.vacio-box {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 8px; padding: 20px 12px;
    border-radius: 11px; border: 1px dashed #cdd8e3;
    background: #f5f8fb; color: #7b8a9c; font-size: 12px;
}
.vacio-box .anticon { font-size: 20px; opacity: 0.5; }

/* ==========================================================
   MODALES "modal-tarea" — estilo común
   ========================================================== */
.modal-tarea :deep(.ant-modal-content) {
    padding: 0; overflow: hidden; border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}
.modal-tarea :deep(.ant-modal-body) { padding: 0; }

.modal-tarea__wrap {
    display: flex; flex-direction: column; background: #f5f8fb;
}

/* Header */
.modal-head {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f8fb 100%);
    border-bottom: 1px solid #e2e8f0; flex-shrink: 0;
}
.modal-head__ico {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px; color: #fff;
    background: linear-gradient(135deg, var(--mh1, #0d84c9) 0%, var(--mh2, #0f6fb0) 100%);
    box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--mh1, #0d84c9) 60%, transparent);
    flex-shrink: 0;
}
.modal-head__meta { flex: 1; min-width: 0; }
.modal-head__titulo {
    font-size: 17px; font-weight: 800; color: #173a5f;
    margin: 0; line-height: 1.2; letter-spacing: -0.2px;
}
.modal-head__sub {
    margin: 2px 0 0; font-size: 12px; color: #7b8a9c; line-height: 1.3;
}
.modal-head__sub strong { color: var(--mh1, #0d84c9); font-weight: 800; }
.modal-head__close {
    width: 32px; height: 32px; border-radius: 9px;
    border: 1px solid #e2e8f0; background: #fff; color: #64748b;
    cursor: pointer; font-size: 13px;
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: background 0.14s ease, color 0.14s ease,
        border-color 0.14s ease, transform 0.14s ease;
}
.modal-head__close:hover:not(:disabled) {
    background: #fdecec; border-color: #fecaca; color: #d64545;
    transform: rotate(90deg);
}
.modal-head__close:disabled { opacity: 0.5; cursor: not-allowed; }

/* Body */
.modal-body {
    display: flex; flex-direction: column; gap: 12px;
    padding: 16px 20px; background: #fff;
}

.row { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: start; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; align-items: start; }

.label {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.05em; color: #173a5f;
}
.label .anticon { color: var(--mh1, #0d84c9); font-size: 11px; }

/* Badge dentro de label */
.label .badge {
    margin-left: 4px; height: 17px; min-width: 20px;
    background: #e6f0f9; color: #0d6ca6;
}

/* Preview fecha */
.preview-fecha {
    display: inline-flex; align-items: center; gap: 5px;
    margin-top: 2px; padding: 3px 9px; border-radius: 999px;
    background: color-mix(in srgb, var(--pc) 12%, transparent);
    color: var(--pc); font-size: 11px; font-weight: 800;
    align-self: flex-start;
    box-shadow: 0 1px 3px color-mix(in srgb, var(--pc) 20%, transparent);
}
.preview-fecha .anticon { font-size: 11px; }

/* Prioridades radio-cards */
.sin-prioridades {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 9px;
    background: #f8fafc; border: 1px dashed #cdd8e3;
    color: #7b8a9c; font-size: 12px;
}
.prioridades {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px;
}
.prio-card {
    position: relative; display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 10px; background: #fff;
    border: 1.5px solid #e2e8f0; cursor: pointer;
    transition: border-color 0.14s ease, background 0.14s ease,
        transform 0.14s ease, box-shadow 0.14s ease;
    user-select: none; min-width: 0;
}
.prio-card:hover {
    border-color: color-mix(in srgb, var(--pc) 55%, #e2e8f0);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px -6px color-mix(in srgb, var(--pc) 40%, transparent);
}
.prio-card--active {
    border-color: var(--pc);
    background: color-mix(in srgb, var(--pc) 6%, #fff);
    box-shadow: 0 4px 12px -6px color-mix(in srgb, var(--pc) 45%, transparent);
}
.prio-card__input { position: absolute; opacity: 0; pointer-events: none; }
.prio-card__dot {
    width: 12px; height: 12px; flex-shrink: 0; border-radius: 50%;
    background: var(--pc);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--pc) 22%, transparent);
    transition: box-shadow 0.14s ease;
}
.prio-card--active .prio-card__dot {
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--pc) 30%, transparent);
}
.prio-card__label {
    font-size: 11.5px; font-weight: 700; color: #2b3a4f;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.prio-card--active .prio-card__label { color: #173a5f; }

/* Usuario select option */
.usuario-op {
    display: inline-flex; align-items: center; gap: 8px; font-weight: 600;
}
.usuario-op__av {
    width: 20px; height: 20px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    color: #fff; font-size: 10px; font-weight: 800; flex-shrink: 0;
}

/* Vista previa del usuario asignado */
.usuario-preview {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 12px;
    background: linear-gradient(180deg, #f5fbf8 0%, #eef9f5 100%);
    border: 1px solid #b8e4d3;
    animation: fadePreview 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes fadePreview {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}
.usuario-preview__av {
    width: 42px; height: 42px; flex-shrink: 0; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 4px 10px -3px rgba(31, 158, 134, 0.5);
}
.usuario-preview__info {
    display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1;
}
.usuario-preview__nombre {
    font-size: 14px; font-weight: 800; color: #173a5f; line-height: 1.2;
}
.usuario-preview__sub {
    font-size: 11.5px; color: #16806c;
    display: inline-flex; align-items: center; gap: 5px;
}

/* Check principal */
.check-principal {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 10px;
    background: #f5fbf8; border: 1px solid #b8e4d3;
    cursor: pointer; user-select: none;
    transition: background 0.14s ease, border-color 0.14s ease;
}
.check-principal:hover { background: #eef9f5; border-color: #1f9e86; }
.check-principal__input { position: absolute; opacity: 0; pointer-events: none; }
.check-principal__box {
    width: 22px; height: 22px; flex-shrink: 0; border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    background: #fff; border: 1.5px solid #cbd5e1; color: transparent;
    transition: all 0.14s ease;
}
.check-principal__input:checked + .check-principal__box {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    border-color: #1f9e86; color: #fff;
}
.check-principal__txt {
    display: flex; flex-direction: column; gap: 1px; min-width: 0;
}
.check-principal__txt strong {
    font-size: 12.5px; font-weight: 800; color: #173a5f; line-height: 1.15;
}
.check-principal__txt small {
    font-size: 10.5px; color: #7b8a9c; line-height: 1.15;
}
.hint-principal {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 6px; padding: 5px 10px; border-radius: 8px;
    background: #eef9f5; color: #16806c;
    font-size: 11px; font-weight: 600;
}

/* Vista previa del material del catálogo */
.mat-preview-card {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 12px;
    background: linear-gradient(180deg, #fffaf1 0%, #fdf7ec 100%);
    border: 1px solid #f7e4c4;
    animation: fadePreview 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.mat-preview-card__ic {
    width: 38px; height: 38px; flex-shrink: 0; border-radius: 11px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 16px; color: #fff;
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%);
    box-shadow: 0 4px 10px -3px rgba(224, 138, 30, 0.5);
}
.mat-preview-card__info {
    display: flex; flex-direction: column; gap: 2px; min-width: 0;
}
.mat-preview-card__nombre {
    font-size: 13.5px; font-weight: 800; color: #173a5f; line-height: 1.2;
}
.mat-preview-card__sub { font-size: 11.5px; color: #a86717; font-weight: 600; }

/* Preview-bar común */
.preview-bar {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px; border-radius: 10px;
    background: linear-gradient(135deg, #f5f8fb 0%, #eef4fb 100%);
    border: 1px dashed #dbe3ec; flex-wrap: wrap;
}
.preview-bar__lbl {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.05em; color: #7b8a9c; flex-shrink: 0;
}
.preview-bar__lbl .anticon { color: #1f9e86; }
.preview-bar__chips { display: flex; flex-wrap: wrap; gap: 5px; }
.preview__chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 999px;
    background: color-mix(in srgb, var(--pc) 12%, transparent);
    color: var(--pc); font-size: 10.5px; font-weight: 800;
}
.preview__chip .anticon { font-size: 10px; }

/* Total preview materiales */
.total-preview {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px; border-radius: 11px;
    background: linear-gradient(135deg, #fffaf1 0%, #fdf3e6 100%);
    border: 1px solid #f7e4c4;
    animation: fadePreview 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.total-preview__l {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.05em; color: #a86717;
}
.total-preview__v { font-size: 18px; font-weight: 800; color: #173a5f; }

/* Footer común */
.modal-footer {
    display: flex; justify-content: flex-end; gap: 10px;
    padding: 12px 20px; background: #fff;
    border-top: 1px solid #e2e8f0; flex-shrink: 0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

/* Botones submit con acento según el modal */
.btn-submit {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%) !important;
    border-color: #0d84c9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}
.btn-submit:hover:not(:disabled) {
    transform: translateY(-1px); filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(13, 132, 201, 0.75);
}
.btn-submit--purple {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%) !important;
    border-color: #6b4bc9 !important;
    box-shadow: 0 6px 16px -6px rgba(107, 75, 201, 0.65);
}
.btn-submit--purple:hover:not(:disabled) {
    box-shadow: 0 8px 20px -6px rgba(107, 75, 201, 0.75);
}
.btn-submit--green {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%) !important;
    border-color: #1f9e86 !important;
    box-shadow: 0 6px 16px -6px rgba(31, 158, 134, 0.65);
}
.btn-submit--green:hover:not(:disabled) {
    box-shadow: 0 8px 20px -6px rgba(31, 158, 134, 0.75);
}
.btn-submit--orange {
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%) !important;
    border-color: #e08a1e !important;
    box-shadow: 0 6px 16px -6px rgba(224, 138, 30, 0.65);
}
.btn-submit--orange:hover:not(:disabled) {
    box-shadow: 0 8px 20px -6px rgba(224, 138, 30, 0.75);
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 1199px) {
    .grid-ficha { grid-template-columns: 1fr; }
}

@media (max-width: 767px) {
    .mini-grid--3 { grid-template-columns: 1fr; }
    .hist-item { flex-direction: column; gap: 10px; }
    .hist-item__lado { align-items: flex-start; max-width: 100%; }

    .btn-hero {
        width: 100%;
        justify-content: flex-start;
    }

    .btn-more {
        width: 100%;
    }
}

@media (max-width: 575px) {
    .modal-head { padding: 12px 14px; }
    .modal-head__titulo { font-size: 15px; }
    .modal-body { padding: 14px; gap: 10px; }
    .grid-2, .grid-3 { grid-template-columns: 1fr; gap: 12px; }
    .prioridades { grid-template-columns: 1fr; }
    .modal-footer {
        padding: 10px 14px; flex-direction: column-reverse;
    }
    .modal-footer .ant-btn { width: 100%; }
}
</style>