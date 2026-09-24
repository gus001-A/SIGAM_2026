<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { InboxOutlined } from '@ant-design/icons-vue';

const abierto = ref(false);
const equipo = ref(null);
const fileList = ref([]);

const form = useForm({ motivo: '', evidencia: null, _method: 'delete' });

const abrir = (e) => {
    equipo.value = e;
    form.clearErrors();
    form.motivo = '';
    form.evidencia = null;
    form._method = 'delete';
    fileList.value = [];
    abierto.value = true;
};

const antesDeSubir = (file) => {
    fileList.value = [file];
    form.evidencia = file;
    return false;
};

const quitar = () => {
    fileList.value = [];
    form.evidencia = null;
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
        title="Dar de baja el equipo"
        ok-text="Dar de baja"
        cancel-text="Cancelar"
        :confirm-loading="form.processing"
        :ok-button-props="{ danger: true }"
        @ok="enviar"
    >
        <p v-if="equipo" class="opacity-70 mb-3">
            <strong>{{ equipo.codigo_activo }}</strong> — {{ equipo.descripcion }}. Se conservará en el
            historial y podrás consultarlo o reactivarlo después.
        </p>

        <a-form layout="vertical">
            <a-form-item
                label="Motivo de la baja"
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
                <a-upload-dragger
                    :file-list="fileList"
                    :max-count="1"
                    :before-upload="antesDeSubir"
                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                    @remove="quitar"
                >
                    <p class="ant-upload-drag-icon"><InboxOutlined /></p>
                    <p class="ant-upload-text">Foto o reporte del estado del equipo</p>
                    <p class="ant-upload-hint">PDF, JPG, PNG o WEBP · máx. 20 MB</p>
                </a-upload-dragger>
            </a-form-item>
        </a-form>
    </a-modal>
</template>
