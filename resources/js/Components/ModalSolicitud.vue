<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { FileAddOutlined } from '@ant-design/icons-vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import SelectObjetivoMantenimiento from '@/Components/SelectObjetivoMantenimiento.vue';
import { mananaISO, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    equipos: { type: Array, default: () => [] },
    ubicaciones: { type: Array, default: () => [] },
    prioridades: { type: Array, default: () => [] },
});

const abierto = ref(false);

const form = useForm({
    equipo_id: undefined,
    ubicacion_id: undefined,
    descripcion: '',
    prioridad_id: undefined,
    fecha_requerida: '',
});

const reglas = {
    descripcion: [{ required: true, message: 'Describe el problema o servicio.' }],
    fecha_requerida: [reglaNoPasada('La fecha requerida debe ser posterior a hoy.', true)],
};

const esInstalacion = computed(() => !!form.ubicacion_id);

const abrir = (equipoId = null) => {
    form.reset();
    form.clearErrors();
    if (equipoId) form.equipo_id = equipoId;
    abierto.value = true;
};

const enviar = () =>
    form.post(route('solicitudes.store'), {
        onSuccess: () => (abierto.value = false),
    });

defineExpose({ abrir });
</script>

<template>
    <a-modal
        v-model:open="abierto"
        :width="560"
        :confirm-loading="form.processing"
        ok-text="Enviar solicitud"
        cancel-text="Cancelar"
        centered
        wrap-class-name="modal-formulario"
        @ok="enviar"
    >
        <template #title>
            <span class="mf-titulo"><FileAddOutlined /> Nueva solicitud de servicio</span>
        </template>
        <a-form :model="form" :rules="reglas" layout="vertical" class="pt-1" @finish="enviar">
            <a-form-item label="Equipo o instalación" :validate-status="form.errors.equipo_id ? 'error' : ''" :help="form.errors.equipo_id">
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
                :validate-status="form.errors.descripcion ? 'error' : ''"
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

            <a-row :gutter="14">
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Prioridad sugerida" :help="form.errors.prioridad_id" :validate-status="form.errors.prioridad_id ? 'error' : ''">
                        <SelectCatalogo
                            v-model:value="form.prioridad_id"
                            :options="prioridades"
                            ruta="catalogos.prioridades"
                            etiqueta="prioridad"
                            etiqueta-plural="prioridades"
                            placeholder="Sin definir"
                            :campos="[{ name: 'nivel', label: 'Nivel (1 = más urgente)', tipo: 'number', min: 1 }]"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Fecha requerida" name="fecha_requerida" :help="form.errors.fecha_requerida" :validate-status="form.errors.fecha_requerida ? 'error' : ''">
                        <CampoFechaHora v-model="form.fecha_requerida" solo-fecha :min-fecha="mananaISO()" />
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
