<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CalendarOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    FileTextOutlined,
    FlagOutlined,
    FolderOutlined,
    InfoCircleOutlined,
    SendOutlined,
    TeamOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import { hoyISO, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    usuarios: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
    proyectos: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
});

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

/* Paleta de prioridades: usa el color del backend si viene, con fallback */
const PRIORIDAD_FALLBACK = ['#1f9e86', '#0d84c9', '#e08a1e', '#d64545'];
const prioridadColor = (p, i) => p.color || PRIORIDAD_FALLBACK[i % PRIORIDAD_FALLBACK.length];

/* Responsables seleccionados (para mostrar avatares) */
const responsablesSeleccionados = computed(() =>
    opcionesUsuarios.value.filter((u) => form.responsables.includes(u.value)),
);

/* Preview del rango de la fecha límite */
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

const enviar = () => form.post(route('tareas.store'));
const cancelar = () => router.visit(route('tareas.index'));

const colorAvatar = (i) => {
    const paleta = [
        'linear-gradient(135deg, #0d84c9, #0f6fb0)',
        'linear-gradient(135deg, #1f9e86, #16806c)',
        'linear-gradient(135deg, #6b4bc9, #563a9e)',
        'linear-gradient(135deg, #e08a1e, #a86717)',
        'linear-gradient(135deg, #d64545, #b91c1c)',
    ];
    return paleta[i % paleta.length];
};
</script>

