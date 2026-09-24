<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { FileAddOutlined } from '@ant-design/icons-vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import { hoyISO, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    usuarios: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
});

const abierto = ref(false);

const form = useForm({
    titulo: '',
    descripcion: '',
    fecha_limite: '',
    prioridad_id: undefined,
    responsables: [],
});

const reglas = {
    titulo: [{ required: true, message: 'Dale un título corto a la tarea.' }, { max: 150, message: 'Máximo 150 caracteres.' }],
    descripcion: [{ required: true, message: 'Describe la tarea.' }],
    fecha_limite: [{ required: true, message: 'Indica la fecha límite.' }, reglaNoPasada('La fecha límite no puede ser anterior a hoy.')],
    responsables: [{ required: true, type: 'array', min: 1, message: 'Asigna al menos un responsable.' }],
};

const opcionesUsuarios = () => props.usuarios.map((u) => ({
    value: u.id,
    label: `${u.nombre} ${u.apellidos ?? ''}`.trim(),
}));

const abrir = () => {
    form.reset();
    form.clearErrors();
    abierto.value = true;
};

const enviar = () =>
    form.post(route('tareas.store'), {
        onSuccess: () => (abierto.value = false),
    });

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        :width="560"
        :confirm-loading="form.processing"
        ok-text="Registrar tarea"
        cancel-text="Cancelar"
        centered
        wrap-class-name="modal-formulario"
        @ok="enviar"
    >
        <template #title>
            <span class="mf-titulo"><FileAddOutlined /> Nueva tarea</span>
        </template>
        <a-form :model="form" :rules="reglas" layout="vertical" class="pt-1" @finish="enviar">
            <a-form-item
                label="Título"
                name="titulo"
                :validate-status="form.errors.titulo ? 'error' : ''"
                :help="form.errors.titulo"
            >
                <a-input v-model:value="form.titulo" placeholder="p. ej. Revisar extintores del área de urgencias" :maxlength="150" show-count />
            </a-form-item>

            <a-form-item
                label="Descripción"
                name="descripcion"
                :validate-status="form.errors.descripcion ? 'error' : ''"
                :help="form.errors.descripcion"
            >
                <a-textarea v-model:value="form.descripcion" :rows="4" placeholder="¿Qué hay que hacer?" />
            </a-form-item>

            <a-row :gutter="14">
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Fecha límite" name="fecha_limite" :help="form.errors.fecha_limite" :validate-status="form.errors.fecha_limite ? 'error' : ''">
                        <CampoFechaHora v-model="form.fecha_limite" solo-fecha :min-fecha="hoyISO()" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Prioridad" :help="form.errors.prioridad_id" :validate-status="form.errors.prioridad_id ? 'error' : ''">
                        <a-select
                            v-model:value="form.prioridad_id"
                            :options="prioridades.map((p) => ({ value: p.id, label: p.nombre }))"
                            allow-clear
                            placeholder="Sin definir"
                        />
                    </a-form-item>
                </a-col>
                <a-col :span="24">
                    <a-form-item label="Responsable(s)" :help="form.errors.responsables" :validate-status="form.errors.responsables ? 'error' : ''">
                        <a-select v-model:value="form.responsables" mode="multiple" :options="opcionesUsuarios()" placeholder="Selecciona uno o más" />
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
    </a-modal>
</template>

<style scoped>
.mf-titulo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.mf-titulo .anticon {
    color: var(--sigam-teal);
}
</style>
