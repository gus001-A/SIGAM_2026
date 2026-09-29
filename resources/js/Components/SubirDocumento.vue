<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    CloseCircleFilled,
    FileExcelOutlined,
    FilePdfOutlined,
    FileWordOutlined,
    FileUnknownOutlined,
    InboxOutlined,
    PictureOutlined,
} from '@ant-design/icons-vue';
import BotonTomarFoto from '@/Components/BotonTomarFoto.vue';

const props = defineProps({
    relacionableTipo: { type: String, required: true },
    relacionableId: { type: [Number, String], required: true },
    roles: { type: Array, default: () => [] },
});

// =========================================================
//  Modal PDF
// =========================================================
const abiertoPdf = ref(false);
const fileListPdf = ref([]);
const previewUrlPdf = ref(null);

const formPdf = useForm({
    archivo: null,
    relacionable_tipo: props.relacionableTipo,
    relacionable_id: props.relacionableId,
    titulo: '',
    descripcion: '',
    rol: undefined,
});

const abrirPdf = () => {
    formPdf.reset();
    formPdf.relacionable_tipo = props.relacionableTipo;
    formPdf.relacionable_id = props.relacionableId;
    fileListPdf.value = [];
    if (previewUrlPdf.value) URL.revokeObjectURL(previewUrlPdf.value);
    previewUrlPdf.value = null;
    abiertoPdf.value = true;
};

const antesDeSubirPdf = (file) => {
    fileListPdf.value = [file];
    formPdf.archivo = file;
    if (previewUrlPdf.value) URL.revokeObjectURL(previewUrlPdf.value);
    previewUrlPdf.value = URL.createObjectURL(file);
    if (!formPdf.titulo) formPdf.titulo = file.name.replace(/\.[^.]+$/, '');
    return false;
};

const quitarArchivoPdf = () => {
    fileListPdf.value = [];
    formPdf.archivo = null;
    if (previewUrlPdf.value) URL.revokeObjectURL(previewUrlPdf.value);
    previewUrlPdf.value = null;
};

const pesoArchivoPdf = computed(() =>
    formPdf.archivo ? `${(formPdf.archivo.size / 1024).toFixed(0)} KB` : '',
);

const enviarPdf = () => {
    if (!formPdf.archivo) {
        formPdf.setError('archivo', 'Selecciona un archivo PDF.');
        return;
    }
    formPdf.post(route('documentos.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            quitarArchivoPdf();
            abiertoPdf.value = false;
        },
    });
};

// =========================================================
//  Modal Imagen
// =========================================================
const abiertoImg = ref(false);
const fileListImg = ref([]);
const previewUrlImg = ref(null);

const formImg = useForm({
    archivo: null,
    relacionable_tipo: props.relacionableTipo,
    relacionable_id: props.relacionableId,
    titulo: '',
    descripcion: '',
    rol: undefined,
});

const abrirImagen = () => {
    formImg.reset();
    formImg.relacionable_tipo = props.relacionableTipo;
    formImg.relacionable_id = props.relacionableId;
    fileListImg.value = [];
    if (previewUrlImg.value) URL.revokeObjectURL(previewUrlImg.value);
    previewUrlImg.value = null;
    abiertoImg.value = true;
};

const antesDeSubirImg = (file) => {
    fileListImg.value = [file];
    formImg.archivo = file;
    if (previewUrlImg.value) URL.revokeObjectURL(previewUrlImg.value);
    previewUrlImg.value = URL.createObjectURL(file);
    if (!formImg.titulo) formImg.titulo = file.name.replace(/\.[^.]+$/, '');
    return false;
};

const quitarArchivoImg = () => {
    fileListImg.value = [];
    formImg.archivo = null;
    if (previewUrlImg.value) URL.revokeObjectURL(previewUrlImg.value);
    previewUrlImg.value = null;
};

