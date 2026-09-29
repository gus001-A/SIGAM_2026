<script setup>
import { computed, onMounted, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/es';
import {
    BellOutlined,
    CalendarOutlined,
    CheckCircleFilled,
    ClockCircleOutlined,
    ExclamationCircleFilled,
    FileTextOutlined,
    FlagOutlined,
    HistoryOutlined,
    LinkOutlined,
    LockOutlined,
    ReloadOutlined,
    SaveOutlined,
    SafetyCertificateOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import SelectObjetivoMantenimiento from '@/Components/SelectObjetivoMantenimiento.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';
import { hoyISO, reglaNoPasada } from '@/utils/restricciones';

const CATEGORIAS_MANTENIMIENTO = [
    { value: 'preventivo', label: 'Preventivo' },
    { value: 'correctivo', label: 'Correctivo' },
    { value: 'urgente', label: 'Urgente' },
    { value: 'inspeccion', label: 'Inspección' },
];

const props = defineProps({
    plan: { type: Object, default: null },
    preseleccion: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
    frecuencias: { type: Array, default: () => [] },
});

const editando = computed(() => !!props.plan);

/* ==========================================================
   Tipo de mantenimiento preventivo por defecto
   ========================================================== */
const tipoPreventivo = computed(() => {
    const tipos = props.catalogos?.tipos ?? [];
    const porCategoria = tipos.find(
        (t) => (t.categoria ?? '').toLowerCase() === 'preventivo',
    );
    if (porCategoria) return porCategoria;

    const porNombre = tipos.find((t) =>
        (t.nombre ?? '').toLowerCase().includes('preventiv'),
    );
    return porNombre ?? null;
});

const tipoBloqueado = computed(() => !!tipoPreventivo.value);

/* ==========================================================
   Tarjetas visuales de frecuencia — colores variados
   ========================================================== */
const TARJETAS_FRECUENCIA = [
    { value: 'dias', titulo: 'Diario', subtitulo: 'Cada N días', color: '#64748b', icono: HistoryOutlined },
    { value: 'semanal', titulo: 'Semanal', subtitulo: 'Cada 7 días', color: '#0d84c9', icono: CalendarOutlined },
    { value: 'mensual', titulo: 'Mensual', subtitulo: 'Cada mes', color: '#1f9e86', icono: CalendarOutlined },
    { value: 'bimestral', titulo: 'Bimestral', subtitulo: 'Cada 2 meses', color: '#6b4bc9', icono: CalendarOutlined },
    { value: 'trimestral', titulo: 'Trimestral', subtitulo: 'Cada 3 meses', color: '#e08a1e', icono: CalendarOutlined },
    { value: 'semestral', titulo: 'Semestral', subtitulo: 'Cada 6 meses', color: '#d64545', icono: CalendarOutlined },
    { value: 'anual', titulo: 'Anual', subtitulo: 'Una vez al año', color: '#0891b2', icono: CalendarOutlined },
];

const PASO_FRECUENCIA = {
    dias: [1, 'day'],
    semanal: [1, 'week'],
    mensual: [1, 'month'],
    bimestral: [2, 'month'],
    trimestral: [3, 'month'],
    semestral: [6, 'month'],
    anual: [1, 'year'],
};

const UNIDAD_FRECUENCIA = {
    dias: 'día',
    semanal: 'semana',
    mensual: 'mes',
    bimestral: 'bimestre',
    trimestral: 'trimestre',
    semestral: 'semestre',
    anual: 'año',
    personalizada: 'periodo',
};

const previewFrecuencia = computed(() => {
    const tipo = form.tipo_frecuencia;
    const valor = Number(form.valor_frecuencia) || 1;

    if (!tipo) return null;

    if (tipo === 'dias') {
        return {
            titulo: `Cada ${valor} día${valor === 1 ? '' : 's'}`,
            detalle:
                valor === 1
                    ? 'Se repetirá diariamente.'
                    : `Se repetirá cada ${valor} días.`,
        };
    }

    if (tipo === 'personalizada') {
        return {
            titulo: 'Frecuencia personalizada',
            detalle: 'Se repetirá según la regla configurada.',
        };
    }

    const unidad = UNIDAD_FRECUENCIA[tipo] ?? 'periodo';
    const plural = valor === 1 ? unidad : `${unidad}s`;
    return {
        titulo: `Cada ${valor} ${plural}`,
        detalle:
            valor === 1
                ? `Se repetirá una vez por ${unidad}.`
                : `Se repetirá cada ${valor} ${plural}.`,
    };
});

const colorFrecuencia = computed(
    () => TARJETAS_FRECUENCIA.find((t) => t.value === form.tipo_frecuencia)?.color ?? '#e08a1e',
);

// Próximas 4 ejecuciones (siempre desde la fecha de inicio, para no acumular error en meses)
const proximasFechas = computed(() => {
    const paso = PASO_FRECUENCIA[form.tipo_frecuencia];
    const base = form.fecha_inicio ? dayjs(form.fecha_inicio) : null;
    if (!paso || !base?.isValid()) return [];

    const valor = Math.max(1, Number(form.valor_frecuencia) || 1);
    const aviso = Number(form.dias_aviso_anticipado) || 0;

    return Array.from({ length: 4 }, (_, i) => {
        const fecha = base.add(paso[0] * valor * i, paso[1]);
        return {
            key: i,
            fecha: fecha.locale('es').format('dddd D [de] MMMM, YYYY'),
            aviso: aviso > 0 ? fecha.subtract(aviso, 'day').locale('es').format('D MMM') : null,
        };
    });
});

const form = useForm({
    equipo_id: props.plan?.equipo_id ?? props.preseleccion.equipo_id ?? undefined,
    ubicacion_id: props.plan?.ubicacion_id ?? undefined,
    tipo_mantenimiento_id:
        props.plan?.tipo_mantenimiento_id ??
        tipoPreventivo.value?.id ??
        undefined,
    nombre: props.plan?.nombre ?? '',
    tipo_frecuencia: props.plan?.tipo_frecuencia ?? 'mensual',
    valor_frecuencia: props.plan?.valor_frecuencia ?? 1,
    fecha_inicio: props.plan?.fecha_inicio?.slice(0, 10) ?? '',
    dias_aviso_anticipado: props.plan?.dias_aviso_anticipado ?? 7,
    norma_id: props.plan?.norma_id ?? undefined,
    formato_id: props.plan?.formato_id ?? undefined,
    prioridad_id: props.plan?.prioridad_id ?? undefined,
    tecnico_id: props.plan?.tecnico_id ?? undefined,
    estado: props.plan?.estado ?? 'activo',
});

onMounted(() => {
    if (!editando.value && !form.tipo_mantenimiento_id && tipoPreventivo.value) {
        form.tipo_mantenimiento_id = tipoPreventivo.value.id;
    }
});

const reglas = reactive({
    tipo_mantenimiento_id: [{ required: true, message: 'Selecciona el tipo de mantenimiento.' }],
    tipo_frecuencia: [{ required: true, message: 'Selecciona la frecuencia.' }],
    valor_frecuencia: [{ required: true, message: 'Indica el valor de la frecuencia.' }],
    ...(editando.value ? {} : { fecha_inicio: [reglaNoPasada('La fecha de inicio no puede ser anterior a hoy.')] }),
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const TABS = {
    eq: {
        titulo: 'Equipo y tipo',
        subtitulo: 'A qué aplica el plan',
        color: '#0d84c9',
        campos: ['equipo_id', 'ubicacion_id', 'tipo_mantenimiento_id', 'nombre'],
    },
    frec: {
        titulo: 'Frecuencia',
        subtitulo: 'Cada cuándo se repite',
        color: '#e08a1e',
        campos: ['tipo_frecuencia', 'valor_frecuencia', 'fecha_inicio', 'dias_aviso_anticipado'],
    },
    ref: {
        titulo: 'Referencias',
        subtitulo: 'Norma y checklist',
        color: '#6b4bc9',
        campos: ['norma_id', 'formato_id'],
    },
    asig: {
        titulo: 'Asignación',
        subtitulo: 'Prioridad y técnico',
        color: '#1f9e86',
        campos: ['prioridad_id', 'tecnico_id'],
    },
};

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas(
    Object.fromEntries(Object.entries(TABS).map(([k, t]) => [k, t.campos])),
    'eq',
);

const estadoTab = (key) => {
    if (TABS[key].campos.some((c) => form.errors[c])) return 'error';
    if (key === 'eq' && (form.equipo_id || form.ubicacion_id) && form.tipo_mantenimiento_id) return 'ok';
    return null;
};

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor };
    if (editando.value) form.put(route('planes.update', props.plan.id), opciones);
    else form.post(route('planes.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('planes.show', props.plan.id) : route('planes.index'));
</script>

<template>
    <Head :title="editando ? 'Editar plan preventivo' : 'Nuevo plan preventivo'" />

    <AppLayout :titulo="editando ? 'Editar plan preventivo' : 'Nuevo plan preventivo'"
        descripcion="Define el equipo, la frecuencia y los datos con que se generarán las órdenes preventivas.">
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva" class="form-tabs">
                    <!-- TAB: EQUIPO Y TIPO -->
                    <a-tab-pane key="eq">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.eq.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico">
                                        <ToolOutlined />
                                    </span>
                                    <span v-if="estadoTab('eq') === 'ok'" class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                    <span v-else-if="estadoTab('eq') === 'error'"
                                        class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.eq.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.eq.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Sobre qué equipo o instalación aplica este plan y qué tipo de trabajo
                            genera.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Equipo o instalación" :validate-status="est('equipo_id')"
                                    :help="form.errors.equipo_id">
                                    <SelectObjetivoMantenimiento v-model:equipo-id="form.equipo_id"
                                        v-model:ubicacion-id="form.ubicacion_id" :equipos="catalogos.equipos"
                                        :ubicaciones="catalogos.ubicaciones" :disabled="editando" />
                                </a-form-item>
                            </a-col>

                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Tipo de mantenimiento" name="tipo_mantenimiento_id"
                                    :validate-status="est('tipo_mantenimiento_id')"
                                    :help="form.errors.tipo_mantenimiento_id">
                                    <div class="tipo-fijo">
                                        <SelectCatalogo v-if="!tipoBloqueado"
                                            v-model:value="form.tipo_mantenimiento_id" :options="catalogos.tipos"
                                            ruta="catalogos.tipos_mantenimiento" etiqueta="tipo de mantenimiento"
                                            etiqueta-plural="tipos de mantenimiento"
                                            :campos="[{ name: 'categoria', label: 'Categoría', tipo: 'select', opciones: CATEGORIAS_MANTENIMIENTO }]" />

                                        <div v-else class="tipo-fijo__box">
                                            <ToolOutlined class="tipo-fijo__ico" />
                                            <span class="tipo-fijo__txt">
                                                {{ tipoPreventivo.nombre }}
                                            </span>
                                            <span class="tipo-fijo__lock" title="Fijado por ser plan preventivo">
                                                <LockOutlined />
                                            </span>
                                        </div>
                                    </div>
                                    <template v-if="tipoBloqueado" #extra>
                                        <span class="tipo-fijo__hint">
                                            Este plan es <strong>preventivo</strong>, por lo que el tipo queda fijo.
                                        </span>
                                    </template>
                                </a-form-item>
                            </a-col>

                            <a-col :span="24">
                                <a-form-item label="Nombre del plan" extra="Opcional — para identificarlo rápido."
                                    :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre"
                                        placeholder="p. ej. Mantenimiento preventivo trimestral" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <!-- ==========================================================
                         TAB: FRECUENCIA — tarjetas + ajustes + vista previa en vivo
                         ========================================================== -->
                    <a-tab-pane key="frec">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.frec.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico">
                                        <CalendarOutlined />
                                    </span>
                                    <span v-if="estadoTab('frec') === 'error'"
                                        class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.frec.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.frec.subtitulo }}</span>
                                </span>
                            </span>
                        </template>

                        <p class="tab-ayuda">
                            Selecciona la recurrencia que mejor se adapte a este plan. Se muestra en vivo cómo quedará.
                        </p>

                        <a-row :gutter="[20, 20]">
                            <!-- IZQUIERDA: configuración -->
                            <a-col :xs="24" :lg="15">
                                <a-form-item label="¿Cada cuándo se repite?" :validate-status="est('tipo_frecuencia')"
                                    :help="form.errors.tipo_frecuencia" class="frec-item">
                                    <div class="frec-cards">
                                        <button v-for="t in TARJETAS_FRECUENCIA" :key="t.value" type="button"
                                            class="frec-opt"
                                            :class="{ 'frec-opt--active': form.tipo_frecuencia === t.value }"
                                            :style="{ '--fc': t.color }" @click="form.tipo_frecuencia = t.value">
                                            <span class="frec-opt__ico">
                                                <component :is="t.icono" />
                                            </span>
                                            <span class="frec-opt__meta">
                                                <span class="frec-opt__titulo">{{ t.titulo }}</span>
                                                <span class="frec-opt__sub">{{ t.subtitulo }}</span>
                                            </span>
                                            <CheckCircleFilled v-if="form.tipo_frecuencia === t.value"
                                                class="frec-opt__check" />
                                        </button>
                                    </div>
                                </a-form-item>

                                <a-divider class="frec-divider">Ajustes</a-divider>

                                <a-row :gutter="16">
                                    <a-col :xs="24" :sm="8">
                                        <a-form-item
                                            :label="form.tipo_frecuencia === 'dias' ? 'Cada cuántos días' : 'Multiplicador'"
                                            :validate-status="est('valor_frecuencia')"
                                            :help="form.errors.valor_frecuencia"
                                            :extra="previewFrecuencia?.titulo">
                                            <a-input-number v-model:value="form.valor_frecuencia" :min="1"
                                                size="large" style="width: 100%">
                                                <template #prefix>
                                                    <ClockCircleOutlined />
                                                </template>
                                            </a-input-number>
                                        </a-form-item>
                                    </a-col>

                                    <a-col :xs="24" :sm="8">
                                        <a-form-item label="Fecha de inicio" :validate-status="est('fecha_inicio')"
                                            :help="form.errors.fecha_inicio" extra="Primera orden del plan">
                                            <CampoFechaHora v-model="form.fecha_inicio" solo-fecha size="large"
                                                style="width: 100%" :min-fecha="editando ? undefined : hoyISO()" />
                                        </a-form-item>
                                    </a-col>

                                    <a-col :xs="24" :sm="8">
                                        <a-form-item label="Días de aviso"
                                            :validate-status="est('dias_aviso_anticipado')"
                                            :help="form.errors.dias_aviso_anticipado"
                                            extra="Antes de cada vencimiento">
                                            <a-input-number v-model:value="form.dias_aviso_anticipado" :min="0"
                                                :max="365" size="large" style="width: 100%">
                                                <template #prefix>
                                                    <BellOutlined />
                                                </template>
                                            </a-input-number>
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </a-col>

                            <!-- DERECHA: vista previa en vivo -->
                            <a-col :xs="24" :lg="9">
                                <div class="frec-side" :style="{ '--fc': colorFrecuencia }">
                                    <div class="frec-side__head">
                                        <span class="frec-side__ico">
                                            <ReloadOutlined />
                                        </span>
                                        <div class="frec-side__txt">
                                            <strong>{{ previewFrecuencia?.titulo ?? 'Sin frecuencia' }}</strong>
                                            <span>{{ previewFrecuencia?.detalle }}</span>
                                        </div>
                                    </div>

                                    <a-divider style="margin: 12px 0" />

                                    <div class="frec-side__label">Próximas órdenes</div>

                                    <a-timeline v-if="proximasFechas.length" class="frec-timeline">
                                        <a-timeline-item v-for="p in proximasFechas" :key="p.key"
                                            :color="p.key === 0 ? colorFrecuencia : 'gray'">
                                            <div class="frec-fecha" :class="{ 'frec-fecha--first': p.key === 0 }">
                                                {{ p.fecha }}
                                            </div>
                                            <a-tag v-if="p.aviso" color="orange" class="frec-aviso">
                                                <BellOutlined /> Aviso: {{ p.aviso }}
                                            </a-tag>
                                        </a-timeline-item>
                                    </a-timeline>

                                    <a-empty v-else :image="false"
                                        description="Elige una fecha de inicio para ver el calendario" />
                                </div>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <!-- TAB: REFERENCIAS -->
                    <a-tab-pane key="ref">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.ref.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico">
                                        <LinkOutlined />
                                    </span>
                                    <span v-if="estadoTab('ref') === 'error'"
                                        class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.ref.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.ref.subtitulo }}</span>
                                </span>
                            </span>
                        </template>

                        <p class="tab-ayuda">
                            Norma y checklist que aplican cada vez que se genera una orden de este plan — opcional.
                        </p>

                        <a-row :gutter="16">
                            <a-col :xs="24" :sm="12">
                                <div class="ref-card">
                                    <div class="ref-card__head">
                                        <span class="ref-card__ico ref-card__ico--norma">
                                            <SafetyCertificateOutlined />
                                        </span>
                                        <div class="ref-card__meta">
                                            <span class="ref-card__title">Norma aplicable</span>
                                            <span class="ref-card__sub">Reglamento o estándar que rige el plan</span>
                                        </div>
                                    </div>
                                    <div class="ref-card__body">
                                        <a-select v-model:value="form.norma_id" :options="catalogos.normas"
                                            :field-names="{ label: 'codigo', value: 'id' }" allow-clear size="large"
                                            placeholder="Sin norma asociada" style="width: 100%" />
                                    </div>
                                </div>
                            </a-col>

                            <a-col :xs="24" :sm="12">
                                <div class="ref-card">
                                    <div class="ref-card__head">
                                        <span class="ref-card__ico ref-card__ico--formato">
                                            <FileTextOutlined />
                                        </span>
                                        <div class="ref-card__meta">
                                            <span class="ref-card__title">Formato / checklist</span>
                                            <span class="ref-card__sub">Plantilla que se llenará en cada orden</span>
                                        </div>
                                    </div>
                                    <div class="ref-card__body">
                                        <a-select v-model:value="form.formato_id" :options="catalogos.formatos"
                                            :field-names="{ label: 'nombre', value: 'id' }" allow-clear size="large"
                                            placeholder="Sin formato asociado" style="width: 100%" />
                                    </div>
                                </div>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <!-- TAB: ASIGNACIÓN -->
                    <a-tab-pane key="asig">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.asig.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico">
                                        <UserOutlined />
                                    </span>
                                    <span v-if="estadoTab('asig') === 'error'"
                                        class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.asig.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.asig.subtitulo }}</span>
                                </span>
                            </span>
                        </template>

                        <p class="tab-ayuda">
                            Prioridad por defecto y a quién se le sugiere cada vez que este plan genere una orden.
                        </p>

                        <a-row :gutter="16">
                            <a-col :xs="24" :sm="12">
                                <div class="asig-card">
                                    <div class="asig-card__head">
                                        <span class="asig-card__ico asig-card__ico--flag">
                                            <FlagOutlined />
                                        </span>
                                        <div class="asig-card__meta">
                                            <span class="asig-card__title">Prioridad</span>
                                            <span class="asig-card__sub">Qué tan urgente es este plan</span>
                                        </div>
                                    </div>
                                    <div class="asig-card__body">
                                        <SelectCatalogo v-model:value="form.prioridad_id"
                                            :options="catalogos.prioridades" ruta="catalogos.prioridades"
                                            etiqueta="prioridad" etiqueta-plural="prioridades"
                                            :campos="[{ name: 'nivel', label: 'Nivel (1 = más urgente)', tipo: 'number', min: 1 }]" />
                                    </div>
                                </div>
                            </a-col>

                            <a-col :xs="24" :sm="12">
                                <div class="asig-card">
                                    <div class="asig-card__head">
                                        <span class="asig-card__ico asig-card__ico--user">
                                            <UserOutlined />
                                        </span>
                                        <div class="asig-card__meta">
                                            <span class="asig-card__title">Técnico sugerido</span>
                                            <span class="asig-card__sub">A quién se le asignará por defecto</span>
                                        </div>
                                    </div>
                                    <div class="asig-card__body">
                                        <a-select v-model:value="form.tecnico_id" :options="catalogos.tecnicos"
                                            :field-names="{ label: 'nombre', value: 'id' }" allow-clear
                                            placeholder="Sin técnico sugerido" style="width: 100%" />
                                    </div>
                                </div>
                            </a-col>
                        </a-row>
                    </a-tab-pane>
                </a-tabs>

                <div class="form-footer">
                    <a-space>
                        <a-button size="large" @click="cancelar">Cancelar</a-button>
                        <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                            <template #icon>
                                <SaveOutlined />
                            </template>
                            {{ editando ? 'Guardar cambios' : 'Crear plan' }}
                        </a-button>
                    </a-space>
                </div>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.form-card {
    border-radius: 16px;
    box-shadow: var(--sigam-sombra-sm);
    overflow: hidden;
}

