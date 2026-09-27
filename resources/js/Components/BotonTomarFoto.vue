<script setup>
import { ref } from 'vue';
import { CameraOutlined } from '@ant-design/icons-vue';
import CapturaFoto from '@/Components/CapturaFoto.vue';

defineProps({
    texto: { type: String, default: 'Tomar foto' },
    size: { type: String, default: 'small' },
    block: { type: Boolean, default: false },
    titulo: { type: String, default: 'Tomar foto' },
});

const emit = defineEmits(['capturada']);

const abierto = ref(false);

const onCapturada = (archivo) => {
    emit('capturada', archivo);
    abierto.value = false;
};
</script>

<template>
    <div>
        <a-button :size="size" :block="block" @click="abierto = true">
            <template #icon><CameraOutlined /></template>
            {{ texto }}
        </a-button>

        <CapturaFoto :show="abierto" :titulo="titulo" @close="abierto = false" @capturada="onCapturada" />
    </div>
</template>
