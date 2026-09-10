<script setup>
import { ref } from 'vue';
import { FilePdfOutlined, PictureOutlined, PrinterOutlined } from '@ant-design/icons-vue';

/**
 * Modal con el código QR del activo. Carga los datos vía JSON (sin recargar)
 * y ofrece descargar el QR como PDF o imagen JPG, o imprimir la etiqueta.
 */
const abierto = ref(false);
const cargando = ref(false);
const error = ref('');
const equipoId = ref(null);
const datos = ref({ codigo_activo: '', descripcion: '', url: '', png: '' });

const abrir = async (id) => {
    equipoId.value = id;
    error.value = '';
    datos.value = { codigo_activo: '', descripcion: '', url: '', png: '' };
    abierto.value = true;
    cargando.value = true;
    try {
        const { data } = await window.axios.get(route('equipos.qr_data', id));
        datos.value = data;
    } catch (e) {
        error.value = 'No se pudo generar el código QR.';
    } finally {
        cargando.value = false;
    }
};

const descargarPdf = () => window.open(route('equipos.qr_pdf', equipoId.value), '_blank');
const descargarImagen = () => window.open(route('equipos.qr_img', equipoId.value), '_blank');

const imprimir = () => {
    const v = window.open('', '_blank', 'width=480,height=640');
    if (!v) return;
    v.document.write(`<!doctype html><html><head><title>QR ${datos.value.codigo_activo}</title>
        <style>
            body{font-family:system-ui,sans-serif;text-align:center;color:#173a5f;padding:28px}
            .marca{font-weight:800;letter-spacing:2px}
            .sub{font-size:10px;color:#6b7a8b;text-transform:uppercase;letter-spacing:1px;margin-bottom:14px}
            .qr img{width:240px;height:240px}
            .codigo{font-size:18px;font-weight:700;margin-top:10px}
            .desc{font-size:12px;color:#42566b}
            .url{font-size:8px;color:#9aa7b4;word-break:break-all;margin-top:6px}
        </style></head><body>
        <div class="marca">SIGAM</div><div class="sub">Identificación de activo</div>
        <div class="qr"><img src="${datos.value.png}" alt="QR"></div>
        <div class="codigo">${datos.value.codigo_activo}</div>
        <div class="desc">${datos.value.descripcion ?? ''}</div>
        <div class="url">${datos.value.url}</div>
        <script>window.onload=function(){window.print();setTimeout(function(){window.close()},300)}<\/script>
        </body></html>`);
    v.document.close();
};

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        :footer="null"
        :width="400"
        centered
        wrap-class-name="modal-qr"
        title="Código QR del activo"
    >
        <a-spin :spinning="cargando">
            <a-alert v-if="error" type="error" :message="error" show-icon class="mb-3" />

            <div v-else class="mq">
                <div class="mq__marca">SIGAM</div>
                <div class="mq__sub">Identificación de activo</div>
                <div class="mq__barra" />

                <div class="mq__qr">
                    <img v-if="datos.png" :src="datos.png" alt="Código QR" />
                </div>

                <div class="mq__codigo">{{ datos.codigo_activo || '—' }}</div>
                <div class="mq__desc">{{ datos.descripcion }}</div>
                <div class="mq__url">{{ datos.url }}</div>
            </div>
        </a-spin>

        <div class="mq__acciones">
            <a-button :disabled="cargando || !!error" @click="imprimir">
                <template #icon><PrinterOutlined /></template>
                Imprimir
            </a-button>
            <a-button :disabled="cargando || !!error" @click="descargarImagen">
                <template #icon><PictureOutlined /></template>
                Imagen JPG
            </a-button>
            <a-button type="primary" :disabled="cargando || !!error" @click="descargarPdf">
                <template #icon><FilePdfOutlined /></template>
                PDF
            </a-button>
        </div>
    </a-modal>
</template>

<style>
.modal-qr .ant-modal-body {
    padding-bottom: 0;
}
</style>

<style scoped>
.mq {
    text-align: center;
    border: 1px solid var(--sigam-borde);
    border-radius: 14px;
    padding: 20px 16px 18px;
    background: linear-gradient(180deg, #fbfdfe 0%, #fff 40%);
}
.mq__marca {
    font-weight: 800;
    letter-spacing: 3px;
    color: var(--sigam-navy);
    font-size: 15px;
}
.mq__sub {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--sigam-tenue);
}
.mq__barra {
    width: 46px;
    height: 4px;
    border-radius: 3px;
    background: var(--sigam-grad);
    margin: 8px auto 14px;
}
.mq__qr {
    width: 200px;
    height: 200px;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.mq__qr img {
    width: 200px;
    height: 200px;
    image-rendering: pixelated;
}
.mq__codigo {
    font-size: 17px;
    font-weight: 800;
    color: var(--sigam-navy);
    letter-spacing: -0.01em;
}
.mq__desc {
    font-size: 12.5px;
    color: var(--sigam-tenue);
    margin-top: 2px;
}
.mq__url {
    font-size: 8px;
    color: #9aa7b4;
    word-break: break-all;
    margin-top: 8px;
}
.mq__acciones {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin: 18px -24px 0;
    padding: 13px 20px;
    border-top: 1px solid var(--sigam-borde-suave);
    background: #fbfcfe;
}
</style>
