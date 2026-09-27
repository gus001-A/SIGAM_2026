<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckCircleOutlined,
    CheckSquareOutlined,
    ClockCircleOutlined,
    CloseCircleOutlined,
    DeleteOutlined,
    DollarOutlined,
    EditOutlined,
    FlagOutlined,
    PaperClipOutlined,
    PlayCircleOutlined,
    PlusOutlined,
    SwapOutlined,
    TeamOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import ModalFicha from '@/Components/ModalFicha.vue';
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
const confirmar = ref(null);
const modalDocumentos = ref(false);

const ETIQUETA_ESTADO = { en_proceso: 'Iniciar tarea', realizada: 'Marcar como realizada', cancelada: 'Cancelar tarea' };
const ICONO_ESTADO = { en_proceso: PlayCircleOutlined, realizada: CheckCircleOutlined, cancelada: CloseCircleOutlined };
const COLOR_ESTADO = { pendiente: 'gold', en_proceso: 'blue', realizada: 'green', cancelada: 'red' };
const ETIQUETA_ESTADO_TAG = { pendiente: 'Pendiente', en_proceso: 'En proceso', realizada: 'Realizada', cancelada: 'Cancelada' };

const fecha = (v) => (v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado');
const soloFecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const moneda = (v) => (v == null ? 'No especificado' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v));

const vencida = computed(() => ['pendiente', 'en_proceso'].includes(t.value.estado) && new Date(t.value.fecha_limite) < new Date().setHours(0, 0, 0, 0));
const esFinal = computed(() => ['realizada', 'cancelada'].includes(t.value.estado));

const datos = computed(() => [
    { icono: CalendarOutlined, label: 'Fecha límite', valor: soloFecha(t.value.fecha_limite), color: vencida.value ? '#d64545' : '#173a5f' },
    { icono: FlagOutlined, label: 'Prioridad', valor: t.value.prioridad?.nombre ?? 'No especificado', color: t.value.prioridad?.color || '#d64545' },
    { icono: ClockCircleOutlined, label: 'Iniciada', valor: fecha(t.value.iniciada_at), color: '#e08a1e' },
    { icono: ClockCircleOutlined, label: 'Realizada', valor: fecha(t.value.realizada_at), color: '#1f9e86' },
    { icono: ClockCircleOutlined, label: 'Cancelada', valor: fecha(t.value.cancelada_at), color: '#d64545' },
    { icono: DollarOutlined, label: 'Costo', valor: t.value.costo != null ? moneda(t.value.costo) : 'No especificado', color: '#6b4bc9' },
]);

const responsablesActivos = computed(() => (t.value.asignaciones ?? []).filter((a) => !a.desasignado_at));
const responsablesInactivos = computed(() => (t.value.asignaciones ?? []).filter((a) => a.desasignado_at));

// --- KPIs -----------------------------------------------------
const diasRestantes = computed(() => {
    if (!t.value.fecha_limite || esFinal.value) return null;
    const ms = new Date(t.value.fecha_limite).setHours(0, 0, 0, 0) - new Date().setHours(0, 0, 0, 0);
    return Math.round(ms / 86400000);
});
const etiquetaPlazo = computed(() => {
    if (esFinal.value) return ETIQUETA_ESTADO_TAG[t.value.estado];
    const d = diasRestantes.value;
    if (d == null) return 'No especificado';
    if (d < 0) return `Vencida hace ${Math.abs(d)} d.`;
    if (d === 0) return 'Hoy';
    return `En ${d} día${d === 1 ? '' : 's'}`;
});

