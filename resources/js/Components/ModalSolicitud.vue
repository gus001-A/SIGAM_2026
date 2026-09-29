<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    ClockCircleOutlined,
    FileAddOutlined,
    FileTextOutlined,
    FlagOutlined,
    InfoCircleOutlined,
    SendOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import SelectObjetivoMantenimiento from '@/Components/SelectObjetivoMantenimiento.vue';
import { mananaISO, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    equipos: { type: Array, default: () => [] },
    ubicaciones: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
});

const abierto = ref(false);

const form = useForm({
    equipo_id: undefined,
    ubicacion_id: undefined,
    descripcion: '',
    prioridad_id: undefined,
    fecha_requerida: '',
});

const reglas = {
    descripcion: [{ required: true, message: 'Describe el problema o servicio.' }],
    fecha_requerida: [
        reglaNoPasada('La fecha requerida debe ser posterior a hoy.', true),
    ],
};

const esInstalacion = computed(() => !!form.ubicacion_id);

const opcionesPrioridades = computed(() =>
    (props.prioridades ?? []).map((p) => ({
        value: p.id,
        label: p.nombre,
        color: p.color,
    })),
);

const prioridadSeleccionada = computed(() =>
    opcionesPrioridades.value.find((p) => p.value === form.prioridad_id),
);

/* Paleta de fallback si no viene color del backend */
const PRIORIDAD_FALLBACK = ['#1f9e86', '#0d84c9', '#e08a1e', '#d64545'];
const prioridadColor = (p, i) =>
    p?.color || PRIORIDAD_FALLBACK[(i ?? 0) % PRIORIDAD_FALLBACK.length];

/* Preview de la fecha requerida */
const previewFecha = computed(() => {
    if (!form.fecha_requerida) return null;
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const fecha = new Date(form.fecha_requerida);
    fecha.setHours(0, 0, 0, 0);
    const dias = Math.round((fecha - hoy) / 86400000);
    if (dias < 0) return { texto: 'Fecha vencida', color: '#d64545' };
    if (dias === 0) return { texto: 'Es hoy', color: '#e08a1e' };
    if (dias === 1) return { texto: 'Mañana', color: '#e08a1e' };
    if (dias <= 7) return { texto: `En ${dias} días`, color: '#0d84c9' };
    return { texto: `En ${dias} días`, color: '#1f9e86' };
});

/* Hay datos para mostrar preview */
const hayPreview = computed(
    () =>
        form.equipo_id ||
        form.ubicacion_id ||
        form.prioridad_id ||
        form.fecha_requerida,
);

const abrir = (equipoId = null) => {
    form.reset();
    form.clearErrors();
    if (equipoId) form.equipo_id = equipoId;
    abierto.value = true;
};

const cerrar = () => {
    abierto.value = false;
    form.reset();
    form.clearErrors();
};

const enviar = () =>
    form.post(route('solicitudes.store'), {
        preserveScroll: true,
        onSuccess: () => cerrar(),
    });

defineExpose({ abrir });
</script>

