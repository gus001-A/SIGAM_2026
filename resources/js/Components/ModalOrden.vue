<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';

const props = defineProps({
    equipos: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
});

const abierto = ref(false);

const form = useForm({
    equipo_id: undefined,
    tipo_id: undefined,
    prioridad_id: undefined,
    programado_inicio: '',
    programado_fin: '',
    problema_reportado: '',
});

const reglas = {
    equipo_id: [{ required: true, message: 'Selecciona el equipo.' }],
    tipo_id: [{ required: true, message: 'Selecciona el tipo.' }],
    prioridad_id: [{ required: true, message: 'Selecciona la prioridad.' }],
};

const equipoOpciones = computed(() =>
    props.equipos.map((e) => ({ value: e.id, label: `${e.codigo_activo} — ${e.descripcion}` })),
);
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
        title="Nueva orden de mantenimiento"
        :width="600"
        :confirm-loading="form.processing"
        ok-text="Crear orden"
        cancel-text="Cancelar"
        centered
        @ok="enviar"
    >
        <a-form :model="form" :rules="reglas" layout="vertical" class="pt-1" @finish="enviar">
            <a-form-item label="Equipo" name="equipo_id" :validate-status="est('equipo_id')" :help="form.errors.equipo_id">
                <a-select v-model:value="form.equipo_id" :options="equipoOpciones" show-search option-filter-prop="label" placeholder="Buscar equipo" />
            </a-form-item>

            <a-row :gutter="14">
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Tipo de mantenimiento" name="tipo_id" :validate-status="est('tipo_id')" :help="form.errors.tipo_id">
                        <a-select v-model:value="form.tipo_id" :options="catalogos.tipos?.map((t) => ({ value: t.id, label: t.nombre }))" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Prioridad" name="prioridad_id" :validate-status="est('prioridad_id')" :help="form.errors.prioridad_id">
                        <a-select v-model:value="form.prioridad_id" :options="catalogos.prioridades?.map((p) => ({ value: p.id, label: p.nombre }))" />
                    </a-form-item>
                </a-col>
                <a-col :span="24">
                    <a-form-item label="Programado — inicio (fecha y hora)" :validate-status="est('programado_inicio')" :help="form.errors.programado_inicio">
                        <CampoFechaHora v-model="form.programado_inicio" />
                    </a-form-item>
                </a-col>
                <a-col :span="24">
                    <a-form-item label="Programado — fin (fecha y hora)" :validate-status="est('programado_fin')" :help="form.errors.programado_fin">
                        <CampoFechaHora v-model="form.programado_fin" />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-form-item label="Problema reportado / motivo" :validate-status="est('problema_reportado')" :help="form.errors.problema_reportado">
                <a-textarea v-model:value="form.problema_reportado" :rows="3" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>