const kpis = computed(() => [
    { clave: 'estado', etiqueta: 'Estado', valor: ETIQUETA_ESTADO_TAG[t.value.estado], icono: CheckSquareOutlined, color: t.value.estado === 'realizada' ? '#1f9e86' : (t.value.estado === 'cancelada' ? '#d64545' : '#0d84c9') },
    { clave: 'plazo', etiqueta: esFinal.value ? 'Cerrada' : 'Plazo', valor: etiquetaPlazo.value, icono: ClockCircleOutlined, color: vencida.value ? '#d64545' : '#e08a1e' },
    { clave: 'responsables', etiqueta: 'Responsables', valor: responsablesActivos.value.length, icono: TeamOutlined, color: '#6b4bc9' },
    { clave: 'documentos', etiqueta: 'Documentos', valor: t.value.documentos?.length ?? 0, icono: PaperClipOutlined, color: '#1f9e86', abrir: () => (modalDocumentos.value = true) },
]);

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
const botonesTransicion = computed(() =>
    props.transicionesPosibles.map((slug, i) => ({
        slug,
        label: ETIQUETA_ESTADO[slug] ?? slug,
        icono: ICONO_ESTADO[slug] ?? CheckCircleOutlined,
        tipo: slug === 'cancelada' ? undefined : (i === 0 ? 'primary' : 'default'),
        peligro: slug === 'cancelada',
    })),
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
                <template v-if="puede('tareas.editar')">
                    <a-button v-for="b in botonesTransicion" :key="b.slug" :type="b.tipo" :danger="b.peligro"
                        @click="abrirEstado(b.slug)">
                        <template #icon>
                            <component :is="b.icono" />
                        </template>
                        {{ b.label }}
                    </a-button>
                </template>
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

        <!-- KPIs con colores sólidos -->
        <div class="kpis">
            <button v-for="k in kpis" :key="k.clave" type="button" class="kpi" :class="{ 'kpi--link': !!k.abrir }"
                :style="{ '--acc': k.color }" @click="k.abrir && k.abrir()">
                <div class="kpi__icono">
                    <component :is="k.icono" />
                </div>
                <div class="kpi__txt">
                    <div class="kpi__valor">{{ k.valor }}</div>
                    <div class="kpi__etq">{{ k.etiqueta }}</div>
                </div>
            </button>
        </div>

        <div class="grid-ficha">
            <!-- COLUMNA IZQUIERDA -->
            <div class="col-izq">
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #0d84c9">
                            <CheckSquareOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Información de la tarea</div>
                            <div class="card__sub">{{ t.descripcion || 'Sin descripción' }}</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="mini-grid mini-grid--2">
                            <div v-for="d in datos" :key="d.label" class="mini" :style="{ '--c': d.color }">
                                <span class="mini__ic">
                                    <component :is="d.icono" />
                                </span>
                                <span class="mini__t">
                                    <span class="mini__l">{{ d.label }}</span>
                                    <span class="mini__v">{{ d.valor }}</span>
                                </span>
                            </div>
                        </div>

                        <a-alert v-if="t.nota_avance" type="info" show-icon class="mt-3" :message="`Avance: ${t.nota_avance}`" />
                        <a-alert v-if="t.nota_cierre" type="success" show-icon class="mt-3" :message="`Cierre: ${t.nota_cierre}`" />
                        <a-alert v-if="t.nota_cancelacion" type="error" show-icon class="mt-3" :message="`Cancelación: ${t.nota_cancelacion}`" />
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA -->
            <div class="col-der">
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #6b4bc9">
                            <TeamOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Responsables</div>
                            <div class="card__sub">{{ responsablesActivos.length }} asignado(s)</div>
                        </div>
                        <a-button v-if="puede('tareas.asignar')" size="small" type="primary" ghost @click="modalResponsable = true">
                            <template #icon><PlusOutlined /></template>
                            Asignar
                        </a-button>
                    </div>
                    <div class="card__body">
                        <a-list :data-source="responsablesActivos" :locale="{ emptyText: 'Sin responsables asignados' }">
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta :title="item.usuario?.nombre" :description="`Asignado por ${item.asignado_por?.nombre ?? 'No especificado'} · ${fecha(item.asignado_at)}`" />
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
                                        <a-list-item-meta :title="item.usuario?.nombre" :description="`Retirado el ${fecha(item.desasignado_at)}`" />
                                    </a-list-item>
                                </template>
                            </a-list>
                        </template>
                    </div>
                </div>

                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #e08a1e">
                            <SwapOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Historial de estados</div>
                            <div class="card__sub">Movimientos de la tarea</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <a-timeline v-if="t.historial_estados?.length">
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
                        <div v-else class="vacio-box">
                            <SwapOutlined />
                            <span>Sin movimientos registrados</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        <ModalFicha :show="modalDocumentos" titulo="Documentos" :subtitulo="`${t.documentos?.length ?? 0} archivo(s)`"
            :icono="PaperClipOutlined" color="#1f9e86" max-width="lg" @close="modalDocumentos = false">
            <ListaDocumentos
                :documentos="t.documentos ?? []"
                relacionable-tipo="tarea"
                :relacionable-id="t.id"
                :roles="['evidencia', 'foto']"
                :puede-subir="puede('documentos.crear')"
                :puede-eliminar="puede('documentos.desactivar')"
            />
        </ModalFicha>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   KPIs con colores sólidos
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
    margin-bottom: 14px;
}

.kpi {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    position: relative;
    overflow: hidden;
    width: 100%;
    text-align: left;
    font-family: inherit;
    cursor: default;
    transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
}

.kpi--link {
    cursor: pointer;
}

.kpi--link:hover {
    box-shadow: 0 6px 16px -10px rgba(15, 37, 71, 0.28);
    transform: translateY(-2px);
    border-color: var(--acc);
}

.kpi--link:active {
    transform: translateY(0) scale(0.98);
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}

.kpi__icono {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: var(--acc);
    flex-shrink: 0;
}

.kpi__valor {
    font-size: 18px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.1;
}

.kpi__etq {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ==========================================================
   Grid
   ========================================================== */
.grid-ficha {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 14px;
    align-items: start;
}

.col-izq,
.col-der {
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-width: 0;
    padding-bottom: 4px;
}

/* ==========================================================
   Cards
   ========================================================== */
.card {
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 14px -10px rgba(15, 37, 71, 0.18);
    transition: box-shadow 0.18s ease;
}

.card:hover {
    box-shadow: 0 8px 22px -14px rgba(15, 37, 71, 0.28);
}

.card__head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: var(--sigam-navy-050);
}

.card__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: var(--c);
    flex-shrink: 0;
}

.card__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    flex: 1;
}

.card__titulo {
    font-weight: 800;
    font-size: 13.5px;
    color: var(--sigam-navy);
    letter-spacing: -0.1px;
    line-height: 1.2;
}

.card__sub {
    font-size: 11px;
    color: var(--sigam-tenue);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card__body {
    padding: 13px 16px;
}

/* ==========================================================
   Mini-cards con colores sólidos
   ========================================================== */
.mini-grid {
    display: grid;
    gap: 9px;
}

.mini-grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.mini {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px -6px rgba(15, 37, 71, 0.22);
    border-color: var(--c);
}

.mini__ic {
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    background: var(--c);
}

.mini__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 0;
}

.mini__l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    line-height: 1.1;
}

.mini__v {
    font-size: 13px;
    font-weight: 700;
    color: var(--sigam-texto);
    word-break: break-word;
    line-height: 1.25;
}

/* ==========================================================
   Caja vacía
   ========================================================== */
.vacio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 20px 12px;
    border-radius: 11px;
    border: 1px dashed var(--sigam-borde);
    background: #f5f8fb;
    color: var(--sigam-tenue);
    font-size: 12px;
}

.vacio-box .anticon {
    font-size: 20px;
    opacity: 0.5;
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 1199px) {
    .grid-ficha {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