<template>
    <a-modal v-model:open="abierto" :footer="null" :closable="false" :width="720" centered class="modal-solicitud"
        :mask-closable="!form.processing">
        <div class="modal-solicitud__wrap">
            <!-- ==========================================================
                 Encabezado
                 ========================================================== -->
            <header class="modal-head">
                <div class="modal-head__ico">
                    <FileAddOutlined />
                </div>
                <div class="modal-head__meta">
                    <h2 class="modal-head__titulo">Nueva solicitud de servicio</h2>
                    <p class="modal-head__sub">
                        Se enviará al equipo de mantenimiento para su revisión.
                    </p>
                </div>
                <button type="button" class="modal-head__close" :disabled="form.processing" title="Cerrar"
                    @click="cerrar">
                    ✕
                </button>
            </header>

            <!-- ==========================================================
                 Body (SIN scroll)
                 ========================================================== -->
            <form class="modal-body" @submit.prevent="enviar">
                <!-- Fila 1: Equipo o instalación -->
                <div class="row">
                    <label class="label">
                        <ToolOutlined /> Equipo o instalación
                    </label>
                    <SelectObjetivoMantenimiento v-model:equipo-id="form.equipo_id"
                        v-model:ubicacion-id="form.ubicacion_id" :equipos="equipos" :ubicaciones="ubicaciones" />
                    <p v-if="form.errors.equipo_id" class="error-msg">
                        {{ form.errors.equipo_id }}
                    </p>
                </div>

                <!-- Fila 2: Descripción -->
                <div class="row">
                    <label class="label">
                        <FileTextOutlined /> Descripción
                    </label>
                    <a-form :model="form" :rules="reglas" layout="vertical">
                        <a-form-item name="descripcion" :validate-status="form.errors.descripcion ? 'error' : undefined"
                            :help="form.errors.descripcion" class="mb-0">
                            <a-textarea v-model:value="form.descripcion" :rows="3" :placeholder="esInstalacion
                                    ? 'Describe el servicio que necesitas (p. ej. pintar paredes, resanar, instalar cableado)'
                                    : '¿Qué falla presenta el equipo?'
                                " show-count :maxlength="1000" />
                        </a-form-item>
                    </a-form>
                </div>

                <!-- Fila 3: Prioridad + Fecha requerida -->
                <div class="grid-2">
                    <div class="row">
                        <label class="label">
                            <FlagOutlined /> Prioridad sugerida
                        </label>

                        <div v-if="!opcionesPrioridades.length" class="sin-prioridades">
                            <InfoCircleOutlined />
                            <span>Sin prioridades configuradas.</span>
                        </div>

                        <div v-else class="prioridades">
                            <label v-for="(p, i) in opcionesPrioridades" :key="p.value" class="prio-card"
                                :class="{ 'prio-card--active': form.prioridad_id === p.value }"
                                :style="{ '--pc': prioridadColor(p, i) }">
                                <input type="radio" class="prio-card__input" :value="p.value"
                                    v-model="form.prioridad_id" />
                                <span class="prio-card__dot"></span>
                                <span class="prio-card__label">{{ p.label }}</span>
                            </label>
                        </div>

                        <!-- Alternativa: si prefieres el catálogo editable, cambia el bloque de arriba por:
                        <SelectCatalogo
                            v-model:value="form.prioridad_id"
                            :options="prioridades"
                            ruta="catalogos.prioridades"
                            etiqueta="prioridad"
                            etiqueta-plural="prioridades"
                            placeholder="Sin definir"
                            :campos="[{ name: 'nivel', label: 'Nivel (1 = más urgente)', tipo: 'number', min: 1 }]"
                        />
                        -->
                    </div>

                    <div class="row">
                        <label class="label">
                            <CalendarOutlined /> Fecha requerida
                        </label>
                        <a-form :model="form" :rules="reglas" layout="vertical">
                            <a-form-item name="fecha_requerida" :help="form.errors.fecha_requerida"
                                :validate-status="form.errors.fecha_requerida ? 'error' : undefined" class="mb-0">
                                <CampoFechaHora v-model="form.fecha_requerida" solo-fecha :min-fecha="mananaISO()" />
                            </a-form-item>
                        </a-form>

                        <div v-if="previewFecha" class="preview-fecha" :style="{ '--pc': previewFecha.color }">
                            <ClockCircleOutlined />
                            <span>{{ previewFecha.texto }}</span>
                        </div>
                    </div>
                </div>

                <!-- Preview compacto (solo si hay datos) -->
                <div v-if="hayPreview" class="preview-bar">
                    <span class="preview-bar__lbl">
                        <CheckCircleOutlined /> Vista previa
                    </span>
                    <div class="preview-bar__chips">
                        <span v-if="form.equipo_id || form.ubicacion_id" class="preview__chip" style="--pc: #0d84c9">
                            <ToolOutlined />
                            {{ esInstalacion ? 'Instalación' : 'Equipo' }}
                        </span>
                        <span v-if="prioridadSeleccionada" class="preview__chip"
                            :style="{ '--pc': prioridadColor(prioridadSeleccionada, 0) }">
                            <FlagOutlined /> {{ prioridadSeleccionada.label }}
                        </span>
                        <span v-if="form.fecha_requerida" class="preview__chip"
                            :style="{ '--pc': previewFecha?.color || '#0d84c9' }">
                            <CalendarOutlined /> {{ form.fecha_requerida }}
                        </span>
                    </div>
                </div>
            </form>

            <!-- ==========================================================
                 Footer
                 ========================================================== -->
            <footer class="modal-footer">
                <a-button size="large" :disabled="form.processing" @click="cerrar">
                    Cancelar
                </a-button>
                <a-button type="primary" size="large" class="btn-submit" :loading="form.processing"
                    :disabled="form.processing" @click="enviar">
                    <template #icon>
                        <SendOutlined />
                    </template>
                    Enviar solicitud
                </a-button>
            </footer>
        </div>
    </a-modal>
