<script setup>
import { computed, h, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CalendarOutlined,
    CheckCircleFilled,
    CheckCircleOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    DollarOutlined,
    EditOutlined,
    EllipsisOutlined,
    EnvironmentOutlined,
    EyeOutlined,
    FileDoneOutlined,
    FileTextOutlined,
    FlagOutlined,
    InboxOutlined,
    MessageOutlined,
    PlusOutlined,
    StarFilled,
    SwapOutlined,
    TagOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import ModalFicha from '@/Components/ModalFicha.vue';
import CampoEvidencia from '@/Components/CampoEvidencia.vue';
import { usePermisos } from '@/composables/usePermisos';
import { hoyISO, reglaDespuesDe, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    mantenimiento: { type: Object, required: true },
    transicionesPosibles: { type: Array, default: () => [] },
    faltantesCierre: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const m = computed(() => props.mantenimiento);
const confirmar = ref(null);

const ETIQUETA_ESTADO = {
    autorizado: 'Autorizar', asignado: 'Marcar asignado', en_proceso: 'Iniciar trabajo',
    en_espera_refaccion: 'En espera de refacción', fuera_de_servicio: 'Equipo fuera de servicio',
    realizado: 'Marcar realizado', supervisado: 'Supervisar', cerrado: 'Cerrar orden',
    reprogramado: 'Reprogramar', cancelado: 'Cancelar orden',
};
const ICONO_ESTADO = {
    autorizado: CheckCircleOutlined, asignado: UserOutlined, en_proceso: ToolOutlined,
    en_espera_refaccion: ClockCircleOutlined, fuera_de_servicio: ToolOutlined,
    realizado: CheckCircleOutlined, supervisado: FlagOutlined, cerrado: CheckCircleOutlined,
    reprogramado: CalendarOutlined, cancelado: DeleteOutlined,
};

const ESTADO_COLOR = {
    autorizado: 'cyan', asignado: 'blue', en_proceso: 'processing',
    en_espera_refaccion: 'orange', fuera_de_servicio: 'volcano', realizado: 'lime',
    supervisado: 'geekblue', cerrado: 'green', reprogramado: 'purple', cancelado: 'red',
};

const ESTADO_COLOR_HEX = {
    solicitado: '#64748b', autorizado: '#0ea5e9', asignado: '#0d84c9', en_proceso: '#e08a1e',
    en_espera_refaccion: '#a86717', fuera_de_servicio: '#d64545', realizado: '#16a34a',
    supervisado: '#7c3aed', cerrado: '#1f9e86', reprogramado: '#6b4bc9', cancelado: '#991b1b',
};

const estadoColor = computed(() => ESTADO_COLOR[m.value.estado?.clave] ?? 'default');
const estadoClave = computed(() => m.value.estado?.clave);
const esCerrada = computed(() => ['cerrado', 'cancelado'].includes(estadoClave.value));
const enProceso = computed(() => estadoClave.value === 'en_proceso');
const puedeGestionarEjecucion = computed(() => !['realizado', 'supervisado', 'cerrado', 'cancelado'].includes(estadoClave.value));

const fecha = (v) => (v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado');
const soloFecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const fechaHora = (v) =>
    v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado';
const moneda = (v) => (v == null ? 'No especificado' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v));
const dato = (v) => v || 'No especificado';

const cronologia = computed(() => {
    const deEstados = (m.value.historial_estados ?? []).map((hh) => ({
        key: `estado-${hh.id}`,
        fecha: hh.cambiado_at,
        titulo: hh.estado_destino?.nombre ?? 'Estado actualizado',
        origen: hh.estado_origen?.nombre ?? null,
        nota: hh.nota,
        usuario: hh.cambiado_por?.nombre ?? 'Sistema',
        color: 'blue',
    }));
    const deReprogramaciones = (m.value.reprogramaciones ?? []).map((r) => ({
        key: `reprog-${r.id}`,
        fecha: r.created_at,
        titulo: 'Reprogramación',
        origen: null,
        nota: `${soloFecha(r.inicio_anterior)} → ${soloFecha(r.inicio_nuevo)} · ${r.motivo}`,
        usuario: r.reprogramado_por?.nombre ?? 'Sistema',
        color: 'orange',
    }));
    return [...deEstados, ...deReprogramaciones].sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
});

// ===== infoOrden CONDICIONAL: solo muestra plan y solicitud si existen =====
const infoOrden = computed(() => {
    const base = [
        { icono: TagOutlined, label: 'Tipo', valor: dato(m.value.tipo?.nombre), color: '#0d84c9' },
        { icono: FlagOutlined, label: 'Prioridad', valor: dato(m.value.prioridad?.nombre), color: m.value.prioridad?.color || '#d64545' },
        { icono: CalendarOutlined, label: 'Programado', valor: fecha(m.value.programado_inicio), color: '#6b4bc9' },
    ];
    if (m.value.solicitud?.folio) {
        base.push({
            icono: FileDoneOutlined,
            label: 'Solicitud origen',
            valor: m.value.solicitud.folio,
            color: '#e08a1e',
        });
    }
    if (m.value.plan?.nombre) {
        base.push({
            icono: CalendarOutlined,
            label: 'Plan preventivo',
            valor: m.value.plan.nombre,
            color: '#1f9e86',
        });
    }
    return base;
});

const infoUbicacion = computed(() => [
    m.value.equipo
        ? { icono: ToolOutlined, label: 'Equipo', valor: `${m.value.equipo.codigo_activo} — ${m.value.equipo.descripcion}`, color: '#0d84c9' }
        : { icono: EnvironmentOutlined, label: 'Instalación', valor: dato(m.value.ubicacion?.nombre), color: '#0d84c9' },
    { icono: ApartmentOutlined, label: 'Sucursal', valor: dato(m.value.sucursal?.nombre), color: '#1f9e86' },
]);

const infoResponsables = computed(() => {
    const base = [
        {
            icono: UserOutlined,
            label: 'Creado por',
            valor: m.value.creado?.usuario
                ? `${m.value.creado.usuario} · ${fechaHora(m.value.creado.fecha)}`
                : `${dato(m.value.creado_por?.nombre)} · ${fechaHora(m.value.created_at)}`,
            color: '#173a5f',
        },
    ];
    if (m.value.supervisor?.nombre) {
        base.push({
            icono: UserOutlined,
            label: 'Supervisor',
            valor: m.value.supervisor.nombre,
            color: '#1f9e86',
        });
    }
    return base;
});

const tecnicosActivos = computed(() => (m.value.asignaciones ?? []).filter((a) => !a.desasignado_at));

const tieneDiagnostico = computed(() =>
    Boolean(
        m.value.diagnostico ||
        m.value.descripcion_trabajo ||
        m.value.observaciones ||
        m.value.condicion_final ||
        m.value.costo_mano_obra ||
        m.value.costo_otros,
    ),
);

const puedeDiagnosticar = computed(() => {
    if (!puede('mantenimientos.editar')) return false;
    if (esCerrada.value) return false;
    if (!enProceso.value) return false;
    if (tecnicosActivos.value.length === 0) return false;
    return true;
});

const motivoDiagnosticar = computed(() => {
    if (!puede('mantenimientos.editar')) return 'No tienes permiso para capturar el diagnóstico.';
    if (esCerrada.value) return 'La orden está cerrada o cancelada.';
    if (tecnicosActivos.value.length === 0) return 'Asigna primero un técnico para poder diagnosticar.';
    if (!enProceso.value) return 'La orden debe estar en proceso para capturar el diagnóstico.';
    return '';
});

const resumenTrabajo = computed(() => [
    { key: 'diagnostico', icono: FileDoneOutlined, label: 'Diagnóstico', valor: m.value.diagnostico, color: '#6b4bc9' },
    { key: 'actividades', icono: ToolOutlined, label: 'Actividades', valor: m.value.descripcion_trabajo, color: '#0d84c9' },
    { key: 'observaciones', icono: FileTextOutlined, label: 'Observaciones', valor: m.value.observaciones, color: '#173a5f' },
    { key: 'condicion', icono: CheckCircleOutlined, label: 'Condición final', valor: m.value.condicion_final, color: '#1f9e86' },
    { key: 'mano_obra', icono: DollarOutlined, label: 'Mano de obra', valor: moneda(m.value.costo_mano_obra), color: '#e08a1e', mono: true },
    { key: 'otros_costos', icono: DollarOutlined, label: 'Otros costos', valor: moneda(m.value.costo_otros), color: '#a86717', mono: true },
]);

const LIMITE_MAT = 6;
const materialesVisibles = computed(() => (m.value.materiales ?? []).slice(0, LIMITE_MAT));
const materialesResto = computed(() => Math.max(0, (m.value.materiales?.length ?? 0) - LIMITE_MAT));
const modalMaterialesTodos = ref(false);

const totalMateriales = computed(() => {
    return (m.value.materiales ?? []).reduce((s, mat) => {
        const cant = Number(mat.cantidad) || 0;
        const costo = Number(mat.costo_unitario) || 0;
        return s + cant * costo;
    }, 0);
});

// --- Cambiar estado ---
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
        formEstado.setError('evidencia', 'Adjunta una evidencia para este movimiento.');
        return;
    }
    formEstado.post(route('mantenimientos.transicion', m.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (modalEstado.value = false),
    });
};

/* ==========================================================
   Botones de transición con estilo hero
   ========================================================== */
const botonesTransicion = computed(() =>
    props.transicionesPosibles
        .filter((slug) => slug !== 'reprogramado' && slug !== 'autorizado')
        .map((slug, i) => {
            const colorHex = ESTADO_COLOR_HEX[slug] || '#0d84c9';
            const colorMap = {
                en_proceso: { c1: '#e08a1e', c2: '#a86717' },
                en_espera_refaccion: { c1: '#e08a1e', c2: '#a86717' },
                fuera_de_servicio: { c1: '#d64545', c2: '#b91c1c' },
                realizado: { c1: '#16a34a', c2: '#15803d' },
                supervisado: { c1: '#7c3aed', c2: '#6d28d9' },
                cerrado: { c1: '#1f9e86', c2: '#16806c' },
                cancelado: { c1: '#d64545', c2: '#b91c1c' },
                asignado: { c1: '#0d84c9', c2: '#0a6ba6' },
            };
            const colors = colorMap[slug] || { c1: colorHex, c2: colorHex };
            return {
                slug,
                label: ETIQUETA_ESTADO[slug] ?? slug,
                icono: ICONO_ESTADO[slug] ?? SwapOutlined,
                variante: slug === 'cancelado' ? 'danger' : (i === 0 ? 'primary' : 'default'),
                color: colors.c1,
                color2: colors.c2,
                deshabilitado: slug === 'realizado' && !tieneDiagnostico.value,
                motivo: slug === 'realizado' && !tieneDiagnostico.value
                    ? 'Captura primero el diagnóstico para poder marcar la orden como realizada.'
                    : '',
            };
        }),
);

// --- Reprogramar ---
const modalReprogramar = ref(false);
const formReprogramar = useForm({ programado_inicio: '', programado_fin: '', motivo: '', evidencia: null });
const reglasReprogramar = reactive({
    programado_inicio: [{ required: true, message: 'Indica la nueva fecha.' }, reglaNoPasada('La nueva fecha no puede ser anterior a hoy.')],
    programado_fin: [reglaDespuesDe(() => formReprogramar.programado_inicio, 'El fin debe ser posterior al inicio.', true)],
    motivo: [{ required: true, message: 'Indica el motivo.' }],
});

