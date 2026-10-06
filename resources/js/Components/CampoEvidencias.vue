<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { DeleteOutlined, FileDoneOutlined, InboxOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import BotonTomarFoto from '@/Components/BotonTomarFoto.vue';

/**
 * Varias fotos/archivos de evidencia para un avance (máximo `maximo`).
 * `modelValue` es un arreglo de File; se emite un arreglo nuevo en cada cambio.
 */
const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    maximo: { type: Number, default: 10 },
    soloImagenes: { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue']);

/* Copia local: al elegir varios archivos de golpe, cada `before-upload` se
   dispara antes de que el padre actualice `modelValue`. */
const lista = ref([...props.modelValue]);
watch(
    () => props.modelValue,
    (v) => (lista.value = [...v]),
);

const accept = computed(() =>
    props.soloImagenes ? '.jpg,.jpeg,.png,.webp,image/*' : '.pdf,.doc,.docx,.jpg,.jpeg,.png,.webp',
);
const hint = computed(() =>
    `${props.soloImagenes ? 'JPG, PNG o WEBP' : 'PDF, Word, JPG, PNG o WEBP'} · máx. 20 MB c/u · hasta ${props.maximo} archivos`,
);
const lleno = computed(() => lista.value.length >= props.maximo);

const esImagen = (file) => !!file?.type?.startsWith('image/');

/* Vistas previas: se crean y se liberan al cambiar la lista */
const vistas = ref([]);
const liberar = () => {
    vistas.value.forEach((v) => v.url && URL.revokeObjectURL(v.url));
    vistas.value = [];
};
watch(
    lista,
    (items) => {
        liberar();
        vistas.value = items.map((file) => ({ file, url: esImagen(file) ? URL.createObjectURL(file) : null }));
    },
    { immediate: true },
);
onBeforeUnmount(liberar);

const agregar = (archivo) => {
    if (props.soloImagenes && !esImagen(archivo)) {
        message.error('Solo se permiten imágenes (JPG, PNG o WEBP).');
        return false;
    }
    if (lista.value.length >= props.maximo) {
        message.warning(`Máximo ${props.maximo} archivos por avance.`);
        return false;
    }
    lista.value = [...lista.value, archivo];
    emit('update:modelValue', lista.value);
    return false;
};

const quitar = (indice) => {
    lista.value = lista.value.filter((_, i) => i !== indice);
    emit('update:modelValue', lista.value);
};
</script>

<template>
    <div class="ce">
        <a-upload-dragger
            v-if="!lleno"
            :file-list="[]"
            :multiple="true"
            :show-upload-list="false"
            :before-upload="agregar"
            :accept="accept"
            class="ce__drop"
        >
            <p class="ant-upload-drag-icon">
                <InboxOutlined />
            </p>
            <p class="ant-upload-text">Haz clic o arrastra archivos</p>
            <p class="ant-upload-hint">{{ hint }}</p>
        </a-upload-dragger>

        <BotonTomarFoto
            v-if="!lleno"
            block
            class="mt-2"
            texto="O tomar foto con la cámara"
            titulo="Evidencia"
            @capturada="agregar"
        />

        <div v-if="vistas.length" class="ce__cabecera">
            <span class="ce__contador">{{ vistas.length }} de {{ maximo }} archivos</span>
        </div>

        <div v-if="vistas.length" class="ce__grid">
            <div v-for="(v, i) in vistas" :key="i" class="ce__tile">
                <img v-if="v.url" :src="v.url" :alt="v.file.name" class="ce__img" />
                <div v-else class="ce__archivo">
                    <FileDoneOutlined />
                    <span>{{ v.file.name }}</span>
                </div>
                <button type="button" class="ce__x" title="Quitar" @click="quitar(i)">
                    <DeleteOutlined />
                </button>
                <span class="ce__nombre" :title="v.file.name">{{ v.file.name }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ce {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.ce__cabecera {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}
.ce__contador {
    font-size: 11.5px;
    font-weight: 800;
    color: #0d6ca6;
    background: #e6f2fb;
    border-radius: 999px;
    padding: 2px 10px;
}
.ce__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
    gap: 8px;
}
.ce__tile {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ce__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.ce__archivo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    color: #64748b;
    font-size: 22px;
    padding: 6px;
    text-align: center;
}
.ce__archivo span {
    font-size: 10px;
    word-break: break-all;
}
.ce__x {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.7);
    color: #fff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}
.ce__x:hover {
    background: #d64545;
}
.ce__nombre {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 2px 4px;
    font-size: 9.5px;
    color: #fff;
    background: linear-gradient(transparent, rgba(15, 23, 42, 0.75));
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
