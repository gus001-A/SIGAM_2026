<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { SendOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    equipos: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
    preseleccion: { type: Object, default: () => ({}) },
});

const form = useForm({
    equipo_id: props.preseleccion?.equipo_id ?? undefined,
    descripcion: '',
    prioridad_id: undefined,
    fecha_requerida: '',
});

const reglas = reactive({
    equipo_id: [{ required: true, message: 'Selecciona el equipo.' }],
    descripcion: [{ required: true, message: 'Describe el problema.' }],
});

const equipoOpciones = computed(() =>
    props.equipos.map((e) => ({ value: e.id, label: `${e.codigo_activo} — ${e.descripcion}` })),
);

const enviar = () => form.post(route('solicitudes.store'));
</script>

<template>
    <Head title="Nueva solicitud" />

    <AppLayout
        titulo="Nueva solicitud de mantenimiento"
        descripcion="Describe la falla o necesidad y el equipo afectado; un supervisor la autorizará."
    >
        <a-row justify="center">
            <a-col :xs="24" :md="16" :lg="12">
                <a-card>
                    <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
                        <a-form-item label="Equipo" name="equipo_id" :validate-status="form.errors.equipo_id ? 'error' : undefined" :help="form.errors.equipo_id">
                            <a-select
                                v-model:value="form.equipo_id"
                                :options="equipoOpciones"
                                show-search
                                option-filter-prop="label"
                                placeholder="Buscar equipo por código o descripción"
                            />
                        </a-form-item>

                        <a-form-item label="Descripción del problema" name="descripcion" :validate-status="form.errors.descripcion ? 'error' : undefined" :help="form.errors.descripcion">
                            <a-textarea v-model:value="form.descripcion" :rows="4" placeholder="¿Qué falla presenta el equipo?" />
                        </a-form-item>

                        <a-row :gutter="16">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Prioridad sugerida" :help="form.errors.prioridad_id" :validate-status="form.errors.prioridad_id ? 'error' : undefined">
                                    <a-select
                                        v-model:value="form.prioridad_id"
                                        :options="prioridades.map((p) => ({ value: p.id, label: p.nombre }))"
                                        allow-clear
                                        placeholder="Sin definir"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Fecha requerida" :help="form.errors.fecha_requerida" :validate-status="form.errors.fecha_requerida ? 'error' : undefined">
                                    <a-input v-model:value="form.fecha_requerida" type="date" />
                                </a-form-item>
                            </a-col>
                        </a-row>

                        <div class="flex gap-2 mt-2">
                            <a-button type="primary" html-type="submit" :loading="form.processing">
                                <template #icon><SendOutlined /></template>
                                Enviar solicitud
                            </a-button>
                            <a-button type="text" @click="router.visit(route('solicitudes.index'))">Cancelar</a-button>
                        </div>
                    </a-form>
                </a-card>
            </a-col>
        </a-row>
    </AppLayout>
</template>
