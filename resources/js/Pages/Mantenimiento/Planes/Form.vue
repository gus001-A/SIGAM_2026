<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarOutlined, LinkOutlined, SaveOutlined, ToolOutlined, UserOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';

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
    equipo_id: [{ required: true, message: 'Selecciona el equipo.' }],
    tipo_mantenimiento_id: [{ required: true, message: 'Selecciona el tipo de mantenimiento.' }],
    tipo_frecuencia: [{ required: true, message: 'Selecciona la frecuencia.' }],
    valor_frecuencia: [{ required: true, message: 'Indica el valor de la frecuencia.' }],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const enviar = () => {
    const opciones = { preserveScroll: true };
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
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
            <a-row :gutter="16">
                <a-col :xs="24" :lg="16">
                    <a-card size="small" class="mb-4 sec"><template #title><ToolOutlined /> Equipo y tipo</template>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Equipo" name="equipo_id" :validate-status="est('equipo_id')" :help="form.errors.equipo_id">
                                    <a-select
                                        v-model:value="form.equipo_id"
                                        :options="catalogos.equipos"
                                        :field-names="{ label: 'codigo_activo', value: 'id' }"
                                        show-search
                                        option-filter-prop="codigo_activo"
                                        :disabled="editando"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Tipo de mantenimiento" name="tipo_mantenimiento_id" :validate-status="est('tipo_mantenimiento_id')" :help="form.errors.tipo_mantenimiento_id">
                                    <a-select
                                        v-model:value="form.tipo_mantenimiento_id"
                                        :options="catalogos.tipos"
                                        :field-names="{ label: 'nombre', value: 'id' }"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Nombre del plan" extra="Opcional — para identificarlo rápido" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" placeholder="p. ej. Mantenimiento preventivo trimestral" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>

                    <a-card size="small" class="mb-4 sec"><template #title><CalendarOutlined /> Frecuencia</template>
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
                                <a-form-item label="Fecha de inicio" :validate-status="est('fecha_inicio')" :help="form.errors.fecha_inicio">
                                    <CampoFechaHora v-model="form.fecha_inicio" solo-fecha />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Días de aviso anticipado" name="dias_aviso_anticipado" :validate-status="est('dias_aviso_anticipado')" :help="form.errors.dias_aviso_anticipado">
                                    <a-input-number v-model:value="form.dias_aviso_anticipado" :min="0" :max="365" style="width: 100%" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>

                    <a-card size="small" class="sec"><template #title><LinkOutlined /> Referencias</template>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Norma aplicable">
                                    <a-select
                                        v-model:value="form.norma_id"
                                        :options="catalogos.normas"
                                        :field-names="{ label: 'codigo', value: 'id' }"
                                        allow-clear
                                        show-search
                                        option-filter-prop="codigo"
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
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="8">
                    <a-card size="small" class="mb-4 sec"><template #title><UserOutlined /> Asignación</template>
                        <a-form-item label="Prioridad">
                            <a-select
                                v-model:value="form.prioridad_id"
                                :options="catalogos.prioridades"
                                :field-names="{ label: 'nombre', value: 'id' }"
                                allow-clear
                            />
                        </a-form-item>
                        <a-form-item label="Técnico sugerido">
                            <a-select
                                v-model:value="form.tecnico_id"
                                :options="catalogos.tecnicos"
                                :field-names="{ label: 'nombre', value: 'id' }"
                                allow-clear
                                show-search
                                option-filter-prop="nombre"
                            />
                        </a-form-item>
                        <a-form-item label="Estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activo</a-radio-button>
                                <a-radio-button value="inactivo">Inactivo</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-card>

                    <a-card size="small">
                        <a-button type="primary" size="large" block html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Crear plan' }}
                        </a-button>
                        <a-button type="text" block class="mt-2" @click="cancelar">Cancelar</a-button>
                    </a-card>
                </a-col>
            </a-row>
        </a-form>
    </AppLayout>
</template>
