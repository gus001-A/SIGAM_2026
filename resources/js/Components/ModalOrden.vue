<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    FileTextOutlined,
    FlagOutlined,
    PlusOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectObjetivoMantenimiento from '@/Components/SelectObjetivoMantenimiento.vue';
import { hoyISO } from '@/utils/restricciones';

const props = defineProps({
    equipos: { type: Array, default: () => [] },
    ubicaciones: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
});

const visible = ref(false);

const form = useForm({
    equipo_id: undefined,
    ubicacion_id: undefined,
    tipo_id: undefined,
    prioridad_id: undefined,
    programado_inicio: '',
    programado_fin: '',
    problema_reportado: '',
});

const est = (c) => (form.errors[c] ? 'error' : '');

const esInstalacion = computed(() => !!form.ubicacion_id);

/* Preview del inicio */
const previewInicio = computed(() => {
    if (!form.programado_inicio) return null;
    const inicio = new Date(form.programado_inicio);
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

/* Duración entre inicio y fin */
const duracion = computed(() => {
    if (!form.programado_inicio || !form.programado_fin) return null;
    const inicio = new Date(form.programado_inicio);
    const fin = new Date(form.programado_fin);
    const diffMs = fin - inicio;
    if (diffMs <= 0) return { texto: 'Rango inválido', color: '#d64545' };

    const horas = diffMs / 3600000;
    if (horas < 1) return { texto: `${Math.round(horas * 60)} min`, color: '#1f9e86' };
    if (horas < 24) return { texto: `${Math.round(horas)} h`, color: '#1f9e86' };

    const dias = Math.round(horas / 24);
    return { texto: `${dias} día${dias === 1 ? '' : 's'}`, color: '#1f9e86' };
});

const abrir = () => {
    form.reset();
    form.clearErrors();
    visible.value = true;
};

const cerrar = () => {
    visible.value = false;
    form.reset();
    form.clearErrors();
};

const guardar = () => {
    form.post(route('mantenimientos.store'), {
        preserveScroll: true,
        onSuccess: () => cerrar(),
    });
};

defineExpose({ abrir, cerrar });
</script>

<template>
    <a-modal v-model:open="visible" :footer="null" :closable="false" :width="860" centered class="modal-orden"
        :mask-closable="!form.processing">
        <div class="modal-orden__wrap">
            <!-- ==========================================================
                 Encabezado
                 ========================================================== -->
            <header class="modal-head">
                <div class="modal-head__ico">
                    <ToolOutlined />
                </div>
                <div class="modal-head__meta">
                    <h2 class="modal-head__titulo">Nueva orden de mantenimiento</h2>
                    <p class="modal-head__sub">
                        Se registrará como <strong>Autorizada</strong> con el tipo y la prioridad indicados.
                    </p>
                </div>
                <button type="button" class="modal-head__close" :disabled="form.processing" title="Cerrar"
                    @click="cerrar">
                    ✕
                </button>
            </header>

            <!-- ==========================================================
                 Body
                 ========================================================== -->
            <form class="modal-body" @submit.prevent="guardar">
                <!-- ================================================
                     Fila 1: Equipo o instalación
                     ================================================ -->
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

                <!-- ================================================
                     Fila 2: Descripción / motivo
                     ================================================ -->
                <div class="row">
                    <label class="label">
                        <FileTextOutlined /> Descripción / motivo
                    </label>
                    <a-textarea v-model:value="form.problema_reportado" :rows="3" :placeholder="esInstalacion
                            ? 'Describe la necesidad del servicio (p. ej. revisión eléctrica, pintura, plomería)'
                            : '¿Qué falla presenta el equipo?'
                        " show-count :maxlength="1000" />
                    <p v-if="form.errors.problema_reportado" class="error-msg">
                        {{ form.errors.problema_reportado }}
                    </p>
                </div>

                <!-- ================================================
                     Fila 3: Tipo + Prioridad (2 columnas)
                     ================================================ -->
                <div class="grid-2">
                    <div class="row">
                        <label class="label">
                            <ToolOutlined /> Tipo de mantenimiento
                        </label>
                        <a-select v-model:value="form.tipo_id"
                            :options="(catalogos.tipos || []).map(t => ({ value: t.id, label: t.nombre }))"
                            placeholder="Selecciona tipo" size="large" allow-clear :status="est('tipo_id')" />
                        <p v-if="form.errors.tipo_id" class="error-msg">{{ form.errors.tipo_id }}</p>
                    </div>

                    <div class="row">
                        <label class="label">
                            <FlagOutlined /> Prioridad
                        </label>
                        <a-select v-model:value="form.prioridad_id"
                            :options="(catalogos.prioridades || []).map(p => ({ value: p.id, label: p.nombre }))"
                            placeholder="Selecciona prioridad" size="large" allow-clear :status="est('prioridad_id')" />
                        <p v-if="form.errors.prioridad_id" class="error-msg">{{ form.errors.prioridad_id }}</p>
                    </div>
                </div>

                <!-- ================================================
                     Fila 4: Programado — inicio "al" fin
                     ================================================ -->
                <div class="row">
                    <label class="label">
                        <CalendarOutlined /> Programado
                    </label>
                    <div class="rango">
                        <div class="rango__campo">
                            <CampoFechaHora v-model="form.programado_inicio" :min-fecha="hoyISO()" />
                            <p v-if="form.errors.programado_inicio" class="error-msg">
                                {{ form.errors.programado_inicio }}
                            </p>
                        </div>

                        <span class="rango__sep">al</span>

                        <div class="rango__campo">
                            <CampoFechaHora v-model="form.programado_fin" :min-fecha="form.programado_inicio
                                    ? form.programado_inicio.slice(0, 10)
                                    : hoyISO()
                                " />
                            <p v-if="form.errors.programado_fin" class="error-msg">
                                {{ form.errors.programado_fin }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ================================================
                     Preview de programación
                     ================================================ -->
                <div v-if="previewInicio || duracion" class="preview-bar">
                    <span class="preview-bar__lbl">
                        <CheckCircleOutlined /> Programación
                    </span>
                    <div class="preview-bar__chips">
                        <span v-if="previewInicio" class="preview__chip" :style="{ '--pc': previewInicio.color }">
                            <ClockCircleOutlined /> Inicia: {{ previewInicio.texto }}
                        </span>
                        <span v-if="duracion" class="preview__chip" :style="{ '--pc': duracion.color }">
                            <ClockCircleOutlined /> Duración: {{ duracion.texto }}
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
                    :disabled="form.processing" @click="guardar">
                    <template #icon>
                        <PlusOutlined />
                    </template>
                    Crear orden
                </a-button>
            </footer>
        </div>
    </a-modal>
</template>

<style scoped>
/* ==========================================================
   Modal base
   ========================================================== */
.modal-orden :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-orden :deep(.ant-modal-body) {
    padding: 0;
}

.modal-orden__wrap {
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
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.55);
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
    color: #0d84c9;
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

.modal-head__close:hover {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

/* ==========================================================
   Body
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
    color: #0d84c9;
    font-size: 11px;
}

/* ==========================================================
   Rango programado (inicio "al" fin)
   ========================================================== */
.rango {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: start;
    /* NO centrar verticalmente: cada celda crece con su error */
    gap: 12px;
}

.rango__campo {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

/* El "al" se alinea con la ALTURA DEL INPUT, no con la celda completa.
   height = altura del input grande de Ant Design (~40px). */
.rango__sep {
    align-self: start;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 40px;
    padding: 0 4px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7b8a9c;
    white-space: nowrap;
}

/* ==========================================================
   Error auxiliar
   ========================================================== */
.error-msg {
    margin: 2px 0 0;
    font-size: 12px;
    color: #d64545;
    line-height: 1.3;
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
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%) !important;
    border-color: #0d84c9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.65);
    transition:
        transform 0.14s ease,
        box-shadow 0.14s ease,
        filter 0.14s ease;
}

.btn-submit:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(13, 132, 201, 0.75);
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

    /* En móvil el rango se apila */
    .rango {
        grid-template-columns: 1fr;
        gap: 6px;
    }

    .rango__sep {
        height: auto;
        padding: 2px 0;
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