<template>
    <Head title="Nueva tarea" />

    <AppLayout>
        <div class="form-tarea">
            <!-- ==========================================================
                 Encabezado tipo ficha
                 ========================================================== -->
            <header class="form-head">
                <div class="form-head__ico">
                    <CheckCircleOutlined />
                </div>
                <div class="form-head__meta">
                    <h1 class="form-head__titulo">Nueva tarea</h1>
                    <p class="form-head__sub">
                        Describe la tarea, su fecha límite y a quién se le asigna. Una vez creada, pasa a
                        <strong>Pendiente</strong>.
                    </p>
                </div>
                <button type="button" class="form-head__cancel" @click="cancelar">
                    Cancelar
                </button>
            </header>

            <div class="form-grid">
                <!-- ==========================================================
                     COLUMNA PRINCIPAL
                     ========================================================== -->
                <form class="form-main" @submit.prevent="enviar">
                    <!-- ---------- Sección: Información ---------- -->
                    <section class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <FileTextOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Información de la tarea</div>
                                <div class="card__sub">Título y descripción</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <a-form :model="form" :rules="reglas" layout="vertical">
                                <a-form-item
                                    label="Título"
                                    name="titulo"
                                    :validate-status="form.errors.titulo ? 'error' : undefined"
                                    :help="form.errors.titulo"
                                >
                                    <a-input
                                        v-model:value="form.titulo"
                                        placeholder="p. ej. Revisar extintores del área de urgencias"
                                        :maxlength="150"
                                        show-count
                                        size="large"
                                    />
                                </a-form-item>

                                <a-form-item
                                    label="Descripción"
                                    name="descripcion"
                                    :validate-status="form.errors.descripcion ? 'error' : undefined"
                                    :help="form.errors.descripcion"
                                    class="mb-0"
                                >
                                    <a-textarea
                                        v-model:value="form.descripcion"
                                        :rows="5"
                                        placeholder="¿Qué hay que hacer? Sé específico con los entregables."
                                        show-count
                                        :maxlength="1000"
                                    />
                                </a-form-item>
                            </a-form>
                        </div>
                    </section>

                    <!-- ---------- Sección: Programación ---------- -->
                    <section class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <CalendarOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Programación</div>
                                <div class="card__sub">Fecha límite y prioridad</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <a-form :model="form" :rules="reglas" layout="vertical">
                                <a-row :gutter="16">
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item
                                            label="Fecha límite"
                                            name="fecha_limite"
                                            :help="form.errors.fecha_limite"
                                            :validate-status="form.errors.fecha_limite ? 'error' : undefined"
                                        >
                                            <CampoFechaHora
                                                v-model="form.fecha_limite"
                                                solo-fecha
                                                :min-fecha="hoyISO()"
                                            />
                                        </a-form-item>

                                        <!-- Preview de la fecha -->
                                        <div
                                            v-if="previewFecha"
                                            class="preview-fecha"
                                            :style="{ '--pc': previewFecha.color }"
                                        >
                                            <ClockCircleOutlined />
                                            <span>{{ previewFecha.texto }}</span>
                                        </div>
                                    </a-col>

                                    <a-col :xs="24" :sm="12">
                                        <a-form-item
                                            label="Prioridad"
                                            :help="form.errors.prioridad_id"
                                            :validate-status="form.errors.prioridad_id ? 'error' : undefined"
                                        >
                                            <!-- Sin prioridades -->
                                            <div v-if="!prioridades.length" class="sin-prioridades">
                                                <InfoCircleOutlined />
                                                <span>No hay prioridades configuradas.</span>
                                            </div>

                                            <!-- Radio-cards de prioridad -->
                                            <div v-else class="prioridades">
                                                <label
                                                    v-for="(p, i) in prioridades"
                                                    :key="p.id"
                                                    class="prio-card"
                                                    :class="{
                                                        'prio-card--active': form.prioridad_id === p.id,
                                                    }"
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
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </a-form>
                        </div>
                    </section>

                    <!-- ---------- Sección: Clasificación ---------- -->
                    <section class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0f6fb0">
                                <FolderOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Clasificación</div>
                                <div class="card__sub">¿Es una tarea general, de un proyecto o de una categoría?</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="clasificacion">
                                <label
                                    v-for="c in opcionesClasificacion"
                                    :key="c.value"
                                    class="clasif-card"
                                    :class="{ 'clasif-card--active': form.clasificacion === c.value }"
                                >
                                    <input
                                        type="radio"
                                        class="clasif-card__input"
                                        :value="c.value"
                                        v-model="form.clasificacion"
                                    />
                                    <component :is="c.icono" class="clasif-card__ico" />
                                    <span class="clasif-card__label">{{ c.label }}</span>
                                </label>
                            </div>

                            <a-form
                                v-if="form.clasificacion === 'proyecto'"
                                :model="form"
                                layout="vertical"
                                class="clasificacion-select"
                            >
                                <a-form-item
                                    label="Proyecto"
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

                            <a-form
                                v-if="form.clasificacion === 'categoria'"
                                :model="form"
                                layout="vertical"
                                class="clasificacion-select"
                            >
                                <a-form-item
                                    label="Categoría"
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
                    </section>

                    <!-- ---------- Sección: Asignación ---------- -->
                    <section class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <TeamOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Responsables
                                    <span
                                        v-if="form.responsables.length"
                                        class="badge badge--green"
                                    >
                                        {{ form.responsables.length }}
                                    </span>
                                </div>
                                <div class="card__sub">Quién se encarga de la tarea</div>
                            </div>
                        </div>
                        <div class="card__body">
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
                                        :max-tag-count="6"
                                    >
                                        <template #option="{ label, value }">
                                            <div class="usuario-op">
                                                <span class="usuario-op__av">
                                                    {{ label.charAt(0).toUpperCase() }}
                                                </span>
                                                <span>{{ label }}</span>
                                            </div>
                                        </template>
                                    </a-select>
                                </a-form-item>

                                <!-- Avatares de los seleccionados -->
                                <div
                                    v-if="responsablesSeleccionados.length"
                                    class="seleccionados"
                                >
                                    <span class="seleccionados__lbl">
                                        <CheckCircleOutlined /> Asignados
                                    </span>
                                    <div class="seleccionados__list">
                                        <span
                                            v-for="(u, i) in responsablesSeleccionados"
                                            :key="u.value"
                                            class="chip"
                                            :style="{ background: colorAvatar(i) }"
                                        >
                                            <span class="chip__av">
                                                {{ u.label.charAt(0).toUpperCase() }}
                                            </span>
                                            {{ u.label }}
                                        </span>
                                    </div>
                                </div>
                            </a-form>
                        </div>
                    </section>

                    <!-- ---------- Footer de acciones ---------- -->
                    <div class="form-actions">
                        <a-button
                            size="large"
                            @click="cancelar"
                        >
                            Cancelar
                        </a-button>
                        <a-button
                            type="primary"
                            html-type="submit"
                            size="large"
                            class="btn-submit"
                            :loading="form.processing"
                            :disabled="form.processing"
                        >
                            <template #icon><SendOutlined /></template>
                            Registrar tarea
                        </a-button>
                    </div>
                </form>

                <!-- ==========================================================
                     COLUMNA LATERAL: tips / preview
                     ========================================================== -->
                <aside class="form-side">
                    <div class="tip">
                        <div class="tip__ic" style="--c: #0d84c9">
                            <InfoCircleOutlined />
                        </div>
                        <div class="tip__t">Consejos</div>
                        <ul class="tip__list">
                            <li>Un buen título cabe en una línea y empieza con un verbo.</li>
                            <li>Sé concreto en la descripción: qué, dónde y cómo se valida.</li>
                            <li>La fecha límite es informativa: quien tenga permiso puede cerrarla antes.</li>
                            <li>Todos los responsables reciben una notificación al guardar.</li>
                        </ul>
                    </div>

                    <div v-if="form.titulo || form.descripcion || form.fecha_limite || form.responsables.length" class="preview">
                        <div class="preview__head">
                            <ClockCircleOutlined />
                            <span>Vista previa</span>
                        </div>
                        <div class="preview__card">
                            <div class="preview__titulo">
                                {{ form.titulo || 'Sin título' }}
                            </div>
                            <div v-if="form.descripcion" class="preview__desc">
                                {{ form.descripcion }}
                            </div>
                            <div class="preview__chips">
                                <span
                                    v-if="form.fecha_limite"
                                    class="preview__chip"
                                    :style="{ '--pc': previewFecha?.color || '#0d84c9' }"
                                >
                                    <CalendarOutlined />
                                    {{ form.fecha_limite }}
                                </span>
                                <span
                                    v-if="form.prioridad_id"
                                    class="preview__chip"
                                    :style="{ '--pc': prioridadColor(prioridades.find(p => p.id === form.prioridad_id) || {}, 0) }"
                                >
                                    <FlagOutlined />
                                    {{ prioridades.find(p => p.id === form.prioridad_id)?.nombre }}
                                </span>
                                <span
                                    v-if="form.responsables.length"
                                    class="preview__chip"
                                    style="--pc: #1f9e86"
                                >
                                    <UserOutlined />
                                    {{ form.responsables.length }}
                                    {{ form.responsables.length === 1 ? 'responsable' : 'responsables' }}
                                </span>
                                <span
                                    v-if="form.clasificacion === 'proyecto' && form.proyecto_id"
                                    class="preview__chip"
                                    style="--pc: #0f6fb0"
                                >
                                    <FolderOutlined />
                                    {{ proyectos.find((p) => p.id === form.proyecto_id)?.nombre }}
                                </span>
                                <span
                                    v-if="form.clasificacion === 'categoria' && form.categoria_tarea_id"
                                    class="preview__chip"
                                    style="--pc: #b45309"
                                >
                                    <ApartmentOutlined />
                                    {{ categorias.find((c) => c.id === form.categoria_tarea_id)?.nombre }}
                                </span>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Layout
   ========================================================== */