.form-tabs :deep(.ant-tabs-nav) {
    padding: 8px 12px 0;
    margin-bottom: 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid var(--sigam-borde-suave);
}

.form-tabs :deep(.ant-tabs-nav::before) {
    border-bottom: none;
}

.form-tabs :deep(.ant-tabs-nav-wrap) {
    align-items: stretch;
}

.form-tabs :deep(.ant-tabs-nav-list) {
    gap: 4px;
    align-items: stretch;
}

.form-tabs :deep(.ant-tabs-tab) {
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 11px 11px 0 0;
    transition: background 0.2s ease;
    height: 56px;
    display: inline-flex !important;
    align-items: center !important;
}

.form-tabs :deep(.ant-tabs-tab:hover) {
    background: #f3f6fa;
}

.form-tabs :deep(.ant-tabs-tab-active) {
    background: #fff;
}

.form-tabs :deep(.ant-tabs-tab-btn) {
    color: inherit !important;
    height: 100%;
    display: inline-flex !important;
    align-items: center !important;
    padding: 0 14px !important;
    transition: none !important;
}

.form-tabs :deep(.ant-tabs-ink-bar) {
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: #0d84c9;
}

.form-tabs :deep(.ant-tabs-content-holder) {
    padding: 22px 24px 20px;
}

