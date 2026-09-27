<script setup>
import { computed, h, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CalendarOutlined,
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
import BotonTomarFoto from '@/Components/BotonTomarFoto.vue';
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
    autorizado: 'cyan',
    asignado: 'blue',
    en_proceso: 'processing',
    en_espera_refaccion: 'orange',
    fuera_de_servicio: 'volcano',
    realizado: 'lime',
    supervisado: 'geekblue',
    cerrado: 'green',
    reprogramado: 'purple',
    cancelado: 'red',
};

const estadoColor = computed(() => ESTADO_COLOR[m.value.estado?.clave] ?? 'default');
const estadoClave = computed(() => m.value.estado?.clave);
const esCerrada = computed(() => ['cerrado', 'cancelado'].includes(estadoClave.value));
const enProceso = computed(() => estadoClave.value === 'en_proceso');

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

const infoOrden = computed(() => {
    const base = [
        { icono: TagOutlined, label: 'Tipo', valor: dato(m.value.tipo?.nombre), color: '#0d84c9' },
        { icono: FlagOutlined, label: 'Prioridad', valor: dato(m.value.prioridad?.nombre), color: m.value.prioridad?.color || '#d64545' },
        { icono: CalendarOutlined, label: 'Programado', valor: fecha(m.value.programado_inicio), color: '#6b4bc9' },
        { icono: FileDoneOutlined, label: 'Solicitud origen', valor: dato(m.value.solicitud?.folio), color: '#e08a1e' },
    ];
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

/* ---------- Resumen compacto del diagnóstico ---------- */
const resumenTrabajo = computed(() => [
    { key: 'diagnostico',   icono: FileDoneOutlined,    label: 'Diagnóstico',     valor: m.value.diagnostico,             color: '#6b4bc9' },
    { key: 'actividades',   icono: ToolOutlined,        label: 'Actividades',     valor: m.value.descripcion_trabajo,     color: '#0d84c9' },
    { key: 'observaciones', icono: FileTextOutlined,    label: 'Observaciones',   valor: m.value.observaciones,           color: '#173a5f' },
    { key: 'condicion',     icono: CheckCircleOutlined, label: 'Condición final', valor: m.value.condicion_final,         color: '#1f9e86' },
    { key: 'mano_obra',     icono: DollarOutlined,      label: 'Mano de obra',    valor: moneda(m.value.costo_mano_obra), color: '#e08a1e', mono: true },
    { key: 'otros_costos',  icono: DollarOutlined,      label: 'Otros costos',    valor: moneda(m.value.costo_otros),     color: '#a86717', mono: true },
]);

/* ---------- Materiales: grid 3×2 con paginación en "más" ---------- */
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
const fileListEstado = ref([]);
const abrirEstado = (slug) => {
    formEstado.reset();
    formEstado.clearErrors();
    formEstado.estado = slug;
    fileListEstado.value = [];
    modalEstado.value = true;
};
const antesDeSubirEstado = (file) => {
    fileListEstado.value = [file];
    formEstado.evidencia = file;
    return false;
};
const quitarEvidenciaEstado = () => {
    fileListEstado.value = [];
    formEstado.evidencia = null;
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

const botonesTransicion = computed(() =>
    props.transicionesPosibles
        .filter((slug) => slug !== 'reprogramado' && slug !== 'autorizado')
        .map((slug, i) => ({
            slug,
            label: ETIQUETA_ESTADO[slug] ?? slug,
            icono: ICONO_ESTADO[slug] ?? SwapOutlined,
            tipo: slug === 'cancelado' ? undefined : (i === 0 ? 'primary' : 'default'),
            peligro: slug === 'cancelado',
        })),
);

// --- Reprogramar ---
const modalReprogramar = ref(false);
const formReprogramar = useForm({ programado_inicio: '', programado_fin: '', motivo: '', evidencia: null });
const fileListReprog = ref([]);
const reglasReprogramar = reactive({
    programado_inicio: [{ required: true, message: 'Indica la nueva fecha.' }, reglaNoPasada('La nueva fecha no puede ser anterior a hoy.')],
    programado_fin: [reglaDespuesDe(() => formReprogramar.programado_inicio, 'El fin debe ser posterior al inicio.', true)],
    motivo: [{ required: true, message: 'Indica el motivo.' }],
});
const abrirReprogramar = () => {
    formReprogramar.reset();
    formReprogramar.clearErrors();
    fileListReprog.value = [];
    modalReprogramar.value = true;
};
const antesDeSubirReprog = (file) => {
    fileListReprog.value = [file];
    formReprogramar.evidencia = file;
    return false;
};
const quitarEvidenciaReprog = () => {
    fileListReprog.value = [];
    formReprogramar.evidencia = null;
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

// --- Diagnóstico y trabajo (MODAL ÚNICO: ver / editar) ---
const modalTrabajo = ref(false);
const modoTrabajo = ref('ver'); // 'ver' | 'editar'
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
const formTecnico = useForm({ tecnico_id: undefined, es_principal: false, notas: '' });
const tecnicoSeleccionado = computed(() =>
    (props.catalogos.tecnicos ?? []).find((t) => t.id === formTecnico.tecnico_id),
);
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
const agregarObs = () => {
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
const modalBitacora = ref(false);

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
                        <a-button v-for="b in botonesTransicion" :key="b.slug" :type="b.tipo" :danger="b.peligro"
                            @click="abrirEstado(b.slug)">
                            <template #icon>
                                <component :is="b.icono" />
                            </template>
                            {{ b.label }}
                        </a-button>
                        <a-button @click="abrirReprogramar">
                            <template #icon>
                                <CalendarOutlined />
                            </template>
                            Reprogramar
                        </a-button>
                    </template>
                    <a-dropdown v-if="puede('mantenimientos.editar') && !esCerrada">
                        <a-button type="text">
                            <template #icon>
                                <EllipsisOutlined />
                            </template>
                        </a-button>
                        <template #overlay>
                            <a-menu
                                :items="[{ key: 'baja', label: 'Eliminar orden', danger: true, icon: () => h(DeleteOutlined) }]"
                                @click="darBaja" />
                        </template>
                    </a-dropdown>
                </template>
            </FichaEncabezado>

            <!-- Descripción destacada + botón Bitácora -->
            <div class="problema-card" :class="{ 'problema-card--cerrada': esCerrada }">
                <div class="problema-card__icono">
                    <component :is="m.equipo ? ToolOutlined : EnvironmentOutlined" />
                </div>
                <div class="problema-card__meta">
                    <div class="problema-card__objetivo">
                        {{ m.equipo ? `${m.equipo.codigo_activo} — ${m.equipo.descripcion}` : (m.ubicacion?.nombre ?? '') }}
                    </div>
                    <div class="problema-card__desc">
                        {{ m.problema_reportado || 'ORDEN DE MANTENIMIENTO SIN DESCRIPCIÓN DE PROBLEMA.' }}
                    </div>
                </div>
                <button type="button" class="btn-bitacora" @click="modalBitacora = true">
                    <ClockCircleOutlined />
                    <span>Bitácora</span>
                    <span v-if="bitacoraTotal" class="btn-bitacora__badge">{{ bitacoraTotal }}</span>
                </button>
            </div>

            <div class="grid-ficha">
                <!-- COLUMNA IZQUIERDA -->
                <div class="col-izq">
                    <!-- Información de la orden -->
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

                    <!-- Ubicación y responsables -->
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
                                <div v-for="d in infoResponsables" :key="d.label" class="mini" :style="{ '--c': d.color }">
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

                    <!-- Técnicos -->
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
                            <a-button v-if="puede('mantenimientos.asignar') && !esCerrada" class="card__extra"
                                size="small" type="text" @click="modalTecnico = true">
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
                                    <button v-if="puede('mantenimientos.asignar') && !esCerrada" type="button"
                                        class="tecnico__x" title="Retirar" @click="quitarTecnico(t)">
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

                <!-- COLUMNA DERECHA -->
                <div class="col-der">
                    <!-- Diagnóstico y trabajo (resumen + botón Ver detalle) -->
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

                            <!-- Estado vacío -->
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

                            <!-- Resumen + botón Ver detalle -->
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

                    <!-- Materiales (grid 3×2) -->
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
                            <a-button v-if="puede('mantenimientos.editar') && !esCerrada" class="card__extra"
                                size="small" type="text" @click="modalMaterial = true">
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
                                            <button v-if="puede('mantenimientos.editar') && !esCerrada" type="button"
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
             MODALES
             ========================================================== -->

        <!-- Diagnóstico y trabajo (modal general VER / EDITAR) -->
        <a-modal v-model:open="modalTrabajo"
            :title="modoTrabajo === 'ver' ? 'Diagnóstico y trabajo' : 'Editar diagnóstico y trabajo'"
            :ok-text="modoTrabajo === 'ver' ? 'Cerrar' : 'Guardar'"
            :cancel-text="modoTrabajo === 'ver' ? undefined : 'Cancelar'"
            :cancel-button-props="modoTrabajo === 'ver' ? { style: 'display: none' } : {}"
            :confirm-loading="formTrabajo.processing" :width="720" centered
            @ok="modoTrabajo === 'ver' ? cerrarTrabajo() : guardarTrabajo()" @cancel="cerrarTrabajo">

            <!-- Vista de solo lectura -->
            <div v-if="modoTrabajo === 'ver'" class="trabajo-ver">
                <div v-for="b in resumenTrabajo" :key="b.key" class="tv-bloque" :style="{ '--c': b.color }">
                    <div class="tv-bloque__head">
                        <span class="tv-bloque__ic">
                            <component :is="b.icono" />
                        </span>
                        <span class="tv-bloque__l">{{ b.label }}</span>
                    </div>
                    <div class="tv-bloque__v" :class="{ 'tv-bloque__v--vacio': !b.valor, 'tv-bloque__v--mono': b.mono }">
                        {{ b.valor || 'Sin registrar' }}
                    </div>
                </div>
            </div>

            <!-- Vista de edición -->
            <div v-else>
                <a-alert v-if="!puede('mantenimientos.editar')" type="info" show-icon class="mb-3"
                    message="Solo lectura: no tienes permiso para editar la captura de trabajo." />
                <a-form layout="vertical" class="pt-2" :disabled="!puede('mantenimientos.editar')">
                    <a-form-item label="Diagnóstico">
                        <a-textarea v-model:value="formTrabajo.diagnostico" :rows="3" />
                    </a-form-item>
                    <a-form-item label="Actividades realizadas">
                        <a-textarea v-model:value="formTrabajo.descripcion_trabajo" :rows="3" />
                    </a-form-item>
                    <a-form-item label="Observaciones">
                        <a-textarea v-model:value="formTrabajo.observaciones" :rows="2" />
                    </a-form-item>
                    <a-row :gutter="16">
                        <a-col :xs="24" :sm="8">
                            <a-form-item label="Condición final">
                                <a-input v-model:value="formTrabajo.condicion_final" />
                            </a-form-item>
                        </a-col>
                        <a-col :xs="12" :sm="8">
                            <a-form-item label="Costo mano de obra">
                                <a-input v-model:value="formTrabajo.costo_mano_obra" type="number" prefix="$" />
                            </a-form-item>
                        </a-col>
                        <a-col :xs="12" :sm="8">
                            <a-form-item label="Otros costos">
                                <a-input v-model:value="formTrabajo.costo_otros" type="number" prefix="$" />
                            </a-form-item>
                        </a-col>
                    </a-row>
                </a-form>
            </div>

            <template #footer>
                <template v-if="modoTrabajo === 'ver'">
                    <a-button v-if="puede('mantenimientos.editar') && !esCerrada" type="primary"
                        @click="abrirEditarTrabajo">
                        <template #icon>
                            <EditOutlined />
                        </template>
                        Editar
                    </a-button>
                    <a-button @click="cerrarTrabajo">Cerrar</a-button>
                </template>
                <template v-else>
                    <a-button @click="cerrarTrabajo">Cancelar</a-button>
                    <a-button type="primary" :loading="formTrabajo.processing" @click="guardarTrabajo">Guardar</a-button>
                </template>
            </template>
        </a-modal>

        <!-- Modal: todos los materiales -->
        <a-modal v-model:open="modalMaterialesTodos" :title="`Materiales (${m.materiales?.length ?? 0})`"
            :footer="null" :width="720">
            <div class="mat-grid mat-grid--modal">
                <div v-for="mat in (m.materiales ?? [])" :key="mat.id" class="mat-card">
                    <span class="mat-card__head">
                        <span class="mat-card__ic">
                            <ToolOutlined />
                        </span>
                        <span class="mat-card__l">
                            {{ mat.material?.nombre || mat.descripcion }}
                        </span>
                        <button v-if="puede('mantenimientos.editar') && !esCerrada" type="button"
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

        <!-- Bitácora -->
        <a-modal v-model:open="modalBitacora" :title="`Bitácora (${bitacoraTotal})`" :footer="null" :width="640">
            <div class="bitacora-modal">
                <template v-if="m.bitacora?.length">
                    <div v-for="o in (m.bitacora ?? [])" :key="o.id" class="bitacora__it">
                        <div class="bitacora__cont">
                            <div class="bitacora__t">
                                <a-tag class="bitacora__tag">{{ o.tipo }}</a-tag>
                                <span class="bitacora__cuerpo">{{ o.cuerpo }}</span>
                            </div>
                            <div class="bitacora__meta">
                                {{ o.usuario?.nombre ?? 'Sistema' }} · {{ fecha(o.created_at) }}
                            </div>
                        </div>
                        <button v-if="o.evidencia" type="button" class="bitacora__foto" title="Ver evidencia"
                            @click="abrirFotoBitacora(o.evidencia)">
                            <img :src="o.evidencia.url" alt="Evidencia" />
                        </button>
                    </div>
                </template>
                <div v-else class="vacio-box">
                    <ClockCircleOutlined />
                    <span>Sin movimientos registrados</span>
                </div>
            </div>
            <template #footer>
                <a-button v-if="puede('mantenimientos.editar') && !esCerrada" type="primary"
                    @click="modalBitacora = false; modalObs = true">
                    <template #icon>
                        <PlusOutlined />
                    </template>
                    Agregar observación
                </a-button>
            </template>
        </a-modal>

        <!-- Evidencias -->
        <a-modal v-model:open="modalEvidencias" :title="`Evidencias (${m.documentos?.length ?? 0})`" :footer="null"
            :width="720">
            <ListaDocumentos :documentos="m.documentos ?? []" relacionable-tipo="mantenimiento" :relacionable-id="m.id"
                :roles="['evidencia', 'antes', 'despues', 'factura', 'garantia']"
                :puede-subir="puede('documentos.crear') && !esCerrada"
                :puede-eliminar="puede('documentos.desactivar') && !esCerrada" />
        </a-modal>

        <!-- Foto bitácora -->
        <ModalFicha :show="modalFotoBitacora" :titulo="fotoBitacora?.titulo ?? 'Evidencia'"
            :subtitulo="fotoBitacora?.nombre_original" :icono="ToolOutlined" color="#e08a1e" max-width="lg"
            @close="modalFotoBitacora = false">
            <div v-if="fotoBitacora?.url" class="foto-modal">
                <img :src="fotoBitacora.url" alt="Evidencia" />
            </div>
            <template #footer>
                <a v-if="fotoBitacora" :href="route('documentos.download', fotoBitacora.id)"
                    class="ant-btn ant-btn-default" target="_blank">Descargar</a>
                <a v-if="fotoBitacora" :href="fotoBitacora.url"
                    class="ant-btn ant-btn-primary" target="_blank">Abrir en nueva pestaña</a>
            </template>
        </ModalFicha>

        <!-- Cronología -->
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
        <a-modal v-model:open="modalEstado" :title="ETIQUETA_ESTADO[formEstado.estado] || 'Cambiar estado'"
            ok-text="Confirmar" cancel-text="Cancelar" :confirm-loading="formEstado.processing" @ok="confirmarEstado">
            <a-form layout="vertical" class="pt-2">
                <a-form-item label="Nota (opcional)">
                    <a-textarea v-model:value="formEstado.nota" :rows="2" />
                </a-form-item>
                <a-form-item label="Evidencia" required extra="Foto, reporte o documento que respalde este movimiento."
                    :validate-status="formEstado.errors.evidencia ? 'error' : undefined"
                    :help="formEstado.errors.evidencia">
                    <a-upload-dragger :file-list="fileListEstado" :max-count="1" :before-upload="antesDeSubirEstado"
                        accept=".pdf,.jpg,.jpeg,.png,.webp" @remove="quitarEvidenciaEstado">
                        <p class="ant-upload-drag-icon">
                            <InboxOutlined />
                        </p>
                        <p class="ant-upload-text">Haz clic o arrastra un archivo</p>
                        <p class="ant-upload-hint">PDF, JPG, PNG o WEBP · máx. 20 MB</p>
                    </a-upload-dragger>
                    <BotonTomarFoto block class="mt-2" texto="O tomar foto con la cámara" titulo="Evidencia"
                        @capturada="antesDeSubirEstado" />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- Reprogramar -->
        <a-modal v-model:open="modalReprogramar" title="Reprogramar orden" ok-text="Reprogramar" cancel-text="Cancelar"
            :confirm-loading="formReprogramar.processing" @ok="reprogramar">
            <a-form :model="formReprogramar" :rules="reglasReprogramar" layout="vertical" class="pt-2">
                <a-form-item label="Nueva fecha y hora de inicio" name="programado_inicio"
                    :validate-status="formReprogramar.errors.programado_inicio ? 'error' : undefined"
                    :help="formReprogramar.errors.programado_inicio">
                    <CampoFechaHora v-model="formReprogramar.programado_inicio" :min-fecha="hoyISO()" />
                </a-form-item>
                <a-form-item label="Nueva fecha y hora de fin" name="programado_fin"
                    :help="formReprogramar.errors.programado_fin"
                    :validate-status="formReprogramar.errors.programado_fin ? 'error' : undefined">
                    <CampoFechaHora v-model="formReprogramar.programado_fin"
                        :min-fecha="formReprogramar.programado_inicio ? formReprogramar.programado_inicio.slice(0, 10) : hoyISO()" />
                </a-form-item>
                <a-form-item label="Motivo" name="motivo"
                    :validate-status="formReprogramar.errors.motivo ? 'error' : undefined"
                    :help="formReprogramar.errors.motivo">
                    <a-textarea v-model:value="formReprogramar.motivo" :rows="2" />
                </a-form-item>
                <a-form-item label="Evidencia" required extra="Lo que justifica el cambio de fecha."
                    :validate-status="formReprogramar.errors.evidencia ? 'error' : undefined"
                    :help="formReprogramar.errors.evidencia">
                    <a-upload-dragger :file-list="fileListReprog" :max-count="1" :before-upload="antesDeSubirReprog"
                        accept=".pdf,.jpg,.jpeg,.png,.webp" @remove="quitarEvidenciaReprog">
                        <p class="ant-upload-drag-icon">
                            <InboxOutlined />
                        </p>
                        <p class="ant-upload-text">Haz clic o arrastra un archivo</p>
                        <p class="ant-upload-hint">PDF, JPG, PNG o WEBP · máx. 20 MB</p>
                    </a-upload-dragger>
                    <BotonTomarFoto block class="mt-2" texto="O tomar foto con la cámara" titulo="Evidencia"
                        @capturada="antesDeSubirReprog" />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- Asignar técnico -->
        <a-modal v-model:open="modalTecnico" title="Asignar técnico" ok-text="Asignar" cancel-text="Cancelar"
            :confirm-loading="formTecnico.processing" @ok="asignarTecnico">
            <a-form layout="vertical" class="pt-2">
                <a-form-item label="Técnico"
                    extra="Los marcados con ⭐ tienen una especialidad registrada que coincide con esta orden."
                    :validate-status="formTecnico.errors.tecnico_id ? 'error' : undefined"
                    :help="formTecnico.errors.tecnico_id">
                    <a-select v-model:value="formTecnico.tecnico_id">
                        <a-select-option v-for="t in catalogos.tecnicos" :key="t.id" :value="t.id">
                            <StarFilled v-if="t.recomendado" class="tecnico-op__star" />
                            {{ t.nombre }}
                            <span v-if="t.recomendado" class="tecnico-op__tag">Recomendado</span>
                        </a-select-option>
                    </a-select>
                </a-form-item>
                <div v-if="tecnicoSeleccionado" class="tecnico-cualidades">
                    <span class="tecnico-cualidades__t">Cualidades de {{ tecnicoSeleccionado.nombre }}</span>
                    <a-space v-if="tecnicoSeleccionado.cualidades?.length" wrap>
                        <a-tag v-for="c in tecnicoSeleccionado.cualidades" :key="c" color="blue">{{ c }}</a-tag>
                    </a-space>
                    <span v-else class="tecnico-cualidades__vacio">Sin especialidades registradas todavía.</span>
                </div>
                <a-form-item>
                    <a-checkbox v-model:checked="formTecnico.es_principal">Responsable principal</a-checkbox>
                </a-form-item>
                <a-form-item label="Notas">
                    <a-input v-model:value="formTecnico.notas" />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- Agregar material -->
        <a-modal v-model:open="modalMaterial" title="Agregar material" ok-text="Agregar" cancel-text="Cancelar"
            :confirm-loading="formMaterial.processing" @ok="agregarMaterial">
            <a-form :model="formMaterial" :rules="reglasMaterial" layout="vertical" class="pt-2">
                <a-form-item label="Del catálogo">
                    <SelectCatalogo v-model:value="formMaterial.material_id" :options="catalogos.materiales"
                        ruta="catalogos.materiales" etiqueta="material" etiqueta-plural="materiales"
                        placeholder="Opcional — o captura la descripción abajo" :campos="[
                            { name: 'unidad', label: 'Unidad (pza, m, lt…)', ancho: 12 },
                            { name: 'costo_referencia', label: 'Costo referencia', tipo: 'number', min: 0 },
                        ]" @update:value="onMaterialSel" />
                </a-form-item>
                <a-form-item v-if="!formMaterial.material_id" label="Descripción"
                    :validate-status="formMaterial.errors.descripcion ? 'error' : undefined"
                    :help="formMaterial.errors.descripcion">
                    <a-input v-model:value="formMaterial.descripcion" />
                </a-form-item>
                <a-row :gutter="12">
                    <a-col :span="8">
                        <a-form-item label="Cantidad" name="cantidad"
                            :validate-status="formMaterial.errors.cantidad ? 'error' : undefined"
                            :help="formMaterial.errors.cantidad">
                            <a-input v-model:value="formMaterial.cantidad" type="number" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="8">
                        <a-form-item label="Unidad"><a-input v-model:value="formMaterial.unidad" /></a-form-item>
                    </a-col>
                    <a-col :span="8">
                        <a-form-item label="Costo unit."><a-input v-model:value="formMaterial.costo_unitario"
                                type="number" prefix="$" /></a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>

        <!-- Agregar observación -->
        <a-modal v-model:open="modalObs" title="Agregar observación" ok-text="Agregar" cancel-text="Cancelar"
            :confirm-loading="formObs.processing" @ok="agregarObs">
            <a-alert type="info" show-icon class="mb-3"
                message="La bitácora es para notas rápidas o seguimiento. El diagnóstico y las actividades formales se registran en «Diagnóstico y trabajo»." />
            <a-form :model="formObs" :rules="reglasObs" layout="vertical" class="pt-2">
                <a-form-item label="Tipo">
                    <a-select v-model:value="formObs.tipo" :options="[
                        { value: 'comentario', label: 'Comentario' },
                        { value: 'supervision', label: 'Supervisión' },
                    ]" />
                </a-form-item>
                <a-form-item label="Observación" name="cuerpo"
                    :validate-status="formObs.errors.cuerpo ? 'error' : undefined" :help="formObs.errors.cuerpo">
                    <a-textarea v-model:value="formObs.cuerpo" :rows="3" />
                </a-form-item>
            </a-form>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
/* Modal foto */
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
   Layout base
   ========================================================== */
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

/* ---------- Descripción destacada + botón bitácora ---------- */
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
    box-shadow: 0 4px 10px rgba(100, 116, 139, 0.25);
}
.problema-card__meta { min-width: 0; flex: 1; }
.problema-card__objetivo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 800;
    font-size: 14.5px;
    color: #b52222;
    margin-bottom: 3px;
}
.problema-card--cerrada .problema-card__objetivo { color: var(--sigam-tenue); }
.problema-card__desc {
    white-space: pre-line;
    text-transform: uppercase;
    font-weight: 700;
    font-size: 13px;
    line-height: 1.4;
    color: #d64545;
}
.problema-card--cerrada .problema-card__desc { color: var(--sigam-texto); }

.btn-bitacora {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 38px;
    padding: 0 16px;
    border: 1px solid #cfe3f2;
    border-radius: 10px;
    background: #fff;
    color: #0d6ca6;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    transition: background 0.14s ease, border-color 0.14s ease, transform 0.14s ease, box-shadow 0.14s ease;
}
.btn-bitacora:hover {
    background: #e8f3fb;
    border-color: #0d84c9;
    color: #0a6ba6;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(13, 132, 201, 0.18);
}
.btn-bitacora .anticon { font-size: 14px; }
.btn-bitacora__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    background: #0d84c9;
    color: #fff;
    font-size: 10.5px;
    font-weight: 800;
}

