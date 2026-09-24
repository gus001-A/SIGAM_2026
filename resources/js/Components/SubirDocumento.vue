<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    CloseCircleFilled,
    FileExcelOutlined,
    FilePdfOutlined,
    FileWordOutlined,
    FileUnknownOutlined,
    InboxOutlined,
} from '@ant-design/icons-vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';

/**
 * Diálogo para adjuntar un documento a cualquier entidad (relación polimórfica).
 * Especificación §14.
 */
const props = defineProps({
    relacionableTipo: { type: String, required: true },
    relacionableId: { type: [Number, String], required: true },
    roles: { type: Array, default: () => [] },
    categorias: {
        type: Array,
        default: () => ['manual', 'factura', 'garantia', 'certificado', 'foto', 'evidencia', 'otro'],
    },
});

const abierto = ref(false);
const fileList = ref([]);
const previewUrl = ref(null);

const form = useForm({
    archivo: null,
    relacionable_tipo: props.relacionableTipo,
    relacionable_id: props.relacionableId,
    titulo: '',
    categoria: undefined,
    rol: undefined,
    visibilidad: 'privado',
    vence_at: '',
});

const abrir = () => {
    form.reset();
    form.relacionable_tipo = props.relacionableTipo;
    form.relacionable_id = props.relacionableId;
    fileList.value = [];
    previewUrl.value = null;
    abierto.value = true;
};

const antesDeSubir = (file) => {
    fileList.value = [file];
    form.archivo = file;
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = /^image\//.test(file.type) ? URL.createObjectURL(file) : null;
    if (!form.titulo) form.titulo = file.name.replace(/\.[^.]+$/, '');
    return false; // no subir automáticamente
};

const quitarArchivo = () => {
    fileList.value = [];
    form.archivo = null;
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
};

const iconoArchivo = computed(() => {
    const t = form.archivo?.type || '';
    if (t.includes('pdf')) return FilePdfOutlined;
    if (t.includes('word') || t.includes('document')) return FileWordOutlined;
    if (t.includes('sheet') || t.includes('excel')) return FileExcelOutlined;
    return FileUnknownOutlined;
});
const pesoArchivo = computed(() =>
    form.archivo ? `${(form.archivo.size / 1024).toFixed(0)} KB` : '',
);

const enviar = () => {
    if (!form.archivo) {
        form.setError('archivo', 'Selecciona un archivo.');
        return;
    }
    form.post(route('documentos.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            quitarArchivo();
            abierto.value = false;
        },
    });
};

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        title="Adjuntar documento"
        :width="520"
        :confirm-loading="form.processing"
        ok-text="Subir documento"
        cancel-text="Cancelar"
        centered
        @ok="enviar"
    >
        <a-form layout="vertical" class="pt-1">
            <a-form-item
                :validate-status="form.errors.archivo ? 'error' : undefined"
                :help="form.errors.archivo || undefined"
            >
                <a-upload-dragger
                    v-if="!form.archivo"
                    :file-list="fileList"
                    :max-count="1"
                    :before-upload="antesDeSubir"
                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                    @remove="quitarArchivo"
                >
                    <p class="ant-upload-drag-icon"><InboxOutlined /></p>
                    <p class="ant-upload-text">Haz clic o arrastra un archivo</p>
                    <p class="ant-upload-hint">PDF, imágenes u Office · máx. 20 MB</p>
                </a-upload-dragger>

                <div v-else class="sd-prev">
                    <div class="sd-prev__vis">
                        <img v-if="previewUrl" :src="previewUrl" alt="Vista previa" />
                        <component :is="iconoArchivo" v-else />
                    </div>
                    <div class="sd-prev__info">
                        <div class="sd-prev__nombre">{{ form.archivo.name }}</div>
                        <div class="sd-prev__peso">{{ pesoArchivo }}</div>
                    </div>
                    <button type="button" class="sd-prev__quitar" @click="quitarArchivo">
                        <CloseCircleFilled />
                    </button>
                </div>
            </a-form-item>

            <a-form-item label="Título">
                <a-input v-model:value="form.titulo" placeholder="Nombre descriptivo" />
            </a-form-item>

            <a-row :gutter="12">
                <a-col :span="12">
                    <a-form-item label="Categoría">
                        <a-select v-model:value="form.categoria" :options="categorias.map((c) => ({ value: c, label: c }))" allow-clear placeholder="Sin categoría" />
                    </a-form-item>
                </a-col>
                <a-col v-if="roles.length" :span="12">
                    <a-form-item label="Rol en el expediente">
                        <a-select v-model:value="form.rol" :options="roles.map((r) => ({ value: r, label: r }))" allow-clear placeholder="Sin rol" />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="12">
                <a-col :span="12">
                    <a-form-item label="Visibilidad">
                        <a-select
                            v-model:value="form.visibilidad"
                            :options="[
                                { value: 'privado', label: 'Privado' },
                                { value: 'publico', label: 'Público' },
                            ]"
                        />
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Vence (opcional)">
                        <CampoFechaHora v-model="form.vence_at" solo-fecha />
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
    </a-modal>
</template>

<style scoped>
.sd-prev {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--sigam-borde);
    border-radius: 12px;
    background: var(--sigam-navy-050);
}
.sd-prev__vis {
    width: 52px;
    height: 52px;
    flex: none;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--sigam-navy);
}
.sd-prev__vis img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.sd-prev__info {
    flex: 1;
    min-width: 0;
}
.sd-prev__nombre {
    font-weight: 600;
    font-size: 13px;
    color: var(--sigam-texto);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sd-prev__peso {
    font-size: 11.5px;
    color: var(--sigam-tenue);
}
.sd-prev__quitar {
    border: 0;
    background: transparent;
    color: #94a3b8;
    font-size: 18px;
    cursor: pointer;
    transition: color 0.14s ease;
}
.sd-prev__quitar:hover {
    color: #d64545;
}
</style>
