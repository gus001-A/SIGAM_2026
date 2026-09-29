<script setup>
import { ref, watch } from 'vue';
import { DeleteOutlined, FileDoneOutlined, InboxOutlined } from '@ant-design/icons-vue';
import BotonTomarFoto from '@/Components/BotonTomarFoto.vue';

const props = defineProps({
    modelValue: { type: [Object, File], default: null },
    accept: { type: String, default: '.pdf,.jpg,.jpeg,.png,.webp' },
    hint: { type: String, default: 'PDF, JPG, PNG o WEBP · máx. 20 MB' },
    texto: { type: String, default: 'Haz clic o arrastra un archivo' },
});
const emit = defineEmits(['update:modelValue']);

const fileList = ref([]);
const previewUrl = ref(null);

const limpiarPreview = () => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
};

const antesDeSubir = (file) => {
    fileList.value = [file];
    emit('update:modelValue', file);
    limpiarPreview();
    previewUrl.value = file.type?.startsWith('image/') ? URL.createObjectURL(file) : null;
    return false;
};

const quitar = () => {
    fileList.value = [];
    emit('update:modelValue', null);
    limpiarPreview();
};

watch(() => props.modelValue, (v) => {
    if (!v) {
        fileList.value = [];
        limpiarPreview();
    }
});
</script>

<template>
    <div>
        <a-upload-dragger v-if="!modelValue" :file-list="fileList" :max-count="1" :before-upload="antesDeSubir"
            :accept="accept" @remove="quitar">
            <p class="ant-upload-drag-icon">
                <InboxOutlined />
            </p>
            <p class="ant-upload-text">{{ texto }}</p>
            <p class="ant-upload-hint">{{ hint }}</p>
        </a-upload-dragger>

        <div v-else-if="previewUrl" class="ce-preview">
            <img :src="previewUrl" alt="Vista previa" />
            <button type="button" class="ce-preview__x" title="Quitar" @click="quitar">
                <DeleteOutlined />
            </button>
        </div>

        <div v-else class="ce-archivo">
            <FileDoneOutlined />
            <span>{{ modelValue?.name }}</span>
            <button type="button" class="ce-archivo__x" title="Quitar" @click="quitar">
                <DeleteOutlined />
            </button>
        </div>

        <BotonTomarFoto v-if="!modelValue" block class="mt-2" texto="O tomar foto con la cámara" titulo="Evidencia"
            @capturada="antesDeSubir" />
    </div>
</template>

<style scoped>
.ce-preview {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--sigam-borde-suave);
    background: #f8fafc;
    max-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ce-preview img {
    display: block;
    width: 100%;
    max-height: 220px;
    object-fit: contain;
}
.ce-preview__x {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.65);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.14s ease;
}
.ce-preview__x:hover { background: #d64545; }

.ce-archivo {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 10px;
    background: var(--sigam-navy-050);
    border: 1px solid var(--sigam-navy-100);
    color: var(--sigam-navy);
    font-size: 12.5px;
    font-weight: 600;
}
.ce-archivo span { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ce-archivo__x {
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: #c23b3b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.14s ease;
}
.ce-archivo__x:hover { background: #fbeaea; }
</style>
