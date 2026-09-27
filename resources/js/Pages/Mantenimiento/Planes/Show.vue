<script setup>
import { computed, h, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckCircleOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    EnvironmentOutlined,
    FieldTimeOutlined,
    FileProtectOutlined,
    HourglassOutlined,
    PlusOutlined,
    SnippetsOutlined,
    ThunderboltOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    plan: { type: Object, required: true },
    ocurrencias: { type: Array, default: () => [] },
    sello: { type: Object, default: null },
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

const datosObjetivo = computed(() => [
    props.plan.equipo
        ? { label: 'Equipo', valor: `${props.plan.equipo.codigo_activo} · ${props.plan.equipo.descripcion}`, icono: ToolOutlined, color: '#0d84c9' }
        : { label: 'Instalación', valor: props.plan.ubicacion?.nombre ?? 'No especificado', icono: EnvironmentOutlined, color: '#0d84c9' },
    { label: 'Tipo de mantenimiento', valor: props.plan.tipo?.nombre ?? 'No especificado', icono: ToolOutlined, color: '#0d84c9' },
]);

const datosFrecuencia = computed(() => [
    { label: 'Frecuencia', valor: `${etiquetaFrecuencia(props.plan.tipo_frecuencia)} (${props.plan.valor_frecuencia})`, icono: CalendarOutlined, color: '#e08a1e' },
    { label: 'Fecha de inicio', valor: fecha(props.plan.fecha_inicio), icono: CalendarOutlined, color: '#e08a1e' },
    { label: 'Próxima fecha', valor: fecha(props.plan.proxima_fecha), icono: CalendarOutlined, color: '#e08a1e' },
    { label: 'Días de aviso', valor: props.plan.dias_aviso_anticipado ?? 'No especificado', icono: ClockCircleOutlined, color: '#e08a1e' },
]);

const datosReferencias = computed(() => [
    { label: 'Norma', valor: props.plan.norma?.codigo ?? 'No especificado', icono: FileProtectOutlined, color: '#6b4bc9' },
    { label: 'Formato', valor: props.plan.formato?.nombre ?? 'No especificado', icono: SnippetsOutlined, color: '#6b4bc9' },
    { label: 'Técnico sugerido', valor: props.plan.tecnico?.nombre ?? 'No especificado', icono: UserOutlined, color: '#6b4bc9' },
]);

const colorOcurrencia = (estado) =>
    ({ pendiente: 'blue', generada: 'green', omitida: 'default' })[estado] ?? 'default';

// --- KPIs -----------------------------------------------------
const ocurrenciasPendientes = computed(() => props.ocurrencias.filter((o) => o.estado === 'pendiente').length);
const ocurrenciasGeneradas = computed(() => props.ocurrencias.filter((o) => o.estado === 'generada').length);
const diasProxima = computed(() => {
    if (!props.plan.proxima_fecha) return null;
    const ms = new Date(props.plan.proxima_fecha).setHours(0, 0, 0, 0) - new Date().setHours(0, 0, 0, 0);
    return Math.round(ms / 86400000);
});
const etiquetaProxima = computed(() => {
    const d = diasProxima.value;
    if (d == null) return 'No especificado';
    if (d < 0) return `Vencida hace ${Math.abs(d)} d.`;
    if (d === 0) return 'Hoy';
    return `En ${d} día${d === 1 ? '' : 's'}`;
});

const kpis = computed(() => [
    { clave: 'total', etiqueta: 'Ocurrencias', valor: props.ocurrencias.length, icono: CalendarOutlined, color: '#0d84c9' },
    { clave: 'pendientes', etiqueta: 'Pendientes', valor: ocurrenciasPendientes.value, icono: HourglassOutlined, color: '#e08a1e' },
    { clave: 'generadas', etiqueta: 'Órdenes generadas', valor: ocurrenciasGeneradas.value, icono: CheckCircleOutlined, color: '#1f9e86' },
    { clave: 'proxima', etiqueta: 'Próxima fecha', valor: etiquetaProxima.value, icono: FieldTimeOutlined, color: '#6b4bc9' },
]);

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

const eliminarOcurrencia = async (ocurrencia) => {
    const ok = await confirmar.value.abrir({
        titulo: `Eliminar ocurrencia del ${fecha(ocurrencia.fecha_programada)}`,
        mensaje: 'Se quita del calendario de este plan. Si ya generó una orden, no se puede eliminar.',
        confirmar: 'Eliminar',
        peligro: true,
    });
    if (ok) router.delete(route('planes.ocurrencias.destroy', [props.plan.id, ocurrencia.id]), { preserveScroll: true });
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
            :subtitulo="plan.equipo ? `${plan.equipo.codigo_activo} · ${plan.equipo.descripcion}` : (plan.ubicacion?.nombre ?? '')"
            :icono="CalendarOutlined"
            volver="planes.index"
            :sello="sello"
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

        <!-- KPIs con colores sólidos -->
        <div class="kpis">
            <div v-for="k in kpis" :key="k.clave" class="kpi" :style="{ '--acc': k.color }">
                <div class="kpi__icono">
                    <component :is="k.icono" />
                </div>
                <div class="kpi__txt">
                    <div class="kpi__valor">{{ k.valor }}</div>
                    <div class="kpi__etq">{{ k.etiqueta }}</div>
                </div>
            </div>
        </div>

        <div class="grid-ficha">
            <!-- COLUMNA IZQUIERDA -->
            <div class="col-izq">
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #0d84c9">
                            <ToolOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Objetivo</div>
                            <div class="card__sub">A qué aplica este plan</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="mini-grid mini-grid--2">
                            <div v-for="d in datosObjetivo" :key="d.label" class="mini" :style="{ '--c': d.color }">
                                <span class="mini__ic">
                                    <component :is="d.icono" />
                                </span>
                                <span class="mini__t">
                                    <span class="mini__l">{{ d.label }}</span>
                                    <span class="mini__v">{{ d.valor }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #e08a1e">
                            <CalendarOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Frecuencia</div>
                            <div class="card__sub">Cada cuándo se repite</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="mini-grid mini-grid--2">
                            <div v-for="d in datosFrecuencia" :key="d.label" class="mini" :style="{ '--c': d.color }">
                                <span class="mini__ic">
                                    <component :is="d.icono" />
                                </span>
                                <span class="mini__t">
                                    <span class="mini__l">{{ d.label }}</span>
                                    <span class="mini__v">{{ d.valor }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #6b4bc9">
                            <FileProtectOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Referencias y asignación</div>
                            <div class="card__sub">Norma, checklist y técnico</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="mini-grid mini-grid--2">
                            <div v-for="d in datosReferencias" :key="d.label" class="mini" :style="{ '--c': d.color }">
                                <span class="mini__ic">
                                    <component :is="d.icono" />
                                </span>
                                <span class="mini__t">
                                    <span class="mini__l">{{ d.label }}</span>
                                    <span class="mini__v">{{ d.valor }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA -->
            <div class="col-der">
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #1f9e86">
                            <ThunderboltOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Ocurrencias programadas</div>
                            <div class="card__sub">{{ ocurrencias.length }} registradas</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <a-timeline v-if="ocurrencias.length">
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
                                    <a-space :size="4">
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
                                        <a-tooltip v-if="!o.mantenimiento_id && puede('mantenimientos.editar')" title="Eliminar esta ocurrencia">
                                            <a-button size="small" danger @click="eliminarOcurrencia(o)">
                                                <template #icon><DeleteOutlined /></template>
                                            </a-button>
                                        </a-tooltip>
                                    </a-space>
                                </div>
                            </a-timeline-item>
                        </a-timeline>

                        <div v-else class="vacio-box">
                            <CalendarOutlined />
                            <span>Aún no se han generado ocurrencias para este plan</span>
                            <a-button v-if="puede('mantenimientos.editar')" type="primary" size="small" @click="modalGenerar.abierto = true">
                                <template #icon><PlusOutlined /></template>
                                Generar ahora
                            </a-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