.form-tarea {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding-bottom: 24px;
}

.form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(280px, 1fr);
    gap: 18px;
    align-items: start;
}

.form-main {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ==========================================================
   Encabezado
   ========================================================== */
.form-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f8fb 100%);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
}

.form-head__ico {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.55);
    flex-shrink: 0;
}

.form-head__meta {
    flex: 1;
    min-width: 0;
}

.form-head__titulo {
    font-size: 20px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.3px;
}

.form-head__sub {
    margin: 2px 0 0;
    font-size: 12.5px;
    color: #7b8a9c;
    line-height: 1.4;
}

.form-head__sub strong {
    color: #0d84c9;
    font-weight: 800;
}

.form-head__cancel {
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    height: 36px;
    padding: 0 16px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition:
        background 0.14s ease,
        border-color 0.14s ease,
        color 0.14s ease;
    flex-shrink: 0;
}

.form-head__cancel:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #173a5f;
}

/* ==========================================================
   Cards
   ========================================================== */
.card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    transition: box-shadow 0.18s ease;
}

.card:hover {
    box-shadow: 0 6px 18px -12px rgba(15, 37, 71, 0.22);
}

.card__head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 18px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #f8fafc 0%, #f3f7fb 100%);
}

.card__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: var(--c);
    flex-shrink: 0;
    box-shadow: 0 4px 10px -4px rgba(15, 37, 71, 0.35);
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
}

