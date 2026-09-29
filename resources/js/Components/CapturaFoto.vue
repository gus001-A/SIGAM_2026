<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import {
    CameraOutlined,
    LoadingOutlined,
    RedoOutlined,
    SwapOutlined,
    WarningOutlined,
} from '@ant-design/icons-vue';
import ModalFicha from '@/Components/ModalFicha.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    titulo: { type: String, default: 'Tomar foto' },
});

const emit = defineEmits(['close', 'capturada']);

const video = ref(null);
const canvas = ref(null);
const stream = ref(null);
const cargando = ref(false);
const error = ref(null);
const fotoPreview = ref(null);
const dispositivos = ref([]);
const dispositivoActualId = ref(null);

const detener = () => {
    if (stream.value) {
        stream.value.getTracks().forEach((t) => t.stop());
        stream.value = null;
    }
};

const iniciarCamara = async (deviceId) => {
    detener();
    error.value = null;
    cargando.value = true;
    try {
        const constraints = {
            video: deviceId
                ? { deviceId: { exact: deviceId } }
                : { facingMode: { ideal: 'environment' } },
            audio: false,
        };
        stream.value = await navigator.mediaDevices.getUserMedia(constraints);

        // El <video> solo existe en el DOM cuando `cargando` es false (ver
        // template): hay que apagar el spinner ANTES de esperar el siguiente
        // tick, si no `video.value` sigue siendo null y el stream nunca se
        // conecta — la cámara queda "prendida" pero el visor se ve en negro.
        cargando.value = false;
        await nextTick();
        if (video.value) {
            video.value.srcObject = stream.value;
            await video.value.play();
        }

        // Los labels de los dispositivos solo llegan una vez concedido el permiso.
        const todos = await navigator.mediaDevices.enumerateDevices();
        dispositivos.value = todos.filter((d) => d.kind === 'videoinput');
        const actual = stream.value.getVideoTracks()[0]?.getSettings()?.deviceId;
        dispositivoActualId.value = actual ?? dispositivos.value[0]?.deviceId ?? null;
    } catch (e) {
        if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
            error.value = 'Permiso de cámara denegado. Actívalo en la configuración del navegador e inténtalo de nuevo.';
        } else if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError') {
            error.value = 'No se encontró ninguna cámara en este dispositivo.';
        } else if (location.protocol !== 'https:' && !['localhost', '127.0.0.1'].includes(location.hostname)) {
            error.value = 'La cámara solo funciona en una conexión segura (HTTPS).';
        } else {
            error.value = 'No se pudo acceder a la cámara.';
        }
    } finally {
        cargando.value = false;
    }
};

const cambiarCamara = () => {
    if (dispositivos.value.length < 2) return;
    const idx = dispositivos.value.findIndex((d) => d.deviceId === dispositivoActualId.value);
    const siguiente = dispositivos.value[(idx + 1) % dispositivos.value.length];
    iniciarCamara(siguiente.deviceId);
};

const capturar = () => {
    const v = video.value;
    const c = canvas.value;
    if (!v || !c) return;
    c.width = v.videoWidth;
    c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    fotoPreview.value = c.toDataURL('image/jpeg', 0.92);
};

const repetir = () => {
    fotoPreview.value = null;
};

const usarFoto = () => {
    canvas.value.toBlob((blob) => {
        const archivo = new File([blob], `foto-${Date.now()}.jpg`, { type: 'image/jpeg' });
        emit('capturada', archivo);
        cerrar();
    }, 'image/jpeg', 0.92);
};

const cerrar = () => {
    detener();
    fotoPreview.value = null;
    error.value = null;
    emit('close');
};

watch(() => props.show, (val) => {
    if (val) iniciarCamara();
    else detener();
});

onBeforeUnmount(detener);
</script>

<template>
    <ModalFicha :show="show" :titulo="titulo" subtitulo="Explorador o cámara del dispositivo"
        :icono="CameraOutlined" color="#6b4bc9" max-width="sm" fondo="oscuro" @close="cerrar">
        <div class="cf">
            <div v-if="error" class="cf__estado">
                <WarningOutlined class="cf__estado-ic cf__estado-ic--error" />
                <p>{{ error }}</p>
                <a-button size="small" @click="iniciarCamara()">Reintentar</a-button>
            </div>

            <div v-else-if="cargando" class="cf__estado">
                <LoadingOutlined class="cf__estado-ic" spin />
                <p>Activando cámara…</p>
            </div>

            <template v-else>
                <div class="cf__visor" v-show="!fotoPreview">
                    <video ref="video" autoplay playsinline muted class="cf__video"></video>
                    <button v-if="dispositivos.length > 1" type="button" class="cf__flip" title="Cambiar cámara"
                        @click="cambiarCamara">
                        <SwapOutlined />
                    </button>
                </div>

                <div v-if="fotoPreview" class="cf__visor">
                    <img :src="fotoPreview" alt="Foto capturada" class="cf__foto" />
                </div>
            </template>

            <canvas ref="canvas" style="display: none"></canvas>
        </div>

        <template #footer>
            <template v-if="!error && !cargando">
                <template v-if="!fotoPreview">
                    <a-button @click="cerrar">Cancelar</a-button>
                    <a-button type="primary" @click="capturar">
                        <template #icon><CameraOutlined /></template>
                        Capturar
                    </a-button>
                </template>
                <template v-else>
                    <a-button @click="repetir">
                        <template #icon><RedoOutlined /></template>
                        Repetir
                    </a-button>
                    <a-button type="primary" @click="usarFoto">Usar esta foto</a-button>
                </template>
            </template>
        </template>
    </ModalFicha>
</template>

<style scoped>
.cf {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.cf__visor {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 3;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
}

.cf__video,
.cf__foto {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.cf__flip {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(15, 23, 42, 0.55);
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.16s ease, transform 0.16s ease;
}

.cf__flip:hover {
    background: rgba(15, 23, 42, 0.8);
    transform: scale(1.06);
}

.cf__estado {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 40px 16px;
    text-align: center;
    color: rgba(255, 255, 255, 0.85);
}

.cf__estado-ic {
    font-size: 30px;
    color: rgba(255, 255, 255, 0.7);
}

.cf__estado-ic--error {
    color: #f5a3a3;
}

.cf__estado p {
    margin: 0;
    font-size: 13px;
    max-width: 320px;
}
</style>
