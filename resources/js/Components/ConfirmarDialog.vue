<script setup>
import { computed, ref } from 'vue';
import { ExclamationCircleFilled, QuestionCircleFilled } from '@ant-design/icons-vue';

const abierto = ref(false);
const procesando = ref(false);
const opciones = ref({
    titulo: '¿Confirmar acción?',
    mensaje: '',
    confirmar: 'Confirmar',
    cancelar: 'Cancelar',
    peligro: false,
});

let resolver = null;

/** Abre el diálogo y resuelve `true`/`false` según la respuesta. */
const abrir = (config = {}) => {
    opciones.value = {
        titulo: '¿Confirmar acción?',
        mensaje: '',
        confirmar: 'Confirmar',
        cancelar: 'Cancelar',
        ...config,
        peligro: config.peligro ?? config.color === 'error',
    };
    procesando.value = false;
    abierto.value = true;
    return new Promise((res) => (resolver = res));
};

const aceptar = () => {
    abierto.value = false;
    resolver?.(true);
};

const cancelar = () => {
    abierto.value = false;
    resolver?.(false);
};

const cerrar = () => {
    abierto.value = false;
    procesando.value = false;
};

defineExpose({ abrir, cerrar });

const icono = computed(() =>
    opciones.value.peligro ? ExclamationCircleFilled : QuestionCircleFilled,
);
</script>

<template>
    <a-modal
        v-model:open="abierto"
        :footer="null"
        :closable="false"
        :mask-closable="!procesando"
        :width="420"
        centered
        wrap-class-name="confirmar-dialog"
        @cancel="cancelar"
    >
        <div class="cd">
            <div class="cd__icono" :class="opciones.peligro ? 'cd__icono--peligro' : 'cd__icono--info'">
                <component :is="icono" />
            </div>
            <div class="cd__cuerpo">
                <h3 class="cd__titulo">{{ opciones.titulo }}</h3>
                <p v-if="opciones.mensaje" class="cd__mensaje">{{ opciones.mensaje }}</p>
            </div>
        </div>

        <div class="cd__acciones">
            <a-button :disabled="procesando" @click="cancelar">{{ opciones.cancelar }}</a-button>
            <a-button
                type="primary"
                :loading="procesando"
                class="cd__ok"
                :class="{ 'cd__ok--peligro': opciones.peligro }"
                @click="aceptar"
            >
                {{ opciones.confirmar }}
            </a-button>
        </div>
    </a-modal>
</template>

<style>
.confirmar-dialog .ant-modal-content {
    padding: 0;
}
.confirmar-dialog .ant-modal-body {
    padding: 22px 22px 0;
}
/* Botón "eliminar" en rojo plano y sólido */
.confirmar-dialog .cd__ok--peligro.ant-btn-primary,
.confirmar-dialog .cd__ok--peligro.ant-btn-primary:not(:disabled):hover,
.confirmar-dialog .cd__ok--peligro.ant-btn-primary:not(:disabled):focus {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #fff !important;
    box-shadow: 0 6px 16px -6px rgba(220, 38, 38, 0.5) !important;
}
.confirmar-dialog .cd__ok--peligro.ant-btn-primary:not(:disabled):hover {
    background: #b91c1c !important;
    border-color: #b91c1c !important;
}
</style>

<style scoped>
.cd {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}
.cd__icono {
    flex: none;
    width: 44px;
    height: 44px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}
.cd__icono--peligro {
    background: #fdeaea;
    color: #d64545;
}
.cd__icono--info {
    background: var(--sigam-teal-050);
    color: var(--sigam-teal-700);
}
.cd__titulo {
    margin: 3px 0 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--sigam-navy);
    line-height: 1.35;
}
.cd__mensaje {
    margin: 7px 0 0;
    font-size: 13px;
    line-height: 1.55;
    color: var(--sigam-tenue);
}
.cd__acciones {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin: 22px -22px 0;
    padding: 14px 20px;
    border-top: 1px solid var(--sigam-borde-suave);
    background: #fbfcfe;
}
</style>