/* ---------- Grid 2 columnas ---------- */
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

/* ---------- Cards ---------- */
.card {
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--sigam-sombra-sm);
    transition: box-shadow 0.18s ease;
}
.card:hover { box-shadow: var(--sigam-sombra-md); }
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
    letter-spacing: -0.1px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
.card__sub { font-size: 10.5px; color: var(--sigam-tenue); }
.card__extra { flex-shrink: 0; }
.card__body { padding: 12px 15px; }

/* ---------- Badges ---------- */
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
.badge--green { background: #e4f4ec; color: #16806c; }
.badge--orange { background: #fdf3e6; color: #a86717; }

/* ---------- Mini-cards ---------- */
.mini-grid { display: grid; gap: 8px; }
.mini-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.mini-grid--full { grid-template-columns: 1fr; }
.mt-grid { margin-top: 8px; }
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
    letter-spacing: 0.03em;
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

/* ---------- Normas inline ---------- */
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
    letter-spacing: 0.03em;
    color: var(--sigam-tenue);
}

/* ==========================================================
   DIAGNÓSTICO Y TRABAJO (resumen compacto + ver detalle)
   ========================================================== */
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
.diagnostico-vacio__txt { flex: 1; min-width: 180px; }
.diagnostico-vacio__t {
    font-weight: 800;
    color: var(--sigam-navy);
    font-size: 13px;
    line-height: 1.2;
    letter-spacing: -0.1px;
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
    letter-spacing: -0.1px;
    color: #fff;
    cursor: pointer;
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 4px 12px rgba(107, 75, 201, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
    -webkit-tap-highlight-color: transparent;
    flex-shrink: 0;
}
.btn-diagnosticar:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.08);
    box-shadow: 0 6px 16px rgba(107, 75, 201, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.25);
}
.btn-diagnosticar:active:not(:disabled) { transform: translateY(0) scale(0.97); }
.btn-diagnosticar:disabled {
    cursor: not-allowed;
    color: #94a3b8;
    background: #e2e8f0;
    box-shadow: none;
    filter: none;
}

