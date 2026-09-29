<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckCircleFilled,
    CheckOutlined,
    ClockCircleOutlined,
    CloseOutlined,
    EnvironmentOutlined,
    FileImageOutlined,
    FilePdfOutlined,
    FileTextOutlined,
    FlagOutlined,
    FormOutlined,
    RocketOutlined,
    StopOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SubirDocumento from '@/Components/SubirDocumento.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    solicitud: { type: Object, required: true },
    puedeConvertir: { type: Boolean, default: false },
    catalogos: { type: Object, default: () => ({}) },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();

const subirDoc = ref(null);
const abrirPdf = () => subirDoc.value?.abrirPdf();
const abrirImagen = () => subirDoc.value?.abrirImagen();

const ROLES_DOC = ['evidencia', 'cotización', 'factura', 'foto'];

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const fechaHora = (v) =>
    v
        ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' })
        : 'No especificado';
const dato = (v) => v || 'No especificado';

const s = computed(() => props.solicitud);

const estadoClave = computed(() => s.value.estado?.clave);
const rechazada = computed(() => estadoClave.value === 'cancelado' || !!s.value.motivo_rechazo);
const autorizada = computed(() => !!s.value.revisadoPor && !rechazada.value);
const convertida = computed(() => (s.value.mantenimientos ?? []).length > 0);
const terminada = computed(() => rechazada.value);

const puedeEditarDocs = computed(
    () => !rechazada.value && !autorizada.value && !convertida.value,
);

const infoSolicitud = computed(() => {
    const base = [
        {
            icono: CalendarOutlined,
            label: 'Solicitada',
            valor: `${fechaHora(s.value.solicitado_at)} · ${dato(s.value.solicitante?.nombre)}`,
            color: '#0d84c9',
        },
        {
            icono: CalendarOutlined,
            label: 'Requerida',
            valor: fecha(s.value.fecha_requerida),
            color: '#e08a1e',
        },
        {
            icono: FlagOutlined,
            label: 'Prioridad sugerida',
            valor: dato(s.value.prioridad?.nombre),
            color: s.value.prioridad?.color || '#d64545',
        },
    ];

    if (autorizada.value) {
        base.push({
            icono: CheckOutlined,
            label: 'Autorizada por',
            valor: `${fechaHora(s.value.revisado_at)} · ${dato(s.value.revisadoPor.nombre)}`,
            color: '#1f9e86',
        });
    } else if (!rechazada.value) {
        base.push({
            icono: CheckOutlined,
            label: 'Autorización',
            valor: 'Pendiente',
            color: '#94a3b8',
        });
    }

    return base;
});

const infoObjetivo = computed(() => [
    s.value.equipo
        ? { icono: ToolOutlined, label: 'Equipo', valor: `${s.value.equipo.codigo_activo} — ${s.value.equipo.descripcion}`, color: '#0d84c9' }
        : { icono: EnvironmentOutlined, label: 'Instalación', valor: dato(s.value.ubicacion?.nombre), color: '#0d84c9' },
    { icono: EnvironmentOutlined, label: 'Sucursal', valor: dato(s.value.sucursal?.nombre), color: '#1f9e86' },
]);

/* ==========================================================
   Autorizar y crear orden
   ========================================================== */
const modalAutorizar = ref(false);
const formAutorizar = useForm({
    tipo_id: undefined,
    prioridad_id: s.value.prioridad_id ?? undefined,
    programado_inicio: '',
    programado_fin: '',
});
const reglasAutorizar = reactive({
    tipo_id: [{ required: true, message: 'Selecciona el tipo.' }],
    prioridad_id: [{ required: true, message: 'Selecciona la prioridad.' }],
});

const opcionesTipos = computed(() =>
    (props.catalogos.tipos ?? []).map((t) => ({ value: t.id, label: t.nombre })),
);
const opcionesPrioridades = computed(() =>
    (props.catalogos.prioridades ?? []).map((p) => ({
        value: p.id,
        label: p.nombre,
        color: p.color,
    })),
);

const prioridadSeleccionada = computed(() =>
    opcionesPrioridades.value.find((p) => p.value === formAutorizar.prioridad_id),
);
const tipoSeleccionado = computed(() =>
    opcionesTipos.value.find((t) => t.value === formAutorizar.tipo_id),
);

const prioridadColor = computed(() => prioridadSeleccionada.value?.color || '#d64545');

