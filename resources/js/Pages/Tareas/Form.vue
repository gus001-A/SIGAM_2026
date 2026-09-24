<script setup>
import { reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { SendOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import { hoyISO, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    usuarios: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
});

const form = useForm({
    titulo: '',
    descripcion: '',
    fecha_limite: '',
    prioridad_id: undefined,
    responsables: [],
});

const reglas = reactive({
    titulo: [{ required: true, message: 'Dale un título corto a la tarea.' }, { max: 150, message: 'Máximo 150 caracteres.' }],
    descripcion: [{ required: true, message: 'Describe la tarea.' }],
    fecha_limite: [{ required: true, message: 'Indica la fecha límite.' }, reglaNoPasada('La fecha límite no puede ser anterior a hoy.')],
    responsables: [{ required: true, type: 'array', min: 1, message: 'Asigna al menos un responsable.' }],
});

const opcionesUsuarios = props.usuarios.map((u) => ({
    value: u.id,
    label: `${u.nombre} ${u.apellidos ?? ''}`.trim(),
}));

const enviar = () => form.post(route('tareas.store'));
</script>

<template>
    <Head title="Nueva tarea" />

    <AppLayout
        titulo="Nueva tarea"
        descripcion="Describe la tarea, su fecha límite y a quién se le asigna. Una vez creada, pasa a Pendiente."
    >
        <a-row justify="center">
            <a-col :xs="24" :md="16" :lg="12">
                <a-card>
                    <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
                        <a-form-item
                            label="Título"
                            name="titulo"
                            :validate-status="form.errors.titulo ? 'error' : undefined"
                            :help="form.errors.titulo"
                        >
                            <a-input v-model:value="form.titulo" placeholder="p. ej. Revisar extintores del área de urgencias" :maxlength="150" show-count />
                        </a-form-item>

                        <a-form-item
                            label="Descripción"
                            name="descripcion"
                            :validate-status="form.errors.descripcion ? 'error' : undefined"
                            :help="form.errors.descripcion"
                        >
                            <a-textarea v-model:value="form.descripcion" :rows="4" placeholder="¿Qué hay que hacer?" />
                        </a-form-item>

                        <a-row :gutter="16">
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    label="Fecha límite"
                                    name="fecha_limite"
                                    :help="form.errors.fecha_limite"
                                    :validate-status="form.errors.fecha_limite ? 'error' : undefined"
                                >
                                    <CampoFechaHora v-model="form.fecha_limite" solo-fecha :min-fecha="hoyISO()" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Prioridad" :help="form.errors.prioridad_id" :validate-status="form.errors.prioridad_id ? 'error' : undefined">
                                    <a-select
                                        v-model:value="form.prioridad_id"
                                        :options="prioridades.map((p) => ({ value: p.id, label: p.nombre }))"
                                        allow-clear
                                        placeholder="Sin definir"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item
                                    label="Responsable(s)"
                                    name="responsables"
                                    :help="form.errors.responsables"
                                    :validate-status="form.errors.responsables ? 'error' : undefined"
                                >
                                    <a-select
                                        v-model:value="form.responsables"
                                        mode="multiple"
                                        :options="opcionesUsuarios"
                                        placeholder="Selecciona uno o más"
                                    />
                                </a-form-item>
                            </a-col>
                        </a-row>

                        <div class="flex gap-2 mt-2">
                            <a-button type="primary" html-type="submit" :loading="form.processing">
                                <template #icon><SendOutlined /></template>
                                Registrar tarea
                            </a-button>
                            <a-button type="text" @click="router.visit(route('tareas.index'))">Cancelar</a-button>
                        </div>
                    </a-form>
                </a-card>
            </a-col>
        </a-row>
    </AppLayout>
</template>
