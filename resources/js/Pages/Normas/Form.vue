<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarOutlined, FileProtectOutlined, SaveOutlined, TagOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';

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

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas({
    id: ['codigo', 'nombre', 'version', 'descripcion'],
    vig: ['fecha_vigencia', 'fecha_revision'],
    estado: ['estado'],
}, 'id');

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor };
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
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva">
                    <a-tab-pane key="id">
                        <template #tab><span><FileProtectOutlined /> Identificación</span></template>
                        <p class="tab-ayuda">Qué norma es y a qué se refiere.</p>
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
                    </a-tab-pane>

                    <a-tab-pane key="vig">
                        <template #tab><span><CalendarOutlined /> Vigencia</span></template>
                        <p class="tab-ayuda">Cuándo entró en vigor y cuándo toca revisarla otra vez.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Fecha de entrada en vigor" :validate-status="est('fecha_vigencia')" :help="form.errors.fecha_vigencia">
                                    <CampoFechaHora v-model="form.fecha_vigencia" solo-fecha />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    label="Próxima revisión"
                                    extra="Se marcará en rojo cuando la fecha haya pasado."
                                    :validate-status="est('fecha_revision')"
                                    :help="form.errors.fecha_revision"
                                >
                                    <CampoFechaHora v-model="form.fecha_revision" solo-fecha />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="estado">
                        <template #tab><span><TagOutlined /> Estado</span></template>
                        <p class="tab-ayuda">Una norma inactiva no aparece al asociar equipos ni planes preventivos.</p>
                        <a-form-item name="estado" :validate-status="est('estado')" :help="form.errors.estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Vigente</a-radio-button>
                                <a-radio-button value="inactivo">Inactiva</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <a-card size="small" class="form-acciones">
                <a-space>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon><SaveOutlined /></template>
                        {{ editando ? 'Guardar cambios' : 'Registrar norma' }}
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
