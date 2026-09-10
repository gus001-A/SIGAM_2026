<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
const props = defineProps({
    equipos: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
});

const abierto = ref(false);

const form = useForm({
    equipo_id: undefined,
    descripcion: '',
    prioridad_id: undefined,
    fecha_requerida: '',
});

const reglas = {
    equipo_id: [{ required: true, message: 'Selecciona el equipo.' }],
    descripcion: [{ required: true, message: 'Describe el problema.' }],
};

const equipoOpciones = computed(() =>
    props.equipos.map((e) => ({ value: e.id, label: `${e.codigo_activo} — ${e.descripcion}` })),
);

const abrir = (equipoId = null) => {
    form.reset();
    form.clearErrors();
    if (equipoId) form.equipo_id = equipoId;
    abierto.value = true;
};

const enviar = () =>
    form.post(route('solicitudes.store'), {
        onSuccess: () => (abierto.value = false),
    });

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        title="Nueva solicitud de mantenimiento"
        :width="560"
        :confirm-loading="form.processing"
        ok-text="Enviar solicitud"
        cancel-text="Cancelar"
        centered
        @ok="enviar"
    >
        <a-form :model="form" :rules="reglas" layout="vertical" class="pt-1" @finish="enviar">
            <a-form-item label="Equipo" name="equipo_id" :validate-status="form.errors.equipo_id ? 'error' : ''" :help="form.errors.equipo_id">
                <a-select
                    v-model:value="form.equipo_id"
                    :options="equipoOpciones"
                    placeholder="Selecciona el equipo"
                />
            </a-form-item>

            <a-form-item label="Descripción del problema" name="descripcion" :validate-status="form.errors.descripcion ? 'error' : ''" :help="form.errors.descripcion">
                <a-textarea v-model:value="form.descripcion" :rows="4" placeholder="¿Qué falla presenta el equipo?" />
            </a-form-item>

            <a-row :gutter="14">
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Prioridad sugerida" :help="form.errors.prioridad_id" :validate-status="form.errors.prioridad_id ? 'error' : ''">
                        <a-select
                            v-model:value="form.prioridad_id"
                            :options="prioridades.map((p) => ({ value: p.id, label: p.nombre }))"
                            allow-clear
                            placeholder="Sin definir"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Fecha requerida" :help="form.errors.fecha_requerida" :validate-status="form.errors.fecha_requerida ? 'error' : ''">
                        <a-input v-model:value="form.fecha_requerida" type="date" />
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
    </a-modal>
</template>
