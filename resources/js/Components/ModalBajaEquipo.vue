<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import CampoEvidencia from '@/Components/CampoEvidencia.vue';

const abierto = ref(false);
const equipo = ref(null);

const form = useForm({ motivo: '', evidencia: null, _method: 'delete' });

const abrir = (e) => {
    equipo.value = e;
    form.clearErrors();
    form.motivo = '';
    form.evidencia = null;
    form._method = 'delete';
    abierto.value = true;
};

const enviar = () => {
    form.clearErrors();
    if (!form.motivo.trim()) {
        form.setError('motivo', 'Indica el motivo de la baja.');
        return;
    }
    form.post(route('equipos.destroy', equipo.value.id), {
        forceFormData: true,
        onSuccess: () => { abierto.value = false; },
    });
};

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        title="Marcar el equipo como fuera de servicio"
        ok-text="Marcar fuera de servicio"
        cancel-text="Cancelar"
        :confirm-loading="form.processing"
        :ok-button-props="{ danger: true }"
        @ok="enviar"
    >
        <p v-if="equipo" class="opacity-70 mb-3">
            <strong>{{ equipo.codigo_activo }}</strong> — {{ equipo.descripcion }}. Seguirá visible en el
            listado con estado "Fuera de servicio"; puedes reactivarlo editándolo cuando quieras.
        </p>

        <a-form layout="vertical">
            <a-form-item
                label="Motivo"
                required
                :validate-status="form.errors.motivo ? 'error' : ''"
                :help="form.errors.motivo"
            >
                <a-textarea
                    v-model:value="form.motivo"
                    :rows="3"
                    placeholder="Ej. equipo obsoleto, dañado sin reparación posible, robado, reemplazado, etc."
                />
            </a-form-item>

            <a-form-item
                label="Evidencia (opcional)"
                :validate-status="form.errors.evidencia ? 'error' : ''"
                :help="form.errors.evidencia"
            >
                <CampoEvidencia v-model="form.evidencia" texto="Foto o reporte del estado del equipo" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>