const abrirReprogramar = () => {
    formReprogramar.reset();
    formReprogramar.clearErrors();
    modalReprogramar.value = true;
};

const cerrarReprogramar = () => {
    modalReprogramar.value = false;
};

const reprogramar = () => {
    formReprogramar.clearErrors();
    if (!formReprogramar.evidencia) {
        formReprogramar.setError('evidencia', 'Adjunta una evidencia para reprogramar.');
        return;
    }
    formReprogramar.post(route('mantenimientos.reprogramar', m.value.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => (modalReprogramar.value = false),
    });
};

// --- Diagnóstico y trabajo ---
const modalTrabajo = ref(false);
const modoTrabajo = ref('ver');
const formTrabajo = useForm({
    diagnostico: m.value.diagnostico ?? '',
    descripcion_trabajo: m.value.descripcion_trabajo ?? '',
    observaciones: m.value.observaciones ?? '',
    condicion_final: m.value.condicion_final ?? '',
    costo_mano_obra: m.value.costo_mano_obra ?? '',
    costo_otros: m.value.costo_otros ?? '',
});

const abrirVerTrabajo = () => {
    formTrabajo.clearErrors();
    formTrabajo.diagnostico = m.value.diagnostico ?? '';
    formTrabajo.descripcion_trabajo = m.value.descripcion_trabajo ?? '';
    formTrabajo.observaciones = m.value.observaciones ?? '';
    formTrabajo.condicion_final = m.value.condicion_final ?? '';
    formTrabajo.costo_mano_obra = m.value.costo_mano_obra ?? '';
    formTrabajo.costo_otros = m.value.costo_otros ?? '';
    modoTrabajo.value = 'ver';
    modalTrabajo.value = true;
};

const abrirEditarTrabajo = () => {
    formTrabajo.clearErrors();
    formTrabajo.diagnostico = m.value.diagnostico ?? '';
    formTrabajo.descripcion_trabajo = m.value.descripcion_trabajo ?? '';
    formTrabajo.observaciones = m.value.observaciones ?? '';
    formTrabajo.condicion_final = m.value.condicion_final ?? '';
    formTrabajo.costo_mano_obra = m.value.costo_mano_obra ?? '';
    formTrabajo.costo_otros = m.value.costo_otros ?? '';
    modoTrabajo.value = 'editar';
    modalTrabajo.value = true;
};

const guardarTrabajo = () =>
    formTrabajo.put(route('mantenimientos.update', m.value.id), {
        preserveScroll: true,
        onSuccess: () => (modalTrabajo.value = false),
    });

const cerrarTrabajo = () => {
    modalTrabajo.value = false;
    modoTrabajo.value = 'ver';
};

// --- Técnico ---
const modalTecnico = ref(false);
const formTecnico = useForm({ tecnico_id: undefined, es_principal: false });
const tecnicoSeleccionado = computed(() =>
    (props.catalogos.tecnicos ?? []).find((t) => t.id === formTecnico.tecnico_id),
);

const abrirTecnico = () => {
    formTecnico.reset();
    formTecnico.clearErrors();
    modalTecnico.value = true;
};

const cerrarTecnico = () => {
    modalTecnico.value = false;
};

const asignarTecnico = () => {
    formTecnico.post(route('mantenimientos.asignaciones.store', m.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalTecnico.value = false;
            formTecnico.reset();
        },
    });
};

const quitarTecnico = async (asig) => {
    const ok = await confirmar.value.abrir({ titulo: `Retirar a ${asig.tecnico?.nombre}`, confirmar: 'Retirar', peligro: true });
    if (ok) router.delete(route('mantenimientos.asignaciones.destroy', [m.value.id, asig.id]), { preserveScroll: true });
};

// --- Materiales ---
const modalMaterial = ref(false);
const formMaterial = useForm({ material_id: undefined, descripcion: '', cantidad: 1, unidad: 'pza', costo_unitario: '', notas: '' });
const reglasMaterial = reactive({ cantidad: [{ required: true, message: 'Indica la cantidad.' }] });

const materialSeleccionado = computed(() =>
    (props.catalogos.materiales ?? []).find((x) => x.id === formMaterial.material_id),
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
};

const agregarMaterial = () => {
    formMaterial.post(route('mantenimientos.materiales.store', m.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalMaterial.value = false;
            formMaterial.reset();
        },
    });
};

const quitarMaterial = (mat) =>
    router.delete(route('mantenimientos.materiales.destroy', [m.value.id, mat.id]), { preserveScroll: true });

const onMaterialSel = (id) => {
    const mat = (props.catalogos.materiales ?? []).find((x) => x.id === id);
    if (mat) {
        formMaterial.unidad = mat.unidad;
        if (mat.costo_referencia) formMaterial.costo_unitario = mat.costo_referencia;
    }
};

// --- Observación ---
const modalObs = ref(false);
const formObs = useForm({ tipo: 'comentario', cuerpo: '' });
const reglasObs = reactive({ cuerpo: [{ required: true, message: 'Escribe la observación.' }] });

const abrirObs = () => {
    formObs.reset();
    formObs.clearErrors();
    formObs.tipo = 'comentario';
    modalObs.value = true;
};

const cerrarObs = () => {
    modalObs.value = false;
};

const agregarObs = () => {
    formObs.clearErrors();
    formObs.post(route('mantenimientos.observaciones.store', m.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalObs.value = false;
            formObs.reset();
        },
    });
};

const modalEvidencias = ref(false);
const modalCronologia = ref(false);

const darBaja = async () => {
    const ok = await confirmar.value.abrir({ titulo: `Eliminar orden ${m.value.folio}`, confirmar: 'Eliminar', peligro: true });
    if (ok) router.delete(route('mantenimientos.destroy', m.value.id));
};

const nombreAsignador = (t) => t.asignado_por?.nombre ?? 'Sistema';

const bitacoraTotal = computed(() => m.value.bitacora?.length ?? 0);

const fotoBitacora = ref(null);
const modalFotoBitacora = ref(false);
const abrirFotoBitacora = (doc) => {
    fotoBitacora.value = doc;
    modalFotoBitacora.value = true;
};
</script>