.tab-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    height: 100%;
    line-height: 1;
}

.tab-label__ico-wrap {
    position: relative;
    flex: none;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.tab-label__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #94a3b8;
    background: #eef2f7;
    transition: all 0.25s cubic-bezier(0.34, 1.4, 0.4, 1);
    line-height: 1;
}

.tab-label__ico .anticon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}

.form-tabs :deep(.ant-tabs-tab-active) .tab-label__ico {
    color: #fff;
    background: var(--tab-color);
    box-shadow: 0 4px 10px -4px var(--tab-color);
    transform: scale(1.05);
}

.tab-label__badge {
    position: absolute;
    bottom: -3px;
    right: -3px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    line-height: 1;
    border: 2px solid #fff;
    background: #fff;
    pointer-events: none;
    z-index: 2;
}

.tab-label__badge .anticon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}

.tab-label__badge--ok {
    color: #1f9e86;
}

.tab-label__badge--error {
    color: #d64545;
    animation: pulseError 1.6s ease infinite;
}

@keyframes pulseError {

    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.15);
    }
}

.tab-label__col {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 2px;
    min-width: 0;
    text-align: left;
    line-height: 1.15;
}

.tab-label__txt {
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
    transition: color 0.2s ease;
    white-space: nowrap;
    line-height: 1.2;
    display: block;
}

