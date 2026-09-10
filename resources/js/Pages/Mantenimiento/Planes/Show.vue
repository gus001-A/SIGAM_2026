<script setup>
import { computed, h, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowLeftOutlined,
    CalendarOutlined,
    DeleteOutlined,
    EditOutlined,
    PlusOutlined,
    ThunderboltOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    plan: { type: Object, required: true },
    ocurrencias: { type: Array, default: () => [] },
});

const { puede } = usePermisos();
const confirmar = ref(null);

const inactivo = computed(() => props.plan.estado !== 'activo');
const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');

const etiquetaFrecuencia = (f) =>
    ({
        dias: 'Cada N días', semanal: 'Semanal', mensual: 'Mensual', bimestral: 'Bimestral',
        trimestral: 'Trimestral', semestral: 'Semestral', anual: 'Anual', personalizada: 'Personalizada',
    })[f] ?? f;

const datos = computed(() => [
    ['Equipo', props.plan.equipo ? `${props.plan.equipo.codigo_activo} · ${props.plan.equipo.descripcion}` : 'No especificado'],
    ['Tipo de mantenimiento', props.plan.tipo?.nombre],
    ['Frecuencia', `${etiquetaFrecuencia(props.plan.tipo_frecuencia)} (${props.plan.valor_frecuencia})`],
    ['Fecha de inicio', fecha(props.plan.fecha_inicio)],
    ['Próxima fecha', fecha(props.plan.proxima_fecha)],
    ['Días de aviso', props.plan.dias_aviso_anticipado],
    ['Norma', props.plan.norma?.codigo],
    ['Formato', props.plan.formato?.nombre],
    ['Técnico sugerido', props.plan.tecnico?.nombre],
]);

const colorOcurrencia = (estado) =>
    ({ pendiente: 'blue', generada: 'green', omitida: 'default' })[estado] ?? 'default';

const irA = (n, p) => router.visit(route(n, p));

// --- Generar ocurrencias ------------------------------------------
const modalGenerar = reactive({ abierto: false, cantidad: 6, procesando: false });
const generarOcurrencias = () => {
    modalGenerar.procesando = true;
    router.post(route('planes.ocurrencias.generar', props.plan.id), { cantidad: modalGenerar.cantidad }, {
        preserveScroll: true,
        onFinish: () => { modalGenerar.procesando = false; modalGenerar.abierto = false; },
    });
};

const generarOrden = (ocurrencia) => {
    router.post(route('planes.orden.generar', props.plan.id), { ocurrencia_id: ocurrencia.id }, { preserveScroll: true });
};

const desactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: 'Desactivar plan preventivo',
        mensaje: 'El plan dejará de generar avisos. Las órdenes ya generadas se conservan.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('planes.destroy', props.plan.id));
};

const menuAcciones = [{ key: 'baja', label: 'Desactivar plan', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && desactivar();
</script>

<template>
    <Head :title="plan.nombre || 'Plan preventivo'" />

    <AppLayout>
        <FichaEncabezado
            :titulo="plan.nombre || 'Plan preventivo'"
            :subtitulo="`${plan.equipo?.codigo_activo ?? ''} · ${plan.equipo?.descripcion ?? ''}`"
            :icono="CalendarOutlined"
            volver="planes.index"
        >
            <template #tags>
                <a-tag :color="inactivo ? 'default' : 'green'">{{ inactivo ? 'Inactivo' : 'Activo' }}</a-tag>
                <a-tag v-if="plan.proxima_fecha && new Date(plan.proxima_fecha) < new Date()" color="error">Vencido</a-tag>
            </template>
            <template v-if="!inactivo" #acciones>
                <a-button v-if="puede('mantenimientos.editar')" @click="modalGenerar.abierto = true">
                    <template #icon><CalendarOutlined /></template>
                    Generar ocurrencias
                </a-button>
                <a-button v-if="puede('mantenimientos.editar')" type="primary" @click="irA('planes.edit', plan.id)">
                    <template #icon><EditOutlined /></template>
                    Editar
                </a-button>
                <a-dropdown v-if="puede('mantenimientos.editar')">
                    <a-button type="text"><template #icon><DeleteOutlined /></template></a-button>
                    <template #overlay>
                        <a-menu :items="menuAcciones" @click="onMenuAccion" />
                    </template>
                </a-dropdown>
            </template>
        </FichaEncabezado>

        <a-row :gutter="16">
            <a-col :xs="24" :md="10">
                <a-card title="Configuración del plan" size="small">
                    <a-descriptions bordered :column="1" size="small">
                        <a-descriptions-item v-for="[k, v] in datos" :key="k" :label="k">{{ v || 'No especificado' }}</a-descriptions-item>
                    </a-descriptions>
                </a-card>
            </a-col>

            <a-col :xs="24" :md="14">
                <a-card size="small">
                    <template #title>Ocurrencias programadas ({{ ocurrencias.length }})</template>

                    <a-timeline v-if="ocurrencias.length" class="mt-2">
                        <a-timeline-item
                            v-for="o in ocurrencias"
                            :key="o.id"
                            :color="colorOcurrencia(o.estado)"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <strong>{{ fecha(o.fecha_programada) }}</strong>
                                    <a-tag class="ml-2" :color="colorOcurrencia(o.estado)">{{ o.estado }}</a-tag>
                                    <div v-if="o.mantenimiento" class="text-xs opacity-60">
                                        Orden
                                        <a @click="irA('mantenimientos.show', o.mantenimiento.id)">{{ o.mantenimiento.folio }}</a>
                                    </div>
                                </div>
                                <a-button
                                    v-if="o.estado === 'pendiente' && !o.mantenimiento_id && puede('mantenimientos.crear')"
                                    size="small"
                                    type="primary"
                                    ghost
                                    @click="generarOrden(o)"
                                >
                                    <template #icon><ThunderboltOutlined /></template>
                                    Generar orden
                                </a-button>
                            </div>
                        </a-timeline-item>
                    </a-timeline>

                    <a-empty v-else description="Aún no se han generado ocurrencias para este plan">
                        <a-button v-if="puede('mantenimientos.editar')" type="primary" @click="modalGenerar.abierto = true">
                            <template #icon><PlusOutlined /></template>
                            Generar ahora
                        </a-button>
                    </a-empty>
                </a-card>
            </a-col>
        </a-row>

        <a-modal
            v-model:open="modalGenerar.abierto"
            title="Generar ocurrencias calendarizadas"
            :confirm-loading="modalGenerar.procesando"
            ok-text="Generar"
            cancel-text="Cancelar"
            @ok="generarOcurrencias"
        >
            <p class="opacity-70">
                Se crearán las próximas fechas según la frecuencia del plan
                ({{ etiquetaFrecuencia(plan.tipo_frecuencia) }}, cada {{ plan.valor_frecuencia }}).
            </p>
            <a-form-item label="¿Cuántas ocurrencias generar?">
                <a-input-number v-model:value="modalGenerar.cantidad" :min="1" :max="36" style="width: 100%" />
            </a-form-item>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>
