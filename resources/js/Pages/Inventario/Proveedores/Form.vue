<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircleFilled,
    ContactsOutlined,
    ExclamationCircleFilled,
    FileTextOutlined,
    IdcardOutlined,
    InboxOutlined,
    SaveOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BotonTomarFoto from '@/Components/BotonTomarFoto.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';
import { reglaCorreo, reglaRequerido, reglaTelefono, soloDigitos } from '@/utils/restricciones';

const props = defineProps({
    proveedor: { type: Object, default: null },
});

const editando = computed(() => !!props.proveedor);

const form = useForm({
    razon_social: props.proveedor?.razon_social ?? '',
    nombre_comercial: props.proveedor?.nombre_comercial ?? '',
    rfc: props.proveedor?.rfc ?? '',
    contacto: props.proveedor?.contacto ?? '',
    telefono: props.proveedor?.telefono ?? '',
    correo: props.proveedor?.correo ?? '',
    direccion: props.proveedor?.direccion ?? '',
    especialidad: props.proveedor?.especialidad ?? '',
    notas: props.proveedor?.notas ?? '',
    documentos: [],
});

const listaDocumentos = ref([]);
const antesDeSubirDocumento = (file) => {
    listaDocumentos.value = [...listaDocumentos.value, file];
    form.documentos = listaDocumentos.value;
    return false;
};
const quitarDocumento = (file) => {
    listaDocumentos.value = listaDocumentos.value.filter((f) => f.uid !== file.uid);
    form.documentos = listaDocumentos.value;
};

const reglas = reactive({
    razon_social: [reglaRequerido('La razón social es obligatoria.')],
    correo: [reglaCorreo()],
    telefono: [reglaTelefono(10)],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const TABS = {
    id: {
        titulo: 'Identificación',
        subtitulo: 'Razón social y especialidad',
        color: '#0d84c9',
        campos: ['razon_social', 'nombre_comercial', 'rfc', 'especialidad'],
    },
    contacto: {
        titulo: 'Contacto',
        subtitulo: 'Cómo comunicarse',
        color: '#1f9e86',
        campos: ['contacto', 'telefono', 'correo', 'direccion'],
    },
    notas: {
        titulo: 'Notas',
        subtitulo: 'Información y documentos',
        color: '#6b4bc9',
        campos: ['notas', 'documentos'],
    },
};

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas(
    Object.fromEntries(Object.entries(TABS).map(([k, t]) => [k, t.campos])),
    'id',
);

const estadoTab = (key) => {
    if (TABS[key].campos.some((c) => form.errors[c])) return 'error';
    if (key === 'id' && form.razon_social) return 'ok';
    return null;
};

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor };
    if (editando.value) form.put(route('proveedores.update', props.proveedor.id), opciones);
    else form.post(route('proveedores.store'), { ...opciones, forceFormData: true });
};

const cancelar = () =>
    router.visit(editando.value ? route('proveedores.show', props.proveedor.id) : route('proveedores.index'));
</script>

<template>

    <Head :title="editando ? `Editar ${proveedor.razon_social}` : 'Nuevo proveedor'" />

    <AppLayout :titulo="editando ? 'Editar proveedor' : 'Nuevo proveedor'"
        descripcion="Razón social, contacto y especialidad del proveedor.">
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva" class="form-tabs">
                    <a-tab-pane key="id">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.id.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><IdcardOutlined /></span>
                                    <span v-if="estadoTab('id') === 'ok'" class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                    <span v-else-if="estadoTab('id') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.id.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.id.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Quién es el proveedor y en qué se especializa.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="14">
                                <a-form-item label="Razón social" name="razon_social"
                                    :validate-status="est('razon_social')" :help="form.errors.razon_social">
                                    <a-input v-model:value="form.razon_social" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="10">
                                <a-form-item label="Nombre comercial" :validate-status="est('nombre_comercial')"
                                    :help="form.errors.nombre_comercial">
                                    <a-input v-model:value="form.nombre_comercial" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="RFC" :validate-status="est('rfc')" :help="form.errors.rfc">
                                    <a-input v-model:value="form.rfc" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="16">
                                <a-form-item label="Especialidad" extra="Áreas o equipos que atiende."
                                    :validate-status="est('especialidad')" :help="form.errors.especialidad">
                                    <a-input v-model:value="form.especialidad"
                                        placeholder="p. ej. Equipo de laboratorio, climatización" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="contacto">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.contacto.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><ContactsOutlined /></span>
                                    <span v-if="estadoTab('contacto') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.contacto.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.contacto.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Cómo comunicarse con este proveedor.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Persona de contacto" :validate-status="est('contacto')"
                                    :help="form.errors.contacto">
                                    <a-input v-model:value="form.contacto" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Teléfono" name="telefono" :validate-status="est('telefono')"
                                    :help="form.errors.telefono">
                                    <a-input v-model:value="form.telefono" :maxlength="10" inputmode="numeric"
                                        placeholder="10 dígitos" @keypress="soloDigitos" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Correo" name="correo" :validate-status="est('correo')"
                                    :help="form.errors.correo">
                                    <a-input v-model:value="form.correo" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Dirección" :validate-status="est('direccion')"
                                    :help="form.errors.direccion">
                                    <a-input v-model:value="form.direccion" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="notas">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.notas.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><FileTextOutlined /></span>
                                    <span v-if="estadoTab('notas') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.notas.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.notas.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Información adicional libre, visible en la ficha del proveedor.</p>
                        <a-form-item :validate-status="est('notas')" :help="form.errors.notas">
                            <a-textarea v-model:value="form.notas" :auto-size="{ minRows: 3, maxRows: 8 }" />
                        </a-form-item>

                        <a-form-item v-if="!editando" label="Documentos / contrato"
                            extra="Opcional — contrato, ficha técnica u otro documento del proveedor. Puedes subir varios."
                            :validate-status="est('documentos')" :help="form.errors.documentos">
                            <a-upload-dragger :file-list="listaDocumentos" :multiple="true" :max-count="5"
                                list-type="picture" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                :before-upload="antesDeSubirDocumento" @remove="quitarDocumento">
                                <p class="ant-upload-drag-icon">
                                    <InboxOutlined />
                                </p>
                                <p class="ant-upload-text">Haz clic o arrastra uno o varios documentos</p>
                                <p class="ant-upload-hint">PDF, imagen o Word · máx. 20 MB c/u · hasta 5 archivos</p>
                            </a-upload-dragger>
                            <BotonTomarFoto block class="mt-2" texto="O tomar foto con la cámara"
                                titulo="Documento del proveedor" @capturada="antesDeSubirDocumento" />
                        </a-form-item>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <a-card size="small" class="form-acciones">
                <a-space>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon>
                            <SaveOutlined />
                        </template>
                        {{ editando ? 'Guardar cambios' : 'Registrar proveedor' }}
                    </a-button>
                    <a-button size="large" @click="cancelar">Cancelar</a-button>
                </a-space>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.form-card {
    margin-bottom: 10px;
    border-radius: 16px;
    box-shadow: var(--sigam-sombra-sm);
}

