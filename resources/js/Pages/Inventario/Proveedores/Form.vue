<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ContactsOutlined, FileTextOutlined, IdcardOutlined, InboxOutlined, SaveOutlined, TagOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
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
    estado: props.proveedor?.estado ?? 'activo',
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

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas({
    id: ['razon_social', 'nombre_comercial', 'rfc', 'especialidad'],
    contacto: ['contacto', 'telefono', 'correo', 'direccion'],
    notas: ['notas'],
    estado: ['estado'],
}, 'id');

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

    <AppLayout
        :titulo="editando ? 'Editar proveedor' : 'Nuevo proveedor'"
        descripcion="Razón social, contacto y especialidad del proveedor."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva">
                    <a-tab-pane key="id">
                        <template #tab><span><IdcardOutlined /> Identificación</span></template>
                        <p class="tab-ayuda">Quién es el proveedor y en qué se especializa.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="14">
                                <a-form-item label="Razón social" name="razon_social" :validate-status="est('razon_social')" :help="form.errors.razon_social">
                                    <a-input v-model:value="form.razon_social" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="10">
                                <a-form-item label="Nombre comercial" :validate-status="est('nombre_comercial')" :help="form.errors.nombre_comercial">
                                    <a-input v-model:value="form.nombre_comercial" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="RFC" :validate-status="est('rfc')" :help="form.errors.rfc">
                                    <a-input v-model:value="form.rfc" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="16">
                                <a-form-item label="Especialidad" extra="Áreas o equipos que atiende." :validate-status="est('especialidad')" :help="form.errors.especialidad">
                                    <a-input v-model:value="form.especialidad" placeholder="p. ej. Equipo de laboratorio, climatización" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="contacto">
                        <template #tab><span><ContactsOutlined /> Contacto</span></template>
                        <p class="tab-ayuda">Cómo comunicarse con este proveedor.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Persona de contacto" :validate-status="est('contacto')" :help="form.errors.contacto">
                                    <a-input v-model:value="form.contacto" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Teléfono" name="telefono" :validate-status="est('telefono')" :help="form.errors.telefono">
                                    <a-input
                                        v-model:value="form.telefono"
                                        :maxlength="10"
                                        inputmode="numeric"
                                        placeholder="10 dígitos"
                                        @keypress="soloDigitos"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Correo" name="correo" :validate-status="est('correo')" :help="form.errors.correo">
                                    <a-input v-model:value="form.correo" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Dirección" :validate-status="est('direccion')" :help="form.errors.direccion">
                                    <a-input v-model:value="form.direccion" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="notas">
                        <template #tab><span><FileTextOutlined /> Notas</span></template>
                        <p class="tab-ayuda">Información adicional libre, visible en la ficha del proveedor.</p>
                        <a-form-item :validate-status="est('notas')" :help="form.errors.notas">
                            <a-textarea v-model:value="form.notas" :auto-size="{ minRows: 3, maxRows: 8 }" />
                        </a-form-item>

                        <a-form-item
                            v-if="!editando"
                            label="Documentos / contrato"
                            extra="Opcional — contrato, ficha técnica u otro documento del proveedor. Puedes subir varios."
                            :validate-status="est('documentos')"
                            :help="form.errors.documentos"
                        >
                            <a-upload-dragger
                                :file-list="listaDocumentos"
                                :multiple="true"
                                :max-count="5"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                :before-upload="antesDeSubirDocumento"
                                @remove="quitarDocumento"
                            >
                                <p class="ant-upload-drag-icon"><InboxOutlined /></p>
                                <p class="ant-upload-text">Haz clic o arrastra uno o varios documentos</p>
                                <p class="ant-upload-hint">PDF, imagen o Word · máx. 20 MB c/u · hasta 5 archivos</p>
                            </a-upload-dragger>
                        </a-form-item>
                    </a-tab-pane>

                    <a-tab-pane key="estado">
                        <template #tab><span><TagOutlined /> Estado</span></template>
                        <p class="tab-ayuda">Un proveedor inactivo no aparece como opción al registrar equipos.</p>
                        <a-form-item name="estado" :validate-status="est('estado')" :help="form.errors.estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activo</a-radio-button>
                                <a-radio-button value="inactivo">Inactivo</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <a-card size="small" class="form-acciones">
                <a-space>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon><SaveOutlined /></template>
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
}
.form-card :deep(.ant-tabs-nav) {
    margin-bottom: 10px;
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