const previewProgramacion = computed(() => {
    if (!formAutorizar.programado_inicio) return null;
    const inicio = new Date(formAutorizar.programado_inicio);
    const ahora = new Date();
    const diffMs = inicio - ahora;
    const diffHrs = diffMs / 3600000;

    if (diffHrs < 0) return { texto: 'Fecha pasada', color: '#d64545' };
    if (diffHrs < 1) return { texto: 'En minutos', color: '#d64545' };
    if (diffHrs < 24) return { texto: `En ${Math.round(diffHrs)} h`, color: '#e08a1e' };

    const dias = Math.round(diffHrs / 24);
    if (dias === 1) return { texto: 'Mañana', color: '#e08a1e' };
    if (dias <= 7) return { texto: `En ${dias} días`, color: '#0d84c9' };
    return { texto: `En ${dias} días`, color: '#1f9e86' };
});

const duracion = computed(() => {
    if (!formAutorizar.programado_inicio || !formAutorizar.programado_fin) return null;
    const inicio = new Date(formAutorizar.programado_inicio);
    const fin = new Date(formAutorizar.programado_fin);
    const diffMs = fin - inicio;
    if (diffMs <= 0) return { texto: 'Rango inválido', color: '#d64545' };

    const horas = diffMs / 3600000;
    if (horas < 1) return { texto: `${Math.round(horas * 60)} min`, color: '#1f9e86' };
    if (horas < 24) return { texto: `${Math.round(horas)} h`, color: '#1f9e86' };

    const dias = Math.round(horas / 24);
    return { texto: `${dias} día${dias === 1 ? '' : 's'}`, color: '#1f9e86' };
});

const hayPreviewOrden = computed(
    () => formAutorizar.tipo_id || formAutorizar.prioridad_id || formAutorizar.programado_inicio,
);

const abrirAutorizar = () => {
    formAutorizar.reset();
    formAutorizar.clearErrors();
    formAutorizar.prioridad_id = s.value.prioridad_id ?? undefined;
    modalAutorizar.value = true;
};

const cerrarAutorizar = () => {
    modalAutorizar.value = false;
};

const autorizar = () => {
    formAutorizar.post(route('solicitudes.autorizar', s.value.id), {
        preserveScroll: true,
        onSuccess: () => cerrarAutorizar(),
    });
};

/* ==========================================================
   Rechazar
   ========================================================== */
const modalRechazar = ref(false);
const formRechazar = useForm({ motivo_rechazo: '' });
const reglasRechazar = reactive({ motivo_rechazo: [{ required: true, message: 'Indica el motivo.' }] });

const abrirRechazar = () => {
    formRechazar.reset();
    formRechazar.clearErrors();
    modalRechazar.value = true;
};

const rechazar = () => {
    formRechazar.post(route('solicitudes.rechazar', s.value.id), {
        preserveScroll: true,
        onSuccess: () => (modalRechazar.value = false),
    });
};

/* ==========================================================
   Modales de visualización
   ========================================================== */
const modalOrdenes = ref(false);
const modalDocumentos = ref(false);
</script>

