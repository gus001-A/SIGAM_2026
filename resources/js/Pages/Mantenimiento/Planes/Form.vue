<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarOutlined, LinkOutlined, SaveOutlined, ToolOutlined, UserOutlined } from '@ant-design/icons-vue';
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

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas({
    eq: ['equipo_id', 'ubicacion_id', 'tipo_mantenimiento_id', 'nombre'],
    frec: ['tipo_frecuencia', 'valor_frecuencia', 'fecha_inicio', 'dias_aviso_anticipado'],
    ref: ['norma_id', 'formato_id'],
    asig: ['prioridad_id', 'tecnico_id', 'estado'],
}, 'eq');

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
                <a-tabs v-model:activeKey="pestanaActiva">
                    <a-tab-pane key="eq">
                        <template #tab><span><ToolOutlined /> Equipo y tipo</span></template>
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
                        <template #tab><span><CalendarOutlined /> Frecuencia</span></template>
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
                        <template #tab><span><LinkOutlined /> Referencias</span></template>
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
                        <template #tab><span><UserOutlined /> Asignación</span></template>
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
}
.form-card :deep(.ant-tabs-nav) {
    margin-bottom: 10px;
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
