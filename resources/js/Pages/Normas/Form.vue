<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarOutlined, FileProtectOutlined, SaveOutlined, TagOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';

const props = defineProps({
    norma: { type: Object, default: null },
});

const editando = computed(() => !!props.norma);

const form = useForm({
    codigo: props.norma?.codigo ?? '',
    nombre: props.norma?.nombre ?? '',
    version: props.norma?.version ?? '',
    descripcion: props.norma?.descripcion ?? '',
    fecha_vigencia: props.norma?.fecha_vigencia?.slice(0, 10) ?? '',
    fecha_revision: props.norma?.fecha_revision?.slice(0, 10) ?? '',
    documento_id: props.norma?.documento_id ?? undefined,
    estado: props.norma?.estado ?? 'activo',
});

const reglas = reactive({
    codigo: [{ required: true, message: 'El código es obligatorio.' }],
    nombre: [{ required: true, message: 'El nombre es obligatorio.' }],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const enviar = () => {
    const opciones = { preserveScroll: true };
    if (editando.value) form.put(route('normas.update', props.norma.id), opciones);
    else form.post(route('normas.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('normas.show', props.norma.id) : route('normas.index'));
</script>

<template>
    <Head :title="editando ? `Editar ${norma.codigo}` : 'Nueva norma'" />

    <AppLayout
        :titulo="editando ? `Editar norma ${norma.codigo}` : 'Nueva norma'"
        descripcion="Código, versión y fechas de vigencia y revisión de la normativa."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
            <a-row :gutter="16">
                <a-col :xs="24" :lg="16">
                    <a-card size="small" class="mb-4 sec">
                        <template #title><FileProtectOutlined /> Identificación</template>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Código" name="codigo" :validate-status="est('codigo')" :help="form.errors.codigo">
                                    <a-input v-model:value="form.codigo" placeholder="p. ej. NOM-137-SSA1-2008" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Nombre" name="nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="4">
                                <a-form-item label="Versión" :validate-status="est('version')" :help="form.errors.version">
                                    <a-input v-model:value="form.version" placeholder="2008" />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Descripción / alcance" :validate-status="est('descripcion')" :help="form.errors.descripcion">
                                    <a-textarea v-model:value="form.descripcion" :auto-size="{ minRows: 3, maxRows: 8 }" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>

                    <a-card size="small" class="sec">
                        <template #title><CalendarOutlined /> Vigencia</template>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Fecha de entrada en vigor" :validate-status="est('fecha_vigencia')" :help="form.errors.fecha_vigencia">
                                    <CampoFechaHora v-model="form.fecha_vigencia" solo-fecha />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    label="Próxima revisión"
                                    extra="Se marcará en rojo cuando la fecha haya pasado"
                                    :validate-status="est('fecha_revision')"
                                    :help="form.errors.fecha_revision"
                                >
                                    <CampoFechaHora v-model="form.fecha_revision" solo-fecha />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="8">
                    <a-card size="small" class="mb-4 sec">
                        <template #title><TagOutlined /> Estado</template>
                        <a-form-item name="estado" :validate-status="est('estado')" :help="form.errors.estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Vigente</a-radio-button>
                                <a-radio-button value="inactivo">Inactiva</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                        <p class="text-xs opacity-60 m-0">
                            Una norma inactiva no aparece al asociar equipos ni planes preventivos.
                        </p>
                    </a-card>

                    <a-card size="small">
                        <a-button type="primary" size="large" block html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Registrar norma' }}
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
