<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { SaveOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    equipos: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
});

const form = useForm({
    equipo_id: undefined,
    tipo_id: undefined,
    prioridad_id: undefined,
    programado_inicio: '',
    programado_fin: '',
    problema_reportado: '',
});

const reglas = reactive({
    equipo_id: [{ required: true, message: 'Selecciona el equipo.' }],
    tipo_id: [{ required: true, message: 'Selecciona el tipo.' }],
    prioridad_id: [{ required: true, message: 'Selecciona la prioridad.' }],
});

const equipoOpciones = computed(() =>
    props.equipos.map((e) => ({ value: e.id, label: `${e.codigo_activo} — ${e.descripcion}` })),
);
const est = (c) => (form.errors[c] ? 'error' : undefined);

const enviar = () => form.post(route('mantenimientos.store'));
</script>

<template>
    <Head title="Nueva orden" />

    <AppLayout
        titulo="Nueva orden de mantenimiento"
        descripcion="Genera un trabajo correctivo o preventivo con su equipo, tipo y prioridad."
    >
        <a-row justify="center">
            <a-col :xs="24" :md="18" :lg="14">
                <a-card>
                    <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
                        <a-form-item label="Equipo" name="equipo_id" :validate-status="est('equipo_id')" :help="form.errors.equipo_id">
                            <a-select v-model:value="form.equipo_id" :options="equipoOpciones" placeholder="Selecciona el equipo" />
                        </a-form-item>

                        <a-row :gutter="16">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Tipo de mantenimiento" name="tipo_id" :validate-status="est('tipo_id')" :help="form.errors.tipo_id">
                                    <a-select v-model:value="form.tipo_id" :options="catalogos.tipos?.map((t) => ({ value: t.id, label: t.nombre }))" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Prioridad" name="prioridad_id" :validate-status="est('prioridad_id')" :help="form.errors.prioridad_id">
                                    <a-select v-model:value="form.prioridad_id" :options="catalogos.prioridades?.map((p) => ({ value: p.id, label: p.nombre }))" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Programado (inicio)" :validate-status="est('programado_inicio')" :help="form.errors.programado_inicio">
                                    <a-input v-model:value="form.programado_inicio" type="datetime-local" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Programado (fin)" :validate-status="est('programado_fin')" :help="form.errors.programado_fin">
                                    <a-input v-model:value="form.programado_fin" type="datetime-local" />
                                </a-form-item>
                            </a-col>
                        </a-row>

                        <a-form-item label="Problema reportado / motivo" :validate-status="est('problema_reportado')" :help="form.errors.problema_reportado">
                            <a-textarea v-model:value="form.problema_reportado" :rows="3" />
                        </a-form-item>

                        <div class="flex gap-2 mt-2">
                            <a-button type="primary" html-type="submit" :loading="form.processing">
                                <template #icon><SaveOutlined /></template>
                                Crear orden
                            </a-button>
                            <a-button type="text" @click="router.visit(route('mantenimientos.index'))">Cancelar</a-button>
                        </div>
                    </a-form>
                </a-card>
            </a-col>
        </a-row>
    </AppLayout>
</template>