</template>

<style scoped>
/* ==========================================================
   Modal base
   ========================================================== */
.modal-solicitud :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-solicitud :deep(.ant-modal-body) {
    padding: 0;
}

.modal-solicitud__wrap {
    display: flex;
    flex-direction: column;
    background: #f5f8fb;
}

/* ==========================================================
   Encabezado
   ========================================================== */
.modal-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f8fb 100%);
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.modal-head__ico {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    color: #fff;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 6px 16px -6px rgba(31, 158, 134, 0.55);
    flex-shrink: 0;
}

.modal-head__meta {
    flex: 1;
    min-width: 0;
}

.modal-head__titulo {
    font-size: 17px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.2px;
}

.modal-head__sub {
    margin: 2px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.modal-head__sub strong {
    color: #1f9e86;
    font-weight: 800;
}

.modal-head__close {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition:
        background 0.14s ease,
        color 0.14s ease,
        border-color 0.14s ease,
        transform 0.14s ease;
}

.modal-head__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.modal-head__close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ==========================================================
   Body (sin scroll)
   ========================================================== */
.modal-body {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px 20px;
    background: #fff;
}

/* ==========================================================
   Filas y grid
   ========================================================== */
.row {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    align-items: start;
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
}

.label .anticon {
    color: #1f9e86;
    font-size: 11px;
}

/* ==========================================================
   Mensaje de error auxiliar
   ========================================================== */
.error-msg {
    margin: 2px 0 0;
    font-size: 12px;
    color: #d64545;
    line-height: 1.3;
}

/* ==========================================================
   Preview fecha
   ========================================================== */
.preview-fecha {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 2px;
    padding: 3px 9px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--pc) 12%, transparent);
    color: var(--pc);
    font-size: 11px;
    font-weight: 800;
    align-self: flex-start;
    box-shadow: 0 1px 3px color-mix(in srgb, var(--pc) 20%, transparent);
}

.preview-fecha .anticon {
    font-size: 11px;
}

/* ==========================================================
   Prioridades radio-cards
   ========================================================== */
.sin-prioridades {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 9px;
    background: #f8fafc;
    border: 1px dashed #cdd8e3;
    color: #7b8a9c;
    font-size: 12px;
}

.prioridades {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
}

.prio-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 10px;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    cursor: pointer;
    transition:
        border-color 0.14s ease,
        background 0.14s ease,
        transform 0.14s ease,
        box-shadow 0.14s ease;
    user-select: none;
    min-width: 0;
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

.prio-card__input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.prio-card__dot {
    width: 12px;
    height: 12px;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--pc);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--pc) 22%, transparent);
    transition: box-shadow 0.14s ease;
}

.prio-card--active .prio-card__dot {
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--pc) 30%, transparent);
}

.prio-card__label {
    font-size: 11.5px;
    font-weight: 700;
    color: #2b3a4f;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.prio-card--active .prio-card__label {
    color: #173a5f;
}

/* ==========================================================
   Preview compacto horizontal
   ========================================================== */
.preview-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f5f8fb 0%, #eef4fb 100%);
    border: 1px dashed #dbe3ec;
    flex-wrap: wrap;
}

.preview-bar__lbl {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #7b8a9c;
    flex-shrink: 0;
}

.preview-bar__lbl .anticon {
    color: #1f9e86;
}

.preview-bar__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.preview__chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--pc) 12%, transparent);
    color: var(--pc);
    font-size: 10.5px;
    font-weight: 800;
}

.preview__chip .anticon {
    font-size: 10px;
}

/* ==========================================================
   Footer
   ========================================================== */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 20px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.btn-submit {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%) !important;
    border-color: #1f9e86 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(31, 158, 134, 0.65);
    transition:
        transform 0.14s ease,
        box-shadow 0.14s ease,
        filter 0.14s ease;
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(31, 158, 134, 0.75);
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 575px) {
    .modal-head {
        padding: 12px 14px;
    }

    .modal-head__titulo {
        font-size: 15px;
    }

    .modal-body {
        padding: 14px;
        gap: 10px;
    }

    .grid-2 {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .prioridades {
        grid-template-columns: 1fr;
    }

    .modal-footer {
        padding: 10px 14px;
        flex-direction: column-reverse;
    }

    .modal-footer .ant-btn {
        width: 100%;
    }
}
</style>