<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckOutlined,
    ClockCircleOutlined,
    CloseOutlined,
    EnvironmentOutlined,
    FileImageOutlined,
    FilePdfOutlined,
    FileTextOutlined,
    FlagOutlined,
    FormOutlined,
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

// Estados
const estadoClave = computed(() => s.value.estado?.clave);
const rechazada = computed(() => estadoClave.value === 'cancelado' || !!s.value.motivo_rechazo);
const autorizada = computed(() => !!s.value.revisadoPor && !rechazada.value);
const convertida = computed(() => (s.value.mantenimientos ?? []).length > 0);
const terminada = computed(() => rechazada.value);

// 🔒 Documentos solo editables mientras la solicitud esté pendiente
const puedeEditarDocs = computed(
    () => !rechazada.value && !autorizada.value && !convertida.value,
);

// --- Mini-cards: datos de la solicitud (SIN "Rechazada por") ---
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

// --- Autorizar ---
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
const autorizar = () => {
    formAutorizar.post(route('solicitudes.autorizar', s.value.id), {
        onSuccess: () => (modalAutorizar.value = false),
    });
};

// --- Rechazar ---
const modalRechazar = ref(false);
const formRechazar = useForm({ motivo_rechazo: '' });
const reglasRechazar = reactive({ motivo_rechazo: [{ required: true, message: 'Indica el motivo.' }] });
const rechazar = () => {
    formRechazar.post(route('solicitudes.rechazar', s.value.id), {
        onSuccess: () => (modalRechazar.value = false),
    });
};

// --- Modales de visualización ---
const modalOrdenes = ref(false);
const modalDocumentos = ref(false);
</script>

<template>
    <Head :title="`Solicitud ${solicitud.folio}`" />

    <AppLayout>
        <div class="ficha-compacta">
            <FichaEncabezado :titulo="solicitud.folio"
                :subtitulo="solicitud.equipo?.codigo_activo ?? solicitud.ubicacion?.nombre" :icono="FormOutlined"
                volver="solicitudes.index" :sello="sello">
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

                    <a-button v-if="!terminada && !convertida && puede('solicitudes.editar')" danger
                        @click="modalRechazar = true">
                        <template #icon>
                            <CloseOutlined />
                        </template>
                        Rechazar
                    </a-button>
                    <a-button v-if="puedeConvertir" type="primary" @click="modalAutorizar = true">
                        <template #icon>
                            <CheckOutlined />
                        </template>
                        Autorizar y crear orden
                    </a-button>
                </template>
            </FichaEncabezado>

            <!-- Descripción destacada -->
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

            <!-- Grid principal -->
            <div class="grid-ficha">
                <!-- COLUMNA IZQUIERDA -->
                <div class="col-izq">
                    <!-- Objetivo -->
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

                    <!-- Datos de la solicitud -->
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

                    <!-- MOTIVO DEL RECHAZO -->
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

                <!-- COLUMNA DERECHA -->
                <div class="col-der">
                    <!-- Órdenes generadas -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <ToolOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Órdenes generadas
                                    <span v-if="solicitud.mantenimientos?.length" class="badge badge--blue">{{
                                        solicitud.mantenimientos.length }}</span>
                                </div>
                                <div class="card__sub">Conversión a mantenimiento</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div v-if="solicitud.mantenimientos?.length" class="ordenes">
                                <button v-for="item in solicitud.mantenimientos" :key="item.id" type="button"
                                    class="orden" @click="router.visit(route('mantenimientos.show', item.id))">
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

                    <!-- Documentos (mismo patrón que Proveedor) -->
                    <div class="card card--docs">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <FileTextOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Documentos
                                    <span v-if="solicitud.documentos?.length" class="badge badge--purple">{{
                                        solicitud.documentos.length }}</span>
                                </div>
                                <div class="card__sub">
                                    <template v-if="rechazada">Solicitud rechazada — solo lectura</template>
                                    <template v-else-if="autorizada">Solicitud autorizada — solo lectura</template>
                                    <template v-else-if="convertida">Solicitud convertida en orden — solo lectura</template>
                                    <template v-else>Evidencia adjunta</template>
                                </div>
                            </div>

                            <!-- Botones PDF / Imagen (solo si puede editar docs) -->
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
                            <ListaDocumentos :documentos="solicitud.documentos ?? []" relacionable-tipo="solicitud"
                                :relacionable-id="solicitud.id" :roles="ROLES_DOC"
                                :puede-subir="false"
                                :puede-eliminar="puedeEditarDocs && puede('documentos.desactivar')" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL ÓRDENES -->
        <a-modal v-model:open="modalOrdenes" :title="`Órdenes generadas (${solicitud.mantenimientos?.length ?? 0})`"
            :footer="null" :width="560">
            <div v-if="solicitud.mantenimientos?.length" class="ordenes">
                <button v-for="item in solicitud.mantenimientos" :key="item.id" type="button" class="orden"
                    @click="router.visit(route('mantenimientos.show', item.id))">
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
            <a-empty v-else description="Aún no se ha generado ninguna orden" />
        </a-modal>

        <!-- MODAL DOCUMENTOS -->
        <a-modal v-model:open="modalDocumentos" :title="`Documentos (${solicitud.documentos?.length ?? 0})`"
            :footer="null" :width="720">
            <ListaDocumentos :documentos="solicitud.documentos ?? []" relacionable-tipo="solicitud"
                :relacionable-id="solicitud.id" :roles="ROLES_DOC"
                :puede-subir="false"
                :puede-eliminar="puedeEditarDocs && puede('documentos.desactivar')" />
        </a-modal>

        <!-- MODAL AUTORIZAR -->
        <a-modal v-model:open="modalAutorizar" title="Autorizar solicitud y crear orden" ok-text="Crear orden"
            cancel-text="Cancelar" :confirm-loading="formAutorizar.processing" centered @ok="autorizar">
            <a-form :model="formAutorizar" :rules="reglasAutorizar" layout="vertical" class="pt-1">
                <a-row :gutter="12">
                    <a-col :span="12">
                        <a-form-item label="Tipo de mantenimiento" name="tipo_id"
                            :validate-status="formAutorizar.errors.tipo_id ? 'error' : undefined"
                            :help="formAutorizar.errors.tipo_id">
                            <a-select v-model:value="formAutorizar.tipo_id"
                                :options="catalogos.tipos?.map((t) => ({ value: t.id, label: t.nombre }))" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Prioridad" name="prioridad_id"
                            :validate-status="formAutorizar.errors.prioridad_id ? 'error' : undefined"
                            :help="formAutorizar.errors.prioridad_id">
                            <a-select v-model:value="formAutorizar.prioridad_id"
                                :options="catalogos.prioridades?.map((p) => ({ value: p.id, label: p.nombre }))" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Programado — inicio">
                            <CampoFechaHora v-model="formAutorizar.programado_inicio" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Programado — fin" :help="formAutorizar.errors.programado_fin"
                            :validate-status="formAutorizar.errors.programado_fin ? 'error' : undefined">
                            <CampoFechaHora v-model="formAutorizar.programado_fin" />
                        </a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>

        <!-- MODAL RECHAZAR -->
        <a-modal v-model:open="modalRechazar" title="Rechazar solicitud" ok-text="Rechazar"
            :ok-button-props="{ danger: true }" cancel-text="Cancelar" :confirm-loading="formRechazar.processing"
            centered @ok="rechazar">
            <a-form :model="formRechazar" :rules="reglasRechazar" layout="vertical" class="pt-1">
                <a-form-item label="Motivo del rechazo" name="motivo_rechazo"
                    :validate-status="formRechazar.errors.motivo_rechazo ? 'error' : undefined"
                    :help="formRechazar.errors.motivo_rechazo">
                    <a-textarea v-model:value="formRechazar.motivo_rechazo" :rows="3" />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- SUBIR DOCUMENTO (mismo patrón que Proveedor) -->
        <SubirDocumento ref="subirDoc" relacionable-tipo="solicitud" :relacionable-id="solicitud.id"
            :roles="ROLES_DOC" />
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

