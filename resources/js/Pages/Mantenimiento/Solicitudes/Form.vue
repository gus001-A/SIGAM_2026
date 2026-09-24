<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { SendOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectObjetivoMantenimiento from '@/Components/SelectObjetivoMantenimiento.vue';
import { mananaISO, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    equipos: { type: Array, default: () => [] },
    ubicaciones: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
    preseleccion: { type: Object, default: () => ({}) },
});

const form = useForm({
    equipo_id: props.preseleccion?.equipo_id ?? undefined,
    ubicacion_id: props.preseleccion?.ubicacion_id ?? undefined,
    descripcion: '',
    prioridad_id: undefined,
    fecha_requerida: '',
});

const reglas = reactive({
    descripcion: [{ required: true, message: 'Describe el problema o servicio.' }],
    fecha_requerida: [reglaNoPasada('La fecha requerida debe ser posterior a hoy.', true)],
});

// Una instalación no siempre tiene una "falla" — puede ser un servicio general
// del área (pintar, resanar, instalar cableado), así que el placeholder cambia.
const esInstalacion = computed(() => !!form.ubicacion_id);

const enviar = () => form.post(route('solicitudes.store'));
</script>

<template>
    <Head title="Nueva solicitud" />

    <AppLayout
        titulo="Nueva solicitud de servicio"
        descripcion="Describe la falla o el servicio que necesitas y el equipo o área afectada; un supervisor la autorizará."
    >
        <a-row justify="center">
            <a-col :xs="24" :md="16" :lg="12">
                <a-card>
                    <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
                        <a-form-item label="Equipo o instalación" :validate-status="form.errors.equipo_id ? 'error' : undefined" :help="form.errors.equipo_id">
                            <SelectObjetivoMantenimiento
                                v-model:equipo-id="form.equipo_id"
                                v-model:ubicacion-id="form.ubicacion_id"
                                :equipos="equipos"
                                :ubicaciones="ubicaciones"
                            />
                        </a-form-item>

                        <a-form-item
                            label="Descripción"
                            name="descripcion"
                            :validate-status="form.errors.descripcion ? 'error' : undefined"
                            :help="form.errors.descripcion"
                        >
                            <a-textarea
                                v-model:value="form.descripcion"
                                :rows="4"
                                :placeholder="esInstalacion
                                    ? 'Describe el servicio que necesitas (p. ej. pintar paredes, resanar, instalar cableado)'
                                    : '¿Qué falla presenta el equipo?'"
                            />
                        </a-form-item>

                        <a-row :gutter="16">
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    label="Prioridad sugerida"
                                    extra="Un supervisor puede cambiarla al autorizar."
                                    :help="form.errors.prioridad_id"
                                    :validate-status="form.errors.prioridad_id ? 'error' : undefined"
                                >
                                    <a-select
                                        v-model:value="form.prioridad_id"
                                        :options="prioridades.map((p) => ({ value: p.id, label: p.nombre }))"
                                        allow-clear
                                        placeholder="Sin definir"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    label="Fecha requerida"
                                    name="fecha_requerida"
                                    extra="Opcional — para cuándo se necesita el trabajo."
                                    :help="form.errors.fecha_requerida"
                                    :validate-status="form.errors.fecha_requerida ? 'error' : undefined"
                                >
                                    <CampoFechaHora v-model="form.fecha_requerida" solo-fecha :min-fecha="mananaISO()" />
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