.form-tabs :deep(.ant-tabs-nav) {
    padding: 8px 12px 0;
    margin-bottom: 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.form-tabs :deep(.ant-tabs-nav::before) {
    border-bottom: none;
}
.form-tabs :deep(.ant-tabs-nav-wrap) {
    align-items: stretch;
}
.form-tabs :deep(.ant-tabs-nav-list) {
    gap: 4px;
    align-items: stretch;
}
.form-tabs :deep(.ant-tabs-tab) {
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 11px 11px 0 0;
    transition: background 0.2s ease;
    height: 56px;
    display: inline-flex !important;
    align-items: center !important;
}
.form-tabs :deep(.ant-tabs-tab:hover) {
    background: #f3f6fa;
}
.form-tabs :deep(.ant-tabs-tab-active) {
    background: #fff;
}
.form-tabs :deep(.ant-tabs-tab-btn) {
    color: inherit !important;
    height: 100%;
    display: inline-flex !important;
    align-items: center !important;
    padding: 0 14px !important;
    transition: none !important;
}
.form-tabs :deep(.ant-tabs-ink-bar) {
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: #0d84c9;
}
.form-tabs :deep(.ant-tabs-content-holder) {
    padding: 22px 24px 24px;
}

/* Label completo */
.tab-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    height: 100%;
    line-height: 1;
}
.tab-label__ico-wrap {
    position: relative;
    flex: none;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.tab-label__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #94a3b8;
    background: #eef2f7;
    transition: all 0.25s cubic-bezier(0.34, 1.4, 0.4, 1);
    line-height: 1;
}
.tab-label__ico .anticon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}
.form-tabs :deep(.ant-tabs-tab-active) .tab-label__ico {
    color: #fff;
    background: var(--tab-color);
    box-shadow: 0 4px 10px -4px var(--tab-color);
    transform: scale(1.05);
}
.tab-label__badge {
    position: absolute;
    bottom: -3px;
    right: -3px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    line-height: 1;
    border: 2px solid #fff;
    background: #fff;
    pointer-events: none;
    z-index: 2;
}
.tab-label__badge .anticon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}
.tab-label__badge--ok {
    color: #1f9e86;
}
.tab-label__badge--error {
    color: #d64545;
    animation: pulseError 1.6s ease infinite;
}
@keyframes pulseError {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}
.tab-label__col {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 2px;
    min-width: 0;
    text-align: left;
    line-height: 1.15;
}
.tab-label__txt {
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
    transition: color 0.2s ease;
    white-space: nowrap;
    line-height: 1.2;
    display: block;
}
.form-tabs :deep(.ant-tabs-tab-active) .tab-label__txt {
    color: var(--sigam-navy);
}
.tab-label__sub {
    font-size: 10.5px;
    color: var(--sigam-tenue);
    white-space: nowrap;
    line-height: 1.2;
    font-weight: 500;
    display: block;
}
@media (max-width: 991px) {
    .tab-label__sub {
        display: none;
    }
}
@media (max-width: 640px) {
    .tab-label__col {
        display: none;
    }
    .form-tabs :deep(.ant-tabs-tab) {
        height: 50px;
    }
}

.tab-ayuda {
    margin: -4px 0 10px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}

.form-acciones {
    position: sticky;
    bottom: 0;
    z-index: 5;
}
</style>