<template>

    <Head :title="`Orden ${m.folio}`" />

    <AppLayout>
        <div class="ficha-compacta">
            <FichaEncabezado :titulo="m.folio"
                :subtitulo="m.equipo ? `${m.equipo.codigo_activo} · ${m.equipo.descripcion}` : (m.ubicacion?.nombre ?? '')"
                :icono="ToolOutlined" volver="mantenimientos.index" :sello="sello">
                <template #tags>
                    <a-tag :color="estadoColor" class="estado-tag">
                        <component :is="ICONO_ESTADO[m.estado?.clave] || ClockCircleOutlined" />
                        {{ m.estado?.nombre }}
                    </a-tag>
                    <a-tag v-if="m.prioridad" :color="m.prioridad.color || 'default'" class="prio-tag">
                        <FlagOutlined /> {{ m.prioridad.nombre }}
                    </a-tag>
                    <a-tag v-if="m.tipo"
                        :color="m.tipo.categoria === 'urgente' || m.tipo.categoria === 'emergencia' ? 'volcano' : m.tipo.categoria === 'preventivo' ? 'green' : 'blue'"
                        class="tipo-tag">
                        <ToolOutlined /> {{ m.tipo.nombre }}
                    </a-tag>
                </template>
                <template #acciones>
                    <template v-if="puede('mantenimientos.editar') && !esCerrada">
                        <a-tooltip v-for="b in botonesTransicion" :key="b.slug" :title="b.motivo">
                            <button type="button" class="btn-hero" :class="{
                                'btn-hero--primary': b.variante === 'primary',
                                'btn-hero--danger': b.variante === 'danger',
                                'btn-hero--default': b.variante === 'default',
                            }" :disabled="b.deshabilitado" :style="{ '--hc': b.color, '--hc2': b.color2 }"
                                @click="!b.deshabilitado && abrirEstado(b.slug)">
                                <span class="btn-hero__ic">
                                    <component :is="b.icono" />
                                </span>
                                <span class="btn-hero__txt">
                                    <span class="btn-hero__l">{{ b.label }}</span>
                                    <span class="btn-hero__s">Cambiar estado</span>
                                </span>
                            </button>
                        </a-tooltip>

                        <button type="button" class="btn-hero btn-hero--reprog" @click="abrirReprogramar">
                            <span class="btn-hero__ic">
                                <CalendarOutlined />
                            </span>
                            <span class="btn-hero__txt">
                                <span class="btn-hero__l">Reprogramar</span>
                                <span class="btn-hero__s">Cambiar fecha</span>
                            </span>
                        </button>
                    </template>

                    <a-dropdown v-if="puede('mantenimientos.editar') && !esCerrada">
                        <button type="button" class="btn-more">
                            <EllipsisOutlined />
                        </button>
                        <template #overlay>
                            <a-menu
                                :items="[{ key: 'baja', label: 'Eliminar orden', danger: true, icon: () => h(DeleteOutlined) }]"
                                @click="darBaja" />
                        </template>
                    </a-dropdown>
                </template>
            </FichaEncabezado>

            <div class="problema-card" :class="{ 'problema-card--cerrada': esCerrada }">
                <div class="problema-card__icono">
                    <component :is="m.equipo ? ToolOutlined : EnvironmentOutlined" />
                </div>
                <div class="problema-card__meta">
                    <div class="problema-card__objetivo">
                        {{ m.equipo ? `${m.equipo.codigo_activo} — ${m.equipo.descripcion}` : (m.ubicacion?.nombre ??
                        '') }}
                    </div>
                    <div class="problema-card__desc">
                        {{ m.problema_reportado || 'ORDEN DE MANTENIMIENTO SIN DESCRIPCIÓN DE PROBLEMA.' }}
                    </div>
                </div>
            </div>

            <div class="grid-ficha">
                <div class="col-izq">
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <ToolOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Información de la orden</div>
                                <div class="card__sub">Datos generales del servicio</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--2">
                                <div v-for="d in infoOrden" :key="d.label" class="mini" :style="{ '--c': d.color }">
                                    <span class="mini__ic">
                                        <component :is="d.icono" />
                                    </span>
                                    <span class="mini__t">
                                        <span class="mini__l">{{ d.label }}</span>
                                        <span class="mini__v">{{ d.valor }}</span>
                                    </span>
                                </div>
                            </div>
                            <div v-if="m.normas?.length" class="normas-inline">
                                <span class="normas-inline__l">Normas aplicadas:</span>
                                <a-tag v-for="n in m.normas" :key="n.id" color="blue">{{ n.codigo }}</a-tag>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <EnvironmentOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Ubicación y responsables</div>
                                <div class="card__sub">Dónde se realiza y quién participa</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--2">
                                <div v-for="d in infoUbicacion" :key="d.label" class="mini" :style="{ '--c': d.color }">
                                    <span class="mini__ic">
                                        <component :is="d.icono" />
                                    </span>
                                    <span class="mini__t">
                                        <span class="mini__l">{{ d.label }}</span>
                                        <span class="mini__v">{{ d.valor }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="mini-grid mini-grid--full mt-grid">
                                <div v-for="d in infoResponsables" :key="d.label" class="mini"
                                    :style="{ '--c': d.color }">
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

                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <UserOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Técnicos asignados
                                    <span v-if="tecnicosActivos.length" class="badge badge--green">{{
                                        tecnicosActivos.length
                                        }}</span>
                                </div>
                                <div class="card__sub">Personal que atiende la orden</div>
                            </div>
                            <a-button v-if="puede('mantenimientos.asignar') && puedeGestionarEjecucion"
                                class="card__extra btn-asignar-tecnico" size="small" type="text" @click="abrirTecnico">
                                <template #icon>
                                    <PlusOutlined />
                                </template>
                                Asignar
                            </a-button>
                        </div>
                        <div class="card__body">
                            <div v-if="tecnicosActivos.length" class="tecnicos">
                                <div v-for="t in tecnicosActivos" :key="t.id" class="tecnico">
                                    <div class="tecnico__av">
                                        <UserOutlined />
                                    </div>
                                    <div class="tecnico__t">
                                        <div class="tecnico__nombre">
                                            {{ t.tecnico?.nombre }}
                                            <a-tag v-if="t.es_principal" color="blue"
                                                class="tecnico__tag">Principal</a-tag>
                                        </div>
                                        <div class="tecnico__sub">
                                            Asignado por {{ nombreAsignador(t) }} · {{ fechaHora(t.asignado_at) }}
                                        </div>
                                    </div>
                                    <button v-if="puede('mantenimientos.asignar') && puedeGestionarEjecucion"
                                        type="button" class="tecnico__x" title="Retirar" @click="quitarTecnico(t)">
                                        <DeleteOutlined />
                                    </button>
                                </div>
                            </div>
                            <div v-else class="vacio-box">
                                <UserOutlined />
                                <span>Sin técnicos asignados</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-der">
                    <!-- ==========================================================
                         BITÁCORA (compacta, sin tag, thumb más grande)
                         ========================================================== -->
                    <div class="card card--bitacora">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <ClockCircleOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Bitácora
                                    <span v-if="bitacoraTotal" class="badge badge--blue">{{ bitacoraTotal }}</span>
                                </div>
                                <div class="card__sub">Notas rápidas, seguimiento y supervisiones</div>
                            </div>
                            <a-button v-if="puede('mantenimientos.editar') && !esCerrada" class="card__extra"
                                size="small" type="text" @click="abrirObs">
                                <template #icon>
                                    <PlusOutlined />
                                </template>
                                Agregar
                            </a-button>
                        </div>
                        <div class="card__body card__body--bitacora">
                            <div v-if="m.bitacora?.length" class="mb-list mb-list--inline">
                                <article v-for="(o, i) in (m.bitacora ?? [])" :key="o.id" class="mb-item"
                                    :class="{ 'mb-item--supervision': o.tipo === 'supervision' }">
                                    <div class="mb-item__rail">
                                        <span class="mb-item__ic">
                                            <component :is="o.tipo === 'supervision' ? FlagOutlined : MessageOutlined" />
                                        </span>
                                        <span v-if="i < m.bitacora.length - 1" class="mb-item__linea"></span>
                                    </div>
                                    <div class="mb-item__card">
                                        <div class="mb-item__card-head">
                                            <span class="mb-item__avatar"
                                                :style="{ background: o.tipo === 'supervision' ? 'linear-gradient(135deg, #6b4bc9, #563a9e)' : 'linear-gradient(135deg, #0d84c9, #0a6ba6)' }">
                                                {{ (o.usuario?.nombre ?? 'S').charAt(0).toUpperCase() }}
                                            </span>
                                            <div class="mb-item__user">
                                                <span class="mb-item__user-name">{{ o.usuario?.nombre ?? 'Sistema' }}</span>
                                                <span class="mb-item__user-time">
                                                    <ClockCircleOutlined /> {{ fecha(o.created_at) }}
                                                </span>
                                            </div>
                                            <!-- Thumb más grande, sin tag al lado -->
                                            <button v-if="o.evidencia" type="button" class="mb-item__thumb"
                                                title="Ver evidencia" @click="abrirFotoBitacora(o.evidencia)">
                                                <img :src="o.evidencia.url" alt="Evidencia" />
                                                <span class="mb-item__thumb-overlay">
                                                    <EyeOutlined />
                                                </span>
                                            </button>
                                        </div>
                                        <p class="mb-item__cuerpo">{{ o.cuerpo }}</p>
                                    </div>
                                </article>
                            </div>
                            <div v-else class="mb-vacio mb-vacio--inline">
                                <div class="mb-vacio__ic">
                                    <ClockCircleOutlined />
                                </div>
                                <div class="mb-vacio__t">Sin movimientos registrados</div>
                                <div class="mb-vacio__s">Aquí verás las notas rápidas, seguimiento y supervisiones que
                                    se registren para esta orden.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card card--trabajo">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <FileDoneOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Diagnóstico y trabajo</div>
                                <div class="card__sub">Resultado del servicio</div>
                            </div>
                            <a-button v-if="tieneDiagnostico && puede('mantenimientos.editar') && !esCerrada"
                                class="card__extra" size="small" type="text" @click="abrirEditarTrabajo">
                                <template #icon>
                                    <EditOutlined />
                                </template>
                                Editar
                            </a-button>
                        </div>
                        <div class="card__body">
                            <div v-if="!tieneDiagnostico" class="diagnostico-vacio">
                                <span class="diagnostico-vacio__ico">
                                    <FileDoneOutlined />
                                </span>
                                <div class="diagnostico-vacio__txt">
                                    <div class="diagnostico-vacio__t">Sin diagnóstico registrado</div>
                                    <div class="diagnostico-vacio__s">
                                        {{
                                            puedeDiagnosticar
                                                ? 'Captura el diagnóstico y las actividades realizadas para esta orden.'
                                                : motivoDiagnosticar
                                        }}
                                    </div>
                                </div>
                                <a-tooltip :title="puedeDiagnosticar ? '' : motivoDiagnosticar">
                                    <button type="button" class="btn-diagnosticar" :disabled="!puedeDiagnosticar"
                                        @click="abrirEditarTrabajo">
                                        <FileDoneOutlined />
                                        <span>Diagnosticar</span>
                                    </button>
                                </a-tooltip>
                            </div>

                            <div v-else class="trabajo-resumen">
                                <div class="tr-grid">
                                    <div v-for="b in resumenTrabajo" :key="b.key" class="tr-card"
                                        :class="{ 'tr-card--vacio': !b.valor || b.valor === 'No especificado' }"
                                        :style="{ '--c': b.color }">
                                        <span class="tr-card__head">
                                            <span class="tr-card__ic">
                                                <component :is="b.icono" />
                                            </span>
                                            <span class="tr-card__l">{{ b.label }}</span>
                                        </span>
                                        <span class="tr-card__v" :class="{ 'tr-card__v--mono': b.mono }">
                                            {{ b.valor || 'Sin registrar' }}
                                        </span>
                                    </div>
                                </div>

                                <button type="button" class="btn-ver-trabajo" @click="abrirVerTrabajo">
                                    <EyeOutlined />
                                    <span>Ver detalle completo</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #e08a1e">
                                <ToolOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Materiales
                                    <span v-if="m.materiales?.length" class="badge badge--orange">
                                        {{ m.materiales.length }}
                                    </span>
                                </div>
                                <div class="card__sub">Insumos utilizados</div>
                            </div>
                            <a-button v-if="puede('mantenimientos.editar') && puedeGestionarEjecucion"
                                class="card__extra" size="small" type="text" @click="abrirMaterial">
                                <template #icon>
                                    <PlusOutlined />
                                </template>
                                Agregar
                            </a-button>
                        </div>
                        <div class="card__body">
                            <template v-if="m.materiales?.length">
                                <div class="mat-grid">
                                    <div v-for="mat in materialesVisibles" :key="mat.id" class="mat-card">
                                        <span class="mat-card__head">
                                            <span class="mat-card__ic">
                                                <ToolOutlined />
                                            </span>
                                            <span class="mat-card__l">
                                                {{ mat.material?.nombre || mat.descripcion }}
                                            </span>
                                            <button v-if="puede('mantenimientos.editar') && puedeGestionarEjecucion"
                                                type="button" class="mat-card__x" title="Eliminar"
                                                @click.stop="quitarMaterial(mat)">
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
                                <button v-if="materialesResto" type="button" class="mat-mas"
                                    @click="modalMaterialesTodos = true">
                                    +{{ materialesResto }} más materiales
                                </button>
                                <div v-if="m.materiales?.length > 1" class="mat-total">
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
            </div>
        </div>

        <!-- ==========================================================
             MODAL DIAGNÓSTICO Y TRABAJO — más ancho, 2 columnas
             ========================================================== -->
        <a-modal v-model:open="modalTrabajo" :footer="null" :closable="false" :width="980" centered
            class="modal-trabajo" :mask-closable="!formTrabajo.processing">
            <div class="modal-trabajo__wrap">
                <header class="mt2-head">
                    <div class="mt2-head__ico">
                        <FileDoneOutlined />
                    </div>
                    <div class="mt2-head__meta">
                        <div class="mt2-head__row">
                            <h2 class="mt2-head__titulo">
                                {{ modoTrabajo === 'ver' ? 'Diagnóstico y trabajo' : 'Editar diagnóstico y trabajo' }}
                            </h2>
                            <span class="mt2-head__badge">
                                <component :is="modoTrabajo === 'ver' ? EyeOutlined : EditOutlined" />
                                {{ modoTrabajo === 'ver' ? 'Solo lectura' : 'Editando' }}
                            </span>
                        </div>
                        <p class="mt2-head__sub">
                            {{ modoTrabajo === 'ver'
                                ? 'Resultado del servicio registrado en esta orden.'
                                : 'Captura el diagnóstico, actividades y costos del servicio.' }}
                        </p>
                    </div>
                    <button type="button" class="mt2-head__close" :disabled="formTrabajo.processing" title="Cerrar"
                        @click="cerrarTrabajo">
                        ✕
                    </button>
                </header>

                <div class="mt2-body">
                    <!-- Vista de solo lectura (2 columnas) -->
                    <div v-if="modoTrabajo === 'ver'" class="tv-list">
                        <div v-for="b in resumenTrabajo" :key="b.key" class="tv-bloque" :style="{ '--c': b.color }">
                            <div class="tv-bloque__head">
                                <span class="tv-bloque__ic">
                                    <component :is="b.icono" />
                                </span>
                                <span class="tv-bloque__l">{{ b.label }}</span>
                            </div>
                            <div class="tv-bloque__v"
                                :class="{ 'tv-bloque__v--vacio': !b.valor, 'tv-bloque__v--mono': b.mono }">
                                {{ b.valor || 'Sin registrar' }}
                            </div>
                        </div>
                    </div>

                    <!-- Vista de edición (2 columnas: dx/actividades, obs/costos) -->
                    <div v-else>
                        <a-alert v-if="!puede('mantenimientos.editar')" type="info" show-icon class="mb-3"
                            message="Solo lectura: no tienes permiso para editar la captura de trabajo." />
                        <a-form layout="vertical" :disabled="!puede('mantenimientos.editar')">
                            <div class="trabajo-grid-2x2">
                                <!-- Fila 1: Diagnóstico | Actividades -->
                                <section class="step">
                                    <div class="step__head">
                                        <span class="step__num">1</span>
                                        <div class="step__meta">
                                            <span class="step__title">
                                                <FileDoneOutlined /> Diagnóstico
                                            </span>
                                            <span class="step__sub">¿Qué problema se encontró?</span>
                                        </div>
                                    </div>
                                    <div class="step__body">
                                        <a-form-item class="mb-0">
                                            <a-textarea v-model:value="formTrabajo.diagnostico" :rows="5"
                                                placeholder="Describe el diagnóstico técnico…" show-count
                                                :maxlength="1000" />
                                        </a-form-item>
                                    </div>
                                </section>

                                <section class="step">
                                    <div class="step__head">
                                        <span class="step__num">2</span>
                                        <div class="step__meta">
                                            <span class="step__title">
                                                <ToolOutlined /> Actividades
                                            </span>
                                            <span class="step__sub">¿Qué se hizo para resolverlo?</span>
                                        </div>
                                    </div>
                                    <div class="step__body">
                                        <a-form-item class="mb-0">
                                            <a-textarea v-model:value="formTrabajo.descripcion_trabajo" :rows="5"
                                                placeholder="Describe las actividades realizadas…" show-count
                                                :maxlength="1000" />
                                        </a-form-item>
                                    </div>
                                </section>

                                <!-- Fila 2: Observaciones | Costos -->
                                <section class="step">
                                    <div class="step__head">
                                        <span class="step__num">3</span>
                                        <div class="step__meta">
                                            <span class="step__title">
                                                <FileTextOutlined /> Observaciones
                                            </span>
                                            <span class="step__sub">Notas finales del servicio.</span>
                                        </div>
                                    </div>
                                    <div class="step__body">
                                        <a-form-item label="Observaciones" class="mb-3">
                                            <a-textarea v-model:value="formTrabajo.observaciones" :rows="2" />
                                        </a-form-item>
                                        <a-form-item label="Condición final" class="mb-0">
                                            <a-input v-model:value="formTrabajo.condicion_final"
                                                placeholder="Ej. Operando correctamente, requiere seguimiento…"
                                                size="large" />
                                        </a-form-item>
                                    </div>
                                </section>

                                <section class="step step--costos">
                                    <div class="step__head">
                                        <span class="step__num">4</span>
                                        <div class="step__meta">
                                            <span class="step__title">
                                                <DollarOutlined /> Costos
                                            </span>
                                            <span class="step__sub">Mano de obra y otros gastos.</span>
                                        </div>
                                    </div>
                                    <div class="step__body">
                                        <a-row :gutter="14">
                                            <a-col :xs="24" :sm="12">
                                                <a-form-item label="Costo mano de obra" class="mb-0">
                                                    <a-input-number v-model:value="formTrabajo.costo_mano_obra" :min="0"
                                                        :precision="2" prefix="$" size="large" style="width: 100%" />
                                                </a-form-item>
                                            </a-col>
                                            <a-col :xs="24" :sm="12">
                                                <a-form-item label="Otros costos" class="mb-0">
                                                    <a-input-number v-model:value="formTrabajo.costo_otros" :min="0"
                                                        :precision="2" prefix="$" size="large" style="width: 100%" />
                                                </a-form-item>
                                            </a-col>
                                        </a-row>
                                    </div>
                                </section>
                            </div>
                        </a-form>
                    </div>
                </div>

                <footer class="mt2-footer">
                    <template v-if="modoTrabajo === 'ver'">
                        <a-button size="large" @click="cerrarTrabajo">Cerrar</a-button>
                        <a-button v-if="puede('mantenimientos.editar') && !esCerrada" type="primary" size="large"
                            class="btn-submit" @click="abrirEditarTrabajo">
                            <template #icon>
                                <EditOutlined />
                            </template>
                            Editar
                        </a-button>
                    </template>
                    <template v-else>
                        <a-button size="large" :disabled="formTrabajo.processing" @click="cerrarTrabajo">
                            Cancelar
                        </a-button>
                        <a-button type="primary" size="large" class="btn-submit" :loading="formTrabajo.processing"
                            :disabled="formTrabajo.processing" @click="guardarTrabajo">
                            <template #icon>
                                <CheckCircleOutlined />
                            </template>
                            Guardar cambios
                        </a-button>
                    </template>
                </footer>
            </div>
        </a-modal>

        <!-- Materiales todos -->
        <a-modal v-model:open="modalMaterialesTodos" :title="`Materiales (${m.materiales?.length ?? 0})`" :footer="null"
            :width="720">
            <div class="mat-grid mat-grid--modal">
                <div v-for="mat in (m.materiales ?? [])" :key="mat.id" class="mat-card">
                    <span class="mat-card__head">
                        <span class="mat-card__ic">
                            <ToolOutlined />
                        </span>
                        <span class="mat-card__l">
                            {{ mat.material?.nombre || mat.descripcion }}
                        </span>
                        <button v-if="puede('mantenimientos.editar') && puedeGestionarEjecucion" type="button"
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
            <div class="mat-total mat-total--modal">Total: {{ moneda(totalMateriales) }}</div>
        </a-modal>

        <a-modal v-model:open="modalEvidencias" :title="`Evidencias (${m.documentos?.length ?? 0})`" :footer="null"
            :width="720">
            <ListaDocumentos :documentos="m.documentos ?? []" relacionable-tipo="mantenimiento" :relacionable-id="m.id"
                :roles="['evidencia', 'antes', 'despues', 'factura', 'garantia']"
                :puede-subir="puede('documentos.crear') && !esCerrada"
                :puede-eliminar="puede('documentos.desactivar') && !esCerrada" />
        </a-modal>

        <ModalFicha :show="modalFotoBitacora" :titulo="fotoBitacora?.titulo ?? 'Evidencia'"
            :subtitulo="fotoBitacora?.nombre_original" :icono="ToolOutlined" color="#e08a1e" max-width="lg"
            @close="modalFotoBitacora = false">
            <div v-if="fotoBitacora?.url" class="foto-modal">
                <img :src="fotoBitacora.url" alt="Evidencia" />
            </div>
            <template #footer>
                <a v-if="fotoBitacora" :href="route('documentos.download', fotoBitacora.id)"
                    class="ant-btn ant-btn-default" target="_blank">Descargar</a>
                <a v-if="fotoBitacora" :href="fotoBitacora.url" class="ant-btn ant-btn-primary" target="_blank">Abrir en
                    nueva pestaña</a>
            </template>
        </ModalFicha>

        <a-modal v-model:open="modalCronologia" title="Cronología de la orden" :footer="null" :width="640">
            <a-timeline v-if="cronologia.length">
                <a-timeline-item v-for="ev in cronologia" :key="ev.key" :color="ev.color">
                    <div class="cron">
                        <div>
                            <strong>{{ ev.titulo }}</strong>
                            <span v-if="ev.origen" class="cron__origen"> (desde {{ ev.origen }})</span>
                            <div v-if="ev.nota" class="cron__nota">{{ ev.nota }}</div>
                            <div class="cron__user">{{ ev.usuario }}</div>
                        </div>
                        <div class="cron__fecha">{{ fecha(ev.fecha) }}</div>
                    </div>
                </a-timeline-item>
            </a-timeline>
            <a-empty v-else description="Sin movimientos registrados" />
        </a-modal>

        <!-- Cambiar estado -->
        <a-modal v-model:open="modalEstado" ok-text="Confirmar" cancel-text="Cancelar"
            :confirm-loading="formEstado.processing" class="modal-bonita" @ok="confirmarEstado">
            <template #title>
                <div class="modal-head" :style="{ '--c': ESTADO_COLOR_HEX[formEstado.estado] || '#173a5f' }">
                    <span class="modal-head__ic">
                        <component :is="ICONO_ESTADO[formEstado.estado] || SwapOutlined" />
                    </span>
                    <div class="modal-head__t">
                        <span class="modal-head__l">{{ ETIQUETA_ESTADO[formEstado.estado] || 'Cambiar estado' }}</span>
                        <span class="modal-head__s">Registra este movimiento de la orden</span>
                    </div>
                </div>
            </template>
            <div class="mov-section">
                <span class="mov-section__l">Nota <small>(opcional)</small></span>
                <a-textarea v-model:value="formEstado.nota" :rows="2"
                    placeholder="Detalles adicionales del movimiento…" />
            </div>
            <div class="mov-section">
                <span class="mov-section__l">Evidencia <span class="mov-req">*</span></span>
                <p class="mov-section__hint">Foto, reporte o documento que respalde este movimiento.</p>
                <CampoEvidencia v-model="formEstado.evidencia" />
                <p v-if="formEstado.errors.evidencia" class="mov-error">{{ formEstado.errors.evidencia }}</p>
            </div>
        </a-modal>

        <!-- REPROGRAMAR -->
        <a-modal v-model:open="modalReprogramar" :footer="null" :closable="false" :width="720" centered
            class="modal-reprogramar" :mask-closable="!formReprogramar.processing">
            <div class="modal-reprogramar__wrap">
                <header class="mr-head">
                    <div class="mr-head__ico">
                        <CalendarOutlined />
                    </div>
                    <div class="mr-head__meta">
                        <div class="mr-head__row">
                            <h2 class="mr-head__titulo">Reprogramar orden</h2>
                            <span class="mr-head__badge">
                                <ClockCircleOutlined /> Cambio de fecha
                            </span>
                        </div>
                        <p class="mr-head__sub">Actualiza la programación y adjunta la evidencia del cambio.</p>
                    </div>
                    <button type="button" class="mr-head__close" :disabled="formReprogramar.processing" title="Cerrar"
                        @click="cerrarReprogramar">✕</button>
                </header>

                <form class="mr-body" @submit.prevent="reprogramar">
                    <section class="step">
                        <div class="step__head">
                            <span class="step__num">1</span>
                            <div class="step__meta">
                                <span class="step__title">
                                    <CalendarOutlined /> Nueva programación
                                </span>
                                <span class="step__sub">Fechas de inicio y fin del trabajo.</span>
                            </div>
                        </div>
                        <div class="step__body">
                            <a-form :model="formReprogramar" :rules="reglasReprogramar" layout="vertical">
                                <div class="rango">
                                    <div class="rango__campo">
                                        <label class="label">Inicio</label>
                                        <CampoFechaHora v-model="formReprogramar.programado_inicio"
                                            :min-fecha="hoyISO()" />
                                        <p v-if="formReprogramar.errors.programado_inicio" class="error-msg">{{
                                            formReprogramar.errors.programado_inicio }}</p>
                                    </div>
                                    <span class="rango__sep">al</span>
                                    <div class="rango__campo">
                                        <label class="label">Fin</label>
                                        <CampoFechaHora v-model="formReprogramar.programado_fin"
                                            :min-fecha="formReprogramar.programado_inicio ? formReprogramar.programado_inicio.slice(0, 10) : hoyISO()" />
                                        <p v-if="formReprogramar.errors.programado_fin" class="error-msg">{{
                                            formReprogramar.errors.programado_fin }}</p>
                                    </div>
                                </div>
                            </a-form>
                        </div>
                    </section>

                    <section class="step">
                        <div class="step__head">
                            <span class="step__num">2</span>
                            <div class="step__meta">
                                <span class="step__title">
                                    <FileTextOutlined /> Motivo
                                </span>
                                <span class="step__sub">Explica por qué se reprograma.</span>
                            </div>
                        </div>
                        <div class="step__body">
                            <a-form :model="formReprogramar" :rules="reglasReprogramar" layout="vertical">
                                <a-form-item name="motivo"
                                    :validate-status="formReprogramar.errors.motivo ? 'error' : undefined"
                                    :help="formReprogramar.errors.motivo" class="mb-0">
                                    <a-textarea v-model:value="formReprogramar.motivo" :rows="3"
                                        placeholder="Ej. El equipo aún está en uso, no llegó la refacción, etc."
                                        show-count :maxlength="500" />
                                </a-form-item>
                            </a-form>
                        </div>
                    </section>

                    <section class="step step--preview">
                        <div class="step__head">
                            <span class="step__num">3</span>
                            <div class="step__meta">
                                <span class="step__title">
                                    <CheckCircleOutlined /> Evidencia
                                </span>
                                <span class="step__sub">Adjunta un documento o foto que justifique el cambio.</span>
                            </div>
                        </div>
                        <div class="step__body">
                            <CampoEvidencia v-model="formReprogramar.evidencia" />
                            <p v-if="formReprogramar.errors.evidencia" class="error-msg">{{
                                formReprogramar.errors.evidencia }}
                            </p>
                        </div>
                    </section>
                </form>

                <footer class="mr-footer">
                    <a-button size="large" :disabled="formReprogramar.processing"
                        @click="cerrarReprogramar">Cancelar</a-button>
                    <a-button type="primary" size="large" class="btn-submit" :loading="formReprogramar.processing"
                        :disabled="formReprogramar.processing" @click="reprogramar">
                        <template #icon>
                            <CalendarOutlined />
                        </template>
                        Reprogramar
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <!-- ==========================================================
             MODAL ASIGNAR TÉCNICO — sin Notas
             ========================================================== -->
        <a-modal v-model:open="modalTecnico" :footer="null" :closable="false" :width="820" centered
            class="modal-tecnico" :mask-closable="!formTecnico.processing">
            <div class="modal-tecnico__wrap">
                <header class="mt-head">
                    <div class="mt-head__ico">
                        <UserOutlined />
                    </div>
                    <div class="mt-head__meta">
                        <div class="mt-head__row">
                            <h2 class="mt-head__titulo">Asignar técnico</h2>
                            <span class="mt-head__badge">
                                <UserOutlined /> Personal
                            </span>
                        </div>
                        <p class="mt-head__sub">
                            Selecciona el técnico que atenderá esta orden.
                        </p>
                    </div>
                    <button type="button" class="mt-head__close" :disabled="formTecnico.processing" title="Cerrar"
                        @click="cerrarTecnico">
                        ✕
                    </button>
                </header>

                <div class="mt-body">
                    <a-form layout="vertical">
                        <!-- Fila 2x2: Selecciona técnico | Rol y notas -->
                        <div class="tecnico-grid-2x2">
                            <section class="step">
                                <div class="step__head">
                                    <span class="step__num">1</span>
                                    <div class="step__meta">
                                        <span class="step__title">
                                            <UserOutlined /> Selecciona el técnico
                                        </span>
                                        <span class="step__sub">Los marcados con ⭐ coinciden con esta orden.</span>
                                    </div>
                                </div>
                                <div class="step__body">
                                    <a-form-item :validate-status="formTecnico.errors.tecnico_id ? 'error' : undefined"
                                        :help="formTecnico.errors.tecnico_id" class="mb-0">
                                        <a-select v-model:value="formTecnico.tecnico_id"
                                            placeholder="Selecciona un técnico" size="large">
                                            <a-select-option v-for="t in catalogos.tecnicos" :key="t.id" :value="t.id">
                                                <StarFilled v-if="t.recomendado" class="tecnico-op__star" />
                                                {{ t.nombre }}
                                                <span v-if="t.recomendado" class="tecnico-op__tag">Recomendado</span>
                                            </a-select-option>
                                        </a-select>
                                    </a-form-item>
                                </div>
                            </section>

                            <section class="step">
                                <div class="step__head">
                                    <span class="step__num">2</span>
                                    <div class="step__meta">
                                        <span class="step__title">
                                            <FlagOutlined /> Rol
                                        </span>
                                        <span class="step__sub">Define si será el responsable principal.</span>
                                    </div>
                                </div>
                                <div class="step__body">
                                    <a-checkbox v-model:checked="formTecnico.es_principal" class="tec-principal-check">
                                        <strong>Responsable principal</strong>
                                        <span class="tec-check-hint"> — aparece primero en la lista de la orden.</span>
                                    </a-checkbox>
                                </div>
                            </section>
                        </div>

                        <!-- Vista previa abajo (ancho completo) -->
                        <section v-if="tecnicoSeleccionado" class="step step--preview">
                            <div class="step__head">
                                <span class="step__num">3</span>
                                <div class="step__meta">
                                    <span class="step__title">
                                        <StarFilled /> Vista previa
                                    </span>
                                    <span class="step__sub">Así se verá el técnico asignado.</span>
                                </div>
                            </div>
                            <div class="step__body">
                                <div class="tec-preview">
                                    <span class="tec-preview__av">
                                        {{ tecnicoSeleccionado.nombre.charAt(0).toUpperCase() }}
                                    </span>
                                    <div class="tec-preview__info">
                                        <span class="tec-preview__nombre">{{ tecnicoSeleccionado.nombre }}</span>
                                        <span class="tec-preview__sub">
                                            {{ tecnicoSeleccionado.recomendado ? '⭐ Recomendado para esta orden' :
                                            'Técnico asignable' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="tec-preview__cualidades">
                                    <span class="tec-preview__cualidades-l">Especialidades</span>
                                    <a-space v-if="tecnicoSeleccionado.cualidades?.length" wrap>
                                        <a-tag v-for="c in tecnicoSeleccionado.cualidades" :key="c" color="blue">{{ c
                                            }}</a-tag>
                                    </a-space>
                                    <span v-else class="tec-preview__vacio">Sin especialidades registradas
                                        todavía.</span>
                                </div>
                            </div>
                        </section>
                    </a-form>
                </div>

                <footer class="mt-footer">
                    <a-button size="large" :disabled="formTecnico.processing" @click="cerrarTecnico">
                        Cancelar
                    </a-button>
                    <a-button type="primary" size="large" class="btn-submit" :loading="formTecnico.processing"
                        :disabled="formTecnico.processing || !formTecnico.tecnico_id" @click="asignarTecnico">
                        <template #icon>
                            <PlusOutlined />
                        </template>
                        Asignar técnico
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <!-- AGREGAR MATERIAL -->
        <a-modal v-model:open="modalMaterial" :footer="null" :closable="false" :width="720" centered
            class="modal-material" :mask-closable="!formMaterial.processing">
            <div class="modal-material__wrap">
                <header class="mm-head">
                    <div class="mm-head__ico">
                        <InboxOutlined />
                    </div>
                    <div class="mm-head__meta">
                        <div class="mm-head__row">
                            <h2 class="mm-head__titulo">Agregar material</h2>
                            <span class="mm-head__badge">
                                <ToolOutlined /> Insumo
                            </span>
                        </div>
                        <p class="mm-head__sub">Selecciona del catálogo o captura manualmente.</p>
                    </div>
                    <button type="button" class="mm-head__close" :disabled="formMaterial.processing" title="Cerrar"
                        @click="cerrarMaterial">✕</button>
                </header>

                <form class="mm-body" @submit.prevent="agregarMaterial">
                    <a-form :model="formMaterial" :rules="reglasMaterial" layout="vertical">
                        <section class="step">
                            <div class="step__head">
                                <span class="step__num">1</span>
                                <div class="step__meta">
                                    <span class="step__title">
                                        <InboxOutlined /> Material
                                    </span>
                                    <span class="step__sub">Elige del catálogo o escribe un nombre libre.</span>
                                </div>
                            </div>
                            <div class="step__body">
                                <a-form-item label="Del catálogo">
                                    <SelectCatalogo v-model:value="formMaterial.material_id"
                                        :options="catalogos.materiales" ruta="catalogos.materiales" etiqueta="material"
                                        etiqueta-plural="materiales"
                                        placeholder="Opcional — o captura la descripción abajo" :campos="[
                                            { name: 'unidad', label: 'Unidad (pza, m, lt…)', ancho: 12 },
                                            { name: 'costo_referencia', label: 'Costo referencia', tipo: 'number', min: 0 },
                                        ]" @update:value="onMaterialSel" />
                                </a-form-item>

                                <div v-if="materialSeleccionado" class="mat-preview">
                                    <span class="mat-preview__ic">
                                        <ToolOutlined />
                                    </span>
                                    <div class="mat-preview__info">
                                        <span class="mat-preview__nombre">{{ materialSeleccionado.nombre }}</span>
                                        <span class="mat-preview__sub">
                                            {{ materialSeleccionado.unidad || 'unidad' }}
                                            <template v-if="materialSeleccionado.costo_referencia"> · Costo ref. {{
                                                moneda(materialSeleccionado.costo_referencia) }}</template>
                                        </span>
                                    </div>
                                </div>

                                <a-form-item v-if="!formMaterial.material_id" label="Descripción"
                                    :validate-status="formMaterial.errors.descripcion ? 'error' : undefined"
                                    :help="formMaterial.errors.descripcion">
                                    <a-input v-model:value="formMaterial.descripcion"
                                        placeholder="Ej. Cinta aislante 3M" size="large" />
                                </a-form-item>
                            </div>
                        </section>

                        <section class="step">
                            <div class="step__head">
                                <span class="step__num">2</span>
                                <div class="step__meta">
                                    <span class="step__title">
                                        <DollarOutlined /> Cantidad y costos
                                    </span>
                                    <span class="step__sub">Detalla cuánto se utilizó y su costo unitario.</span>
                                </div>
                            </div>
                            <div class="step__body">
                                <a-row :gutter="14">
                                    <a-col :xs="24" :sm="8">
                                        <a-form-item label="Cantidad" name="cantidad"
                                            :validate-status="formMaterial.errors.cantidad ? 'error' : undefined"
                                            :help="formMaterial.errors.cantidad">
                                            <a-input-number v-model:value="formMaterial.cantidad" :min="0.01"
                                                size="large" style="width: 100%" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="8">
                                        <a-form-item label="Unidad">
                                            <a-input v-model:value="formMaterial.unidad" size="large"
                                                placeholder="pza, m, lt…" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="8">
                                        <a-form-item label="Costo unitario">
                                            <a-input-number v-model:value="formMaterial.costo_unitario" :min="0"
                                                :precision="2" prefix="$" size="large" style="width: 100%" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>

                                <div v-if="totalMaterialForm > 0" class="mat-total-preview">
                                    <span class="mat-total-preview__l">
                                        <DollarOutlined /> Total a registrar
                                    </span>
                                    <span class="mat-total-preview__v">{{ moneda(totalMaterialForm) }}</span>
                                </div>
                            </div>
                        </section>
                    </a-form>
                </form>

                <footer class="mm-footer">
                    <a-button size="large" :disabled="formMaterial.processing"
                        @click="cerrarMaterial">Cancelar</a-button>
                    <a-button type="primary" size="large" class="btn-submit" :loading="formMaterial.processing"
                        :disabled="formMaterial.processing" @click="agregarMaterial">
                        <template #icon>
                            <PlusOutlined />
                        </template>
                        Agregar material
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <!-- ==========================================================
             MODAL AGREGAR OBSERVACIÓN — rediseñado
             ========================================================== -->
        <a-modal v-model:open="modalObs" :footer="null" :closable="false" :width="640" centered
            class="modal-obs" :mask-closable="!formObs.processing">
            <div class="modal-obs__wrap">
                <header class="mo-head">
                    <div class="mo-head__ico">
                        <MessageOutlined />
                    </div>
                    <div class="mo-head__meta">
                        <div class="mo-head__row">
                            <h2 class="mo-head__titulo">Agregar a la bitácora</h2>
                            <span class="mo-head__badge">
                                <ClockCircleOutlined /> Movimiento
                            </span>
                        </div>
                        <p class="mo-head__sub">Registra un comentario o una supervisión sobre esta orden.</p>
                    </div>
                    <button type="button" class="mo-head__close" :disabled="formObs.processing" title="Cerrar"
                        @click="cerrarObs">✕</button>
                </header>

                <div class="mo-body">
                    <a-form :model="formObs" :rules="reglasObs" layout="vertical">
                        <section class="step">
                            <div class="step__head">
                                <span class="step__num">1</span>
                                <div class="step__meta">
                                    <span class="step__title">
                                        <TagOutlined /> Tipo de movimiento
                                    </span>
                                    <span class="step__sub">¿Es un comentario o una supervisión?</span>
                                </div>
                            </div>
                            <div class="step__body">
                                <div class="obs-tipos">
                                    <button type="button" class="obs-tipo"
                                        :class="{ 'obs-tipo--active': formObs.tipo === 'comentario' }"
                                        @click="formObs.tipo = 'comentario'">
                                        <span class="obs-tipo__ic obs-tipo__ic--com">
                                            <MessageOutlined />
                                        </span>
                                        <span class="obs-tipo__t">
                                            <span class="obs-tipo__l">Comentario</span>
                                            <span class="obs-tipo__s">Nota rápida o seguimiento</span>
                                        </span>
                                    </button>
                                    <button type="button" class="obs-tipo"
                                        :class="{ 'obs-tipo--active': formObs.tipo === 'supervision' }"
                                        @click="formObs.tipo = 'supervision'">
                                        <span class="obs-tipo__ic obs-tipo__ic--sup">
                                            <FlagOutlined />
                                        </span>
                                        <span class="obs-tipo__t">
                                            <span class="obs-tipo__l">Supervisión</span>
                                            <span class="obs-tipo__s">Revisión o validación</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </section>

                        <section class="step">
                            <div class="step__head">
                                <span class="step__num">2</span>
                                <div class="step__meta">
                                    <span class="step__title">
                                        <FileTextOutlined /> Observación
                                    </span>
                                    <span class="step__sub">Describe el movimiento con el mayor detalle posible.</span>
                                </div>
                            </div>
                            <div class="step__body">
                                <a-form-item name="cuerpo"
                                    :validate-status="formObs.errors.cuerpo ? 'error' : undefined"
                                    :help="formObs.errors.cuerpo" class="mb-0">
                                    <a-textarea v-model:value="formObs.cuerpo" :rows="4"
                                        placeholder="Escribe aquí tu observación…" show-count :maxlength="1000" />
                                </a-form-item>
                            </div>
                        </section>
                    </a-form>
                </div>

                <footer class="mo-footer">
                    <a-button size="large" :disabled="formObs.processing" @click="cerrarObs">
                        Cancelar
                    </a-button>
                    <a-button type="primary" size="large" class="btn-submit" :loading="formObs.processing"
                        :disabled="formObs.processing || !formObs.cuerpo" @click="agregarObs">
                        <template #icon>
                            <PlusOutlined />
                        </template>
                        Agregar a bitácora
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   BOTONES HERO (acciones del header) — aplicados
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

.btn-hero--reprog {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 6px 16px -6px rgba(107, 75, 201, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--reprog:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(107, 75, 201, 0.75), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-more {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    border: 1px solid var(--sigam-borde);
    background: #fff;
    color: var(--sigam-tenue);
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
   Base
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
    border-radius: 8px;
}

.ficha-compacta {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 0;
}

.estado-tag,
.prio-tag,
.tipo-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin: 0;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 12px;
    line-height: 20px;
    border: none;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
}

.estado-tag .anticon,
.prio-tag .anticon,
.tipo-tag .anticon {
    font-size: 12px;
}

.problema-card {
    display: flex;
    gap: 14px;
    align-items: center;
    padding: 14px 18px;
    background: #fff;
    border: 1px solid #fecaca;
    border-left: 4px solid #dc2626;
    border-radius: 14px;
    box-shadow: var(--sigam-sombra-sm);
}

.problema-card--cerrada {
    border-color: var(--sigam-borde-suave);
    border-left-color: var(--sigam-tenue);
    background: #f8fafc;
}

.problema-card__icono {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    color: #fff;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
}

.problema-card--cerrada .problema-card__icono {
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
}

.problema-card__meta {
    min-width: 0;
    flex: 1;
}

.problema-card__objetivo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    font-weight: 800;
    font-size: 14.5px;
    color: #b52222;
    margin-bottom: 3px;
}

.problema-card--cerrada .problema-card__objetivo {
    color: var(--sigam-tenue);
}

.problema-card__desc {
    white-space: pre-line;
    text-transform: uppercase;
    font-weight: 700;
    font-size: 13px;
    line-height: 1.4;
    color: #d64545;
}

.problema-card--cerrada .problema-card__desc {
    color: var(--sigam-texto);
}

.grid-ficha {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 12px;
    align-items: start;
}

.col-izq,
.col-der {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-width: 0;
}

.card {
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--sigam-sombra-sm);
    transition: box-shadow 0.18s ease;
}

.card:hover {
    box-shadow: var(--sigam-sombra-md);
}

.card__head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 15px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 70%);
}

