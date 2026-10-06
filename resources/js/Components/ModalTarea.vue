<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CalendarOutlined,
    CheckCircleOutlined,
    CheckSquareOutlined,
    ClockCircleOutlined,
    FileTextOutlined,
    FlagOutlined,
    FolderOutlined,
    InfoCircleOutlined,
    SendOutlined,
    TeamOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import { hoyISO, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    usuarios: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
    proyectos: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
});

const visible = ref(false);

const form = useForm({
    titulo: '',
    descripcion: '',
    fecha_limite: '',
    prioridad_id: undefined,
    clasificacion: 'general',
    proyecto_id: undefined,
    categoria_tarea_id: undefined,
    responsables: [],
});

const opcionesClasificacion = [
    { value: 'general', label: 'General', icono: CheckCircleOutlined },
    { value: 'proyecto', label: 'Por proyecto', icono: FolderOutlined },
    { value: 'categoria', label: 'Categoría', icono: ApartmentOutlined },
];

const reglas = reactive({
    titulo: [
        { required: true, message: 'Dale un título corto a la tarea.' },
        { max: 150, message: 'Máximo 150 caracteres.' },
    ],
    descripcion: [{ required: true, message: 'Describe la tarea.' }],
    fecha_limite: [
        { required: true, message: 'Indica la fecha límite.' },
        reglaNoPasada('La fecha límite no puede ser anterior a hoy.'),
    ],
    responsables: [
        { required: true, type: 'array', min: 1, message: 'Asigna al menos un responsable.' },
    ],
    proyecto_id: [
        {
            required: true,
            message: 'Selecciona a qué proyecto pertenece.',
            validator: (_, v) => (form.clasificacion !== 'proyecto' || v ? Promise.resolve() : Promise.reject()),
        },
    ],
    categoria_tarea_id: [
        {
            required: true,
            message: 'Selecciona la categoría de la tarea.',
            validator: (_, v) => (form.clasificacion !== 'categoria' || v ? Promise.resolve() : Promise.reject()),
        },
    ],
});

const opcionesUsuarios = computed(() =>
    props.usuarios.map((u) => ({
        value: u.id,
        label: `${u.nombre} ${u.apellidos ?? ''}`.trim(),
    })),
);

/* Paleta de prioridades */
const PRIORIDAD_FALLBACK = ['#1f9e86', '#0d84c9', '#e08a1e', '#d64545'];
const prioridadColor = (p, i) =>
    p?.color || PRIORIDAD_FALLBACK[(i ?? 0) % PRIORIDAD_FALLBACK.length];

/* Responsables seleccionados */
const responsablesSeleccionados = computed(() =>
    opcionesUsuarios.value.filter((u) => form.responsables.includes(u.value)),
);

/* Preview de la fecha límite */
const previewFecha = computed(() => {
    if (!form.fecha_limite) return null;
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const fecha = new Date(form.fecha_limite);
    fecha.setHours(0, 0, 0, 0);
    const dias = Math.round((fecha - hoy) / 86400000);
    if (dias < 0) return { texto: 'Fecha vencida', color: '#d64545' };
    if (dias === 0) return { texto: 'Vence hoy', color: '#e08a1e' };
    if (dias === 1) return { texto: 'Vence mañana', color: '#e08a1e' };
    if (dias <= 7) return { texto: `Vence en ${dias} días`, color: '#0d84c9' };
    return { texto: `Vence en ${dias} días`, color: '#1f9e86' };
});

const prioridadSeleccionada = computed(() =>
    props.prioridades.find((p) => p.id === form.prioridad_id),
);

const hayPreview = computed(
    () =>
        form.titulo ||
        form.descripcion ||
        form.fecha_limite ||
        form.responsables.length ||
        form.prioridad_id,
);

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

const enviar = () => {
    form.post(route('tareas.store'), {
        preserveScroll: true,
        onSuccess: () => cerrar(),
    });
};

watch(visible, (v) => {
    if (v) form.clearErrors();
});

defineExpose({ abrir, cerrar });
</script>

