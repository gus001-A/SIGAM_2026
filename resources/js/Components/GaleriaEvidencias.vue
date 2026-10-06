<script setup>
import { computed, ref, watch } from 'vue';
import { CameraOutlined, DownloadOutlined, FileDoneOutlined, LeftOutlined, RightOutlined } from '@ant-design/icons-vue';
import ModalFicha from '@/Components/ModalFicha.vue';

/**
 * Tira de miniaturas de las evidencias de un movimiento. Al tocar una abre un
 * visor con anterior/siguiente. Cada documento trae `url` (vista previa).
 */
const props = defineProps({
    documentos: { type: Array, default: () => [] },
});

const indice = ref(null);
const abierta = computed(() => indice.value !== null);
const actual = computed(() => (indice.value === null ? null : props.documentos[indice.value]));
const esImagen = (d) => /^image\//.test(d?.tipo_mime || '');
const total = computed(() => props.documentos.length);

const abrir = (i) => (indice.value = i);
const cerrar = () => (indice.value = null);
const mover = (paso) => {
    if (total.value < 2) return;
    indice.value = (indice.value + paso + total.value) % total.value;
};

watch(total, (t) => {
    if (indice.value !== null && indice.value >= t) cerrar();
});

const onTecla = (e) => {
    if (!abierta.value) return;
    if (e.key === 'ArrowLeft') mover(-1);
    if (e.key === 'ArrowRight') mover(1);
};
watch(abierta, (v) => {
    if (v) document.addEventListener('keydown', onTecla);
    else document.removeEventListener('keydown', onTecla);
});
</script>

<template>
    <div v-if="total" class="gal">
        <button
            v-for="(d, i) in documentos"
            :key="d.id"
            type="button"
            class="gal__item"
            :title="d.titulo || d.nombre_original"
            @click.stop="abrir(i)"
        >
            <img v-if="esImagen(d)" :src="d.url" :alt="d.nombre_original" loading="lazy" />
            <span v-else class="gal__pdf"><FileDoneOutlined /></span>
        </button>
        <span class="gal__cuenta"><CameraOutlined /> {{ total }}</span>

        <ModalFicha
            :show="abierta"
            :titulo="actual?.titulo || actual?.nombre_original || 'Evidencia'"
            :subtitulo="total > 1 ? `${indice + 1} de ${total}` : actual?.nombre_original || ''"
            :icono="CameraOutlined"
            color="#1f9e86"
            max-width="2xl"
            sin-padding
            @close="cerrar"
        >
            <div v-if="actual" class="gal__visor">
                <button v-if="total > 1" type="button" class="gal__nav gal__nav--izq" title="Anterior" @click="mover(-1)">
                    <LeftOutlined />
                </button>
                <img v-if="esImagen(actual)" :src="actual.url" :alt="actual.nombre_original" class="gal__img" />
                <iframe v-else :src="actual.url" class="gal__pdf-visor" title="Previsualización" />
                <button v-if="total > 1" type="button" class="gal__nav gal__nav--der" title="Siguiente" @click="mover(1)">
                    <RightOutlined />
                </button>
            </div>
            <template #footer>
                <a
                    v-if="actual"
                    :href="route('documentos.download', actual.id)"
                    target="_blank"
                    class="ant-btn ant-btn-default"
                >
                    <DownloadOutlined /> Descargar
                </a>
                <a v-if="actual" :href="actual.url" target="_blank" class="ant-btn ant-btn-primary">
                    Abrir en nueva pestaña
                </a>
            </template>
        </ModalFicha>
    </div>
</template>

<style scoped>
.gal {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}
.gal__item {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #dbe3ec;
    padding: 0;
    background: #f8fafc;
    cursor: pointer;
    transition: transform 0.14s ease, box-shadow 0.14s ease;
}
.gal__item:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px -8px rgba(15, 37, 71, 0.45);
}
.gal__item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.gal__pdf {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: #d64545;
    font-size: 20px;
}
.gal__cuenta {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 800;
    color: #0d6ca6;
    background: #e6f2fb;
    border-radius: 999px;
    padding: 2px 8px;
    margin-left: 2px;
}
.gal__visor {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0f2c4a;
    min-height: 240px;
}
.gal__img {
    display: block;
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
}
.gal__pdf-visor {
    width: 100%;
    height: 70vh;
    border: 0;
    background: #fff;
}
.gal__nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.9);
    color: #173a5f;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 16px -6px rgba(0, 0, 0, 0.5);
}
.gal__nav:hover {
    background: #fff;
}
.gal__nav--izq {
    left: 10px;
}
.gal__nav--der {
    right: 10px;
}
</style>