.card__ico {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 14%, #fff);
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
    font-size: 13px;
    color: var(--sigam-navy);
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.card__sub {
    font-size: 10.5px;
    color: var(--sigam-tenue);
}

.card__extra {
    flex-shrink: 0;
}

.card__body {
    padding: 12px 15px;
}

/* Card bitácora: fondo distinto, altura reducida */
.card--bitacora .card__head {
    background: linear-gradient(120deg, #eef6fc, #fff 70%);
}

.card__body--bitacora {
    padding: 10px 12px;
    max-height: 280px;
    overflow-y: auto;
    scrollbar-width: thin;
}

/* Botón "Asignar" con letra blanca siempre */
.btn-asignar-tecnico {
    color: #ffffff !important;
    font-weight: 700;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%) !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 0 12px !important;
    height: 28px !important;
    box-shadow: 0 3px 8px -3px rgba(31, 158, 134, 0.6);
    transition: transform 0.14s ease, filter 0.14s ease !important;
}

.btn-asignar-tecnico:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.08);
}

.btn-asignar-tecnico:disabled,
.btn-asignar-tecnico[disabled] {
    color: #ffffff !important;
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%) !important;
    opacity: 0.85;
    cursor: not-allowed;
    box-shadow: none;
}

.btn-asignar-tecnico :deep(.anticon) {
    color: #ffffff !important;
}

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