const pesoArchivoImg = computed(() =>
    formImg.archivo ? `${(formImg.archivo.size / 1024).toFixed(0)} KB` : '',
);

const enviarImg = () => {
    if (!formImg.archivo) {
        formImg.setError('archivo', 'Selecciona una imagen.');
        return;
    }
    formImg.post(route('documentos.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            quitarArchivoImg();
            abiertoImg.value = false;
        },
    });
};

// Exponemos ambos métodos al padre
defineExpose({ abrirPdf, abrirImagen });
</script>

<template>
    <!-- =========================================================
         MODAL PDF
    ========================================================== -->
    <a-modal v-model:open="abiertoPdf" title="Adjuntar PDF" :width="540" :confirm-loading="formPdf.processing"
        ok-text="Subir PDF" cancel-text="Cancelar" centered class="modal-doc" @ok="enviarPdf">
        <a-form layout="vertical" class="pt-1">
            <a-form-item :validate-status="formPdf.errors.archivo ? 'error' : undefined"
                :help="formPdf.errors.archivo || undefined">
                <a-upload-dragger v-if="!formPdf.archivo" :file-list="fileListPdf" :max-count="1"
                    :before-upload="antesDeSubirPdf" accept=".pdf,application/pdf" @remove="quitarArchivoPdf"
                    class="drop-pdf">
                    <p class="ant-upload-drag-icon">
                        <FilePdfOutlined />
                    </p>
                    <p class="ant-upload-text">Haz clic o arrastra un PDF</p>
                    <p class="ant-upload-hint">Solo PDF · máx. 20 MB</p>
                </a-upload-dragger>

                <div v-else class="sd-prev">
                    <div class="sd-prev__vis sd-prev__vis--pdf">
                        <FilePdfOutlined />
                    </div>
                    <div class="sd-prev__info">
                        <div class="sd-prev__nombre">{{ formPdf.archivo.name }}</div>
                        <div class="sd-prev__peso">{{ pesoArchivoPdf }}</div>
                    </div>
                    <button type="button" class="sd-prev__quitar" @click="quitarArchivoPdf" title="Quitar archivo">
                        <CloseCircleFilled />
                    </button>
                </div>
            </a-form-item>

            <a-form-item label="Título">
                <a-input v-model:value="formPdf.titulo" placeholder="Nombre descriptivo" />
            </a-form-item>

            <a-form-item label="Descripción (opcional)">
                <a-textarea v-model:value="formPdf.descripcion" :rows="2" placeholder="Breve descripción del archivo" />
            </a-form-item>

            <a-form-item v-if="roles.length" label="Evidencia de">
                <a-select v-model:value="formPdf.rol" :options="roles.map((r) => ({ value: r, label: r.toUpperCase() }))"
                    allow-clear placeholder="Sin especificar" />
            </a-form-item>
        </a-form>
    </a-modal>

    <!-- =========================================================
         MODAL IMAGEN
    ========================================================== -->
    <a-modal v-model:open="abiertoImg" title="Adjuntar imagen" :width="540" :confirm-loading="formImg.processing"
        ok-text="Subir imagen" cancel-text="Cancelar" centered class="modal-doc" @ok="enviarImg">
        <a-form layout="vertical" class="pt-1">
            <a-form-item :validate-status="formImg.errors.archivo ? 'error' : undefined"
                :help="formImg.errors.archivo || undefined">
                <template v-if="!formImg.archivo">
                    <a-upload-dragger :file-list="fileListImg" :max-count="1"
                        :before-upload="antesDeSubirImg" accept=".jpg,.jpeg,.png,.webp,image/*" @remove="quitarArchivoImg"
                        class="drop-img">
                        <p class="ant-upload-drag-icon">
                            <PictureOutlined />
                        </p>
                        <p class="ant-upload-text">Haz clic o arrastra una imagen</p>
                        <p class="ant-upload-hint">JPG, PNG o WEBP · máx. 20 MB</p>
                    </a-upload-dragger>
                    <BotonTomarFoto class="sd-tomar-foto" texto="O tomar foto con la cámara"
                        titulo="Adjuntar imagen" @capturada="antesDeSubirImg" />
                </template>

                <div v-else class="sd-prev">
                    <div class="sd-prev__vis">
                        <img :src="previewUrlImg" alt="Vista previa" />
                    </div>
                    <div class="sd-prev__info">
                        <div class="sd-prev__nombre">{{ formImg.archivo.name }}</div>
                        <div class="sd-prev__peso">{{ pesoArchivoImg }}</div>
                    </div>
                    <button type="button" class="sd-prev__quitar" @click="quitarArchivoImg" title="Quitar imagen">
                        <CloseCircleFilled />
                    </button>
                </div>
            </a-form-item>

            <!-- Previsualización grande cuando hay imagen -->
            <div v-if="previewUrlImg" class="sd-preview-lg">
                <img :src="previewUrlImg" alt="Previsualización" />
            </div>

            <a-form-item label="Título">
                <a-input v-model:value="formImg.titulo" placeholder="Nombre descriptivo" />
            </a-form-item>

            <a-form-item label="Descripción (opcional)">
                <a-textarea v-model:value="formImg.descripcion" :rows="2" placeholder="Breve descripción del archivo" />
            </a-form-item>

            <a-form-item v-if="roles.length" label="Evidencia de">
                <a-select v-model:value="formImg.rol" :options="roles.map((r) => ({ value: r, label: r.toUpperCase() }))"
                    allow-clear placeholder="Sin especificar" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>

