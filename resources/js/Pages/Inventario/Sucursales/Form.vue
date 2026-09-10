<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { FileTextOutlined, SaveOutlined, ShopOutlined, UserOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { reglaCorreo, reglaRequerido, reglaTelefono, soloDigitos } from '@/utils/restricciones';

const props = defineProps({
    sucursal: { type: Object, default: null },
    responsables: { type: Array, default: () => [] },
});

const editando = computed(() => !!props.sucursal);

const form = useForm({
    codigo: props.sucursal?.codigo ?? '',
    nombre: props.sucursal?.nombre ?? '',
    direccion: props.sucursal?.direccion ?? '',
    telefono: props.sucursal?.telefono ?? '',
    correo: props.sucursal?.correo ?? '',
    responsable_id: props.sucursal?.responsable_id ?? undefined,
    estado: props.sucursal?.estado ?? 'activo',
    notas: props.sucursal?.notas ?? '',
});

const reglas = reactive({
    codigo: [reglaRequerido('El código es obligatorio.')],
    nombre: [reglaRequerido('El nombre es obligatorio.')],
    correo: [reglaCorreo()],
    telefono: [reglaTelefono(10)],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const enviar = () => {
    const opciones = { preserveScroll: true };
    if (editando.value) form.put(route('sucursales.update', props.sucursal.id), opciones);
    else form.post(route('sucursales.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('sucursales.show', props.sucursal.id) : route('sucursales.index'));
</script>

<template>
    <Head :title="editando ? `Editar ${sucursal.nombre}` : 'Nueva sucursal'" />

    <AppLayout
        :titulo="editando ? `Editar sucursal ${sucursal.nombre}` : 'Nueva sucursal'"
        descripcion="Datos de identificación, dirección y responsable de la sede."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
            <a-row :gutter="16">
                <a-col :xs="24" :lg="16">
                    <a-card size="small" class="mb-4 sec">
                        <template #title><ShopOutlined /> Datos generales</template>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Código" name="codigo" :validate-status="est('codigo')" :help="form.errors.codigo">
                                    <a-input v-model:value="form.codigo" placeholder="p. ej. SUC-01" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="16">
                                <a-form-item label="Nombre" name="nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Dirección" :validate-status="est('direccion')" :help="form.errors.direccion">
                                    <a-input v-model:value="form.direccion" />
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
                        </a-row>
                    </a-card>

                    <a-card size="small" class="sec">
                        <template #title><FileTextOutlined /> Notas</template>
                        <a-form-item :validate-status="est('notas')" :help="form.errors.notas">
                            <a-textarea v-model:value="form.notas" :auto-size="{ minRows: 3, maxRows: 8 }" placeholder="Información adicional de la sucursal" />
                        </a-form-item>
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="8">
                    <a-card size="small" class="mb-4 sec">
                        <template #title><UserOutlined /> Asignación</template>
                        <a-form-item label="Responsable" :validate-status="est('responsable_id')" :help="form.errors.responsable_id">
                            <a-select
                                v-model:value="form.responsable_id"
                                :options="responsables"
                                allow-clear
                                show-search
                                option-filter-prop="label"
                                placeholder="Sin asignar"
                            />
                        </a-form-item>
                        <a-form-item label="Estado" name="estado" :validate-status="est('estado')" :help="form.errors.estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activa</a-radio-button>
                                <a-radio-button value="inactivo">Inactiva</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-card>

                    <a-card size="small">
                        <a-button type="primary" size="large" block html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Registrar sucursal' }}
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