.badge--green {
    background: #e4f4ec;
    color: #16806c;
}

.badge--orange {
    background: #fdf3e6;
    color: #a86717;
}

.badge--blue {
    background: #e8f3fb;
    color: #0d6ca6;
}

.mini-grid {
    display: grid;
    gap: 8px;
}

.mini-grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.mini-grid--full {
    grid-template-columns: 1fr;
}

.mt-grid {
    margin-top: 8px;
}

.mini {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 10px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--c) 6%, #fff);
    border: 1px solid color-mix(in srgb, var(--c) 18%, transparent);
    transition: transform 0.14s ease, box-shadow 0.14s ease;
}

.mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px color-mix(in srgb, var(--c) 20%, transparent);
}

.mini__ic {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #fff;
    background: var(--c);
}

.mini__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 0;
}

.mini__l {
    font-size: 9.5px;
    text-transform: uppercase;
    font-weight: 700;
    color: var(--sigam-tenue);
    line-height: 1.1;
}

.mini__v {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--sigam-texto);
    word-break: break-word;
    line-height: 1.22;
}

.normas-inline {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed var(--sigam-borde-suave);
}

.normas-inline__l {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--sigam-tenue);
}

.diagnostico-vacio {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    padding: 14px 16px;
    border: 1px dashed #c9b8f0;
    border-radius: 12px;
    background: linear-gradient(120deg, #f5efff 0%, #fff 80%);
}

.diagnostico-vacio__ico {
    width: 42px;
    height: 42px;
    flex: none;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    color: #fff;
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 5px 14px rgba(107, 75, 201, 0.32);
}

.diagnostico-vacio__txt {
    flex: 1;
    min-width: 180px;
}

.diagnostico-vacio__t {
    font-weight: 800;
    color: var(--sigam-navy);
    font-size: 13px;
    line-height: 1.2;
}

.diagnostico-vacio__s {
    font-size: 11.5px;
    color: var(--sigam-tenue);
    margin-top: 2px;
    line-height: 1.35;
}

.btn-diagnosticar {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    height: 36px;
    padding: 0 16px;
    border: none;
    border-radius: 10px;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 700;
    color: #fff;
    cursor: pointer;
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 4px 12px rgba(107, 75, 201, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
    flex-shrink: 0;
}

.btn-diagnosticar:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.08);
}