<style scoped>
/* ==========================================================
   Vista previa del archivo (compacta)
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
    border-color: #cfe3f2;
    background: #f5fbff;
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

.sd-prev__vis--pdf {
    color: #d64545;
    background: #fdecec;
    border-color: #fecaca;
    font-size: 26px;
}

.sd-prev__vis img {
    width: 100%;
    height: 100%;
    object-fit: cover;
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

/* ==========================================================
   Botón X con animación
   ========================================================== */
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
   Previsualización grande para imágenes
   ========================================================== */
.sd-preview-lg {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: -4px 0 16px;
    padding: 10px;
    background: var(--sigam-navy-050);
    border: 1px dashed var(--sigam-borde);
    border-radius: 12px;
    max-height: 260px;
    overflow: hidden;
}

.sd-preview-lg img {
    max-width: 100%;
    max-height: 240px;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 14px -10px rgba(15, 37, 71, 0.3);
    display: block;
}

.sd-tomar-foto {
    display: flex;
    width: 100%;
    margin-top: 8px;
}

.sd-tomar-foto :deep(.ant-btn) {
    width: 100%;
}

/* ==========================================================
   Dropzones con tinte suave por tipo
   ========================================================== */
.drop-pdf :deep(.ant-upload-drag) {
    border-color: #fecaca;
    background: #fff7f7;
}

.drop-pdf :deep(.ant-upload-drag:hover) {
    border-color: #d64545;
    background: #fdecec;
}

.drop-pdf :deep(.ant-upload-drag-icon .anticon) {
    color: #d64545 !important;
    font-size: 40px;
}

.drop-img :deep(.ant-upload-drag) {
    border-color: #c9b8f0;
    background: #faf7ff;
}

.drop-img :deep(.ant-upload-drag:hover) {
    border-color: #6b4bc9;
    background: #efe9fb;
}

.drop-img :deep(.ant-upload-drag-icon .anticon) {
    color: #6b4bc9 !important;
    font-size: 40px;
}

/* ==========================================================
   Modal: botón principal azul sólido, cancelar neutro
   ========================================================== */
.modal-doc :deep(.ant-btn-primary) {
    background: #0d84c9;
    border-color: #0d84c9;
}

.modal-doc :deep(.ant-btn-primary:hover) {
    background: #0f6fb0;
    border-color: #0f6fb0;
}
</style>