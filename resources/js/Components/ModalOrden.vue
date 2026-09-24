<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ToolOutlined } from '@ant-design/icons-vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import SelectObjetivoMantenimiento from '@/Components/SelectObjetivoMantenimiento.vue';
import { hoyISO, reglaDespuesDe, reglaNoPasada } from '@/utils/restricciones';

const CATEGORIAS_MANTENIMIENTO = [
    { value: 'preventivo', label: 'Preventivo' },
    { value: 'correctivo', label: 'Correctivo' },
    { value: 'urgente', label: 'Urgente' },
    { value: 'inspeccion', label: 'Inspección' },
];

const props = defineProps({
    equipos: { type: Array, default: () => [] },
    ubicaciones: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
});

const abierto = ref(false);

const form = useForm({
    equipo_id: undefined,
    ubicacion_id: undefined,
    tipo_id: undefined,
    prioridad_id: undefined,
    programado_inicio: '',
    programado_fin: '',
    problema_reportado: '',
});

const reglas = {
    tipo_id: [{ required: true, message: 'Selecciona el tipo.' }],
    prioridad_id: [{ required: true, message: 'Selecciona la prioridad.' }],
    programado_inicio: [reglaNoPasada()],
    programado_fin: [reglaDespuesDe(() => form.programado_inicio, 'El fin debe ser posterior al inicio.', true)],
};

const est = (c) => (form.errors[c] ? 'error' : '');

const abrir = (equipoId = null) => {
    form.reset();
    form.clearErrors();
    if (equipoId) form.equipo_id = equipoId;
    abierto.value = true;
};

const enviar = () =>
    form.post(route('mantenimientos.store'), {
        onSuccess: () => (abierto.value = false),
    });

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        :width="600"
        :confirm-loading="form.processing"
        ok-text="Crear orden"
        cancel-text="Cancelar"
        centered
        wrap-class-name="modal-formulario"
        @ok="enviar"
    >
        <template #title>
            <span class="mf-titulo"><ToolOutlined /> Nueva orden de mantenimiento</span>
        </template>
        <a-form :model="form" :rules="reglas" layout="vertical" class="pt-1" @finish="enviar">
            <a-form-item label="Equipo o instalación" :validate-status="est('equipo_id')" :help="form.errors.equipo_id">
                <SelectObjetivoMantenimiento
                    v-model:equipo-id="form.equipo_id"
                    v-model:ubicacion-id="form.ubicacion_id"
                    :equipos="equipos"
                    :ubicaciones="ubicaciones"
                />
            </a-form-item>

            <a-row :gutter="14">
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Tipo de mantenimiento" name="tipo_id" :validate-status="est('tipo_id')" :help="form.errors.tipo_id">
                        <SelectCatalogo
                            v-model:value="form.tipo_id"
                            :options="catalogos.tipos"
                            ruta="catalogos.tipos_mantenimiento"
                            etiqueta="tipo de mantenimiento"
                            etiqueta-plural="tipos de mantenimiento"
                            :campos="[{ name: 'categoria', label: 'Categoría', tipo: 'select', opciones: CATEGORIAS_MANTENIMIENTO }]"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Prioridad" name="prioridad_id" :validate-status="est('prioridad_id')" :help="form.errors.prioridad_id">
                        <SelectCatalogo
                            v-model:value="form.prioridad_id"
                            :options="catalogos.prioridades"
                            ruta="catalogos.prioridades"
                            etiqueta="prioridad"
                            etiqueta-plural="prioridades"
                            :campos="[{ name: 'nivel', label: 'Nivel (1 = más urgente)', tipo: 'number', min: 1 }]"
                        />
                    </a-form-item>
                </a-col>
                <a-col :span="24">
                    <a-form-item label="Programado — inicio (fecha y hora)" name="programado_inicio" :validate-status="est('programado_inicio')" :help="form.errors.programado_inicio">
                        <CampoFechaHora v-model="form.programado_inicio" :min-fecha="hoyISO()" />
                    </a-form-item>
                </a-col>
                <a-col :span="24">
                    <a-form-item label="Programado — fin (fecha y hora)" name="programado_fin" :validate-status="est('programado_fin')" :help="form.errors.programado_fin">
                        <CampoFechaHora v-model="form.programado_fin" :min-fecha="form.programado_inicio ? form.programado_inicio.slice(0, 10) : hoyISO()" />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-form-item label="Problema reportado / motivo" :validate-status="est('problema_reportado')" :help="form.errors.problema_reportado">
                <a-textarea v-model:value="form.problema_reportado" :rows="3" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>

<style scoped>
.mf-titulo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.mf-titulo .anticon {
    color: var(--sigam-teal);
}
</style>