.btn-diagnosticar:disabled {
    cursor: not-allowed;
    color: #94a3b8;
    background: #e2e8f0;
    box-shadow: none;
}

.trabajo-resumen {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.tr-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-template-rows: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.tr-card {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 9px 10px;
    border-radius: 11px;
    background: color-mix(in srgb, var(--c) 5%, #fff);
    border: 1px solid color-mix(in srgb, var(--c) 20%, transparent);
    border-left: 3px solid var(--c);
    min-width: 0;
}

.tr-card--vacio {
    background: #fafbfc;
    border-color: var(--sigam-borde-suave);
    border-left-color: var(--sigam-tenue);
}

.tr-card__head {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.tr-card__ic {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #fff;
    background: var(--c);
}

.tr-card--vacio .tr-card__ic {
    background: var(--sigam-tenue);
}

.tr-card__l {
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: color-mix(in srgb, var(--c) 80%, #000);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tr-card--vacio .tr-card__l {
    color: var(--sigam-tenue);
}

.tr-card__v {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--sigam-texto);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 30px;
}

.tr-card__v--mono {
    font-size: 13px;
    font-weight: 800;
    color: var(--sigam-navy);
    -webkit-line-clamp: 1;
    min-height: auto;
}

.tr-card--vacio .tr-card__v {
    color: var(--sigam-tenue);
    font-style: italic;
    font-weight: 400;
}

.btn-ver-trabajo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 36px;
    padding: 0 16px;
    border: 1px solid #c9b8f0;
    border-radius: 10px;
    background: linear-gradient(135deg, #f5efff 0%, #fff 80%);
    color: #563a9e;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.btn-ver-trabajo:hover {
    transform: translateY(-1px);
    border-color: #6b4bc9;
    box-shadow: 0 6px 14px rgba(107, 75, 201, 0.22);
}

.mat-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-template-rows: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.mat-grid--modal {
    grid-template-rows: auto;
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 6px;
    scrollbar-width: thin;
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

.mat-card__head {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.mat-card__ic {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #fff;
    background: #e08a1e;
}

.mat-card__l {
    flex: 1;
    font-size: 11px;
    font-weight: 800;
    color: var(--sigam-navy);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mat-card__x {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    border: none;
    background: transparent;
    color: #c23b3b;
    cursor: pointer;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    transition: background 0.14s ease;
}

.mat-card__x:hover {
    background: #fbeaea;
}

.mat-card__sub {
    font-size: 10px;
    color: var(--sigam-tenue);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mat-card__total {
    font-size: 13px;
    font-weight: 800;
    color: #a86717;
    margin-top: auto;
}

.mat-mas {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    margin-top: 8px;
    padding: 6px 10px;
    border: 1px dashed #f7e4c4;
    border-radius: 9px;
    background: #fdf7ec;
    color: #a86717;
    font-family: inherit;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
}

.mat-mas:hover {
    background: #fbeed7;
    border-color: #e08a1e;
    transform: translateY(-1px);
}

.mat-total {
    display: flex;
    justify-content: flex-end;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed var(--sigam-borde-suave);
    font-size: 12.5px;
    font-weight: 800;
    color: var(--sigam-navy);
}

.mat-total--modal {
    margin-top: 14px;
    font-size: 14px;
}

.tecnicos {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.tecnico {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 10px;
    border: 1px solid var(--sigam-borde-suave);
    background: #fff;
    transition: transform 0.14s ease, box-shadow 0.14s ease;
}

.tecnico:hover {
    transform: translateY(-1px);
    box-shadow: var(--sigam-sombra-sm);
}

.tecnico__av {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
}

.tecnico__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
    gap: 1px;
}

.tecnico__nombre {
    font-weight: 700;
    font-size: 12.5px;
    color: var(--sigam-navy);
    display: flex;
    align-items: center;
    gap: 6px;
}

.tecnico__tag {
    font-size: 10px;
    line-height: 16px;
    padding: 0 6px;
    margin: 0;
}

.tecnico__sub {
    font-size: 10.5px;
    color: var(--sigam-tenue);
}

.tecnico__x {
    width: 24px;
    height: 24px;
    border-radius: 7px;
    border: none;
    background: transparent;
    color: #c23b3b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: background 0.14s ease;
}

.tecnico__x:hover {
    background: #fbeaea;
}

.modal-head {
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-head__ic {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    background: var(--c);
    box-shadow: 0 8px 16px -8px color-mix(in srgb, var(--c) 70%, transparent);
}

.modal-head__t {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}

.modal-head__l {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.25;
}

.modal-head__s {
    font-size: 12px;
    color: var(--sigam-tenue);
    font-weight: 500;
}

.modal-bonita :deep(.ant-modal-header) {
    padding-bottom: 14px;
}

.modal-bonita :deep(.ant-modal-body) {
    padding-top: 18px;
}

.modal-bonita__footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px solid var(--sigam-borde-suave);
}

.mov-section {
    margin-bottom: 18px;
}

.mov-section:last-child {
    margin-bottom: 0;
}

.mov-section__l {
    display: block;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--sigam-navy);
    margin-bottom: 6px;
}

.mov-section__l small {
    text-transform: none;
    font-weight: 500;
    color: var(--sigam-tenue);
}

.mov-section__hint {
    font-size: 11.5px;
    color: var(--sigam-tenue);
    margin: -2px 0 8px;
}

.mov-req {
    color: #d64545;
}

.mov-error {
    font-size: 12px;
    color: #d64545;
    margin-top: 6px;
}

/* ==========================================================
   BITÁCORA — versión compacta con thumb grande en el header
   ========================================================== */
.mb-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.mb-list--inline {
    gap: 0;
}

.mb-item {
    display: flex;
    align-items: stretch;
    gap: 8px;
    animation: mbFade 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes mbFade {
    from {
        opacity: 0;
        transform: translateY(4px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mb-item__rail {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}

.mb-item__ic {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #fff;
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 0 0 2px #fff, 0 2px 6px -1px rgba(13, 132, 201, 0.4);
    z-index: 1;
}

.mb-item--supervision .mb-item__ic {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 0 0 2px #fff, 0 2px 6px -1px rgba(107, 75, 201, 0.4);
}

.mb-item__linea {
    width: 2px;
    flex: 1;
    min-height: 8px;
    margin: 2px 0;
    background: linear-gradient(180deg, #dbe3ec 0%, #eef2f7 100%);
}

.mb-item__card {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 7px 9px;
    margin-bottom: 8px;
    border-radius: 9px;
    background: #fbfdff;
    border: 1px solid #e2e8f0;
    border-left: 3px solid #0d84c9;
    transition: box-shadow 0.16s ease, transform 0.16s ease;
}

.mb-item--supervision .mb-item__card {
    background: linear-gradient(180deg, #faf7ff 0%, #fbfdff 100%);
    border-color: #e0d3f7;
    border-left-color: #6b4bc9;
}

.mb-item__card:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px -10px rgba(15, 37, 71, 0.3);
}

.mb-item__card-head {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.mb-item__avatar {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 800;
    color: #fff;
    box-shadow: 0 2px 5px -2px rgba(15, 37, 71, 0.35);
}

.mb-item__user {
    display: flex;
    flex-direction: column;
    gap: 0;
    min-width: 0;
    flex: 1;
}

.mb-item__user-name {
    font-size: 11px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.15;
}

.mb-item__user-time {
    font-size: 9.5px;
    color: #7b8a9c;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

/* Cuadrito de evidencia más grande (40x40) */
.mb-item__thumb {
    position: relative;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    border-radius: 8px;
    border: 1px solid #cfe3f2;
    overflow: hidden;
    cursor: pointer;
    background: #0f2c4a;
    padding: 0;
    transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
}

.mb-item__thumb img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mb-item__thumb-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 44, 74, 0.55);
    color: #fff;
    font-size: 14px;
    opacity: 0;
    transition: opacity 0.18s ease;
}

.mb-item__thumb:hover {
    transform: scale(1.08);
    border-color: #0d84c9;
    box-shadow: 0 6px 14px -6px rgba(13, 132, 201, 0.65);
}

.mb-item__thumb:hover .mb-item__thumb-overlay {
    opacity: 1;
}

.mb-item__cuerpo {
    margin: 0;
    font-size: 11.5px;
    line-height: 1.4;
    color: #2b3a4f;
    white-space: pre-line;
    padding-left: 2px;
}

.mb-vacio {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 24px 16px;
    text-align: center;
}

.mb-vacio--inline {
    padding: 20px 12px;
}

.mb-vacio__ic {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #94a3b8;
    background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    animation: mbVacio 2.4s ease-in-out infinite;
}

@keyframes mbVacio {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-3px);
    }
}

.mb-vacio__t {
    font-size: 12.5px;
    font-weight: 800;
    color: #173a5f;
    margin-top: 2px;
}

.mb-vacio__s {
    font-size: 11px;
    color: #7b8a9c;
    max-width: 280px;
    line-height: 1.4;
}

.cron {
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

.cron__origen {
    opacity: 0.6;
}

.cron__nota {
    font-size: 12.5px;
    margin-top: 2px;
}

.cron__user {
    font-size: 11.5px;
    color: var(--sigam-tenue);
    margin-top: 2px;
}

.cron__fecha {
    font-size: 11.5px;
    color: var(--sigam-tenue);
    white-space: nowrap;
}

.vacio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 18px 12px;
    border-radius: 11px;
    border: 1px dashed var(--sigam-borde);
    background: var(--sigam-navy-050);
    color: var(--sigam-tenue);
    font-size: 12px;
}

.vacio-box .anticon {
    font-size: 18px;
    opacity: 0.5;
}

/* ==========================================================
   MODAL TRABAJO (diagnóstico) — más ancho, 2 columnas
   ========================================================== */
.modal-trabajo :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-trabajo :deep(.ant-modal-body) {
    padding: 0;
}

.modal-trabajo__wrap {
    display: flex;
    flex-direction: column;
    background: #f5f8fb;
}

.mt2-head {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    background: linear-gradient(135deg, #ffffff 0%, #f5efff 100%);
    border-bottom: 1px solid #ddd2f5;
    flex-shrink: 0;
    overflow: hidden;
}

.mt2-head::before {
    content: '';
    position: absolute;
    right: -60px;
    top: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(107, 75, 201, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.mt2-head__ico {
    position: relative;
    z-index: 1;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 8px 20px -8px rgba(107, 75, 201, 0.65);
}

.mt2-head__meta {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
}

.mt2-head__row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.mt2-head__titulo {
    font-size: 17px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
}

.mt2-head__badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #6b4bc9;
    background: #efe9fb;
    border: 1px solid #ddd2f5;
}

.mt2-head__sub {
    margin: 3px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.mt2-head__close {
    position: relative;
    z-index: 1;
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

.mt2-head__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.mt2-body {
    display: flex;
    flex-direction: column;
    padding: 16px 22px 6px;
    background: #fff;
}

.tv-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    max-height: 65vh;
    overflow-y: auto;
    padding-right: 6px;
    scrollbar-width: thin;
}

.tv-bloque {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 10px 12px;
    border-radius: 11px;
    background: color-mix(in srgb, var(--c) 5%, #fff);
    border: 1px solid color-mix(in srgb, var(--c) 18%, transparent);
    border-left: 3px solid var(--c);
}

.tv-bloque__head {
    display: flex;
    align-items: center;
    gap: 7px;
}

.tv-bloque__ic {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #fff;
    background: var(--c);
}

.tv-bloque__l {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: color-mix(in srgb, var(--c) 80%, #000);
}

.tv-bloque__v {
    font-size: 13px;
    color: var(--sigam-texto);
    line-height: 1.55;
    white-space: pre-line;
    padding-left: 29px;
}

.tv-bloque__v--vacio {
    color: var(--sigam-tenue);
    font-style: italic;
}

.tv-bloque__v--mono {
    font-size: 20px;
    font-weight: 800;
    color: var(--sigam-navy);
}

.trabajo-grid-2x2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.trabajo-grid-2x2 .step {
    margin-bottom: 0;
}

.step {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px 16px;
    border-radius: 13px;
    background: linear-gradient(180deg, #ffffff 0%, #fafcfe 100%);
    border: 1px solid #e2e8f0;
    transition: border-color 0.14s ease, box-shadow 0.14s ease;
}

.step:hover {
    border-color: #cfe4f5;
    box-shadow: 0 6px 18px -12px rgba(13, 132, 201, 0.35);
}

.step--preview {
    border-color: #b8e4d3;
    background: linear-gradient(180deg, #f5fbf8 0%, #eef9f5 100%);
}

.step--costos {
    border-color: #f7e4c4;
    background: linear-gradient(180deg, #fffaf1 0%, #fdf7ec 100%);
}

.step__head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #e2e8f0;
}

.step--preview .step__head {
    border-bottom-color: #b8e4d3;
}

.step--costos .step__head {
    border-bottom-color: #f7e4c4;
}

.step__num {
    width: 28px;
    height: 28px;
    flex: none;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12.5px;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 5px 12px -5px rgba(107, 75, 201, 0.65);
}

.step--preview .step__num {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 5px 12px -5px rgba(31, 158, 134, 0.65);
}

.step--costos .step__num {
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%);
    box-shadow: 0 5px 12px -5px rgba(224, 138, 30, 0.65);
}

.step__meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.step__title {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    color: #173a5f;
    line-height: 1.15;
}

.step__title .anticon {
    color: #6b4bc9;
    font-size: 12px;
}

.step--preview .step__title .anticon {
    color: #1f9e86;
}

.step--costos .step__title .anticon {
    color: #e08a1e;
}

.step__sub {
    font-size: 11.5px;
    color: #7b8a9c;
    line-height: 1.2;
    font-weight: 500;
}

.step__body {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.mt2-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    background: linear-gradient(180deg, #fafcfe 0%, #ffffff 100%);
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.mt2-footer .btn-submit {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%) !important;
    border-color: #6b4bc9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(107, 75, 201, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.mt2-footer .btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
}

/* ==========================================================
   MODAL TÉCNICO — nuevo color (azul)
   ========================================================== */
.modal-tecnico :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-tecnico :deep(.ant-modal-body) {
    padding: 0;
}

.modal-tecnico__wrap {
    display: flex;
    flex-direction: column;
    background: #f5f8fb;
}

.mt-head {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    background: linear-gradient(135deg, #ffffff 0%, #eef4fb 100%);
    border-bottom: 1px solid #cfe4f5;
    flex-shrink: 0;
    overflow: hidden;
}

.mt-head::before {
    content: '';
    position: absolute;
    right: -60px;
    top: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(13, 132, 201, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.mt-head__ico {
    position: relative;
    z-index: 1;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 8px 20px -8px rgba(13, 132, 201, 0.65);
}

.mt-head__meta {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
}

.mt-head__row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.mt-head__titulo {
    font-size: 17px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
}

.mt-head__badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #0d6ca6;
    background: #e8f3fb;
    border: 1px solid #cfe3f2;
}

.mt-head__sub {
    margin: 3px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.mt-head__close {
    position: relative;
    z-index: 1;
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

.mt-head__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.mt-body {
    padding: 18px 22px 8px;
    background: #fff;
}

.tecnico-grid-2x2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

.tecnico-grid-2x2 .step {
    margin-bottom: 0;
}

.tec-preview {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: linear-gradient(180deg, #f5fbff 0%, #eef4fb 100%);
    border: 1px solid #cfe4f5;
    animation: tecFade 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes tecFade {
    from {
        opacity: 0;
        transform: translateY(4px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tec-preview__av {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 4px 10px -3px rgba(13, 132, 201, 0.5);
}

.tec-preview__info {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}

.tec-preview__nombre {
    font-size: 14px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.2;
}

.tec-preview__sub {
    font-size: 11.5px;
    color: #0d6ca6;
    font-weight: 600;
}

.tec-preview__cualidades {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 10px;
}

.tec-preview__cualidades-l {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #7b8a9c;
}

.tec-preview__vacio {
    font-size: 12px;
    color: #7b8a9c;
    font-style: italic;
}

.tec-principal-check {
    display: inline-flex !important;
    align-items: center;
    padding: 8px 12px;
    background: #eef4fb;
    border: 1px solid #cfe4f5;
    border-radius: 10px;
    transition: background 0.14s ease, border-color 0.14s ease;
}

.tec-principal-check:hover {
    background: #e0ecf7;
    border-color: #0d84c9;
}

.tec-check-hint {
    font-size: 12px;
    color: #7b8a9c;
    font-weight: 400;
}

.tecnico-op__star {
    color: #e08a1e;
    margin-right: 4px;
}

.tecnico-op__tag {
    float: right;
    font-size: 11px;
    font-weight: 700;
    color: #e08a1e;
}

.mt-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    background: linear-gradient(180deg, #fafcfe 0%, #ffffff 100%);
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.mt-footer .btn-submit {
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%) !important;
    border-color: #0d84c9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.mt-footer .btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
}

/* ==========================================================
   MODAL MATERIAL
   ========================================================== */
.modal-material :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-material :deep(.ant-modal-body) {
    padding: 0;
}

.modal-material__wrap {
    display: flex;
    flex-direction: column;
    background: #f5f8fb;
}

.mm-head {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    background: linear-gradient(135deg, #ffffff 0%, #fff7eb 100%);
    border-bottom: 1px solid #f7e4c4;
    flex-shrink: 0;
    overflow: hidden;
}

.mm-head::before {
    content: '';
    position: absolute;
    right: -60px;
    top: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(224, 138, 30, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.mm-head__ico {
    position: relative;
    z-index: 1;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%);
    box-shadow: 0 8px 20px -8px rgba(224, 138, 30, 0.65);
}

.mm-head__meta {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
}

.mm-head__row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.mm-head__titulo {
    font-size: 17px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
}

.mm-head__badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #a86717;
    background: #fdf3e6;
    border: 1px solid #f7e4c4;
}

.mm-head__sub {
    margin: 3px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.mm-head__close {
    position: relative;
    z-index: 1;
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

.mm-head__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.mm-body {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px 22px 6px;
    background: #fff;
}

.mat-preview {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: linear-gradient(180deg, #fffaf1 0%, #fdf7ec 100%);
    border: 1px solid #f7e4c4;
    animation: matFade 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes matFade {
    from {
        opacity: 0;
        transform: translateY(4px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mat-preview__ic {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%);
    box-shadow: 0 4px 10px -3px rgba(224, 138, 30, 0.5);
}

.mat-preview__info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.mat-preview__nombre {
    font-size: 13.5px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.2;
}

.mat-preview__sub {
    font-size: 11.5px;
    color: #a86717;
    font-weight: 600;
}

.mat-total-preview {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-radius: 11px;
    background: linear-gradient(135deg, #fffaf1 0%, #fdf3e6 100%);
    border: 1px solid #f7e4c4;
    margin-top: 10px;
    animation: matFade 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.mat-total-preview__l {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #a86717;
}

.mat-total-preview__l .anticon {
    font-size: 12px;
}

.mat-total-preview__v {
    font-size: 18px;
    font-weight: 800;
    color: #173a5f;
}

.mm-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    background: linear-gradient(180deg, #fafcfe 0%, #ffffff 100%);
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.mm-footer .btn-submit {
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%) !important;
    border-color: #e08a1e !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(224, 138, 30, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.mm-footer .btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
}

/* ==========================================================
   MODAL AGREGAR OBSERVACIÓN — rediseñado
   ========================================================== */
.modal-obs :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-obs :deep(.ant-modal-body) {
    padding: 0;
}

.modal-obs__wrap {
    display: flex;
    flex-direction: column;
    background: #f5f8fb;
}

.mo-head {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    background: linear-gradient(135deg, #ffffff 0%, #eef4fb 100%);
    border-bottom: 1px solid #cfe4f5;
    flex-shrink: 0;
    overflow: hidden;
}

.mo-head::before {
    content: '';
    position: absolute;
    right: -60px;
    top: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(13, 132, 201, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.mo-head__ico {
    position: relative;
    z-index: 1;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 8px 20px -8px rgba(13, 132, 201, 0.65);
}

.mo-head__meta {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
}

.mo-head__row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.mo-head__titulo {
    font-size: 17px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
}

.mo-head__badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #0d6ca6;
    background: #e8f3fb;
    border: 1px solid #cfe3f2;
}

.mo-head__sub {
    margin: 3px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.mo-head__close {
    position: relative;
    z-index: 1;
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

.mo-head__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.mo-body {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px 22px 6px;
    background: #fff;
}

/* Selector de tipo como tarjetas */
.obs-tipos {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.obs-tipo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 11px;
    border: 1.5px solid #e2e8f0;
    background: linear-gradient(180deg, #ffffff 0%, #fafcfe 100%);
    cursor: pointer;
    font-family: inherit;
    text-align: left;
    transition: border-color 0.14s ease, background 0.14s ease, box-shadow 0.14s ease, transform 0.14s ease;
}

.obs-tipo:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -10px rgba(13, 132, 201, 0.35);
}

.obs-tipo--active {
    border-color: #0d84c9;
    background: linear-gradient(180deg, #eef6fc 0%, #e0effb 100%);
    box-shadow: 0 6px 16px -8px rgba(13, 132, 201, 0.5);
}

.obs-tipo__ic {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    box-shadow: 0 4px 10px -4px rgba(15, 37, 71, 0.5);
}

.obs-tipo__ic--com {
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
}

.obs-tipo__ic--sup {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
}

.obs-tipo__t {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}

.obs-tipo__l {
    font-size: 13px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.15;
}

.obs-tipo__s {
    font-size: 10.5px;
    color: #7b8a9c;
    line-height: 1.2;
}

.mo-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    background: linear-gradient(180deg, #fafcfe 0%, #ffffff 100%);
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.mo-footer .btn-submit {
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%) !important;
    border-color: #0d84c9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.mo-footer .btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
}

/* ==========================================================
   MODAL REPROGRAMAR
   ========================================================== */
.modal-reprogramar :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-reprogramar :deep(.ant-modal-body) {
    padding: 0;
}

.modal-reprogramar__wrap {
    display: flex;
    flex-direction: column;
    background: #f5f8fb;
}

.mr-head {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f1fd 100%);
    border-bottom: 1px solid #e0d3f7;
    flex-shrink: 0;
    overflow: hidden;
}

.mr-head::before {
    content: '';
    position: absolute;
    right: -60px;
    top: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(107, 75, 201, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.mr-head__ico {
    position: relative;
    z-index: 1;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 8px 20px -8px rgba(107, 75, 201, 0.65);
}

.mr-head__meta {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
}

.mr-head__row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.mr-head__titulo {
    font-size: 17px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
}

.mr-head__badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #6b4bc9;
    background: #efe9fb;
    border: 1px solid #ddd2f5;
}

.mr-head__sub {
    margin: 3px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.mr-head__close {
    position: relative;
    z-index: 1;
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

.mr-head__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.mr-body {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px 22px 6px;
    background: #fff;
}

.rango {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 12px;
}

.rango__campo {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}

.rango__sep {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 40px;
    padding: 0 8px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7b8a9c;
    white-space: nowrap;
    align-self: end;
}

.label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #173a5f;
    margin-bottom: 5px;
}

.error-msg {
    margin: 2px 0 0;
    font-size: 12px;
    color: #d64545;
    line-height: 1.3;
}

.mr-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    background: linear-gradient(180deg, #fafcfe 0%, #ffffff 100%);
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.mr-footer .btn-submit {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%) !important;
    border-color: #6b4bc9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(107, 75, 201, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.mr-footer .btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
}

/* Responsive */
@media (min-width: 1200px) {
    .ficha-compacta {
        max-height: calc(100vh - 100px);
        overflow: hidden;
    }

    .grid-ficha {
        min-height: 0;
    }

    .col-izq,
    .col-der {
        max-height: calc(100vh - 260px);
        overflow-y: auto;
        scrollbar-width: thin;
        padding-right: 4px;
    }
}

@media (max-width: 1199px) {
    .grid-ficha {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .trabajo-grid-2x2 {
        grid-template-columns: 1fr;
    }

    .tv-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767px) {

    .tr-grid,
    .mat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .tr-grid {
        grid-template-rows: auto;
    }

    .rango {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .rango__sep {
        height: auto;
        align-self: center;
        padding: 4px 0;
    }

    .btn-hero {
        width: 100%;
        justify-content: flex-start;
    }

    .btn-more {
        width: 100%;
    }

    .tecnico-grid-2x2 {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575px) {
    .mini-grid--2 {
        grid-template-columns: 1fr;
    }

    .problema-card {
        flex-direction: column;
        align-items: stretch;
    }

    .diagnostico-vacio {
        flex-direction: column;
        text-align: center;
    }

    .btn-diagnosticar {
        width: 100%;
        justify-content: center;
    }

    .tr-grid,
    .mat-grid {
        grid-template-columns: 1fr;
    }

    .obs-tipos {
        grid-template-columns: 1fr;
    }

    .mr-head,
    .mt-head,
    .mm-head,
    .mt2-head,
    .mo-head {
        padding: 14px 16px;
    }

    .mr-head__titulo,
    .mt-head__titulo,
    .mm-head__titulo,
    .mt2-head__titulo,
    .mo-head__titulo {
        font-size: 15px;
    }

    .mr-body,
    .mt-body,
    .mm-body,
    .mt2-body,
    .mo-body {
        padding: 14px 16px 6px;
    }

    .step {
        padding: 12px 14px;
    }

    .mr-footer,
    .mt-footer,
    .mm-footer,
    .mt2-footer,
    .mo-footer {
        padding: 12px 16px;
        flex-direction: column-reverse;
    }

    .mr-footer .ant-btn,
    .mt-footer .ant-btn,
    .mm-footer .ant-btn,
    .mt2-footer .ant-btn,
    .mo-footer .ant-btn {
        width: 100%;
    }
}
</style>