<template>
    <Head :title="`Solicitud ${solicitud.folio}`" />

    <AppLayout>
        <div class="ficha-compacta">
            <FichaEncabezado
                :titulo="solicitud.folio"
                :subtitulo="solicitud.equipo?.codigo_activo ?? solicitud.ubicacion?.nombre"
                :icono="FormOutlined"
                volver="solicitudes.index"
                :sello="sello"
            >
                <template #tags>
                    <a-tag>{{ solicitud.estado?.nombre }}</a-tag>
                    <a-tag v-if="solicitud.prioridad" :color="solicitud.prioridad.color || 'default'">
                        {{ solicitud.prioridad.nombre }}
                    </a-tag>
                </template>
                <template #acciones>
                    <span v-if="autorizada" class="badge-estado badge-estado--ok">
                        <CheckOutlined />
                        Autorizada
                    </span>
                    <span v-else-if="rechazada" class="badge-estado badge-estado--danger">
                        <StopOutlined />
                        Rechazada
                    </span>

                    <!-- Orden: primero Autorizar (primario), después Rechazar (danger) -->
                    <button
                        v-if="puedeConvertir"
                        type="button"
                        class="btn-hero btn-hero--primary"
                        @click="abrirAutorizar"
                    >
                        <span class="btn-hero__ic">
                            <RocketOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Autorizar</span>
                            <span class="btn-hero__s">Crear orden</span>
                        </span>
                    </button>

                    <button
                        v-if="!terminada && !convertida && puede('solicitudes.editar')"
                        type="button"
                        class="btn-hero btn-hero--danger"
                        @click="abrirRechazar"
                    >
                        <span class="btn-hero__ic">
                            <StopOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Rechazar</span>
                            <span class="btn-hero__s">Solicitud</span>
                        </span>
                    </button>
                </template>
            </FichaEncabezado>

            <div class="problema-card">
                <div class="problema-card__icono">
                    <component :is="solicitud.equipo ? ToolOutlined : EnvironmentOutlined" />
                </div>
                <div class="problema-card__meta">
                    <div class="problema-card__objetivo">
                        {{ solicitud.equipo ? `${solicitud.equipo.codigo_activo} — ${solicitud.equipo.descripcion}` :
                            solicitud.ubicacion?.nombre }}
                    </div>
                    <div class="problema-card__desc">{{ solicitud.descripcion }}</div>
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
                                <div class="card__titulo">Objetivo del servicio</div>
                                <div class="card__sub">Equipo o instalación a atender</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--2">
                                <div v-for="d in infoObjetivo" :key="d.label" class="mini" :style="{ '--c': d.color }">
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
                            <div class="card__ico" style="--c: #6b4bc9">
                                <FormOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Datos de la solicitud</div>
                                <div class="card__sub">Registro y seguimiento</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--2">
                                <div v-for="d in infoSolicitud" :key="d.label" class="mini" :style="{ '--c': d.color }">
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

                    <div v-if="rechazada" class="card card--rechazo">
                        <div class="card__head card__head--rechazo">
                            <div class="card__ico" style="--c: #d64545">
                                <StopOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo card__titulo--rechazo">Motivo del rechazo</div>
                                <div class="card__sub card__sub--rechazo">Información del rechazo registrado</div>
                            </div>
                        </div>
                        <div class="card__body card__body--rechazo">
                            <div class="rechazo-meta-line">
                                <div class="rechazo-meta">
                                    <span class="rechazo-meta__ic rechazo-meta__ic--fecha">
                                        <ClockCircleOutlined />
                                    </span>
                                    <span class="rechazo-meta__t">
                                        <span class="rechazo-meta__l">Fecha y hora</span>
                                        <span class="rechazo-meta__v">{{ fechaHora(solicitud.revisado_at) }}</span>
                                    </span>
                                </div>
                                <div class="rechazo-meta__sep"></div>
                                <div class="rechazo-meta">
                                    <span class="rechazo-meta__ic rechazo-meta__ic--user">
                                        <UserOutlined />
                                    </span>
                                    <span class="rechazo-meta__t">
                                        <span class="rechazo-meta__l">Rechazada por</span>
                                        <span class="rechazo-meta__v">{{ solicitud.revisadoPor?.nombre || 'Sistema' }}</span>
                                    </span>
                                </div>
                            </div>

                            <div class="rechazo-motivo">
                                <div class="rechazo-motivo__label">
                                    <FileTextOutlined /> Motivo
                                </div>
                                <p class="rechazo-motivo__txt">
                                    {{ solicitud.motivo_rechazo || 'Sin motivo registrado.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-der">
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <ToolOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Órdenes generadas
                                    <span v-if="solicitud.mantenimientos?.length" class="badge badge--blue">
                                        {{ solicitud.mantenimientos.length }}
                                    </span>
                                </div>
                                <div class="card__sub">Conversión a mantenimiento</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div v-if="solicitud.mantenimientos?.length" class="ordenes">
                                <button
                                    v-for="item in solicitud.mantenimientos"
                                    :key="item.id"
                                    type="button"
                                    class="orden"
                                    @click="router.visit(route('mantenimientos.show', item.id))"
                                >
                                    <span class="orden__ic">
                                        <ToolOutlined />
                                    </span>
                                    <span class="orden__t">
                                        <span class="orden__folio">{{ item.folio }}</span>
                                        <span class="orden__estado">{{ item.estado?.nombre }}</span>
                                    </span>
                                    <span class="orden__arrow">→</span>
                                </button>
                            </div>
                            <div v-else class="vacio-box">
                                <ToolOutlined />
                                <span>Aún no se ha generado ninguna orden</span>
                            </div>
                        </div>
                    </div>

                    <div class="card card--docs">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <FileTextOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Documentos
                                    <span v-if="solicitud.documentos?.length" class="badge badge--purple">
                                        {{ solicitud.documentos.length }}
                                    </span>
                                </div>
                                <div class="card__sub">
                                    <template v-if="rechazada">Solicitud rechazada — solo lectura</template>
                                    <template v-else-if="autorizada">Solicitud autorizada — solo lectura</template>
                                    <template v-else-if="convertida">Solicitud convertida en orden — solo lectura</template>
                                    <template v-else>Evidencia adjunta</template>
                                </div>
                            </div>

                            <a-space v-if="puedeEditarDocs && puede('documentos.crear')" class="card__extra">
                                <a-button class="btn-doc btn-doc--pdf" size="small" @click="abrirPdf">
                                    <template #icon>
                                        <FilePdfOutlined />
                                    </template>
                                    PDF
                                </a-button>
                                <a-button class="btn-doc btn-doc--img" size="small" @click="abrirImagen">
                                    <template #icon>
                                        <FileImageOutlined />
                                    </template>
                                    Imagen
                                </a-button>
                            </a-space>
                        </div>
                        <div class="card__body">
                            <ListaDocumentos
                                :documentos="solicitud.documentos ?? []"
                                relacionable-tipo="solicitud"
                                :relacionable-id="solicitud.id"
                                :roles="ROLES_DOC"
                                :puede-subir="false"
                                :puede-eliminar="puedeEditarDocs && puede('documentos.desactivar')"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL ÓRDENES -->
        <a-modal
            v-model:open="modalOrdenes"
            :title="`Órdenes generadas (${solicitud.mantenimientos?.length ?? 0})`"
            :footer="null"
            :width="560"
        >
            <div v-if="solicitud.mantenimientos?.length" class="ordenes">
                <button
                    v-for="item in solicitud.mantenimientos"
                    :key="item.id"
                    type="button"
                    class="orden"
                    @click="router.visit(route('mantenimientos.show', item.id))"
                >
                    <span class="orden__ic"><ToolOutlined /></span>
                    <span class="orden__t">
                        <span class="orden__folio">{{ item.folio }}</span>
                        <span class="orden__estado">{{ item.estado?.nombre }}</span>
                    </span>
                    <span class="orden__arrow">→</span>
                </button>
            </div>
            <a-empty v-else description="Aún no se ha generado ninguna orden" />
        </a-modal>

        <!-- MODAL DOCUMENTOS -->
        <a-modal
            v-model:open="modalDocumentos"
            :title="`Documentos (${solicitud.documentos?.length ?? 0})`"
            :footer="null"
            :width="720"
        >
            <ListaDocumentos
                :documentos="solicitud.documentos ?? []"
                relacionable-tipo="solicitud"
                :relacionable-id="solicitud.id"
                :roles="ROLES_DOC"
                :puede-subir="false"
                :puede-eliminar="puedeEditarDocs && puede('documentos.desactivar')"
            />
        </a-modal>

        <!-- MODAL AUTORIZAR -->
        <a-modal
            v-model:open="modalAutorizar"
            :footer="null"
            :closable="false"
            :width="1020"
            centered
            class="modal-autorizar"
            :mask-closable="!formAutorizar.processing"
        >
            <div class="modal-autorizar__wrap">
                <header class="ma-head">
                    <div class="ma-head__ico">
                        <RocketOutlined />
                    </div>
                    <div class="ma-head__meta">
                        <div class="ma-head__row">
                            <h2 class="ma-head__titulo">Autorizar y crear orden</h2>
                            <span class="ma-head__badge">
                                <CheckCircleFilled /> Nueva orden
                            </span>
                        </div>
                        <p class="ma-head__sub">
                            Se creará una orden de mantenimiento con los datos indicados.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="ma-head__close"
                        :disabled="formAutorizar.processing"
                        title="Cerrar"
                        @click="cerrarAutorizar"
                    >
                        ✕
                    </button>
                </header>

                <form class="ma-body" @submit.prevent="autorizar">
                    <section class="step">
                        <div class="step__head">
                            <span class="step__num">1</span>
                            <div class="step__meta">
                                <span class="step__title">
                                    <ToolOutlined /> Datos de la orden
                                </span>
                                <span class="step__sub">
                                    Tipo de mantenimiento y prioridad con la que se creará.
                                </span>
                            </div>
                        </div>
                        <div class="step__body">
                            <a-form :model="formAutorizar" :rules="reglasAutorizar" layout="vertical">
                                <a-row :gutter="14">
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item
                                            label="Tipo de mantenimiento"
                                            name="tipo_id"
                                            :validate-status="formAutorizar.errors.tipo_id ? 'error' : undefined"
                                            :help="formAutorizar.errors.tipo_id"
                                        >
                                            <a-select
                                                v-model:value="formAutorizar.tipo_id"
                                                :options="opcionesTipos"
                                                placeholder="Selecciona tipo"
                                                size="large"
                                            />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item
                                            label="Prioridad"
                                            name="prioridad_id"
                                            :validate-status="formAutorizar.errors.prioridad_id ? 'error' : undefined"
                                            :help="formAutorizar.errors.prioridad_id"
                                        >
                                            <a-select
                                                v-model:value="formAutorizar.prioridad_id"
                                                :options="opcionesPrioridades.map((p) => ({ value: p.value, label: p.label }))"
                                                placeholder="Selecciona prioridad"
                                                size="large"
                                            />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </a-form>
                        </div>
                    </section>

                    <section class="step">
                        <div class="step__head">
                            <span class="step__num">2</span>
                            <div class="step__meta">
                                <span class="step__title">
                                    <CalendarOutlined /> Programación
                                </span>
                                <span class="step__sub">
                                    Fechas de inicio y fin del trabajo (opcional).
                                </span>
                            </div>
                        </div>
                        <div class="step__body">
                            <div class="rango">
                                <div class="rango__campo">
                                    <label class="label">Inicio</label>
                                    <CampoFechaHora v-model="formAutorizar.programado_inicio" />
                                </div>
                                <span class="rango__sep">al</span>
                                <div class="rango__campo">
                                    <label class="label">Fin</label>
                                    <CampoFechaHora
                                        v-model="formAutorizar.programado_fin"
                                        :min-fecha="
                                            formAutorizar.programado_inicio
                                                ? formAutorizar.programado_inicio.slice(0, 10)
                                                : undefined
                                        "
                                    />
                                    <p
                                        v-if="formAutorizar.errors.programado_fin"
                                        class="error-msg"
                                    >
                                        {{ formAutorizar.errors.programado_fin }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-if="hayPreviewOrden" class="step step--preview">
                        <div class="step__head">
                            <span class="step__num">3</span>
                            <div class="step__meta">
                                <span class="step__title">
                                    <CheckCircleFilled /> Resumen
                                </span>
                                <span class="step__sub">
                                    Así quedará la nueva orden.
                                </span>
                            </div>
                        </div>
                        <div class="step__body">
                            <div class="preview-bar">
                                <div class="preview-bar__chips">
                                    <span
                                        v-if="tipoSeleccionado"
                                        class="preview__chip"
                                        style="--pc: #0d84c9"
                                    >
                                        <ToolOutlined /> {{ tipoSeleccionado.label }}
                                    </span>
                                    <span
                                        v-if="prioridadSeleccionada"
                                        class="preview__chip"
                                        :style="{ '--pc': prioridadColor }"
                                    >
                                        <FlagOutlined /> {{ prioridadSeleccionada.label }}
                                    </span>
                                    <span
                                        v-if="previewProgramacion"
                                        class="preview__chip"
                                        :style="{ '--pc': previewProgramacion.color }"
                                    >
                                        <ClockCircleOutlined /> {{ previewProgramacion.texto }}
                                    </span>
                                    <span
                                        v-if="duracion"
                                        class="preview__chip"
                                        :style="{ '--pc': duracion.color }"
                                    >
                                        <ClockCircleOutlined /> {{ duracion.texto }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>

                <footer class="ma-footer">
                    <a-button
                        size="large"
                        :disabled="formAutorizar.processing"
                        @click="cerrarAutorizar"
                    >
                        Cancelar
                    </a-button>
                    <a-button
                        type="primary"
                        size="large"
                        class="btn-submit"
                        :loading="formAutorizar.processing"
                        :disabled="formAutorizar.processing"
                        @click="autorizar"
                    >
                        <template #icon>
                            <RocketOutlined />
                        </template>
                        Crear orden
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <!-- MODAL RECHAZAR -->
        <a-modal
            v-model:open="modalRechazar"
            :footer="null"
            :closable="false"
            :width="560"
            centered
            class="modal-rechazar"
            :mask-closable="!formRechazar.processing"
        >
            <div class="modal-rechazar__wrap">
                <header class="mr-head">
                    <div class="mr-head__ico">
                        <StopOutlined />
                    </div>
                    <div class="mr-head__meta">
                        <h2 class="mr-head__titulo">Rechazar solicitud</h2>
                        <p class="mr-head__sub">
                            Indica el motivo del rechazo. Esta acción no se puede deshacer.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="mr-head__close"
                        :disabled="formRechazar.processing"
                        title="Cerrar"
                        @click="modalRechazar = false"
                    >
                        ✕
                    </button>
                </header>

                <div class="mr-body">
                    <a-form :model="formRechazar" :rules="reglasRechazar" layout="vertical">
                        <a-form-item
                            label="Motivo del rechazo"
                            name="motivo_rechazo"
                            :validate-status="formRechazar.errors.motivo_rechazo ? 'error' : undefined"
                            :help="formRechazar.errors.motivo_rechazo"
                        >
                            <a-textarea
                                v-model:value="formRechazar.motivo_rechazo"
                                :rows="4"
                                placeholder="Explica brevemente por qué se rechaza la solicitud…"
                                show-count
                                :maxlength="500"
                            />
                        </a-form-item>
                    </a-form>
                </div>

                <footer class="mr-footer">
                    <a-button
                        size="large"
                        :disabled="formRechazar.processing"
                        @click="modalRechazar = false"
                    >
                        Cancelar
                    </a-button>
                    <a-button
                        danger
                        type="primary"
                        size="large"
                        class="btn-rechazar"
                        :loading="formRechazar.processing"
                        :disabled="formRechazar.processing"
                        @click="rechazar"
                    >
                        <template #icon>
                            <StopOutlined />
                        </template>
                        Rechazar solicitud
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <SubirDocumento
            ref="subirDoc"
            relacionable-tipo="solicitud"
            :relacionable-id="solicitud.id"
            :roles="ROLES_DOC"
        />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Layout base
   ========================================================== */
.ficha-compacta {
    display: flex;
    flex-direction: column;
    gap: 13px;
    min-height: 0;
}

.badge-estado {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 34px;
    padding: 0 16px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 12.5px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    border: 1px solid transparent;
    box-shadow: 0 3px 10px rgba(15, 37, 71, 0.08);
}

.badge-estado--ok {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    color: #fff;
    border-color: #16806c;
    box-shadow: 0 4px 12px rgba(31, 158, 134, 0.3);
}

.badge-estado--danger {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: #fff;
    border-color: #b91c1c;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

/* ==========================================================
   Botones hero (Autorizar / Rechazar)
   ========================================================== */
.btn-hero {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    height: 42px;
    padding: 0 16px 0 8px;
    border-radius: 11px;
    border: 1px solid transparent;
    font-family: inherit;
    font-weight: 800;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
    flex-shrink: 0;
}

.btn-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.22) 50%, transparent 100%);
    transform: translateX(-100%) skewX(-20deg);
    transition: transform 0.6s ease;
    pointer-events: none;
}

.btn-hero:hover::after {
    transform: translateX(200%) skewX(-20deg);
}

.btn-hero:active {
    transform: translateY(0) scale(0.98);
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
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
    transition: transform 0.2s ease;
    position: relative;
    z-index: 1;
}

.btn-hero:hover .btn-hero__ic {
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
    font-size: 12.5px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.btn-hero__s {
    font-size: 10px;
    font-weight: 600;
    opacity: 0.85;
    letter-spacing: 0.02em;
}

/* Variante primaria */
.btn-hero--primary {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 6px 16px -6px rgba(31, 158, 134, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--primary:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(31, 158, 134, 0.75), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

/* Variante peligro */
.btn-hero--danger {
    background: linear-gradient(135deg, #d64545 0%, #b91c1c 100%);
    box-shadow: 0 6px 16px -6px rgba(214, 69, 69, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--danger:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(214, 69, 69, 0.75), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.problema-card {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 16px 20px;
    background: #fff;
    border: 1px solid #fecaca;
    border-left: 4px solid #dc2626;
    border-radius: 14px;
    box-shadow: var(--sigam-sombra-sm);
}

.problema-card__icono {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
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
    letter-spacing: 0.02em;
    font-weight: 800;
    font-size: 15px;
    color: #b52222;
    margin-bottom: 4px;
}

.problema-card__desc {
    white-space: pre-line;
    text-transform: uppercase;
    font-weight: 700;
    font-size: 13.5px;
    line-height: 1.45;
    color: #d64545;
}

.grid-ficha {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 14px;
    align-items: start;
}

.col-izq,
.col-der {
    display: flex;
    flex-direction: column;
    gap: 13px;
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
    padding: 12px 16px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 70%);
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
    font-size: 13.5px;
    color: var(--sigam-navy);
    letter-spacing: -0.1px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.card__sub {
    font-size: 11px;
    color: var(--sigam-tenue);
}

.card__extra {
    flex-shrink: 0;
}

.card__body {
    padding: 13px 16px;
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    background: #e8f3fb;
    color: #0d6ca6;
    font-size: 10.5px;
    font-weight: 800;
}

.badge--blue { background: #e8f3fb; color: #0d6ca6; }
.badge--purple { background: #efe9fb; color: #6b4bc9; }

.btn-doc {
    border-radius: 10px;
    font-weight: 700;
    transition: transform 0.14s ease, box-shadow 0.14s ease, background 0.14s ease, border-color 0.14s ease;
}
.btn-doc--pdf {
    background: #fff !important;
    border: 1px solid #fecaca !important;
    color: #d64545 !important;
}
.btn-doc--pdf:hover {
    background: #fdecec !important;
    border-color: #d64545 !important;
    color: #b91c1c !important;
    transform: translateY(-1px);
}
.btn-doc--img {
    background: #fff !important;
    border: 1px solid #c9b8f0 !important;
    color: #6b4bc9 !important;
}
.btn-doc--img:hover {
    background: #faf7ff !important;
    border-color: #6b4bc9 !important;
    color: #563a9e !important;
    transform: translateY(-1px);
}

.mini-grid { display: grid; gap: 9px; }
.mini-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }

.mini {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
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
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
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
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    line-height: 1.1;
}
.mini__v {
    font-size: 13px;
    font-weight: 700;
    color: var(--sigam-texto);
    word-break: break-word;
    line-height: 1.25;
}

.card--rechazo {
    border: 1px solid #f5c2c7;
    background: linear-gradient(180deg, #fff5f5 0%, #ffffff 55%);
    box-shadow: 0 6px 18px -12px rgba(214, 69, 69, 0.35);
    animation: rechazoIn 0.32s ease both;
}
@keyframes rechazoIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.card__head--rechazo {
    background: linear-gradient(90deg, #fdecec 0%, #fbd7d7 100%);
    border-bottom: 1px solid #f5c2c7;
}
.card__titulo--rechazo { color: #a12626; }
.card__sub--rechazo { color: #a15656; }
.card__body--rechazo {
    padding: 16px 16px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.rechazo-meta-line {
    display: flex;
    align-items: stretch;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 11px;
    background: #fff;
    border: 1px solid #f5c2c7;
    flex-wrap: wrap;
}
.rechazo-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1 1 220px;
    min-width: 0;
}
.rechazo-meta__sep {
    width: 1px;
    background: linear-gradient(180deg, transparent 0%, #f5c2c7 30%, #f5c2c7 70%, transparent 100%);
    flex-shrink: 0;
}
.rechazo-meta__ic {
    width: 34px;
    height: 34px;
    flex: none;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
}
.rechazo-meta__ic--fecha { background: #d64545; box-shadow: 0 3px 8px rgba(214, 69, 69, 0.3); }
.rechazo-meta__ic--user { background: #6b4bc9; box-shadow: 0 3px 8px rgba(107, 75, 201, 0.3); }
.rechazo-meta__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 1px;
}
.rechazo-meta__l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: #a15656;
    line-height: 1.1;
}
.rechazo-meta__v {
    font-size: 13px;
    font-weight: 700;
    color: #7a1c1c;
    word-break: break-word;
    line-height: 1.25;
}
.rechazo-motivo {
    border-radius: 11px;
    background: #fff;
    border: 1px dashed #f5c2c7;
    padding: 12px 14px;
}
.rechazo-motivo__label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 800;
    color: #a12626;
    margin-bottom: 6px;
}
.rechazo-motivo__txt {
    margin: 0;
    font-size: 13px;
    line-height: 1.55;
    color: var(--sigam-texto);
    white-space: pre-line;
}

.ordenes { display: flex; flex-direction: column; gap: 8px; }
.orden {
    display: flex;
    align-items: center;
    gap: 11px;
    width: 100%;
    text-align: left;
    padding: 10px 12px;
    border: 1px solid var(--sigam-borde);
    border-radius: 11px;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.14s ease, box-shadow 0.14s ease, transform 0.14s ease;
}
.orden:hover {
    border-color: #0d84c9;
    box-shadow: 0 3px 10px rgba(13, 132, 201, 0.12);
    transform: translateX(2px);
}
.orden__ic {
    width: 34px;
    height: 34px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f3fb;
    color: #0d6ca6;
    font-size: 15px;
}
.orden__t { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.orden__folio { font-weight: 700; color: var(--sigam-navy); font-size: 13px; }
.orden__estado { font-size: 12px; color: var(--sigam-tenue); }
.orden__arrow {
    color: #0d84c9;
    font-weight: 700;
    font-size: 16px;
    opacity: 0;
    transition: opacity 0.14s ease, transform 0.14s ease;
}
.orden:hover .orden__arrow { opacity: 1; transform: translateX(3px); }

.vacio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px 12px;
    border-radius: 11px;
    border: 1px dashed var(--sigam-borde);
    background: var(--sigam-navy-050);
    color: var(--sigam-tenue);
    font-size: 12px;
}
.vacio-box .anticon { font-size: 22px; opacity: 0.5; }

/* ==========================================================
   MODAL AUTORIZAR
   ========================================================== */
.modal-autorizar :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-autorizar :deep(.ant-modal-body) {
    padding: 0;
}

.modal-autorizar__wrap {
    display: flex;
    flex-direction: column;
    background: #f5f8fb;
}

.ma-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    background: linear-gradient(135deg, #ffffff 0%, #f0fbf7 100%);
    border-bottom: 1px solid #d5ece3;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}

.ma-head::before {
    content: '';
    position: absolute;
    right: -60px;
    top: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(31, 158, 134, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.ma-head__ico {
    position: relative;
    z-index: 1;
    width: 46px;
    height: 46px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 8px 20px -8px rgba(31, 158, 134, 0.65);
    flex-shrink: 0;
}

.ma-head__meta {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
}

.ma-head__row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.ma-head__titulo {
    font-size: 17px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.2px;
}

.ma-head__badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #1f9e86;
    background: color-mix(in srgb, #1f9e86 12%, #fff);
    border: 1px solid color-mix(in srgb, #1f9e86 28%, transparent);
}

.ma-head__badge .anticon {
    font-size: 10px;
}

.ma-head__sub {
    margin: 3px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.ma-head__close {
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

.ma-head__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.ma-head__close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ma-body {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px 22px 6px;
    background: #fff;
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
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 5px 12px -5px rgba(31, 158, 134, 0.65);
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
    letter-spacing: 0.04em;
    color: #173a5f;
    line-height: 1.15;
}

.step__title .anticon {
    color: #1f9e86;
    font-size: 12px;
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

/* ==========================================================
   Rango: FECHA HORA "al" FECHA HORA — "al" CENTRADO
   ========================================================== */
.rango {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;  /* centra verticalmente el "al" respecto a los inputs */
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
    height: 40px;  /* misma altura que el input grande */
    padding: 0 8px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7b8a9c;
    white-space: nowrap;
    align-self: end;  /* alineado al final del row para quedar a la altura del input */
    margin-bottom: 0;
}

/* Preview */
.preview-bar {
    padding: 10px 12px;
    border-radius: 11px;
    background: #fff;
    border: 1px dashed #b8e4d3;
}

.preview-bar__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.preview__chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--pc) 12%, transparent);
    color: var(--pc);
    font-size: 11px;
    font-weight: 800;
    box-shadow: 0 1px 3px color-mix(in srgb, var(--pc) 20%, transparent);
}

.preview__chip .anticon {
    font-size: 10px;
}

.ma-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 22px;
    background: linear-gradient(180deg, #fafcfe 0%, #ffffff 100%);
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.ma-footer .btn-submit {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%) !important;
    border-color: #1f9e86 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(31, 158, 134, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.ma-footer .btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(31, 158, 134, 0.75);
}

/* ==========================================================
   MODAL RECHAZAR
   ========================================================== */
.modal-rechazar :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-rechazar :deep(.ant-modal-body) {
    padding: 0;
}

.modal-rechazar__wrap {
    display: flex;
    flex-direction: column;
    background: #fff;
}

.mr-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    background: linear-gradient(135deg, #ffffff 0%, #fef5f5 100%);
    border-bottom: 1px solid #fbd7d7;
    flex-shrink: 0;
    position: relative;
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
    background: radial-gradient(circle, rgba(214, 69, 69, 0.15) 0%, transparent 70%);
    pointer-events: none;
}

.mr-head__ico {
    position: relative;
    z-index: 1;
    width: 46px;
    height: 46px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: linear-gradient(135deg, #d64545 0%, #b91c1c 100%);
    box-shadow: 0 8px 20px -8px rgba(214, 69, 69, 0.65);
    flex-shrink: 0;
}

.mr-head__meta {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
}

.mr-head__titulo {
    font-size: 17px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.2px;
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

.mr-head__close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.mr-body {
    padding: 18px 22px 8px;
    background: #fff;
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

.btn-rechazar {
    background: linear-gradient(135deg, #d64545 0%, #b91c1c 100%) !important;
    border-color: #b91c1c !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(214, 69, 69, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.btn-rechazar:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(214, 69, 69, 0.75);
}

/* Responsive */
@media (max-width: 1199px) {
    .grid-ficha { grid-template-columns: 1fr; }
}

@media (max-width: 767px) {
    .rango {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .rango__sep {
        height: auto;
        align-self: center;
        padding: 4px 0;
    }
}

@media (max-width: 575px) {
    .mini-grid--2 { grid-template-columns: 1fr; }
    .problema-card { flex-direction: column; }
    .badge-estado { height: 28px; padding: 0 12px; font-size: 11px; }
    .rechazo-meta-line { flex-direction: column; gap: 12px; }
    .rechazo-meta__sep {
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, #f5c2c7 30%, #f5c2c7 70%, transparent 100%);
    }

    .ma-head, .mr-head { padding: 14px 16px; }
    .ma-head__titulo, .mr-head__titulo { font-size: 15px; }
    .ma-body { padding: 14px 16px 6px; gap: 10px; }
    .step { padding: 12px 14px; }
    .ma-footer, .mr-footer {
        padding: 12px 16px;
        flex-direction: column-reverse;
    }
    .ma-footer .ant-btn, .mr-footer .ant-btn { width: 100%; }
    .mr-body { padding: 14px 16px 6px; }

    .btn-hero {
        width: 100%;
        justify-content: center;
    }
}
</style>