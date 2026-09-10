<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    DeleteOutlined,
    DownloadOutlined,
    EyeOutlined,
    FileExcelOutlined,
    FileImageOutlined,
    FilePdfOutlined,
    FileWordOutlined,
    FileUnknownOutlined,
    PaperClipOutlined,
} from '@ant-design/icons-vue';
import SubirDocumento from '@/Components/SubirDocumento.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';

const props = defineProps({
    documentos: { type: Array, default: () => [] },
    relacionableTipo: { type: String, required: true },
    relacionableId: { type: [Number, String], required: true },
    roles: { type: Array, default: () => [] },
    puedeSubir: { type: Boolean, default: false },
    puedeEliminar: { type: Boolean, default: false },
});

const subir = ref(null);
const confirmar = ref(null);
const previsualizando = ref(null);

const esImagen = (d) => /^image\//.test(d.tipo_mime || '');
const esPdf = (d) => (d.tipo_mime || '').includes('pdf');

const iconoDe = (d) => {
    const m = d.tipo_mime || '';
    if (esImagen(d)) return FileImageOutlined;
    if (m.includes('pdf')) return FilePdfOutlined;
    if (m.includes('word') || m.includes('document')) return FileWordOutlined;
    if (m.includes('sheet') || m.includes('excel')) return FileExcelOutlined;
    return FileUnknownOutlined;
};
const colorDe = (d) => {
    const m = d.tipo_mime || '';
    if (esImagen(d)) return '#6b4bc9';
    if (m.includes('pdf')) return '#d64545';
    if (m.includes('word') || m.includes('document')) return '#0d84c9';
    if (m.includes('sheet') || m.includes('excel')) return '#1f9e86';
    return '#64748b';
};

const kb = (n) => (n ? `${(n / 1024).toFixed(0)} KB` : '');
const urlVer = (d) => route('documentos.ver', d.id);
const urlDescargar = (d) => route('documentos.download', d.id);

const abrirPrev = (d) => {
    if (esImagen(d) || esPdf(d)) previsualizando.value = d;
    else window.open(urlDescargar(d), '_blank');
};

const eliminar = async (d) => {
    const ok = await confirmar.value.abrir({
        titulo: 'Eliminar documento',
        mensaje: `Se quitará "${d.titulo || d.nombre_original}" del expediente.`,
        confirmar: 'Eliminar',
        peligro: true,
    });
    if (ok) router.delete(route('documentos.destroy', d.id), { preserveScroll: true });
};

const abrirSubir = () => subir.value.abrir();
defineExpose({ abrirSubir });

const vacio = computed(() => props.documentos.length === 0);
</script>

