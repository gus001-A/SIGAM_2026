<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckCircleFilled,
    ExclamationCircleFilled,
    LinkOutlined,
    SaveOutlined,
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

const etiquetaFrecuencia = (f) =>
    ({
        dias: 'Cada N días',
        semanal: 'Semanal',
        mensual: 'Mensual',
        bimestral: 'Bimestral',
        trimestral: 'Trimestral',
        semestral: 'Semestral',
        anual: 'Anual',
        personalizada: 'Personalizada',
    })[f] ?? f;

const form = useForm({
    equipo_id: props.plan?.equipo_id ?? props.preseleccion.equipo_id ?? undefined,
    ubicacion_id: props.plan?.ubicacion_id ?? undefined,
    tipo_mantenimiento_id: props.plan?.tipo_mantenimiento_id ?? undefined,
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

const reglas = reactive({
    tipo_mantenimiento_id: [{ required: true, message: 'Selecciona el tipo de mantenimiento.' }],
    tipo_frecuencia: [{ required: true, message: 'Selecciona la frecuencia.' }],
    valor_frecuencia: [{ required: true, message: 'Indica el valor de la frecuencia.' }],
    // Solo al crear: la fecha base para calendarizar no puede ser pasada. Al
    // editar un plan ya existente se conserva la que tenga, aunque sea histórica.
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
        campos: ['prioridad_id', 'tecnico_id', 'estado'],
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

    <AppLayout
        :titulo="editando ? 'Editar plan preventivo' : 'Nuevo plan preventivo'"
        descripcion="Define el equipo, la frecuencia y los datos con que se generarán las órdenes preventivas."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva" class="form-tabs">
                    <a-tab-pane key="eq">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.eq.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><ToolOutlined /></span>
                                    <span v-if="estadoTab('eq') === 'ok'" class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                    <span v-else-if="estadoTab('eq') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.eq.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.eq.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Sobre qué equipo o instalación aplica este plan y qué tipo de trabajo genera.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Equipo o instalación" :validate-status="est('equipo_id')" :help="form.errors.equipo_id">
                                    <SelectObjetivoMantenimiento
                                        v-model:equipo-id="form.equipo_id"
                                        v-model:ubicacion-id="form.ubicacion_id"
                                        :equipos="catalogos.equipos"
                                        :ubicaciones="catalogos.ubicaciones"
                                        :disabled="editando"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Tipo de mantenimiento" name="tipo_mantenimiento_id" :validate-status="est('tipo_mantenimiento_id')" :help="form.errors.tipo_mantenimiento_id">
                                    <SelectCatalogo
                                        v-model:value="form.tipo_mantenimiento_id"
                                        :options="catalogos.tipos"
                                        ruta="catalogos.tipos_mantenimiento"
                                        etiqueta="tipo de mantenimiento"
                                        etiqueta-plural="tipos de mantenimiento"
                                        :campos="[{ name: 'categoria', label: 'Categoría', tipo: 'select', opciones: CATEGORIAS_MANTENIMIENTO }]"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Nombre del plan" extra="Opcional — para identificarlo rápido." :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" placeholder="p. ej. Mantenimiento preventivo trimestral" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="frec">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.frec.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><CalendarOutlined /></span>
                                    <span v-if="estadoTab('frec') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.frec.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.frec.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Cada cuándo se repite y con cuánta anticipación avisar antes de que toque.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Tipo de frecuencia" name="tipo_frecuencia" :validate-status="est('tipo_frecuencia')" :help="form.errors.tipo_frecuencia">
                                    <a-select v-model:value="form.tipo_frecuencia">
                                        <a-select-option v-for="f in frecuencias" :key="f" :value="f">{{ etiquetaFrecuencia(f) }}</a-select-option>
                                    </a-select>
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    :label="form.tipo_frecuencia === 'dias' ? 'Cada cuántos días' : 'Cada cuántos periodos'"
                                    name="valor_frecuencia"
                                    :validate-status="est('valor_frecuencia')"
                                    :help="form.errors.valor_frecuencia"
                                >
                                    <a-input-number v-model:value="form.valor_frecuencia" :min="1" style="width: 100%" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Fecha de inicio" name="fecha_inicio" :validate-status="est('fecha_inicio')" :help="form.errors.fecha_inicio">
                                    <CampoFechaHora
                                        v-model="form.fecha_inicio"
                                        solo-fecha
                                        :min-fecha="editando ? undefined : hoyISO()"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Días de aviso anticipado" name="dias_aviso_anticipado" extra="Con cuántos días de anticipación aparece en 'Preventivos próximos'." :validate-status="est('dias_aviso_anticipado')" :help="form.errors.dias_aviso_anticipado">
                                    <a-input-number v-model:value="form.dias_aviso_anticipado" :min="0" :max="365" style="width: 100%" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="ref">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.ref.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><LinkOutlined /></span>
                                    <span v-if="estadoTab('ref') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.ref.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.ref.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Norma y checklist que aplican cada vez que se genera una orden de este plan — opcional.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Norma aplicable">
                                    <a-select
                                        v-model:value="form.norma_id"
                                        :options="catalogos.normas"
                                        :field-names="{ label: 'codigo', value: 'id' }"
                                        allow-clear
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Formato / checklist">
                                    <a-select
                                        v-model:value="form.formato_id"
                                        :options="catalogos.formatos"
                                        :field-names="{ label: 'nombre', value: 'id' }"
                                        allow-clear
                                    />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="asig">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.asig.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><UserOutlined /></span>
                                    <span v-if="estadoTab('asig') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.asig.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.asig.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Prioridad por defecto y a quién se le sugiere cada vez que este plan genera una orden.</p>
                        <a-form-item label="Prioridad">
                            <SelectCatalogo
                                v-model:value="form.prioridad_id"
                                :options="catalogos.prioridades"
                                ruta="catalogos.prioridades"
                                etiqueta="prioridad"
                                etiqueta-plural="prioridades"
                                :campos="[{ name: 'nivel', label: 'Nivel (1 = más urgente)', tipo: 'number', min: 1 }]"
                            />
                        </a-form-item>
                        <a-form-item label="Técnico sugerido">
                            <a-select
                                v-model:value="form.tecnico_id"
                                :options="catalogos.tecnicos"
                                :field-names="{ label: 'nombre', value: 'id' }"
                                allow-clear
                            />
                        </a-form-item>
                        <a-form-item label="Estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activo</a-radio-button>
                                <a-radio-button value="inactivo">Inactivo</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <a-card size="small" class="form-acciones">
                <a-space>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon><SaveOutlined /></template>
                        {{ editando ? 'Guardar cambios' : 'Crear plan' }}
                    </a-button>
                    <a-button size="large" @click="cancelar">Cancelar</a-button>
                </a-space>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.form-card {
    margin-bottom: 10px;
    border-radius: 16px;
    box-shadow: var(--sigam-sombra-sm);
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
    padding: 22px 24px 24px;
}

/* Label completo */
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
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
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
    margin: -4px 0 10px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}
.form-acciones {
    position: sticky;
    bottom: 0;
    z-index: 5;
}
</style>
