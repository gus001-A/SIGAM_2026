<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckOutlined,
    CloseOutlined,
    EnvironmentOutlined,
    FileTextOutlined,
    FlagOutlined,
    FormOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDatos from '@/Components/ListaDatos.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    solicitud: { type: Object, required: true },
    puedeConvertir: { type: Boolean, default: false },
    catalogos: { type: Object, default: () => ({}) },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const s = computed(() => props.solicitud);
const terminada = computed(() => s.value.estado?.clave === 'cancelado');
const convertida = computed(() => (s.value.mantenimientos ?? []).length > 0);

const datos = computed(() => [
    s.value.equipo
        ? { icono: ToolOutlined, label: 'Equipo', valor: `${s.value.equipo.codigo_activo} — ${s.value.equipo.descripcion}`, color: '#0d84c9' }
        : { icono: EnvironmentOutlined, label: 'Instalación', valor: s.value.ubicacion?.nombre, color: '#0d84c9' },
    { icono: EnvironmentOutlined, label: 'Sucursal', valor: s.value.sucursal?.nombre, color: '#1f9e86' },
    { icono: UserOutlined, label: 'Solicitante', valor: s.value.solicitante?.nombre, color: '#6b4bc9' },
    { icono: CalendarOutlined, label: 'Fecha de solicitud', valor: fecha(s.value.solicitado_at), color: '#173a5f' },
    { icono: CalendarOutlined, label: 'Fecha requerida', valor: fecha(s.value.fecha_requerida), color: '#e08a1e' },
    { icono: FlagOutlined, label: 'Prioridad sugerida', valor: s.value.prioridad?.nombre, color: '#d64545' },
    { icono: CheckOutlined, label: 'Revisado por', valor: s.value.revisadoPor?.nombre, color: '#1f9e86' },
]);

// --- Autorizar (convertir a orden) --------------------------------
const modalAutorizar = ref(false);
const formAutorizar = useForm({
    tipo_id: undefined,
    prioridad_id: s.value.prioridad_id ?? undefined,
    programado_inicio: '',
    programado_fin: '',
});
const reglasAutorizar = reactive({
    tipo_id: [{ required: true, message: 'Selecciona el tipo.' }],
    prioridad_id: [{ required: true, message: 'Selecciona la prioridad.' }],
});
const autorizar = () => {
    formAutorizar.post(route('solicitudes.autorizar', s.value.id), {
        onSuccess: () => (modalAutorizar.value = false),
    });
};

// --- Rechazar ----------------------------------------------------
const modalRechazar = ref(false);
const formRechazar = useForm({ motivo_rechazo: '' });
const reglasRechazar = reactive({ motivo_rechazo: [{ required: true, message: 'Indica el motivo.' }] });
const rechazar = () => {
    formRechazar.post(route('solicitudes.rechazar', s.value.id), {
        onSuccess: () => (modalRechazar.value = false),
    });
};
</script>

