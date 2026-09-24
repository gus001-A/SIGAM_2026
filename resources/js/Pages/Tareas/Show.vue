<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckSquareOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    DollarOutlined,
    DownOutlined,
    EditOutlined,
    FlagOutlined,
    PlusOutlined,
    SwapOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDatos from '@/Components/ListaDatos.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import { usePermisos } from '@/composables/usePermisos';
import { hoyISO } from '@/utils/restricciones';

const props = defineProps({
    tarea: { type: Object, required: true },
    transicionesPosibles: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const t = computed(() => props.tarea);
const tab = ref('resumen');
const confirmar = ref(null);

const ETIQUETA_ESTADO = { en_proceso: 'Iniciar tarea', realizada: 'Marcar como realizada', cancelada: 'Cancelar tarea' };
const COLOR_ESTADO = { pendiente: 'default', en_proceso: 'processing', realizada: 'success', cancelada: 'error' };
const ETIQUETA_ESTADO_TAG = { pendiente: 'Pendiente', en_proceso: 'En proceso', realizada: 'Realizada', cancelada: 'Cancelada' };

const fecha = (v) => (v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado');
const soloFecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const moneda = (v) => (v == null ? 'No especificado' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v));

const vencida = computed(() => ['pendiente', 'en_proceso'].includes(t.value.estado) && new Date(t.value.fecha_limite) < new Date().setHours(0, 0, 0, 0));
const esFinal = computed(() => ['realizada', 'cancelada'].includes(t.value.estado));

const datos = computed(() => [
    { icono: CalendarOutlined, label: 'Fecha límite', valor: soloFecha(t.value.fecha_limite), color: vencida.value ? '#d64545' : '#173a5f' },
    { icono: FlagOutlined, label: 'Prioridad', valor: t.value.prioridad?.nombre, color: t.value.prioridad?.color || '#d64545' },
    { icono: ClockCircleOutlined, label: 'Iniciada', valor: fecha(t.value.iniciada_at), color: '#e08a1e' },
    { icono: ClockCircleOutlined, label: 'Realizada', valor: fecha(t.value.realizada_at), color: '#1f9e86' },
    { icono: ClockCircleOutlined, label: 'Cancelada', valor: fecha(t.value.cancelada_at), color: '#d64545' },
    { icono: DollarOutlined, label: 'Costo', valor: t.value.costo != null ? moneda(t.value.costo) : null, color: '#6b4bc9' },
]);

const responsablesActivos = computed(() => (t.value.asignaciones ?? []).filter((a) => !a.desasignado_at));
const responsablesInactivos = computed(() => (t.value.asignaciones ?? []).filter((a) => a.desasignado_at));

// --- Cambiar estado -------------------------------------------
const modalEstado = ref(false);
const formEstado = useForm({ estado: null, nota: '', costo: '' });
const abrirEstado = (slug) => {
    formEstado.reset();
    formEstado.estado = slug;
    modalEstado.value = true;
};
const confirmarEstado = () => {
    formEstado.post(route('tareas.transicion', t.value.id), {
        preserveScroll: true,
        onSuccess: () => (modalEstado.value = false),
    });
};
const menuTransiciones = computed(() =>
    props.transicionesPosibles.map((slug) => ({ key: slug, label: ETIQUETA_ESTADO[slug] ?? slug })),
);

// --- Responsables -----------------------------------------
const modalResponsable = ref(false);
const formResponsable = useForm({ usuario_id: undefined, es_principal: false, notas: '' });
const usuariosDisponibles = computed(() => {
    const activos = new Set(responsablesActivos.value.map((a) => a.usuario_id));
    return (props.catalogos.usuarios ?? []).filter((u) => !activos.has(u.id));
});
const asignarResponsable = () => {
    formResponsable.post(route('tareas.responsables.store', t.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalResponsable.value = false;
            formResponsable.reset();
        },
    });
};
const quitarResponsable = async (asig) => {
    const ok = await confirmar.value.abrir({ titulo: `Retirar a ${asig.usuario?.nombre}`, confirmar: 'Retirar', peligro: true });
    if (ok) router.delete(route('tareas.responsables.destroy', [t.value.id, asig.id]), { preserveScroll: true });
};

// --- Editar ------------------------------------------------
const modalEditar = ref(false);
const formEditar = useForm({ titulo: '', descripcion: '', fecha_limite: '', prioridad_id: undefined });
const abrirEditar = () => {
    formEditar.reset();
    formEditar.clearErrors();
    formEditar.titulo = t.value.titulo;
    formEditar.descripcion = t.value.descripcion;
    formEditar.fecha_limite = t.value.fecha_limite?.slice(0, 10) ?? '';
    formEditar.prioridad_id = t.value.prioridad_id ?? undefined;
    modalEditar.value = true;
};
const guardarEdicion = () => {
    formEditar.transform((datos) => ({ ...datos, _method: 'put' })).post(route('tareas.update', t.value.id), {
        preserveScroll: true,
        onSuccess: () => (modalEditar.value = false),
    });
};

// --- Eliminar ------------------------------------------------
const eliminar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: 'Eliminar tarea',
        mensaje: 'La tarea se quitará de los listados; su historial se conserva.',
        confirmar: 'Eliminar',
        peligro: true,
    });
    if (ok) router.delete(route('tareas.destroy', t.value.id));
};
</script>

