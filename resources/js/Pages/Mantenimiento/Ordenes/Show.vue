<script setup>
import { computed, h, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CalendarOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    DownOutlined,
    EllipsisOutlined,
    EnvironmentOutlined,
    FileDoneOutlined,
    FlagOutlined,
    PlusOutlined,
    SaveOutlined,
    StarFilled,
    SwapOutlined,
    TagOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDatos from '@/Components/ListaDatos.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import { usePermisos } from '@/composables/usePermisos';
import { hoyISO, reglaDespuesDe, reglaNoPasada } from '@/utils/restricciones';

const props = defineProps({
    mantenimiento: { type: Object, required: true },
    transicionesPosibles: { type: Array, default: () => [] },
    faltantesCierre: { type: Array, default: () => [] },
    checklistCierre: { type: Array, default: () => [] },
    catalogos: { type: Object, default: () => ({}) },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const m = computed(() => props.mantenimiento);
const tab = ref('resumen');
const confirmar = ref(null);

const ETIQUETA_ESTADO = {
    autorizado: 'Autorizar', asignado: 'Marcar asignado', en_proceso: 'Iniciar trabajo',
    en_espera_refaccion: 'En espera de refacción', fuera_de_servicio: 'Equipo fuera de servicio',
    realizado: 'Marcar realizado', supervisado: 'Supervisar', cerrado: 'Cerrar orden',
    reprogramado: 'Reprogramar', cancelado: 'Cancelar orden',
};

const fecha = (v) => (v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado');
const soloFecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const moneda = (v) => (v == null ? 'No especificado' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v));

const cronologia = computed(() => [
    { icono: CalendarOutlined, label: 'Programado inicio', valor: fecha(m.value.programado_inicio), color: '#0d84c9' },
    { icono: CalendarOutlined, label: 'Programado fin', valor: fecha(m.value.programado_fin), color: '#0d84c9' },
    { icono: ClockCircleOutlined, label: 'Autorizado', valor: fecha(m.value.autorizado_at), color: '#6b4bc9' },
    { icono: ClockCircleOutlined, label: 'Iniciado', valor: fecha(m.value.iniciado_at), color: '#e08a1e' },
    { icono: ClockCircleOutlined, label: 'Completado', valor: fecha(m.value.completado_at), color: '#1f9e86' },
    { icono: ClockCircleOutlined, label: 'Supervisado', valor: fecha(m.value.supervisado_at), color: '#1f9e86' },
    { icono: ClockCircleOutlined, label: 'Cerrado', valor: fecha(m.value.cerrado_at), color: '#173a5f' },
]);

const datos = computed(() => [
    m.value.equipo
        ? { icono: ToolOutlined, label: 'Equipo', valor: `${m.value.equipo.codigo_activo} — ${m.value.equipo.descripcion}`, color: '#0d84c9' }
        : { icono: EnvironmentOutlined, label: 'Instalación', valor: m.value.ubicacion?.nombre, color: '#0d84c9' },
    { icono: EnvironmentOutlined, label: 'Sucursal', valor: m.value.sucursal?.nombre, color: '#1f9e86' },
    { icono: TagOutlined, label: 'Tipo', valor: m.value.tipo?.nombre, color: '#6b4bc9' },
    { icono: FileDoneOutlined, label: 'Solicitud origen', valor: m.value.solicitud?.folio, color: '#e08a1e' },
    { icono: CalendarOutlined, label: 'Plan preventivo', valor: m.value.plan?.nombre, color: '#1f9e86' },
    { icono: UserOutlined, label: 'Creado por', valor: m.value.creadoPor?.nombre, color: '#173a5f' },
    { icono: UserOutlined, label: 'Autorizado por', valor: m.value.autorizadoPor?.nombre, color: '#173a5f' },
    { icono: UserOutlined, label: 'Supervisor', valor: m.value.supervisor?.nombre, color: '#173a5f' },
]);

const tecnicosActivos = computed(() => (m.value.asignaciones ?? []).filter((a) => !a.desasignado_at));

const infoRelevante = computed(() => [
    { icono: FlagOutlined, label: 'Prioridad', valor: m.value.prioridad?.nombre, color: m.value.prioridad?.color || '#d64545' },
    { icono: CalendarOutlined, label: 'Programado', valor: fecha(m.value.programado_inicio), color: '#0d84c9' },
    { icono: UserOutlined, label: 'Técnico(s)', valor: tecnicosActivos.value.map((a) => a.tecnico?.nombre).filter(Boolean).join(', '), color: '#1f9e86' },
    { icono: ApartmentOutlined, label: 'Sucursal', valor: m.value.sucursal?.nombre, color: '#6b4bc9' },
]);

// --- Cambiar estado -------------------------------------------
const modalEstado = ref(false);
const formEstado = useForm({ estado: null, nota: '' });
const abrirEstado = (slug) => {
    formEstado.reset();
    formEstado.estado = slug;
    modalEstado.value = true;
};
const confirmarEstado = () => {
    formEstado.post(route('mantenimientos.transicion', m.value.id), {
        preserveScroll: true,
        onSuccess: () => (modalEstado.value = false),
    });
};

// --- Reprogramar ----------------------------------------------
const modalReprogramar = ref(false);
const formReprogramar = useForm({ programado_inicio: '', programado_fin: '', motivo: '' });
const reglasReprogramar = reactive({
    programado_inicio: [{ required: true, message: 'Indica la nueva fecha.' }, reglaNoPasada('La nueva fecha no puede ser anterior a hoy.')],
    programado_fin: [reglaDespuesDe(() => formReprogramar.programado_inicio, 'El fin debe ser posterior al inicio.', true)],
    motivo: [{ required: true, message: 'Indica el motivo.' }],
});
const reprogramar = () => {
    formReprogramar.post(route('mantenimientos.reprogramar', m.value.id), {
        preserveScroll: true,
        onSuccess: () => (modalReprogramar.value = false),
    });
};

// --- Registrar trabajo --------------------------------------
const formTrabajo = useForm({
    diagnostico: m.value.diagnostico ?? '',
    descripcion_trabajo: m.value.descripcion_trabajo ?? '',
    observaciones: m.value.observaciones ?? '',
    condicion_final: m.value.condicion_final ?? '',
    costo_mano_obra: m.value.costo_mano_obra ?? '',
    costo_otros: m.value.costo_otros ?? '',
});
const guardarTrabajo = () =>
    formTrabajo.put(route('mantenimientos.update', m.value.id), { preserveScroll: true });

// --- Asignar técnico --------------------------------------
const modalTecnico = ref(false);
const formTecnico = useForm({ tecnico_id: undefined, es_principal: false, notas: '' });
const tecnicoSeleccionado = computed(() =>
    (props.catalogos.tecnicos ?? []).find((t) => t.id === formTecnico.tecnico_id),
);
const asignarTecnico = () => {
    formTecnico.post(route('mantenimientos.asignaciones.store', m.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalTecnico.value = false;
            formTecnico.reset();
        },
    });
};
const quitarTecnico = async (asig) => {
    const ok = await confirmar.value.abrir({ titulo: `Retirar a ${asig.tecnico?.nombre}`, confirmar: 'Retirar', peligro: true });
    if (ok) router.delete(route('mantenimientos.asignaciones.destroy', [m.value.id, asig.id]), { preserveScroll: true });
};

// --- Materiales -------------------------------------------
const modalMaterial = ref(false);
const formMaterial = useForm({ material_id: undefined, descripcion: '', cantidad: 1, unidad: 'pza', costo_unitario: '', notas: '' });
const reglasMaterial = reactive({ cantidad: [{ required: true, message: 'Indica la cantidad.' }] });
const agregarMaterial = () => {
    formMaterial.post(route('mantenimientos.materiales.store', m.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalMaterial.value = false;
            formMaterial.reset();
        },
    });
};
const quitarMaterial = (mat) =>
    router.delete(route('mantenimientos.materiales.destroy', [m.value.id, mat.id]), { preserveScroll: true });

const onMaterialSel = (id) => {
    const mat = (props.catalogos.materiales ?? []).find((x) => x.id === id);
    if (mat) {
        formMaterial.unidad = mat.unidad;
        if (mat.costo_referencia) formMaterial.costo_unitario = mat.costo_referencia;
    }
};

// --- Observación -----------------------------------------
const modalObs = ref(false);
const formObs = useForm({ tipo: 'comentario', cuerpo: '' });
const reglasObs = reactive({ cuerpo: [{ required: true, message: 'Escribe la observación.' }] });
const agregarObs = () => {
    formObs.post(route('mantenimientos.observaciones.store', m.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalObs.value = false;
            formObs.reset();
        },
    });
};