<template>
    <a-modal
        v-model:open="visible"
        :footer="null"
        :closable="false"
        :width="720"
        centered
        class="modal-tarea"
        :mask-closable="!form.processing"
    >
        <div class="modal-tarea__wrap">
            <!-- ==========================================================
                 Encabezado
                 ========================================================== -->
            <header class="modal-head">
                <div class="modal-head__ico">
                    <CheckSquareOutlined />
                </div>
                <div class="modal-head__meta">
                    <h2 class="modal-head__titulo">Nueva tarea</h2>
                    <p class="modal-head__sub">
                        Se creará en estado <strong>Pendiente</strong>.
                    </p>
                </div>
                <button
                    type="button"
                    class="modal-head__close"
                    :disabled="form.processing"
                    title="Cerrar"
                    @click="cerrar"
                >
                    ✕
                </button>
            </header>

            <!-- ==========================================================
                 Body (SIN scroll)
                 ========================================================== -->
            <form class="modal-body" @submit.prevent="enviar">
                <!-- Fila 1: Título -->
                <div class="row">
                    <label class="label">
                        <FileTextOutlined /> Título
                    </label>
                    <a-form :model="form" :rules="reglas" layout="vertical">
                        <a-form-item
                            name="titulo"
                            :validate-status="form.errors.titulo ? 'error' : undefined"
                            :help="form.errors.titulo"
                            class="mb-0"
                        >
                            <a-input
                                v-model:value="form.titulo"
                                placeholder="p. ej. Revisar extintores del área de urgencias"
                                :maxlength="150"
                                show-count
                                size="large"
                            />
                        </a-form-item>
                    </a-form>
                </div>

                <!-- Fila 2: Descripción -->
                <div class="row">
                    <label class="label">
                        <FileTextOutlined /> Descripción
                    </label>
                    <a-form :model="form" :rules="reglas" layout="vertical">
                        <a-form-item
                            name="descripcion"
                            :validate-status="form.errors.descripcion ? 'error' : undefined"
                            :help="form.errors.descripcion"
                            class="mb-0"
                        >
                            <a-textarea
                                v-model:value="form.descripcion"
                                :rows="3"
                                placeholder="¿Qué hay que hacer? Sé específico con los entregables."
                                show-count
                                :maxlength="1000"
                            />
                        </a-form-item>
                    </a-form>
                </div>

                <!-- Fila 3: Fecha límite + Prioridad -->
                <div class="grid-2">
                    <div class="row">
                        <label class="label">
                            <CalendarOutlined /> Fecha límite
                        </label>
                        <a-form :model="form" :rules="reglas" layout="vertical">
                            <a-form-item
                                name="fecha_limite"
                                :help="form.errors.fecha_limite"
                                :validate-status="form.errors.fecha_limite ? 'error' : undefined"
                                class="mb-0"
                            >
                                <CampoFechaHora
                                    v-model="form.fecha_limite"
                                    solo-fecha
                                    :min-fecha="hoyISO()"
                                />
                            </a-form-item>
                        </a-form>

                        <div
                            v-if="previewFecha"
                            class="preview-fecha"
                            :style="{ '--pc': previewFecha.color }"
                        >
                            <ClockCircleOutlined />
                            <span>{{ previewFecha.texto }}</span>
                        </div>
                    </div>

                    <div class="row">
                        <label class="label">
                            <FlagOutlined /> Prioridad
                        </label>

                        <div v-if="!prioridades.length" class="sin-prioridades">
                            <InfoCircleOutlined />
                            <span>Sin prioridades configuradas.</span>
                        </div>

                        <div v-else class="prioridades">
                            <label
                                v-for="(p, i) in prioridades"
                                :key="p.id"
                                class="prio-card"
                                :class="{ 'prio-card--active': form.prioridad_id === p.id }"
                                :style="{ '--pc': prioridadColor(p, i) }"
                            >
                                <input
                                    type="radio"
                                    class="prio-card__input"
                                    :value="p.id"
                                    v-model="form.prioridad_id"
                                />
                                <span class="prio-card__dot"></span>
                                <span class="prio-card__label">{{ p.nombre }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Fila: Clasificación -->
                <div class="row">
                    <label class="label">
                        <FolderOutlined /> Clasificación
                    </label>
                    <div class="clasificacion">
                        <label
                            v-for="c in opcionesClasificacion"
                            :key="c.value"
                            class="clasif-card"
                            :class="{ 'clasif-card--active': form.clasificacion === c.value }"
                        >
                            <input type="radio" class="clasif-card__input" :value="c.value" v-model="form.clasificacion" />
                            <component :is="c.icono" class="clasif-card__ico" />
                            <span class="clasif-card__label">{{ c.label }}</span>
                        </label>
                    </div>

                    <a-form v-if="form.clasificacion === 'proyecto'" :model="form" layout="vertical" class="clasificacion-select">
                        <a-form-item
                            name="proyecto_id"
                            :validate-status="form.errors.proyecto_id ? 'error' : undefined"
                            :help="form.errors.proyecto_id"
                            class="mb-0"
                        >
                            <SelectCatalogo
                                v-model:value="form.proyecto_id"
                                :options="proyectos"
                                ruta="catalogos.proyectos"
                                etiqueta="proyecto"
                                placeholder="Selecciona el proyecto"
                            />
                        </a-form-item>
                    </a-form>

                    <a-form v-if="form.clasificacion === 'categoria'" :model="form" layout="vertical" class="clasificacion-select">
                        <a-form-item
                            name="categoria_tarea_id"
                            :validate-status="form.errors.categoria_tarea_id ? 'error' : undefined"
                            :help="form.errors.categoria_tarea_id"
                            class="mb-0"
                        >
                            <SelectCatalogo
                                v-model:value="form.categoria_tarea_id"
                                :options="categorias"
                                ruta="catalogos.categorias_tarea"
                                etiqueta="categoría"
                                etiqueta-plural="categorías"
                                placeholder="Selecciona la categoría"
                            />
                        </a-form-item>
                    </a-form>
                </div>

                <!-- Fila 4: Responsables -->
                <div class="row">
                    <label class="label">
                        <TeamOutlined /> Responsables
                        <span v-if="form.responsables.length" class="badge badge--green">
                            {{ form.responsables.length }}
                        </span>
                    </label>
                    <a-form :model="form" :rules="reglas" layout="vertical">
                        <a-form-item
                            name="responsables"
                            :help="form.errors.responsables"
                            :validate-status="form.errors.responsables ? 'error' : undefined"
                            class="mb-0"
                        >
                            <a-select
                                v-model:value="form.responsables"
                                mode="multiple"
                                :options="opcionesUsuarios"
                                placeholder="Selecciona uno o más responsables"
                                size="large"
                                show-search
                                option-filter-prop="label"
                                :max-tag-count="4"
                            >
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

                    <div v-if="responsablesSeleccionados.length" class="seleccionados">
                        <div class="seleccionados__list">
                            <span
                                v-for="(u, i) in responsablesSeleccionados"
                                :key="u.value"
                                class="chip"
                                :style="{
                                    background: [
                                        'linear-gradient(135deg, #0d84c9, #0f6fb0)',
                                        'linear-gradient(135deg, #1f9e86, #16806c)',
                                        'linear-gradient(135deg, #6b4bc9, #563a9e)',
                                        'linear-gradient(135deg, #e08a1e, #a86717)',
                                        'linear-gradient(135deg, #d64545, #b91c1c)',
                                    ][i % 5],
                                }"
                            >
                                <span class="chip__av">
                                    {{ u.label.charAt(0).toUpperCase() }}
                                </span>
                                {{ u.label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Preview compacto (solo si hay datos) -->
                <div v-if="hayPreview" class="preview-bar">
                    <span class="preview-bar__lbl">
                        <CheckCircleOutlined /> Vista previa
                    </span>
                    <div class="preview-bar__chips">
                        <span v-if="form.fecha_limite" class="preview__chip" :style="{ '--pc': previewFecha?.color || '#0d84c9' }">
                            <CalendarOutlined /> {{ form.fecha_limite }}
                        </span>
                        <span v-if="prioridadSeleccionada" class="preview__chip" :style="{ '--pc': prioridadColor(prioridadSeleccionada, 0) }">
                            <FlagOutlined /> {{ prioridadSeleccionada.nombre }}
                        </span>
                        <span v-if="form.responsables.length" class="preview__chip" style="--pc: #1f9e86">
                            <UserOutlined />
                            {{ form.responsables.length }}
                            {{ form.responsables.length === 1 ? 'responsable' : 'responsables' }}
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
                <a-button
                    type="primary"
                    size="large"
                    class="btn-submit"
                    :loading="form.processing"
                    :disabled="form.processing"
                    @click="enviar"
                >
                    <template #icon><SendOutlined /></template>
                    Registrar tarea
                </a-button>
            </footer>
        </div>
    </a-modal>
</template>

<style scoped>
/* ==========================================================
   Modal base
   ========================================================== */
.modal-tarea :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-tarea :deep(.ant-modal-body) {
    padding: 0;
}

.modal-tarea__wrap {
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
    /* Sin overflow: todo cabe en el modal */
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
   Badge
   ========================================================== */
.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 17px;
    padding: 0 6px;
    border-radius: 999px;
    background: #e6f0f9;
    color: #0d6ca6;
    font-size: 10px;
    font-weight: 800;
    margin-left: 4px;
}

.badge--green {
    background: #e4f4ec;
    color: #16806c;
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
   Prioridades radio-cards (2 columnas compactas)
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
   Clasificación
   ========================================================== */
.clasificacion {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
}

.clasif-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    padding: 10px 8px;
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
}

.clasif-card:hover {
    border-color: #0f6fb0;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px -6px rgba(15, 111, 176, 0.4);
}

.clasif-card--active {
    border-color: #0f6fb0;
    background: #eaf3fb;
    box-shadow: 0 4px 12px -6px rgba(15, 111, 176, 0.45);
}

.clasif-card__input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.clasif-card__ico {
    font-size: 16px;
    color: #7b8a9c;
}

.clasif-card--active .clasif-card__ico {
    color: #0f6fb0;
}

.clasif-card__label {
    font-size: 11px;
    font-weight: 700;
    color: #2b3a4f;
    text-align: center;
}

.clasif-card--active .clasif-card__label {
    color: #173a5f;
}

.clasificacion-select {
    margin-top: 8px;
}

/* ==========================================================
   Usuarios: opción y chips
   ========================================================== */
.usuario-op {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.usuario-op__av {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    flex-shrink: 0;
}

.seleccionados {
    margin-top: 6px;
}

.seleccionados__list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px 3px 3px;
    border-radius: 999px;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(15, 37, 71, 0.15);
}

.chip__av {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.22);
    font-size: 9.5px;
    font-weight: 800;
    flex-shrink: 0;
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

.btn-submit:hover:not(:disabled) {
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

    .prioridades {
        grid-template-columns: 1fr;
    }

    .clasificacion {
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