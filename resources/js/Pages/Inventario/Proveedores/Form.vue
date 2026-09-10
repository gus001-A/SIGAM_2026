<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ContactsOutlined, FileTextOutlined, IdcardOutlined, SaveOutlined, TagOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
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
});

const reglas = reactive({
    razon_social: [reglaRequerido('La razón social es obligatoria.')],
    correo: [reglaCorreo()],
    telefono: [reglaTelefono(10)],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const enviar = () => {
    const opciones = { preserveScroll: true };
    if (editando.value) form.put(route('proveedores.update', props.proveedor.id), opciones);
    else form.post(route('proveedores.store'), opciones);
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
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
            <a-row :gutter="16">
                <a-col :xs="24" :lg="16">
                    <a-card size="small" class="mb-4 sec">
                        <template #title><IdcardOutlined /> Identificación</template>
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
                                <a-form-item label="Especialidad" extra="Áreas o equipos que atiende" :validate-status="est('especialidad')" :help="form.errors.especialidad">
                                    <a-input v-model:value="form.especialidad" placeholder="p. ej. Equipo de laboratorio, climatización" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>

                    <a-card size="small" class="mb-4 sec">
                        <template #title><ContactsOutlined /> Contacto</template>
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
                    </a-card>

                    <a-card size="small" class="sec">
                        <template #title><FileTextOutlined /> Notas</template>
                        <a-form-item :validate-status="est('notas')" :help="form.errors.notas">
                            <a-textarea v-model:value="form.notas" :auto-size="{ minRows: 3, maxRows: 8 }" />
                        </a-form-item>
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="8">
                    <a-card size="small" class="mb-4 sec">
                        <template #title><TagOutlined /> Estado</template>
                        <a-form-item name="estado" :validate-status="est('estado')" :help="form.errors.estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activo</a-radio-button>
                                <a-radio-button value="inactivo">Inactivo</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-card>

                    <a-card size="small">
                        <a-button type="primary" size="large" block html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Registrar proveedor' }}
                        </a-button>
                        <a-button type="text" block class="mt-2" @click="cancelar">Cancelar</a-button>
                    </a-card>
                </a-col>
            </a-row>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.sec :deep(.ant-card-head-title) {
    display: flex;
    align-items: center;
    gap: 8px;
}
.sec :deep(.ant-card-head-title)::before {
    display: none !important;
}
.sec :deep(.ant-card-head-title .anticon) {
    color: var(--sigam-teal);
    font-size: 15px;
}
</style>