/* Resumen */
.trabajo-resumen { display: flex; flex-direction: column; gap: 10px; }
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
.tr-card__head { display: flex; align-items: center; gap: 6px; min-width: 0; }
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
.tr-card--vacio .tr-card__ic { background: var(--sigam-tenue); }
.tr-card__l {
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: color-mix(in srgb, var(--c) 80%, #000);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tr-card--vacio .tr-card__l { color: var(--sigam-tenue); }
.tr-card__v {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--sigam-texto);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
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
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease, filter 0.14s ease;
}
.btn-ver-trabajo:hover {
    transform: translateY(-1px);
    border-color: #6b4bc9;
    filter: brightness(1.02);
    box-shadow: 0 6px 14px rgba(107, 75, 201, 0.22);
}

/* Modal VER: bloques anchos */
.trabajo-ver {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 65vh;
    overflow-y: auto;
    padding-right: 6px;
    scrollbar-width: thin;
}
.trabajo-ver::-webkit-scrollbar { width: 6px; }
.trabajo-ver::-webkit-scrollbar-thumb { background: var(--sigam-borde); border-radius: 3px; }

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
.tv-bloque__head { display: flex; align-items: center; gap: 7px; }
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
    letter-spacing: 0.04em;
    color: color-mix(in srgb, var(--c) 80%, #000);
}
.tv-bloque__v {
    font-size: 13px;
    color: var(--sigam-texto);
    line-height: 1.55;
    white-space: pre-line;
    padding-left: 29px;
}
.tv-bloque__v--vacio { color: var(--sigam-tenue); font-style: italic; }
.tv-bloque__v--mono {
    font-size: 20px;
    font-weight: 800;
    color: var(--sigam-navy);
    letter-spacing: -0.3px;
}

/* ==========================================================
   MATERIALES (grid 3×2)
   ========================================================== */
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
.mat-grid--modal::-webkit-scrollbar { width: 6px; }
.mat-grid--modal::-webkit-scrollbar-thumb { background: var(--sigam-borde); border-radius: 3px; }

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
.mat-card__x:hover { background: #fbeaea; }
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
    letter-spacing: -0.15px;
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
    letter-spacing: -0.1px;
}
.mat-total--modal {
    margin-top: 14px;
    font-size: 14px;
}

/* ---------- Técnicos ---------- */
.tecnicos { display: flex; flex-direction: column; gap: 7px; }
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
.tecnico:hover { transform: translateY(-1px); box-shadow: var(--sigam-sombra-sm); }
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
.tecnico__tag { font-size: 10px; line-height: 16px; padding: 0 6px; margin: 0; }
.tecnico__sub { font-size: 10.5px; color: var(--sigam-tenue); }
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
.tecnico__x:hover { background: #fbeaea; }

/* ---------- Bitácora (dentro del modal) ---------- */
.bitacora__it {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 11px;
    border-radius: 10px;
    background: var(--sigam-navy-050);
    border-left: 3px solid #173a5f;
}
.bitacora__cont { flex: 1; min-width: 0; }
.bitacora__foto {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    padding: 0;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #cfe3f2;
    cursor: pointer;
    transition: transform 0.14s ease, box-shadow 0.14s ease;
}
.bitacora__foto img { display: block; width: 100%; height: 100%; object-fit: cover; }
.bitacora__foto:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(13, 132, 201, 0.2); }
.bitacora__t { display: flex; flex-direction: column; gap: 4px; }
.bitacora__tag { align-self: flex-start; margin: 0; font-size: 10px; line-height: 16px; padding: 0 6px; }
.bitacora__cuerpo { font-size: 12px; color: var(--sigam-texto); white-space: pre-line; }
.bitacora__meta { font-size: 10.5px; color: var(--sigam-tenue); margin-top: 4px; }
.bitacora-modal {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 6px;
    scrollbar-width: thin;
}
.bitacora-modal::-webkit-scrollbar { width: 6px; }
.bitacora-modal::-webkit-scrollbar-thumb { background: var(--sigam-borde); border-radius: 3px; }

/* ---------- Vacio ---------- */
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
.vacio-box .anticon { font-size: 18px; opacity: 0.5; }

/* ---------- Cronología ---------- */
.cron { display: flex; justify-content: space-between; gap: 16px; }
.cron__origen { opacity: 0.6; }
.cron__nota { font-size: 12.5px; margin-top: 2px; }
.cron__user { font-size: 11.5px; color: var(--sigam-tenue); margin-top: 2px; }
.cron__fecha { font-size: 11.5px; color: var(--sigam-tenue); white-space: nowrap; }

/* ---------- Técnico selector ---------- */
.tecnico-op__star { color: #e08a1e; margin-right: 4px; }
.tecnico-op__tag { float: right; font-size: 11px; font-weight: 700; color: #e08a1e; }
.tecnico-cualidades {
    margin: -8px 0 16px;
    padding: 10px 12px;
    border-radius: 10px;
    background: var(--sigam-navy-050);
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.tecnico-cualidades__t {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--sigam-tenue);
}
.tecnico-cualidades__vacio { font-size: 12.5px; color: var(--sigam-tenue); font-style: italic; }

/* ---------- Sin scroll en pantallas grandes ---------- */
@media (min-width: 1200px) {
    .ficha-compacta { max-height: calc(100vh - 100px); overflow: hidden; }
    .grid-ficha { min-height: 0; }
    .col-izq,
    .col-der {
        max-height: calc(100vh - 260px);
        overflow-y: auto;
        scrollbar-width: thin;
        padding-right: 4px;
    }
    .col-izq::-webkit-scrollbar,
    .col-der::-webkit-scrollbar { width: 6px; }
    .col-izq::-webkit-scrollbar-thumb,
    .col-der::-webkit-scrollbar-thumb { background: var(--sigam-borde); border-radius: 3px; }
}

/* ---------- Responsive ---------- */
@media (max-width: 1199px) {
    .grid-ficha { grid-template-columns: 1fr; }
}
@media (max-width: 767px) {
    .tr-grid,
    .mat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .tr-grid { grid-template-rows: auto; }
}
@media (max-width: 575px) {
    .mini-grid--2 { grid-template-columns: 1fr; }
    .problema-card { flex-direction: column; align-items: stretch; }
    .btn-bitacora { width: 100%; justify-content: center; }
    .diagnostico-vacio { flex-direction: column; text-align: center; }
    .diagnostico-vacio__txt { min-width: 0; }
    .btn-diagnosticar { width: 100%; justify-content: center; }
    .tr-grid,
    .mat-grid { grid-template-columns: 1fr; }
}
</style>