/* ---------- Badge de estado ---------- */
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

/* ---------- Card de descripción destacada ---------- */
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

/* ---------- Grid 2 columnas ---------- */
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

/* ---------- Cards ---------- */
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

/* ---------- Badges ---------- */
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

.badge--blue {
    background: #e8f3fb;
    color: #0d6ca6;
}

.badge--purple {
    background: #efe9fb;
    color: #6b4bc9;
}

/* ---------- Botones para subir PDF / Imagen ---------- */
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

/* ---------- Mini-cards ---------- */
.mini-grid {
    display: grid;
    gap: 9px;
}

.mini-grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

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

/* ==========================================================
   Card de rechazo
   ========================================================== */
.card--rechazo {
    border: 1px solid #f5c2c7;
    background: linear-gradient(180deg, #fff5f5 0%, #ffffff 55%);
    box-shadow: 0 6px 18px -12px rgba(214, 69, 69, 0.35);
    animation: rechazoIn 0.32s ease both;
}

@keyframes rechazoIn {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card__head--rechazo {
    background: linear-gradient(90deg, #fdecec 0%, #fbd7d7 100%);
    border-bottom: 1px solid #f5c2c7;
}

.card__titulo--rechazo {
    color: #a12626;
}

.card__sub--rechazo {
    color: #a15656;
}

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

.rechazo-meta__ic--fecha {
    background: #d64545;
    box-shadow: 0 3px 8px rgba(214, 69, 69, 0.3);
}

.rechazo-meta__ic--user {
    background: #6b4bc9;
    box-shadow: 0 3px 8px rgba(107, 75, 201, 0.3);
}

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

/* ---------- Órdenes (lista) ---------- */
.ordenes {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

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

.orden__t {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
}

.orden__folio {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13px;
}

.orden__estado {
    font-size: 12px;
    color: var(--sigam-tenue);
}

.orden__arrow {
    color: #0d84c9;
    font-weight: 700;
    font-size: 16px;
    opacity: 0;
    transition: opacity 0.14s ease, transform 0.14s ease;
}

.orden:hover .orden__arrow {
    opacity: 1;
    transform: translateX(3px);
}

/* ---------- Vacío ---------- */
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

.vacio-box .anticon {
    font-size: 22px;
    opacity: 0.5;
}

/* ---------- Responsive ---------- */
@media (max-width: 1199px) {
    .grid-ficha {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575px) {
    .mini-grid--2 {
        grid-template-columns: 1fr;
    }

    .problema-card {
        flex-direction: column;
    }

    .badge-estado {
        height: 28px;
        padding: 0 12px;
        font-size: 11px;
    }

    .rechazo-meta-line {
        flex-direction: column;
        gap: 12px;
    }

    .rechazo-meta__sep {
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, #f5c2c7 30%, #f5c2c7 70%, transparent 100%);
    }
}
</style>