<template>
    <Head :title="`Solicitud ${solicitud.folio}`" />

    <AppLayout>
        <FichaEncabezado
            :titulo="solicitud.folio"
            :subtitulo="solicitud.equipo?.codigo_activo ?? solicitud.ubicacion?.nombre"
            :icono="FormOutlined"
            volver="solicitudes.index"
            :sello="sello"
        >
            <template #tags>
                <a-tag>{{ solicitud.estado?.nombre }}</a-tag>
                <a-tag v-if="solicitud.prioridad" :color="solicitud.prioridad.color || 'default'">
                    {{ solicitud.prioridad.nombre }}
                </a-tag>
            </template>
            <template #acciones>
                <a-button
                    v-if="!terminada && !convertida && puede('solicitudes.editar')"
                    danger
                    @click="modalRechazar = true"
                >
                    <template #icon><CloseOutlined /></template>
                    Rechazar
                </a-button>
                <a-button v-if="puedeConvertir" type="primary" @click="modalAutorizar = true">
                    <template #icon><CheckOutlined /></template>
                    Autorizar y crear orden
                </a-button>
            </template>
        </FichaEncabezado>

        <a-card size="small" class="mb-4 destacado">
            <div class="destacado__objetivo">
                <component :is="solicitud.equipo ? ToolOutlined : EnvironmentOutlined" />
                {{ solicitud.equipo ? `${solicitud.equipo.codigo_activo} — ${solicitud.equipo.descripcion}` : solicitud.ubicacion?.nombre }}
            </div>
            <div class="destacado__desc">{{ solicitud.descripcion }}</div>
        </a-card>

        <a-row :gutter="16">
            <a-col :xs="24" :lg="15">
                <a-card size="small" class="mb-4">
                    <SeccionFicha titulo="Información de la solicitud" :icono="FormOutlined">
                        <ListaDatos :datos="datos" />
                    </SeccionFicha>

                    <a-alert
                        v-if="solicitud.motivo_rechazo"
                        type="error"
                        show-icon
                        :message="`Rechazada: ${solicitud.motivo_rechazo}`"
                        class="mt-2"
                    />
                </a-card>

                <a-card v-if="convertida" size="small" class="mb-4">
                    <SeccionFicha titulo="Órdenes generadas" :icono="ToolOutlined" color="#0d84c9">
                        <div class="ordenes">
                            <button
                                v-for="item in solicitud.mantenimientos"
                                :key="item.id"
                                type="button"
                                class="orden"
                                @click="router.visit(route('mantenimientos.show', item.id))"
                            >
                                <span class="orden__ic"><ToolOutlined /></span>
                                <span class="orden__t">
                                    <span class="orden__folio">{{ item.folio }}</span>
                                    <span class="orden__estado">{{ item.estado?.nombre }}</span>
                                </span>
                            </button>
                        </div>
                    </SeccionFicha>
                </a-card>
            </a-col>

            <a-col :xs="24" :lg="9">
                <a-card size="small">
                    <SeccionFicha titulo="Documentos" :icono="FileTextOutlined" color="#6b4bc9">
                        <ListaDocumentos
                            :documentos="solicitud.documentos ?? []"
                            relacionable-tipo="solicitud"
                            :relacionable-id="solicitud.id"
                            :puede-subir="puede('documentos.crear')"
                            :puede-eliminar="puede('documentos.desactivar')"
                        />
                    </SeccionFicha>
                </a-card>
            </a-col>
        </a-row>

        <!-- Modal autorizar -->
        <a-modal
            v-model:open="modalAutorizar"
            title="Autorizar solicitud y crear orden"
            ok-text="Crear orden"
            cancel-text="Cancelar"
            :confirm-loading="formAutorizar.processing"
            centered
            @ok="autorizar"
        >
            <a-form :model="formAutorizar" :rules="reglasAutorizar" layout="vertical" class="pt-1">
                <a-row :gutter="12">
                    <a-col :span="12">
                        <a-form-item label="Tipo de mantenimiento" name="tipo_id" :validate-status="formAutorizar.errors.tipo_id ? 'error' : undefined" :help="formAutorizar.errors.tipo_id">
                            <a-select v-model:value="formAutorizar.tipo_id" :options="catalogos.tipos?.map((t) => ({ value: t.id, label: t.nombre }))" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Prioridad" name="prioridad_id" :validate-status="formAutorizar.errors.prioridad_id ? 'error' : undefined" :help="formAutorizar.errors.prioridad_id">
                            <a-select v-model:value="formAutorizar.prioridad_id" :options="catalogos.prioridades?.map((p) => ({ value: p.id, label: p.nombre }))" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Programado — inicio">
                            <CampoFechaHora v-model="formAutorizar.programado_inicio" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Programado — fin" :help="formAutorizar.errors.programado_fin" :validate-status="formAutorizar.errors.programado_fin ? 'error' : undefined">
                            <CampoFechaHora v-model="formAutorizar.programado_fin" />
                        </a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>

        <!-- Modal rechazar -->
        <a-modal
            v-model:open="modalRechazar"
            title="Rechazar solicitud"
            ok-text="Rechazar"
            :ok-button-props="{ danger: true }"
            cancel-text="Cancelar"
            :confirm-loading="formRechazar.processing"
            centered
            @ok="rechazar"
        >
            <a-form :model="formRechazar" :rules="reglasRechazar" layout="vertical" class="pt-1">
                <a-form-item label="Motivo del rechazo" name="motivo_rechazo" :validate-status="formRechazar.errors.motivo_rechazo ? 'error' : undefined" :help="formRechazar.errors.motivo_rechazo">
                    <a-textarea v-model:value="formRechazar.motivo_rechazo" :rows="3" />
                </a-form-item>
            </a-form>
        </a-modal>
    </AppLayout>
</template>

<style scoped>
.destacado {
    border-color: #f3c6c6;
    background: #fef4f4;
}
.destacado__objetivo {
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 800;
    font-size: 17px;
    color: #b52222;
    margin-bottom: 6px;
}
.destacado__desc {
    white-space: pre-line;
    text-transform: uppercase;
    font-weight: 700;
    font-size: 15px;
    line-height: 1.5;
    color: #d64545;
}
.ordenes {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.orden {
    display: flex;
    align-items: center;
    gap: 11px;
    width: 100%;
    text-align: left;
    padding: 10px 12px;
    border: 1px solid var(--sigam-borde);
    border-radius: 11px;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.14s ease, box-shadow 0.14s ease, transform 0.14s ease;
}
.orden:hover {
    border-color: var(--sigam-navy-100);
    box-shadow: var(--sigam-sombra-sm);
    transform: translateX(2px);
}
.orden__ic {
    width: 34px;
    height: 34px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f3fb;
    color: #0d6ca6;
    font-size: 15px;
}
.orden__t {
    display: flex;
    flex-direction: column;
}
.orden__folio {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13px;
}
.orden__estado {
    font-size: 12px;
    color: var(--sigam-tenue);
}
</style>
