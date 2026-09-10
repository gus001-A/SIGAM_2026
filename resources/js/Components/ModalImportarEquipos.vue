<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import {
    CheckCircleFilled,
    CloudUploadOutlined,
    FileExcelOutlined,
    InboxOutlined,
    InfoCircleOutlined,
    WarningFilled,
} from '@ant-design/icons-vue';

const abierto = ref(false);
const fileList = ref([]);
const resultado = ref(null);
const page = usePage();

const form = useForm({ archivo: null });

const abrir = () => {
    form.reset();
    form.clearErrors();
    fileList.value = [];
    resultado.value = null;
    abierto.value = true;
};

const antesDeSubir = (file) => {
    fileList.value = [file];
    form.archivo = file;
    return false;
};

const quitar = () => {
    fileList.value = [];
    form.archivo = null;
};

const enviar = () => {
    if (!form.archivo) {
        form.setError('archivo', 'Selecciona un archivo de Excel (.xlsx).');
        return;
    }
    resultado.value = null;
    form.post(route('equipos.importar'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            fileList.value = [];
            form.archivo = null;
        },
    });
};

watch(
    () => page.props.flash?.importacion,
    (r) => {
        if (r && abierto.value) resultado.value = r;
    },
);

const cerrar = () => {
    abierto.value = false;
    if (resultado.value?.creados > 0) router.reload({ only: ['equipos'] });
};

const hayErrores = computed(() => (resultado.value?.errores?.length ?? 0) > 0);

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        :width="560"
        :footer="null"
        centered
        wrap-class-name="modal-importar"
        title="Cargar equipos desde Excel"
        @cancel="cerrar"
    >
        <!-- Resultado -->
        <div v-if="resultado" class="mi-res">
            <div class="mi-res__head" :class="resultado.creados > 0 ? 'is-ok' : 'is-warn'">
                <component :is="resultado.creados > 0 ? CheckCircleFilled : WarningFilled" />
                <div>
                    <strong>{{ resultado.creados }}</strong> equipo(s) importado(s)
                    <template v-if="hayErrores">
                        · <strong>{{ resultado.errores.length }}</strong> fila(s) con errores
                    </template>
                </div>
            </div>

            <div v-if="hayErrores" class="mi-res__errores">
                <div v-for="e in resultado.errores" :key="e.fila" class="mi-res__fila">
                    <a-tag color="error">Fila {{ e.fila }}</a-tag>
                    <ul>
                        <li v-for="(m, i) in e.mensajes" :key="i">{{ m }}</li>
                    </ul>
                </div>
            </div>

            <div class="mi-acciones">
                <a-button @click="resultado = null">Cargar otro archivo</a-button>
                <a-button type="primary" @click="cerrar">Listo</a-button>
            </div>
        </div>

        <!-- Formulario -->
        <template v-else>
            <a-alert type="info" show-icon class="mb-3">
                <template #message>
                    Descarga la plantilla de <strong>Excel</strong>, llena una fila por equipo
                    (los encabezados van en la primera fila) y súbela aquí.
                </template>
            </a-alert>

            <div class="mb-3">
                <a-button type="primary" ghost :href="route('equipos.importar.plantilla')" target="_blank">
                    <template #icon><FileExcelOutlined /></template>
                    Descargar plantilla (Excel)
                </a-button>
            </div>

            <a-upload-dragger
                :file-list="fileList"
                :max-count="1"
                :before-upload="antesDeSubir"
                accept=".xlsx,.xls"
                @remove="quitar"
            >
                <p class="ant-upload-drag-icon"><InboxOutlined /></p>
                <p class="ant-upload-text">Haz clic o arrastra el archivo de Excel</p>
                <p class="ant-upload-hint">Solo .xlsx o .xls · máx. 5 MB</p>
            </a-upload-dragger>
            <div v-if="form.errors.archivo" class="mi-error">{{ form.errors.archivo }}</div>

            <div class="mi-nota">
                <InfoCircleOutlined />
                <span>
                    Si tienes <strong>muchos equipos</strong>, puedes dividirlos en varios archivos
                    de Excel y subirlos uno por uno; cada carga se suma a la anterior.
                </span>
            </div>

            <div class="mi-acciones">
                <a-button :disabled="form.processing" @click="cerrar">Cancelar</a-button>
                <a-button type="primary" :loading="form.processing" @click="enviar">
                    <template #icon><CloudUploadOutlined /></template>
                    Importar
                </a-button>
            </div>
        </template>
    </a-modal>
</template>

<style>
.modal-importar .ant-modal-body {
    padding-bottom: 0;
}
</style>

<style scoped>
.mi-error {
    color: #d64545;
    font-size: 12.5px;
    margin-top: 6px;
}
.mi-nota {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin-top: 12px;
    padding: 10px 12px;
    border-radius: 11px;
    background: var(--sigam-teal-050);
    color: var(--sigam-teal-700);
    font-size: 12.5px;
    line-height: 1.5;
}
.mi-nota .anticon {
    margin-top: 2px;
    flex: none;
}
.mi-acciones {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin: 18px -24px 0;
    padding: 13px 20px;
    border-top: 1px solid var(--sigam-borde-suave);
    background: #fbfcfe;
}
.mi-res__head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 11px;
    font-size: 13.5px;
}
.mi-res__head.is-ok {
    background: var(--sigam-teal-050);
    color: var(--sigam-teal-700);
}
.mi-res__head.is-warn {
    background: #fdf3e6;
    color: #a86717;
}
.mi-res__head .anticon {
    font-size: 20px;
}
.mi-res__errores {
    margin-top: 12px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 11px;
}
.mi-res__fila {
    display: flex;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.mi-res__fila:last-child {
    border-bottom: none;
}
.mi-res__fila ul {
    margin: 0;
    padding-left: 16px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}
</style>