<template>
    <Head title="Tarea" />

    <AppLayout>
        <FichaEncabezado
            :titulo="t.titulo"
            :subtitulo="`Fecha límite: ${soloFecha(t.fecha_limite)}`"
            :icono="CheckSquareOutlined"
            volver="tareas.index"
            :sello="sello"
        >
            <template #tags>
                <a-tag :color="COLOR_ESTADO[t.estado]">{{ ETIQUETA_ESTADO_TAG[t.estado] }}</a-tag>
                <a-tag v-if="t.prioridad" :color="t.prioridad.color || 'default'">{{ t.prioridad.nombre }}</a-tag>
                <a-tag v-if="vencida" color="error">Vencida</a-tag>
            </template>
            <template #acciones>
                <a-button v-if="!esFinal && puede('tareas.editar')" @click="abrirEditar">
                    <template #icon><EditOutlined /></template>
                    Editar
                </a-button>
                <a-dropdown v-if="menuTransiciones.length && puede('tareas.editar')">
                    <a-button type="primary">
                        <SwapOutlined /> Cambiar estado <DownOutlined />
                    </a-button>
                    <template #overlay>
                        <a-menu :items="menuTransiciones" @click="({ key }) => abrirEstado(key)" />
                    </template>
                </a-dropdown>
                <a-tooltip v-if="!esFinal && puede('tareas.desactivar')" title="Solo se puede eliminar una vez que la tarea esté realizada o cancelada.">
                    <a-button danger disabled>
                        <template #icon><DeleteOutlined /></template>
                        Eliminar
                    </a-button>
                </a-tooltip>
                <a-button v-else-if="esFinal && puede('tareas.desactivar')" danger @click="eliminar">
                    <template #icon><DeleteOutlined /></template>
                    Eliminar
                </a-button>
            </template>
        </FichaEncabezado>

        <a-card size="small" class="mb-4 tarea-info">
            <div class="tarea-info__desc">{{ t.descripcion }}</div>
        </a-card>

        <a-card :body-style="{ padding: 0 }">
            <a-tabs v-model:activeKey="tab" class="px-4 pt-2">
                <a-tab-pane key="resumen" tab="Resumen">
                    <div class="pb-4">
                        <SeccionFicha titulo="Información de la tarea" :icono="CheckSquareOutlined">
                            <ListaDatos :datos="datos" />
                        </SeccionFicha>

                        <a-alert v-if="t.nota_avance" type="info" show-icon class="mb-2" :message="`Avance: ${t.nota_avance}`" />
                        <a-alert v-if="t.nota_cierre" type="success" show-icon class="mb-2" :message="`Cierre: ${t.nota_cierre}`" />
                        <a-alert v-if="t.nota_cancelacion" type="error" show-icon :message="`Cancelación: ${t.nota_cancelacion}`" />
                    </div>
                </a-tab-pane>

                <a-tab-pane key="responsables" :tab="`Responsables (${responsablesActivos.length})`">
                    <div class="p-4">
                        <div class="flex justify-end mb-3">
                            <a-button v-if="puede('tareas.asignar')" type="primary" ghost @click="modalResponsable = true">
                                <template #icon><PlusOutlined /></template>
                                Asignar responsable
                            </a-button>
                        </div>
                        <a-list :data-source="responsablesActivos" :locale="{ emptyText: 'Sin responsables asignados' }">
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta :title="item.usuario?.nombre" :description="`Asignado por ${item.asignado_por?.nombre ?? 'No especificado'} · ${soloFecha(item.asignado_at)}`" />
                                    <template #actions>
                                        <a-tag v-if="item.es_principal" color="blue">Principal</a-tag>
                                        <a v-if="puede('tareas.asignar')" class="text-red-500" @click="quitarResponsable(item)"><DeleteOutlined /></a>
                                    </template>
                                </a-list-item>
                            </template>
                        </a-list>

                        <template v-if="responsablesInactivos.length">
                            <div class="font-semibold mt-4 mb-2 text-xs uppercase opacity-60">Retirados</div>
                            <a-list :data-source="responsablesInactivos" size="small">
                                <template #renderItem="{ item }">
                                    <a-list-item>
                                        <a-list-item-meta :title="item.usuario?.nombre" :description="`Retirado el ${soloFecha(item.desasignado_at)}`" />
                                    </a-list-item>
                                </template>
                            </a-list>
                        </template>
                    </div>
                </a-tab-pane>

                <a-tab-pane key="historial" tab="Historial de estados">
                    <div class="p-4">
                        <a-timeline>
                            <a-timeline-item v-for="hh in t.historial_estados" :key="hh.id" color="blue">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <strong>{{ ETIQUETA_ESTADO_TAG[hh.estado_destino] ?? hh.estado_destino }}</strong>
                                        <span v-if="hh.estado_origen" class="opacity-60"> (desde {{ ETIQUETA_ESTADO_TAG[hh.estado_origen] ?? hh.estado_origen }})</span>
                                        <div v-if="hh.nota" class="text-sm">{{ hh.nota }}</div>
                                        <div class="text-xs opacity-60">{{ hh.cambiado_por?.nombre ?? 'Sistema' }}</div>
                                    </div>
                                    <div class="text-xs whitespace-nowrap">{{ fecha(hh.cambiado_at) }}</div>
                                </div>
                            </a-timeline-item>
                        </a-timeline>
                    </div>
                </a-tab-pane>

                <a-tab-pane key="documentos" :tab="`Documentos (${t.documentos?.length ?? 0})`">
                    <div class="p-4">
                        <ListaDocumentos
                            :documentos="t.documentos ?? []"
                            relacionable-tipo="tarea"
                            :relacionable-id="t.id"
                            :roles="['evidencia', 'foto']"
                            :puede-subir="puede('documentos.crear')"
                            :puede-eliminar="puede('documentos.desactivar')"
                        />
                    </div>
                </a-tab-pane>
            </a-tabs>
        </a-card>

        <!-- Modal cambiar estado -->
        <a-modal
            v-model:open="modalEstado"
            :title="ETIQUETA_ESTADO[formEstado.estado] || 'Cambiar estado'"
            :ok-text="formEstado.estado === 'cancelada' ? 'Cancelar tarea' : 'Confirmar'"
            :ok-button-props="{ danger: formEstado.estado === 'cancelada' }"
            cancel-text="Cerrar"
            :confirm-loading="formEstado.processing"
            @ok="confirmarEstado"
        >
            <a-form layout="vertical" class="pt-2">
                <a-form-item
                    :label="formEstado.estado === 'realizada' ? 'Nota de cierre' : (formEstado.estado === 'cancelada' ? 'Motivo de la cancelación' : 'Nota de avance (opcional)')"
                    :required="formEstado.estado !== 'en_proceso'"
                    :validate-status="formEstado.errors.nota ? 'error' : undefined"
                    :help="formEstado.errors.nota"
                >
                    <a-textarea v-model:value="formEstado.nota" :rows="3" />
                </a-form-item>
                <a-form-item v-if="formEstado.estado === 'realizada'" label="Costo (opcional)" :validate-status="formEstado.errors.costo ? 'error' : undefined" :help="formEstado.errors.costo">
                    <a-input v-model:value="formEstado.costo" type="number" prefix="$" />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- Modal editar -->
        <a-modal v-model:open="modalEditar" title="Editar tarea" ok-text="Guardar cambios" cancel-text="Cancelar" :confirm-loading="formEditar.processing" @ok="guardarEdicion">
            <a-form layout="vertical" class="pt-2">
                <a-form-item label="Título" :validate-status="formEditar.errors.titulo ? 'error' : undefined" :help="formEditar.errors.titulo">
                    <a-input v-model:value="formEditar.titulo" :maxlength="150" show-count />
                </a-form-item>
                <a-form-item label="Descripción" :validate-status="formEditar.errors.descripcion ? 'error' : undefined" :help="formEditar.errors.descripcion">
                    <a-textarea v-model:value="formEditar.descripcion" :rows="4" />
                </a-form-item>
                <a-row :gutter="12">
                    <a-col :span="12">
                        <a-form-item label="Fecha límite" :validate-status="formEditar.errors.fecha_limite ? 'error' : undefined" :help="formEditar.errors.fecha_limite">
                            <CampoFechaHora v-model="formEditar.fecha_limite" solo-fecha :min-fecha="hoyISO()" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Prioridad" :validate-status="formEditar.errors.prioridad_id ? 'error' : undefined" :help="formEditar.errors.prioridad_id">
                            <a-select
                                v-model:value="formEditar.prioridad_id"
                                :options="(catalogos.prioridades ?? []).map((p) => ({ value: p.id, label: p.nombre }))"
                                allow-clear
                                placeholder="Sin definir"
                            />
                        </a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>

        <!-- Modal asignar responsable -->
        <a-modal v-model:open="modalResponsable" title="Asignar responsable" ok-text="Asignar" cancel-text="Cancelar" :confirm-loading="formResponsable.processing" @ok="asignarResponsable">
            <a-form layout="vertical" class="pt-2">
                <a-form-item label="Usuario" :validate-status="formResponsable.errors.usuario_id ? 'error' : undefined" :help="formResponsable.errors.usuario_id">
                    <a-select v-model:value="formResponsable.usuario_id">
                        <a-select-option v-for="u in usuariosDisponibles" :key="u.id" :value="u.id">
                            {{ u.nombre }} {{ u.apellidos }}
                        </a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item>
                    <a-checkbox v-model:checked="formResponsable.es_principal">Responsable principal</a-checkbox>
                </a-form-item>
                <a-form-item label="Notas">
                    <a-input v-model:value="formResponsable.notas" />
                </a-form-item>
            </a-form>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
.tarea-info :deep(.ant-card-body) {
    padding: 0;
}
.tarea-info__desc {
    padding: 16px 18px;
    font-size: 15px;
    line-height: 1.6;
    color: var(--sigam-texto);
    white-space: pre-line;
}
</style>