.form-tabs :deep(.ant-tabs-tab-active) .tab-label__txt {
    color: var(--sigam-navy);
}

.tab-label__sub {
    font-size: 10.5px;
    color: var(--sigam-tenue);
    white-space: nowrap;
    line-height: 1.2;
    font-weight: 500;
    display: block;
}

@media (max-width: 991px) {
    .tab-label__sub {
        display: none;
    }
}

@media (max-width: 640px) {
    .tab-label__col {
        display: none;
    }

    .form-tabs :deep(.ant-tabs-tab) {
        height: 50px;
    }
}

.tab-ayuda {
    margin: -4px 0 12px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}

/* Footer */
.form-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 14px 24px;
    border-top: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

/* Tipo fijo */
.tipo-fijo {
    width: 100%;
}

.tipo-fijo__box {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 40px;
    padding: 8px 12px;
    border-radius: 11px;
    background: linear-gradient(135deg, #eef4fb 0%, #e6f2fb 100%);
    border: 1px solid #cfe4f5;
    color: #0f4f7a;
    font-weight: 700;
    font-size: 14px;
    cursor: not-allowed;
}

.tipo-fijo__ico {
    font-size: 15px;
    color: #0d84c9;
    flex-shrink: 0;
}

.tipo-fijo__txt {
    flex: 1;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #173a5f;
}

.tipo-fijo__lock {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    background: rgba(13, 132, 201, 0.12);
    color: #0d84c9;
    font-size: 11px;
    flex-shrink: 0;
}

.tipo-fijo__hint {
    display: inline-block;
    margin-top: 4px;
    font-size: 12px;
    color: var(--sigam-tenue, #7b8a9c);
}

.tipo-fijo__hint strong {
    color: #0d84c9;
    font-weight: 800;
}

/* ==========================================================
   FRECUENCIA
   ========================================================== */
.frec-item :deep(.ant-form-item-label > label) {
    font-weight: 700;
    color: #173a5f;
}

/* ==========================================================
   Grid de tarjetas de frecuencia — COMPACTO PARA 1 SOLA LÍNEA
   Usamos auto-fit con minmax pequeño (110px) para que quepan
   los 7 en una sola fila en pantallas medianas/grandes.
   ========================================================== */
.frec-cards {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 6px;
    width: 100%;
}

/* En pantallas medianas permitimos envolver a 4 por fila */
@media (max-width: 1100px) {
    .frec-cards {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 767px) {
    .frec-cards {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 480px) {
    .frec-cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* ==========================================================
   Tarjeta de frecuencia — compacta (menos ancha)
   ========================================================== */
.frec-opt {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    padding: 8px 8px 7px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    transition: all 0.16s ease;
    min-width: 0;
}

.frec-opt:hover {
    border-color: var(--fc);
    transform: translateY(-1px);
    box-shadow: 0 6px 14px -10px var(--fc);
}

.frec-opt--active {
    border-color: var(--fc);
    background: color-mix(in srgb, var(--fc) 7%, #fff);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--fc) 15%, transparent);
}

.frec-opt__ico {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: var(--fc);
    background: color-mix(in srgb, var(--fc) 12%, #fff);
    transition: all 0.16s ease;
}

.frec-opt--active .frec-opt__ico {
    color: #fff;
    background: var(--fc);
}

.frec-opt__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    width: 100%;
}

.frec-opt__titulo {
    font-size: 11.5px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.frec-opt__sub {
    font-size: 9.5px;
    color: #7b8a9c;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.frec-opt__check {
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 11px;
    color: var(--fc);
}

.frec-divider {
    margin: 4px 0 16px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #7b8a9c !important;
}

/* Panel lateral */
.frec-side {
    height: 100%;
    padding: 16px;
    border-radius: 14px;
    border: 1px solid color-mix(in srgb, var(--fc) 25%, #e2e8f0);
    background: linear-gradient(180deg, color-mix(in srgb, var(--fc) 6%, #fff) 0%, #fff 60%);
}

.frec-side__head {
    display: flex;
    align-items: center;
    gap: 12px;
}

.frec-side__ico {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    background: var(--fc);
    box-shadow: 0 4px 10px -4px var(--fc);
}

.frec-side__txt {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.frec-side__txt strong {
    font-size: 14.5px;
    color: #173a5f;
    line-height: 1.2;
}

.frec-side__txt span {
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.frec-side__label {
    margin-bottom: 12px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #7b8a9c;
}

.frec-timeline {
    margin-bottom: -20px;
}

.frec-fecha {
    font-size: 13px;
    text-transform: capitalize;
    color: #64748b;
}

.frec-fecha--first {
    font-weight: 800;
    color: #173a5f;
}

.frec-aviso {
    margin-top: 4px;
    font-size: 11px;
}

/* Referencias */
.ref-card {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.04);
    overflow: hidden;
    transition: border-color 0.16s ease, box-shadow 0.16s ease, transform 0.16s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.ref-card:hover {
    border-color: #d8cdf0;
    box-shadow: 0 8px 20px -12px rgba(107, 75, 201, 0.32);
    transform: translateY(-1px);
}

.ref-card__head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px 10px;
    border-bottom: 1px solid #eef2f7;
    background: linear-gradient(180deg, #ffffff 0%, #faf7ff 100%);
}

.ref-card__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    flex-shrink: 0;
}

.ref-card__ico--norma {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    box-shadow: 0 4px 10px -4px rgba(107, 75, 201, 0.55);
}

.ref-card__ico--formato {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 4px 10px -4px rgba(13, 132, 201, 0.55);
}

.ref-card__meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.ref-card__title {
    font-size: 13px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.2;
}

.ref-card__sub {
    font-size: 11px;
    color: #7b8a9c;
    font-weight: 500;
    line-height: 1.2;
}

.ref-card__body {
    padding: 14px;
    flex: 1;
}

/* Asignación */
.asig-card {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.04);
    overflow: hidden;
    transition: border-color 0.16s ease, box-shadow 0.16s ease, transform 0.16s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.asig-card:hover {
    border-color: #cfe4f5;
    box-shadow: 0 8px 20px -12px rgba(13, 132, 201, 0.28);
    transform: translateY(-1px);
}

.asig-card__head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px 10px;
    border-bottom: 1px solid #eef2f7;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.asig-card__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    flex-shrink: 0;
}

.asig-card__ico--flag {
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%);
    box-shadow: 0 4px 10px -4px rgba(224, 138, 30, 0.55);
}

.asig-card__ico--user {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 4px 10px -4px rgba(31, 158, 134, 0.55);
}

.asig-card__meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.asig-card__title {
    font-size: 13px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.2;
}

.asig-card__sub {
    font-size: 11px;
    color: #7b8a9c;
    font-weight: 500;
    line-height: 1.2;
}

.asig-card__body {
    padding: 14px;
    flex: 1;
}
</style>