// --- Baja ------------------------------------------------
const darBaja = async () => {
    const ok = await confirmar.value.abrir({ titulo: `Eliminar orden ${m.value.folio}`, confirmar: 'Eliminar', peligro: true });
    if (ok) router.delete(route('mantenimientos.destroy', m.value.id));
};

const menuTransiciones = computed(() =>
    props.transicionesPosibles.map((slug) => ({ key: slug, label: ETIQUETA_ESTADO[slug] ?? slug })),
);
</script>

<template>
    <Head :title="`Orden ${m.folio}`" />

    <AppLayout>
        <FichaEncabezado
            :titulo="m.folio"
            :subtitulo="m.equipo ? `${m.equipo.codigo_activo} · ${m.equipo.descripcion}` : (m.ubicacion?.nombre ?? '')"
            :icono="ToolOutlined"
            volver="mantenimientos.index"
            :sello="sello"
        >
            <template #tags>
                <a-tag color="processing">{{ m.estado?.nombre }}</a-tag>
                <a-tag v-if="m.prioridad" :color="m.prioridad.color || 'default'">{{ m.prioridad.nombre }}</a-tag>
                <a-tag>{{ m.tipo?.nombre }}</a-tag>
            </template>
            <template #acciones>
                <a-dropdown v-if="menuTransiciones.length && puede('mantenimientos.editar')">
                    <a-button type="primary">
                        <SwapOutlined /> Cambiar estado <DownOutlined />
                    </a-button>
                    <template #overlay>
                        <a-menu :items="menuTransiciones" @click="({ key }) => abrirEstado(key)" />
                    </template>
                </a-dropdown>
                <a-button v-if="puede('mantenimientos.editar')" @click="modalReprogramar = true">
                    <template #icon><CalendarOutlined /></template>
                    Reprogramar
                </a-button>
                <a-dropdown v-if="puede('mantenimientos.editar')">
                    <a-button type="text"><template #icon><EllipsisOutlined /></template></a-button>
                    <template #overlay>
                        <a-menu
                            :items="[{ key: 'baja', label: 'Eliminar orden', danger: true, icon: () => h(DeleteOutlined) }]"
                            @click="darBaja"
                        />
                    </template>
                </a-dropdown>
            </template>
        </FichaEncabezado>

        <a-card size="small" class="mb-4 orden-info">
            <div class="orden-info__desc">
                <div class="destacado__objetivo">
                    <component :is="m.equipo ? ToolOutlined : EnvironmentOutlined" />
                    {{ m.equipo ? `${m.equipo.codigo_activo} — ${m.equipo.descripcion}` : (m.ubicacion?.nombre ?? '') }}
                </div>
                <div class="destacado__desc">{{ m.problema_reportado || 'ORDEN DE MANTENIMIENTO SIN DESCRIPCIÓN DE PROBLEMA.' }}</div>
            </div>
            <a-collapse ghost class="orden-info__col">
                <a-collapse-panel key="i" header="Información relevante">
                    <ListaDatos :datos="infoRelevante" compacto />
                </a-collapse-panel>
            </a-collapse>
        </a-card>

        <div v-if="checklistCierre.length && !['cerrado', 'cancelado'].includes(m.estado?.clave)" class="checklist-cierre">
            <span class="checklist-cierre__l">Para cerrar esta orden:</span>
            <span v-for="item in checklistCierre" :key="item.clave" class="checklist-cierre__item" :class="{ 'is-ok': item.cumplido }">
                <CheckCircleOutlined v-if="item.cumplido" />
                <ClockCircleOutlined v-else />
                {{ item.label }}
            </span>
        </div>

        <a-card :body-style="{ padding: 0 }">
            <a-tabs v-model:activeKey="tab" class="px-4 pt-2">
                <!-- Resumen -->
                <a-tab-pane key="resumen" tab="Resumen">
                    <a-row :gutter="28" class="pb-4">
                        <a-col :xs="24" :md="13">
                            <SeccionFicha titulo="Información de la orden" :icono="ToolOutlined">
                                <ListaDatos :datos="datos" />
                            </SeccionFicha>
                        </a-col>
                        <a-col :xs="24" :md="11">
                            <SeccionFicha titulo="Cronología" :icono="ClockCircleOutlined" color="#6b4bc9">
                                <ListaDatos :datos="cronologia" compacto />
                            </SeccionFicha>
                            <SeccionFicha v-if="m.normas?.length" titulo="Normas aplicadas" :icono="FlagOutlined" color="#1f9e86">
                                <a-space wrap><a-tag v-for="n in m.normas" :key="n.id" color="blue">{{ n.codigo }}</a-tag></a-space>
                            </SeccionFicha>
                        </a-col>
                    </a-row>
                </a-tab-pane>

                <!-- Trabajo -->
                <a-tab-pane key="trabajo" tab="Diagnóstico y trabajo">
                    <div class="p-4" style="max-width: 820px">
                        <a-alert
                            v-if="!puede('mantenimientos.editar')"
                            type="info"
                            show-icon
                            class="mb-3"
                            message="Solo lectura: no tienes permiso para editar la captura de trabajo."
                        />
                        <a-form layout="vertical" :disabled="!puede('mantenimientos.editar')">
                            <a-form-item label="Diagnóstico">
                                <a-textarea v-model:value="formTrabajo.diagnostico" :rows="3" />
                            </a-form-item>
                            <a-form-item label="Actividades realizadas">
                                <a-textarea v-model:value="formTrabajo.descripcion_trabajo" :rows="3" />
                            </a-form-item>
                            <a-form-item label="Observaciones">
                                <a-textarea v-model:value="formTrabajo.observaciones" :rows="2" />
                            </a-form-item>
                            <a-row :gutter="16">
                                <a-col :xs="24" :sm="8">
                                    <a-form-item label="Condición final del equipo">
                                        <a-input v-model:value="formTrabajo.condicion_final" />
                                    </a-form-item>
                                </a-col>
                                <a-col :xs="12" :sm="8">
                                    <a-form-item label="Costo mano de obra">
                                        <a-input v-model:value="formTrabajo.costo_mano_obra" type="number" prefix="$" />
                                    </a-form-item>
                                </a-col>
                                <a-col :xs="12" :sm="8">
                                    <a-form-item label="Otros costos">
                                        <a-input v-model:value="formTrabajo.costo_otros" type="number" prefix="$" />
                                    </a-form-item>
                                </a-col>
                            </a-row>
                            <a-space>
                                <a-button v-if="puede('mantenimientos.editar')" type="primary" :loading="formTrabajo.processing" @click="guardarTrabajo">
                                    <template #icon><SaveOutlined /></template>
                                    Guardar
                                </a-button>
                                <a-button type="link" @click="tab = 'evidencias'">
                                    <template #icon><PlusOutlined /></template>
                                    Adjuntar evidencia
                                </a-button>
                            </a-space>
                        </a-form>
                    </div>
                </a-tab-pane>

                <!-- Técnicos -->
                <a-tab-pane key="tecnicos" :tab="`Técnicos (${tecnicosActivos.length})`">
                    <div class="p-4">
                        <div class="flex justify-end mb-3">
                            <a-button v-if="puede('mantenimientos.asignar')" type="primary" ghost @click="modalTecnico = true">
                                <template #icon><PlusOutlined /></template>
                                Asignar técnico
                            </a-button>
                        </div>
                        <a-list :data-source="tecnicosActivos" :locale="{ emptyText: 'Sin técnicos asignados' }">
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta :title="item.tecnico?.nombre" :description="`Asignado por ${item.asignadoPor?.nombre ?? 'No especificado'} · ${soloFecha(item.asignado_at)}`" />
                                    <template #actions>
                                        <a-tag v-if="item.es_principal" color="blue">Principal</a-tag>
                                        <a v-if="puede('mantenimientos.asignar')" class="text-red-500" @click="quitarTecnico(item)"><DeleteOutlined /></a>
                                    </template>
                                </a-list-item>
                            </template>
                        </a-list>
                    </div>
                </a-tab-pane>

                <!-- Materiales -->
                <a-tab-pane key="materiales" :tab="`Materiales (${m.materiales?.length ?? 0})`">
                    <div class="p-4">
                        <div class="flex justify-end mb-3">
                            <a-button v-if="puede('mantenimientos.editar')" type="primary" ghost @click="modalMaterial = true">
                                <template #icon><PlusOutlined /></template>
                                Agregar material
                            </a-button>
                        </div>
                        <a-table
                            :data-source="m.materiales ?? []"
                            row-key="id"
                            size="small"
                            :pagination="false"
                            :locale="{ emptyText: 'Sin materiales registrados' }"
                            :columns="[
                                { title: 'Material', key: 'nombre' },
                                { title: 'Cantidad', key: 'cantidad', align: 'right' },
                                { title: 'Costo unit.', key: 'costo', align: 'right' },
                                { title: 'Total', key: 'total', align: 'right' },
                                { title: '', key: 'x', align: 'right', width: 50 },
                            ]"
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.key === 'nombre'">{{ record.material?.nombre || record.descripcion }}</template>
                                <template v-else-if="column.key === 'cantidad'">{{ record.cantidad }} {{ record.unidad }}</template>
                                <template v-else-if="column.key === 'costo'">{{ moneda(record.costo_unitario) }}</template>
                                <template v-else-if="column.key === 'total'">{{ moneda(record.costo_unitario ? record.costo_unitario * record.cantidad : null) }}</template>
                                <template v-else-if="column.key === 'x'">
                                    <a v-if="puede('mantenimientos.editar')" class="text-red-500" @click="quitarMaterial(record)"><DeleteOutlined /></a>
                                </template>
                            </template>
                        </a-table>
                    </div>
                </a-tab-pane>

                <!-- Bitácora -->
                <a-tab-pane key="bitacora" :tab="`Bitácora (${m.observaciones?.length ?? 0})`">
                    <div class="p-4">
                        <div class="flex justify-end mb-3">
                            <a-button v-if="puede('mantenimientos.editar')" type="primary" ghost @click="modalObs = true">
                                <template #icon><PlusOutlined /></template>
                                Agregar observación
                            </a-button>
                        </div>
                        <a-timeline v-if="m.observaciones?.length">
                            <a-timeline-item v-for="o in m.observaciones" :key="o.id">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <a-tag>{{ o.tipo }}</a-tag>
                                        <span class="whitespace-pre-line">{{ o.cuerpo }}</span>
                                        <div class="text-xs opacity-60">{{ o.usuario?.nombre ?? 'Sistema' }}</div>
                                    </div>
                                    <div class="text-xs whitespace-nowrap">{{ fecha(o.created_at) }}</div>
                                </div>
                            </a-timeline-item>
                        </a-timeline>
                        <a-empty v-else description="Sin observaciones" />
                    </div>
                </a-tab-pane>

                <!-- Historial de estados -->
                <a-tab-pane key="historial" tab="Historial de estados">
                    <div class="p-4">
                        <a-timeline>
                            <a-timeline-item v-for="hh in m.historialEstados" :key="hh.id" color="blue">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <strong>{{ hh.estadoDestino?.nombre }}</strong>
                                        <span v-if="hh.estadoOrigen" class="opacity-60"> (desde {{ hh.estadoOrigen.nombre }})</span>
                                        <div v-if="hh.nota" class="text-sm">{{ hh.nota }}</div>
                                        <div class="text-xs opacity-60">{{ hh.cambiadoPor?.nombre ?? 'Sistema' }}</div>
                                    </div>
                                    <div class="text-xs whitespace-nowrap">{{ fecha(hh.cambiado_at) }}</div>
                                </div>
                            </a-timeline-item>
                        </a-timeline>

                        <template v-if="m.reprogramaciones?.length">
                            <div class="font-semibold mt-4 mb-2">Reprogramaciones</div>
                            <a-list :data-source="m.reprogramaciones" size="small">
                                <template #renderItem="{ item }">
                                    <a-list-item>
                                        <a-list-item-meta
                                            :title="`${soloFecha(item.inicio_anterior)} → ${soloFecha(item.inicio_nuevo)}`"
                                            :description="`${item.motivo} · ${item.reprogramadoPor?.nombre ?? 'No especificado'}`"
                                        />
                                    </a-list-item>
                                </template>
                            </a-list>
                        </template>
                    </div>
                </a-tab-pane>

                <!-- Evidencias -->
                <a-tab-pane key="evidencias" :tab="`Evidencias (${m.documentos?.length ?? 0})`">
                    <div class="p-4">
                        <ListaDocumentos
                            :documentos="m.documentos ?? []"
                            relacionable-tipo="mantenimiento"
                            :relacionable-id="m.id"
                            :roles="['evidencia', 'antes', 'despues', 'factura', 'garantia']"
                            :puede-subir="puede('documentos.crear')"
                            :puede-eliminar="puede('documentos.desactivar')"
                        />
                    </div>
                </a-tab-pane>
            </a-tabs>
        </a-card>

        <!-- Modales -->
        <a-modal v-model:open="modalEstado" :title="ETIQUETA_ESTADO[formEstado.estado] || 'Cambiar estado'" ok-text="Confirmar" cancel-text="Cancelar" :confirm-loading="formEstado.processing" @ok="confirmarEstado">
            <a-form layout="vertical" class="pt-2">
                <a-form-item label="Nota (opcional)">
                    <a-textarea v-model:value="formEstado.nota" :rows="2" />
                </a-form-item>
            </a-form>
        </a-modal>

        <a-modal v-model:open="modalReprogramar" title="Reprogramar orden" ok-text="Reprogramar" cancel-text="Cancelar" :confirm-loading="formReprogramar.processing" @ok="reprogramar">
            <a-form :model="formReprogramar" :rules="reglasReprogramar" layout="vertical" class="pt-2">
                <a-form-item label="Nueva fecha y hora de inicio" name="programado_inicio" :validate-status="formReprogramar.errors.programado_inicio ? 'error' : undefined" :help="formReprogramar.errors.programado_inicio">
                    <CampoFechaHora v-model="formReprogramar.programado_inicio" :min-fecha="hoyISO()" />
                </a-form-item>
                <a-form-item label="Nueva fecha y hora de fin" name="programado_fin" :help="formReprogramar.errors.programado_fin" :validate-status="formReprogramar.errors.programado_fin ? 'error' : undefined">
                    <CampoFechaHora v-model="formReprogramar.programado_fin" :min-fecha="formReprogramar.programado_inicio ? formReprogramar.programado_inicio.slice(0, 10) : hoyISO()" />
                </a-form-item>
                <a-form-item label="Motivo" name="motivo" :validate-status="formReprogramar.errors.motivo ? 'error' : undefined" :help="formReprogramar.errors.motivo">
                    <a-textarea v-model:value="formReprogramar.motivo" :rows="2" />
                </a-form-item>
            </a-form>
        </a-modal>

        <a-modal v-model:open="modalTecnico" title="Asignar técnico" ok-text="Asignar" cancel-text="Cancelar" :confirm-loading="formTecnico.processing" @ok="asignarTecnico">
            <a-form layout="vertical" class="pt-2">
                <a-form-item
                    label="Técnico"
                    extra="Los marcados con ⭐ tienen una especialidad registrada que coincide con esta orden."
                    :validate-status="formTecnico.errors.tecnico_id ? 'error' : undefined"
                    :help="formTecnico.errors.tecnico_id"
                >
                    <a-select v-model:value="formTecnico.tecnico_id">
                        <a-select-option v-for="t in catalogos.tecnicos" :key="t.id" :value="t.id">
                            <StarFilled v-if="t.recomendado" class="tecnico-op__star" />
                            {{ t.nombre }}
                            <span v-if="t.recomendado" class="tecnico-op__tag">Recomendado</span>
                        </a-select-option>
                    </a-select>
                </a-form-item>

                <div v-if="tecnicoSeleccionado" class="tecnico-cualidades">
                    <span class="tecnico-cualidades__t">Cualidades de {{ tecnicoSeleccionado.nombre }}</span>
                    <a-space v-if="tecnicoSeleccionado.cualidades?.length" wrap>
                        <a-tag v-for="c in tecnicoSeleccionado.cualidades" :key="c" color="blue">{{ c }}</a-tag>
                    </a-space>
                    <span v-else class="tecnico-cualidades__vacio">Sin especialidades registradas todavía.</span>
                </div>
                <a-form-item>
                    <a-checkbox v-model:checked="formTecnico.es_principal">Responsable principal</a-checkbox>
                </a-form-item>
                <a-form-item label="Notas">
                    <a-input v-model:value="formTecnico.notas" />
                </a-form-item>
            </a-form>
        </a-modal>

        <a-modal v-model:open="modalMaterial" title="Agregar material" ok-text="Agregar" cancel-text="Cancelar" :confirm-loading="formMaterial.processing" @ok="agregarMaterial">
            <a-form :model="formMaterial" :rules="reglasMaterial" layout="vertical" class="pt-2">
                <a-form-item label="Del catálogo">
                    <SelectCatalogo
                        v-model:value="formMaterial.material_id"
                        :options="catalogos.materiales"
                        ruta="catalogos.materiales"
                        etiqueta="material"
                        etiqueta-plural="materiales"
                        placeholder="Opcional — o captura la descripción abajo"
                        :campos="[
                            { name: 'unidad', label: 'Unidad (pza, m, lt…)', ancho: 12 },
                            { name: 'costo_referencia', label: 'Costo referencia', tipo: 'number', min: 0 },
                        ]"
                        @update:value="onMaterialSel"
                    />
                </a-form-item>
                <a-form-item v-if="!formMaterial.material_id" label="Descripción" :validate-status="formMaterial.errors.descripcion ? 'error' : undefined" :help="formMaterial.errors.descripcion">
                    <a-input v-model:value="formMaterial.descripcion" />
                </a-form-item>
                <a-row :gutter="12">
                    <a-col :span="8">
                        <a-form-item label="Cantidad" name="cantidad" :validate-status="formMaterial.errors.cantidad ? 'error' : undefined" :help="formMaterial.errors.cantidad">
                            <a-input v-model:value="formMaterial.cantidad" type="number" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="8">
                        <a-form-item label="Unidad"><a-input v-model:value="formMaterial.unidad" /></a-form-item>
                    </a-col>
                    <a-col :span="8">
                        <a-form-item label="Costo unit."><a-input v-model:value="formMaterial.costo_unitario" type="number" prefix="$" /></a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>

        <a-modal v-model:open="modalObs" title="Agregar observación" ok-text="Agregar" cancel-text="Cancelar" :confirm-loading="formObs.processing" @ok="agregarObs">
            <a-alert
                type="info"
                show-icon
                class="mb-3"
                message="La bitácora es para notas rápidas o seguimiento. El diagnóstico y las actividades formales se registran en la pestaña «Diagnóstico y trabajo»."
            />
            <a-form :model="formObs" :rules="reglasObs" layout="vertical" class="pt-2">
                <a-form-item label="Tipo">
                    <a-select
                        v-model:value="formObs.tipo"
                        :options="[
                            { value: 'comentario', label: 'Comentario' },
                            { value: 'supervision', label: 'Supervisión' },
                        ]"
                    />
                </a-form-item>
                <a-form-item label="Observación" name="cuerpo" :validate-status="formObs.errors.cuerpo ? 'error' : undefined" :help="formObs.errors.cuerpo">
                    <a-textarea v-model:value="formObs.cuerpo" :rows="3" />
                </a-form-item>
            </a-form>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