.card__body {
    padding: 18px;
}

/* ==========================================================
   Badge
   ========================================================== */
.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    background: #e6f0f9;
    color: #0d6ca6;
    font-size: 10.5px;
    font-weight: 800;
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
    gap: 6px;
    margin-top: -6px;
    margin-bottom: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--pc) 12%, transparent);
    color: var(--pc);
    font-size: 11.5px;
    font-weight: 800;
    box-shadow: 0 1px 3px color-mix(in srgb, var(--pc) 20%, transparent);
}

.preview-fecha .anticon {
    font-size: 12px;
}

/* ==========================================================
   Prioridades radio-cards
   ========================================================== */
.sin-prioridades {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px dashed #cdd8e3;
    color: #7b8a9c;
    font-size: 12.5px;
}

.prioridades {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.prio-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 12px;
    border-radius: 11px;
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
    width: 14px;
    height: 14px;
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
    font-size: 12.5px;
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
    gap: 8px;
}

.clasif-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    border-radius: 11px;
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
    font-size: 18px;
    color: #7b8a9c;
}

.clasif-card--active .clasif-card__ico {
    color: #0f6fb0;
}

.clasif-card__label {
    font-size: 12px;
    font-weight: 700;
    color: #2b3a4f;
    text-align: center;
}

.clasif-card--active .clasif-card__label {
    color: #173a5f;
}

.clasificacion-select {
    margin-top: 12px;
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
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px dashed #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.seleccionados__lbl {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #7b8a9c;
}

.seleccionados__lbl .anticon {
    color: #1f9e86;
}

.seleccionados__list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px 4px 4px;
    border-radius: 999px;
    color: #fff;
    font-size: 11.5px;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(15, 37, 71, 0.15);
}

.chip__av {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.22);
    font-size: 10px;
    font-weight: 800;
    flex-shrink: 0;
}

/* ==========================================================
   Footer de acciones
   ========================================================== */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 18px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    position: sticky;
    bottom: 12px;
    z-index: 2;
}

.btn-submit {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%) !important;
    border-color: #0d84c9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(13, 132, 201, 0.75);
}

/* ==========================================================
   Columna lateral
   ========================================================== */
.form-side {
    display: flex;
    flex-direction: column;
    gap: 16px;
    position: sticky;
    top: 12px;
}

.tip {
    padding: 16px 18px;
    border-radius: 14px;
    background: linear-gradient(135deg, #f5f8fb 0%, #eef4fb 100%);
    border: 1px solid #dbe3ec;
}

.tip__ic {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--c);
    color: #fff;
    font-size: 14px;
    margin-bottom: 8px;
    box-shadow: 0 4px 10px -4px rgba(15, 37, 71, 0.35);
}

.tip__t {
    font-weight: 800;
    font-size: 13px;
    color: #173a5f;
    margin-bottom: 6px;
}

.tip__list {
    margin: 0;
    padding-left: 18px;
    font-size: 12.5px;
    line-height: 1.55;
    color: #4b5b70;
}

.tip__list li {
    margin-bottom: 4px;
}

.tip__list li::marker {
    color: #0d84c9;
}

/* Vista previa */
.preview {
    padding: 14px 16px;
    border-radius: 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
}

.preview__head {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #7b8a9c;
    margin-bottom: 10px;
}

.preview__card {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.preview__titulo {
    font-weight: 800;
    font-size: 13.5px;
    color: #173a5f;
    line-height: 1.3;
    word-break: break-word;
}

.preview__desc {
    font-size: 12.5px;
    color: #4b5b70;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    white-space: pre-line;
}

.preview__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}

.preview__chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--pc) 12%, transparent);
    color: var(--pc);
    font-size: 11px;
    font-weight: 800;
}

.preview__chip .anticon {
    font-size: 11px;
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 991px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-side {
        position: static;
    }

    .form-actions {
        position: static;
    }
}

@media (max-width: 575px) {
    .form-head {
        padding: 14px;
        flex-wrap: wrap;
    }

    .form-head__titulo {
        font-size: 17px;
    }

    .form-head__cancel {
        width: 100%;
    }

    .prioridades {
        grid-template-columns: 1fr;
    }

    .clasificacion {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .form-actions .ant-btn {
        width: 100%;
    }
}
</style>