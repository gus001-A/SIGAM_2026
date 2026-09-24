<script setup>
import { nextTick, onBeforeUnmount, ref } from 'vue';
import { BrowserMultiFormatReader } from '@zxing/browser';
import { message } from 'ant-design-vue';

/**
 * Modal con lector de código de barras/QR vía cámara (ZXing). Uso:
 * `<LectorCodigoBarras ref="lector" @detectado="v => form.campo = v" />`
 * + `lector.abrir()` desde un botón — mismo patrón de `ref.abrir()` que
 * `ConfirmarDialog.vue`.
 */
const emit = defineEmits(['detectado']);

const abierto = ref(false);
const cargando = ref(true);
const errorCamara = ref('');
const video = ref(null);
let lector = null;
let controles = null;

const detener = () => {
    controles?.stop();
    controles = null;
    lector = null;
};

const abrir = async () => {
    abierto.value = true;
    errorCamara.value = '';
    cargando.value = true;
    await nextTick();

    lector = new BrowserMultiFormatReader();
    try {
        controles = await lector.decodeFromVideoDevice(undefined, video.value, (resultado) => {
            if (resultado) {
                const texto = resultado.getText();
                emit('detectado', texto);
                message.success(`Código detectado: ${texto}`);
                cerrar();
            }
        });
        cargando.value = false;
    } catch (e) {
        cargando.value = false;
        errorCamara.value = 'No se pudo acceder a la cámara. Revisa que el navegador tenga permiso de cámara para este sitio.';
    }
};

const cerrar = () => {
    detener();
    abierto.value = false;
};

onBeforeUnmount(detener);

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        title="Escanear código de barras"
        :footer="null"
        width="420px"
        @cancel="cerrar"
    >
        <div class="lector">
            <div class="lector__marco">
                <video ref="video" class="lector__video" autoplay muted playsinline />
                <a-spin v-if="cargando" class="lector__cargando" />
            </div>
            <a-alert v-if="errorCamara" type="error" :message="errorCamara" show-icon class="mt-3" />
            <p class="lector__ayuda">Apunta la cámara al código de barras o QR del equipo — se llena solo al detectarlo.</p>
        </div>
    </a-modal>
</template>

<style scoped>
.lector__marco {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 3;
    background: #0f172a;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.lector__video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.lector__cargando {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.55);
}
.lector__ayuda {
    margin: 10px 0 0;
    font-size: 12.5px;
    color: var(--sigam-tenue);
    text-align: center;
}
</style>