.tecnico-op__star {
    color: #e08a1e;
    margin-right: 4px;
}
.tecnico-op__tag {
    float: right;
    font-size: 11px;
    font-weight: 700;
    color: #e08a1e;
}
.tecnico-cualidades {
    margin: -8px 0 16px;
    padding: 10px 12px;
    border-radius: 10px;
    background: var(--sigam-navy-050);
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.tecnico-cualidades__t {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--sigam-tenue);
}
.tecnico-cualidades__vacio {
    font-size: 12.5px;
    color: var(--sigam-tenue);
    font-style: italic;
}
.checklist-cierre {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 16px;
    margin-bottom: 16px;
    padding: 10px 14px;
    background: #fbfcfe;
    border: 1px solid var(--sigam-borde);
    border-radius: 12px;
    font-size: 12.5px;
}
.checklist-cierre__l {
    font-weight: 700;
    color: var(--sigam-tenue);
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.04em;
}
.checklist-cierre__item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #a86717;
}
.checklist-cierre__item.is-ok {
    color: var(--sigam-teal-700);
}
.orden-info :deep(.ant-card-body) {
    padding: 0;
}
.orden-info__desc {
    padding: 14px 16px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: #fef4f4;
}
.orden-info__col :deep(.ant-collapse-header) {
    padding: 10px 16px !important;
    font-weight: 600;
    font-size: 12.5px;
    color: var(--sigam-navy);
}
.orden-info__col :deep(.ant-collapse-content-box) {
    padding: 0 16px 12px !important;
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
</style>