<template>
    <div class="ldoc">
        <div v-if="puedeSubir" class="ldoc__top">
            <a-button type="primary" ghost size="small" @click="abrirSubir">
                <template #icon><PaperClipOutlined /></template>
                Adjuntar documento
            </a-button>
        </div>

        <div v-if="!vacio" class="ldoc__grid">
            <div v-for="d in documentos" :key="d.id" class="doc">
                <button type="button" class="doc__thumb" @click="abrirPrev(d)">
                    <img v-if="esImagen(d)" :src="urlVer(d)" :alt="d.nombre_original" loading="lazy" />
                    <span v-else class="doc__ico" :style="{ color: colorDe(d), background: colorDe(d) + '18' }">
                        <component :is="iconoDe(d)" />
                    </span>
                    <span class="doc__lupa"><EyeOutlined /></span>
                </button>
                <div class="doc__meta">
                    <div class="doc__nombre" :title="d.titulo || d.nombre_original">{{ d.titulo || d.nombre_original }}</div>
                    <div class="doc__sub">
                        <a-tag v-if="d.categoria" :bordered="false" color="blue">{{ d.categoria }}</a-tag>
                        <span>{{ kb(d.tamano) }}</span>
                    </div>
                </div>
                <div class="doc__acc">
                    <a-tooltip title="Descargar">
                        <a :href="urlDescargar(d)" target="_blank" class="doc__btn"><DownloadOutlined /></a>
                    </a-tooltip>
                    <a-tooltip v-if="puedeEliminar" title="Eliminar">
                        <button type="button" class="doc__btn doc__btn--del" @click="eliminar(d)"><DeleteOutlined /></button>
                    </a-tooltip>
                </div>
            </div>
        </div>

        <a-empty v-else description="Sin documentos en el expediente" class="ldoc__vacio" />

        <SubirDocumento
            ref="subir"
            :relacionable-tipo="relacionableTipo"
            :relacionable-id="relacionableId"
            :roles="roles"
        />
        <ConfirmarDialog ref="confirmar" />

        <a-modal
            :open="!!previsualizando"
            :footer="null"
            :width="previsualizando && /pdf/.test(previsualizando.tipo_mime || '') ? 820 : 620"
            centered
            :title="previsualizando?.titulo || previsualizando?.nombre_original"
            wrap-class-name="ldoc-prev"
            @cancel="previsualizando = null"
        >
            <template v-if="previsualizando">
                <img
                    v-if="esImagen(previsualizando)"
                    :src="urlVer(previsualizando)"
                    :alt="previsualizando.nombre_original"
                    class="ldoc-prev__img"
                />
                <iframe
                    v-else
                    :src="urlVer(previsualizando)"
                    class="ldoc-prev__pdf"
                    title="Previsualización"
                />
                <div class="ldoc-prev__pie">
                    <a :href="urlDescargar(previsualizando)" target="_blank">
                        <a-button type="primary" size="small">
                            <template #icon><DownloadOutlined /></template>
                            Descargar
                        </a-button>
                    </a>
                </div>
            </template>
        </a-modal>
    </div>
</template>

<style>
.ldoc-prev .ant-modal-body {
    padding: 0;
}
</style>

<style scoped>
.ldoc__top {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 12px;
}
.ldoc__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}
.ldoc__vacio {
    padding: 20px 0;
}
.doc {
    border: 1px solid var(--sigam-borde);
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
}
.doc:hover {
    box-shadow: var(--sigam-sombra-md);
    transform: translateY(-2px);
    border-color: var(--sigam-navy-100);
}
.doc__thumb {
    position: relative;
    width: 100%;
    height: 104px;
    border: 0;
    padding: 0;
    background: var(--sigam-navy-050);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.doc__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.doc__ico {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}
.doc__lupa {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 44, 74, 0.45);
    color: #fff;
    font-size: 20px;
    opacity: 0;
    transition: opacity 0.15s ease;
}
.doc__thumb:hover .doc__lupa {
    opacity: 1;
}
.doc__meta {
    padding: 9px 10px 4px;
}
.doc__nombre {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--sigam-texto);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.doc__sub {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--sigam-tenue);
    margin-top: 2px;
}
.doc__acc {
    display: flex;
    border-top: 1px solid var(--sigam-borde-suave);
}
.doc__btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 30px;
    border: 0;
    background: transparent;
    color: var(--sigam-tenue);
    cursor: pointer;
    transition: background 0.13s ease, color 0.13s ease;
}
.doc__btn:hover {
    background: var(--sigam-navy-050);
    color: var(--sigam-navy);
}
.doc__btn--del:hover {
    background: #fbeaea;
    color: #c23b3b;
}
.doc__acc .doc__btn + .doc__btn {
    border-left: 1px solid var(--sigam-borde-suave);
}
.ldoc-prev__img {
    display: block;
    width: 100%;
    max-height: 70vh;
    object-fit: contain;
    background: #0f2c4a;
}
.ldoc-prev__pdf {
    display: block;
    width: 100%;
    height: 72vh;
    border: 0;
}
.ldoc-prev__pie {
    padding: 12px 16px;
    border-top: 1px solid var(--sigam-borde-suave);
    background: #fbfcfe;
    text-align: right;
}
</style>
