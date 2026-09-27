<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import {
    CheckCircleFilled,
    CloseCircleFilled,
    CloudUploadOutlined,
    FileExcelOutlined,
    InfoCircleOutlined,
    InboxOutlined,
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

const pesoArchivo = computed(() =>
    form.archivo ? `${(form.archivo.size / 1024).toFixed(0)} KB` : '',
);

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
    <a-modal v-model:open="abierto" :width="560" :footer="null" centered wrap-class-name="modal-importar"
        title="Cargar equipos desde Excel" @cancel="cerrar">
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
            <a-alert type="info" show-icon class="mi-alert">
                <template #message>
                    Descarga la plantilla de <strong>Excel</strong>, llena una fila por equipo
                    (los encabezados van en la primera fila) y súbela aquí.
                </template>
            </a-alert>

            <div class="mi-plantilla">
                <a-button type="primary" ghost class="btn-plantilla"
                    :href="route('equipos.importar.plantilla')" target="_blank">
                    <template #icon>
                        <FileExcelOutlined />
                    </template>
                    Descargar plantilla (Excel)
                </a-button>
            </div>

            <!-- Dropzone o vista previa del archivo -->
            <a-upload-dragger v-if="!form.archivo" :file-list="fileList" :max-count="1" :before-upload="antesDeSubir"
                accept=".xlsx,.xls" @remove="quitar" class="drop-excel">
                <p class="ant-upload-drag-icon">
                    <FileExcelOutlined />
                </p>
                <p class="ant-upload-text">Haz clic o arrastra el archivo de Excel</p>
                <p class="ant-upload-hint">Solo .xlsx o .xls · máx. 5 MB</p>
            </a-upload-dragger>

            <div v-else class="sd-prev">
                <div class="sd-prev__vis sd-prev__vis--excel">
                    <FileExcelOutlined />
                </div>
                <div class="sd-prev__info">
                    <div class="sd-prev__nombre">{{ form.archivo.name }}</div>
                    <div class="sd-prev__peso">{{ pesoArchivo }}</div>
                </div>
                <button type="button" class="sd-prev__quitar" @click="quitar" title="Quitar archivo">
                    <CloseCircleFilled />
                </button>
            </div>

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
                    <template #icon>
                        <CloudUploadOutlined />
                    </template>
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
/* ==========================================================
   Alerta y plantilla
   ========================================================== */
.mi-alert {
    border-radius: 11px;
    margin-bottom: 14px;
}

.mi-plantilla {
    margin-bottom: 14px;
}

.btn-plantilla {
    background: #fff !important;
    border: 1px solid #bbf7d0 !important;
    color: #15803d !important;
    font-weight: 700;
    border-radius: 10px;
    transition: transform 0.14s ease, box-shadow 0.14s ease, background 0.14s ease, border-color 0.14s ease;
}

.btn-plantilla:hover {
    background: #f0fdf4 !important;
    border-color: #16a34a !important;
    color: #166534 !important;
    transform: translateY(-1px);
}

/* ==========================================================
   Dropzone Excel (verde)
   ========================================================== */
.drop-excel :deep(.ant-upload-drag) {
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.drop-excel :deep(.ant-upload-drag:hover) {
    border-color: #16a34a;
    background: #dcfce7;
}

.drop-excel :deep(.ant-upload-drag-icon .anticon) {
    color: #16a34a !important;
    font-size: 40px;
}

/* ==========================================================
   Vista previa del archivo (compacta, como en SubirDocumento)
   ========================================================== */
.sd-prev {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--sigam-borde);
    border-radius: 12px;
    background: var(--sigam-navy-050);
    transition: border-color 0.16s ease, background 0.16s ease;
}

.sd-prev:hover {
    border-color: #bbf7d0;
    background: #f0fdf4;
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

.sd-prev__vis--excel {
    color: #15803d;
    background: #f0fdf4;
    border-color: #bbf7d0;
    font-size: 26px;
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

/* Botón X con animación */
.sd-prev__quitar {
    border: 0;
    background: transparent;
    color: #94a3b8;
    font-size: 18px;
    cursor: pointer;
    padding: 6px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition:
        color 0.16s ease,
        background 0.16s ease,
        transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
    will-change: transform;
}

.sd-prev__quitar:hover {
    color: #d64545;
    background: #fdecec;
    transform: rotate(90deg) scale(1.15);
}

.sd-prev__quitar:active {
    transform: rotate(90deg) scale(0.9);
}

/* ==========================================================
   Errores y nota
   ========================================================== */
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
    background: #e6f2fb;
    color: #0d6fae;
    font-size: 12.5px;
    line-height: 1.5;
}

.mi-nota .anticon {
    margin-top: 2px;
    flex: none;
}

/* ==========================================================
   Acciones del modal
   ========================================================== */
.mi-acciones {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin: 18px -24px 0;
    padding: 13px 20px;
    border-top: 1px solid var(--sigam-borde-suave);
    background: #fbfcfe;
}

/* ==========================================================
   Resultado
   ========================================================== */
.mi-res__head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 11px;
    font-size: 13.5px;
}

.mi-res__head.is-ok {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.mi-res__head.is-warn {
    background: #fffaf0;
    color: #a86717;
    border: 1px solid #fde4b6;
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

/* ==========================================================
   Botón principal del modal (Importar / Listo) en azul sólido
   ========================================================== */
.modal-importar :deep(.ant-btn-primary) {
    background: #0d84c9;
    border-color: #0d84c9;
}

.modal-importar :deep(.ant-btn-primary:hover) {
    background: #0f6fb0;
    border-color: #0f6fb0;
}
</style>