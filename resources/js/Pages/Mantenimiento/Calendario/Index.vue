<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/es';
import {
    ArrowRightOutlined,
    CalendarOutlined,
    CheckSquareOutlined,
    ClockCircleOutlined,
    CloseOutlined,
    EnvironmentOutlined,
    FilterOutlined,
    FlagOutlined,
    LeftOutlined,
    RightOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

dayjs.locale('es');

const props = defineProps({
    eventos: { type: Array, default: () => [] },
    rango: { type: Object, required: true },
    sucursalId: { type: [Number, String], default: null },
    puedeVerTodas: { type: Boolean, default: false },
    filtros: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const mesActual = ref(dayjs(props.rango.desde));

/* Semana fija en Lunes */
const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

const filtros = ref({
    sucursal_id: props.sucursalId ?? 'todas',
    tipo_id: props.filtros.tipo_id ?? undefined,
    prioridad_id: props.filtros.prioridad_id ?? undefined,
    estado_id: props.filtros.estado_id ?? undefined,
    tecnico_id: props.filtros.tecnico_id ?? undefined,
});

const hayFiltros = computed(() =>
    filtros.value.sucursal_id !== 'todas'
    || Object.entries(filtros.value).some(([k, v]) => k !== 'sucursal_id' && v !== undefined && v !== null && v !== ''),
);

const navegar = (fechaBase) => {
    const desde = fechaBase.startOf('month').format('YYYY-MM-DD');
    const hasta = fechaBase.endOf('month').format('YYYY-MM-DD');
    const params = { desde, hasta };
    Object.entries(filtros.value).forEach(([k, v]) => {
        if (v !== undefined && v !== null && v !== '') params[k] = v;
    });
    router.get(route('calendario.index'), params, { preserveState: true, preserveScroll: true, replace: true });
};

const cambiarMes = (delta) => {
    mesActual.value = mesActual.value.add(delta, 'month');
    navegar(mesActual.value);
};
const irHoy = () => {
    mesActual.value = dayjs().startOf('month');
    navegar(mesActual.value);
};
const aplicarFiltros = () => navegar(mesActual.value);
const limpiarFiltros = () => {
    filtros.value = {
        sucursal_id: 'todas',
        tipo_id: undefined,
        prioridad_id: undefined,
        estado_id: undefined,
        tecnico_id: undefined,
    };
    navegar(mesActual.value);
};

/* Eventos por día */
const eventosPorDia = computed(() => {
    const mapa = {};
    for (const ev of props.eventos) {
        if (!ev.inicio) continue;
        const clave = dayjs(ev.inicio).format('YYYY-MM-DD');
        (mapa[clave] ??= []).push(ev);
    }
    return mapa;
});

/* Paleta por categoría */
const CATEGORIA_INFO = {
    preventivo: { color: '#1f9e86', color2: '#0d7a66', soft: '#e4f4ec', icono: CalendarOutlined, etq: 'Preventivo' },
    correctivo: { color: '#e08a1e', color2: '#a86717', soft: '#fdf3e6', icono: ToolOutlined, etq: 'Correctivo' },
    predictivo: { color: '#0d84c9', color2: '#0f6fb0', soft: '#e6f2fb', icono: CalendarOutlined, etq: 'Predictivo' },
    tarea: { color: '#6b4bc9', color2: '#563a9e', soft: '#efe9fb', icono: CheckSquareOutlined, etq: 'Tarea' },
};

const colorCategoria = (cat) => CATEGORIA_INFO[cat]?.color ?? '#64748b';
const color2Categoria = (cat) => CATEGORIA_INFO[cat]?.color2 ?? '#475569';
const softCategoria = (cat) => CATEGORIA_INFO[cat]?.soft ?? '#f1f5f9';

const iconoEvento = (tipoEvento, categoria) => {
    if (tipoEvento === 'tarea') return CheckSquareOutlined;
    if (tipoEvento === 'orden') return ToolOutlined;
    return CATEGORIA_INFO[categoria]?.icono ?? CalendarOutlined;
};

const hoy = dayjs().format('YYYY-MM-DD');

const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));
const opcionesSucursal = computed(() => [
    ...(props.puedeVerTodas ? [{ value: 'todas', label: 'Todas las sucursales' }] : []),
    ...opciones(props.catalogos.sucursales),
]);

/* Modal */
const eventoSeleccionado = ref(null);
const abrirEvento = (ev) => (eventoSeleccionado.value = ev);
const cerrarEvento = () => (eventoSeleccionado.value = null);

const ETIQUETA_TIPO_EVENTO = {
    orden: 'Orden de mantenimiento',
    ocurrencia: 'Preventivo programado',
    tarea: 'Tarea',
};
const ETIQUETA_ESTADO_TAREA = {
    pendiente: 'Pendiente',
    en_proceso: 'En proceso',
    realizada: 'Realizada',
    cancelada: 'Cancelada',
};
const COLOR_ESTADO_TAREA = {
    pendiente: 'gold',
    en_proceso: 'blue',
    realizada: 'green',
    cancelada: 'red',
};

const estadoEvento = (ev) => {
    if (!ev) return '';
    if (ev.tipo_evento === 'tarea') return ETIQUETA_ESTADO_TAREA[ev.estado] ?? ev.estado;
    if (ev.tipo_evento === 'orden') return ev.estado_nombre ?? ev.estado;
    return ev.estado;
};

const colorEstadoEvento = (ev) => {
    if (ev?.tipo_evento === 'tarea') return COLOR_ESTADO_TAREA[ev.estado] ?? 'default';
    return 'default';
};

const colorPrincipalEvento = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!ev) return '#0d84c9';
    if (ev.color) return ev.color;
    return colorCategoria(ev.categoria);
});

const colorSecundarioEvento = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!ev) return '#0f6fb0';
    return color2Categoria(ev.categoria);
});

const hexEstadoEvento = (ev) => {
    if (!ev) return '#64748b';
    if (ev.tipo_evento === 'tarea') {
        return {
            pendiente: '#e08a1e',
            en_proceso: '#0d84c9',
            realizada: '#1f9e86',
            cancelada: '#d64545',
        }[ev.estado] ?? '#64748b';
    }
    return ev.color || colorCategoria(ev.categoria);
};

const fechaHora = (v) => (v ? dayjs(v).format('D [de] MMMM, YYYY · h:mm A') : null);

const esTarea = computed(() => eventoSeleccionado.value?.tipo_evento === 'tarea');
const esOrden = computed(() => ['orden', 'ocurrencia'].includes(eventoSeleccionado.value?.tipo_evento));

/* Tarea: Descripción full, Responsable + Prioridad, Fecha full */
const tareaDescripcion = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esTarea.value || !ev?.descripcion) return null;
    return { icono: CheckSquareOutlined, label: 'Descripción', valor: ev.descripcion };
});

const tareaResponsable = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esTarea.value || !ev?.responsables) return null;
    return { icono: UserOutlined, label: 'Responsable(s)', valor: ev.responsables, color: '#6b4bc9' };
});

const tareaPrioridad = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esTarea.value || !ev?.prioridad_nombre) return null;
    return { icono: FlagOutlined, label: 'Prioridad', valor: ev.prioridad_nombre, color: ev.color || '#d64545' };
});

const tareaFecha = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esTarea.value || !ev?.inicio) return null;
    return { icono: CalendarOutlined, label: 'Fecha y hora', valor: fechaHora(ev.inicio) };
});

/* Orden: Tipo+Prioridad, Equipo+Sucursal, Técnicos full, Descripción full, Fecha full */
const ordenPrincipal = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esOrden.value || !ev?.objetivo) return null;
    return {
        icono: ev.objetivo_tipo === 'ubicacion' ? EnvironmentOutlined : ToolOutlined,
        label: ev.objetivo_tipo === 'ubicacion' ? 'Instalación' : 'Equipo',
        valor: ev.objetivo,
        color: '#0d84c9',
    };
});

const ordenSucursal = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esOrden.value || !ev?.sucursal_nombre) return null;
    return { icono: EnvironmentOutlined, label: 'Sucursal', valor: ev.sucursal_nombre, color: '#e08a1e' };
});

const ordenTipo = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esOrden.value || !ev?.tipo_nombre) return null;
    return { icono: ToolOutlined, label: 'Tipo', valor: ev.tipo_nombre, color: '#1f9e86' };
});

const ordenPrioridad = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esOrden.value || !ev?.prioridad_nombre) return null;
    return { icono: FlagOutlined, label: 'Prioridad', valor: ev.prioridad_nombre, color: ev.color || '#d64545' };
});

const ordenTecnicos = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esOrden.value || !ev?.tecnicos) return null;
    return { icono: UserOutlined, label: 'Técnico(s)', valor: ev.tecnicos };
});

const ordenDescripcion = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esOrden.value || !ev?.descripcion) return null;
    return { icono: CheckSquareOutlined, label: 'Descripción', valor: ev.descripcion };
});

const ordenFecha = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!esOrden.value || !ev?.inicio) return null;
    return {
        icono: CalendarOutlined,
        label: ev.fin ? 'Programado' : 'Fecha',
        valor: ev.fin
            ? `${fechaHora(ev.inicio)} — ${dayjs(ev.fin).format('h:mm A')}`
            : fechaHora(ev.inicio),
    };
});

const puedeVerCompleto = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!ev) return false;
    if (ev.tipo_evento === 'orden') return true;
    if (ev.tipo_evento === 'tarea') return true;
    if (ev.plan_id) return true;
    return false;
});

const verCompleto = () => {
    const ev = eventoSeleccionado.value;
    if (!ev) return;
    if (ev.tipo_evento === 'orden') router.visit(route('mantenimientos.show', ev.id));
    else if (ev.tipo_evento === 'tarea') router.visit(route('tareas.show', ev.id));
    else if (ev.plan_id) router.visit(route('planes.show', ev.plan_id));
};

/* ==========================================================
   KPIs: EVENTOS, ÓRDENES, TAREAS
   - Eventos = total de eventos (órdenes + ocurrencias + tareas)
   - Órdenes = órdenes + ocurrencias preventivas
   - Tareas  = tareas
   ========================================================== */
const resumen = computed(() => {
    const total = props.eventos.length;
    const ordenes = props.eventos.filter((e) => ['orden', 'ocurrencia'].includes(e.tipo_evento)).length;
    const tareas = props.eventos.filter((e) => e.tipo_evento === 'tarea').length;
    return { total, ordenes, tareas };
});

const tarjetas = computed(() => [
    {
        etq: 'Eventos',
        val: resumen.value.total,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
        icono: CalendarOutlined,
    },
    {
        etq: 'Órdenes',
        val: resumen.value.ordenes,
        color: '#1f9e86',
        color2: '#0d7a66',
        soft: '#e4f4ec',
        icono: ToolOutlined,
    },
    {
        etq: 'Tareas',
        val: resumen.value.tareas,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
        icono: CheckSquareOutlined,
    },
]);
</script>

<template>
    <Head title="Calendario" />

    <AppLayout
        titulo="Calendario de trabajos"
        descripcion="Vista mensual de órdenes programadas y ocurrencias preventivas pendientes."
    >
        <!-- Barra de controles -->
        <div class="cal-controles">
            <div class="cal-nav">
                <button type="button" class="cal-nav__btn" title="Mes anterior" @click="cambiarMes(-1)">
                    <LeftOutlined />
                </button>
                <button type="button" class="cal-nav__hoy" @click="irHoy">Hoy</button>
                <button type="button" class="cal-nav__btn" title="Mes siguiente" @click="cambiarMes(1)">
                    <RightOutlined />
                </button>
                <span class="cal-nav__mes">{{ mesActual.format('MMMM YYYY') }}</span>
            </div>

            <div class="cal-filtros">
                <a-select
                    v-model:value="filtros.sucursal_id"
                    :options="opcionesSucursal"
                    placeholder="Sucursal"
                    size="middle"
                    class="cal-filtros__select"
                    @change="aplicarFiltros"
                />
                <a-select
                    v-model:value="filtros.tipo_id"
                    :options="opciones(catalogos.tipos)"
                    allow-clear
                    placeholder="Tipo"
                    size="middle"
                    class="cal-filtros__select"
                    @change="aplicarFiltros"
                />
                <a-select
                    v-model:value="filtros.prioridad_id"
                    :options="opciones(catalogos.prioridades)"
                    allow-clear
                    placeholder="Prioridad"
                    size="middle"
                    class="cal-filtros__select"
                    @change="aplicarFiltros"
                />
                <a-select
                    v-model:value="filtros.estado_id"
                    :options="opciones(catalogos.estados)"
                    allow-clear
                    placeholder="Estado"
                    size="middle"
                    class="cal-filtros__select"
                    @change="aplicarFiltros"
                />
                <a-select
                    v-model:value="filtros.tecnico_id"
                    :options="opciones(catalogos.tecnicos)"
                    allow-clear
                    placeholder="Técnico"
                    size="middle"
                    class="cal-filtros__select"
                    @change="aplicarFiltros"
                />
                <a-button v-if="hayFiltros" class="cal-filtros__limpiar" @click="limpiarFiltros">
                    <template #icon><FilterOutlined /></template>
                    Limpiar
                </a-button>
            </div>
        </div>

        <!-- KPIs: Eventos, Órdenes, Tareas -->
        <div class="ctarjs">
            <div
                v-for="t in tarjetas"
                :key="t.etq"
                class="ctarj"
                :style="{ '--acc': t.color, '--acc2': t.color2, '--soft': t.soft }"
            >
                <div class="ctarj__glow"></div>
                <span class="ctarj__ic"><component :is="t.icono" /></span>
                <div class="ctarj__txt">
                    <div class="ctarj__v">{{ t.val }}</div>
                    <div class="ctarj__e">{{ t.etq }}</div>
                </div>
            </div>
        </div>

        <!-- Calendario -->
        <div class="cal-card">
            <div class="cal-semana">
                <span v-for="d in diasSemana" :key="d" class="cal-semana__d">{{ d }}</span>
            </div>

            <a-calendar v-model:value="mesActual" :fullscreen="true">
                <template #headerRender><span /></template>

                <template #dateFullCellRender="{ current }">
                    <div
                        class="cel"
                        :class="{
                            'cel--hoy': current.format('YYYY-MM-DD') === hoy,
                            'cel--otro': current.month() !== mesActual.month(),
                            'cel--finde': [0, 6].includes(current.day()),
                        }"
                    >
                        <span class="cel__n">{{ current.date() }}</span>

                        <ul class="eventos">
                            <li
                                v-for="ev in (eventosPorDia[current.format('YYYY-MM-DD')] || []).slice(0, 2)"
                                :key="ev.tipo_evento + ev.id"
                                class="evento"
                                :class="`evento--${ev.tipo_evento}`"
                                :style="{
                                    '--c': ev.color || colorCategoria(ev.categoria),
                                    '--c2': color2Categoria(ev.categoria),
                                    '--soft': softCategoria(ev.categoria),
                                }"
                                @click.stop="abrirEvento(ev)"
                            >
                                <span class="evento__ic">
                                    <component :is="iconoEvento(ev.tipo_evento, ev.categoria)" />
                                </span>
                                <span class="evento__t">{{ ev.titulo }}</span>
                            </li>

                            <li
                                v-if="(eventosPorDia[current.format('YYYY-MM-DD')] || []).length > 2"
                                class="evento-mas"
                                @click.stop="abrirEvento((eventosPorDia[current.format('YYYY-MM-DD')] || [])[2])"
                            >
                                +{{ (eventosPorDia[current.format('YYYY-MM-DD')] || []).length - 2 }} más
                            </li>
                        </ul>
                    </div>
                </template>
            </a-calendar>
        </div>

        <!-- Modal -->
        <a-modal
            :open="!!eventoSeleccionado"
            :footer="null"
            :closable="false"
            centered
            :width="620"
            class="modal-evento"
            @cancel="cerrarEvento"
        >
            <div
                v-if="eventoSeleccionado"
                class="modal-evento__wrap"
                :style="{ '--ec': colorPrincipalEvento, '--ec2': colorSecundarioEvento }"
            >
                <header class="modal-evento__head">
                    <div class="modal-evento__head-glow"></div>
                    <div class="modal-evento__ico">
                        <component :is="iconoEvento(eventoSeleccionado.tipo_evento, eventoSeleccionado.categoria)" />
                    </div>
                    <div class="modal-evento__meta">
                        <div class="modal-evento__titulo-row">
                            <span class="modal-evento__tipo">
                                {{ ETIQUETA_TIPO_EVENTO[eventoSeleccionado.tipo_evento] ?? 'Evento' }}
                            </span>
                            <span
                                v-if="estadoEvento(eventoSeleccionado)"
                                class="modal-evento__estado-inline"
                            >
                                <span
                                    class="modal-evento__estado-dot"
                                    :style="{ background: hexEstadoEvento(eventoSeleccionado) }"
                                ></span>
                                {{ estadoEvento(eventoSeleccionado) }}
                            </span>
                        </div>
                        <h3 class="modal-evento__titulo">
                            {{ eventoSeleccionado.titulo }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        class="modal-evento__close"
                        title="Cerrar"
                        @click="cerrarEvento"
                    >
                        <CloseOutlined />
                    </button>
                </header>

                <div class="modal-evento__body">
                    <!-- TAREA -->
                    <template v-if="esTarea">
                        <div v-if="tareaDescripcion" class="modal-evento__full" style="--mc: #6b4bc9">
                            <span class="modal-evento__full-ic">
                                <component :is="tareaDescripcion.icono" />
                            </span>
                            <div class="modal-evento__full-t">
                                <span class="modal-evento__full-l">{{ tareaDescripcion.label }}</span>
                                <span class="modal-evento__full-v">{{ tareaDescripcion.valor }}</span>
                            </div>
                        </div>

                        <div class="modal-evento__grid">
                            <div v-if="tareaResponsable" class="modal-evento__mini" :style="{ '--mc': tareaResponsable.color }">
                                <span class="modal-evento__mini-ic">
                                    <component :is="tareaResponsable.icono" />
                                </span>
                                <span class="modal-evento__mini-t">
                                    <span class="modal-evento__mini-l">{{ tareaResponsable.label }}</span>
                                    <span class="modal-evento__mini-v">{{ tareaResponsable.valor }}</span>
                                </span>
                            </div>
                            <div v-if="tareaPrioridad" class="modal-evento__mini" :style="{ '--mc': tareaPrioridad.color }">
                                <span class="modal-evento__mini-ic">
                                    <component :is="tareaPrioridad.icono" />
                                </span>
                                <span class="modal-evento__mini-t">
                                    <span class="modal-evento__mini-l">{{ tareaPrioridad.label }}</span>
                                    <span class="modal-evento__mini-v">{{ tareaPrioridad.valor }}</span>
                                </span>
                            </div>
                        </div>

                        <div v-if="tareaFecha" class="modal-evento__full" style="--mc: #173a5f">
                            <span class="modal-evento__full-ic">
                                <component :is="tareaFecha.icono" />
                            </span>
                            <div class="modal-evento__full-t">
                                <span class="modal-evento__full-l">{{ tareaFecha.label }}</span>
                                <span class="modal-evento__full-v">{{ tareaFecha.valor }}</span>
                            </div>
                        </div>
                    </template>

                    <!-- ORDEN / OCURRENCIA -->
                    <template v-else-if="esOrden">
                        <div v-if="ordenTipo || ordenPrioridad" class="modal-evento__grid">
                            <div v-if="ordenTipo" class="modal-evento__mini" :style="{ '--mc': ordenTipo.color }">
                                <span class="modal-evento__mini-ic">
                                    <component :is="ordenTipo.icono" />
                                </span>
                                <span class="modal-evento__mini-t">
                                    <span class="modal-evento__mini-l">{{ ordenTipo.label }}</span>
                                    <span class="modal-evento__mini-v">{{ ordenTipo.valor }}</span>
                                </span>
                            </div>
                            <div v-if="ordenPrioridad" class="modal-evento__mini" :style="{ '--mc': ordenPrioridad.color }">
                                <span class="modal-evento__mini-ic">
                                    <component :is="ordenPrioridad.icono" />
                                </span>
                                <span class="modal-evento__mini-t">
                                    <span class="modal-evento__mini-l">{{ ordenPrioridad.label }}</span>
                                    <span class="modal-evento__mini-v">{{ ordenPrioridad.valor }}</span>
                                </span>
                            </div>
                        </div>

                        <div v-if="ordenPrincipal || ordenSucursal" class="modal-evento__grid">
                            <div v-if="ordenPrincipal" class="modal-evento__mini" :style="{ '--mc': ordenPrincipal.color }">
                                <span class="modal-evento__mini-ic">
                                    <component :is="ordenPrincipal.icono" />
                                </span>
                                <span class="modal-evento__mini-t">
                                    <span class="modal-evento__mini-l">{{ ordenPrincipal.label }}</span>
                                    <span class="modal-evento__mini-v">{{ ordenPrincipal.valor }}</span>
                                </span>
                            </div>
                            <div v-if="ordenSucursal" class="modal-evento__mini" :style="{ '--mc': ordenSucursal.color }">
                                <span class="modal-evento__mini-ic">
                                    <component :is="ordenSucursal.icono" />
                                </span>
                                <span class="modal-evento__mini-t">
                                    <span class="modal-evento__mini-l">{{ ordenSucursal.label }}</span>
                                    <span class="modal-evento__mini-v">{{ ordenSucursal.valor }}</span>
                                </span>
                            </div>
                        </div>

                        <div v-if="ordenTecnicos" class="modal-evento__full" style="--mc: #173a5f">
                            <span class="modal-evento__full-ic">
                                <component :is="ordenTecnicos.icono" />
                            </span>
                            <div class="modal-evento__full-t">
                                <span class="modal-evento__full-l">{{ ordenTecnicos.label }}</span>
                                <span class="modal-evento__full-v">{{ ordenTecnicos.valor }}</span>
                            </div>
                        </div>

                        <div v-if="ordenDescripcion" class="modal-evento__full" style="--mc: #6b4bc9">
                            <span class="modal-evento__full-ic">
                                <component :is="ordenDescripcion.icono" />
                            </span>
                            <div class="modal-evento__full-t">
                                <span class="modal-evento__full-l">{{ ordenDescripcion.label }}</span>
                                <span class="modal-evento__full-v">{{ ordenDescripcion.valor }}</span>
                            </div>
                        </div>

                        <div v-if="ordenFecha" class="modal-evento__full" style="--mc: #173a5f">
                            <span class="modal-evento__full-ic">
                                <component :is="ordenFecha.icono" />
                            </span>
                            <div class="modal-evento__full-t">
                                <span class="modal-evento__full-l">{{ ordenFecha.label }}</span>
                                <span class="modal-evento__full-v">{{ ordenFecha.valor }}</span>
                            </div>
                        </div>
                    </template>

                    <div class="modal-evento__footer">
                        <a-button size="large" @click="cerrarEvento">
                            Cerrar
                        </a-button>
                        <a-button
                            v-if="puedeVerCompleto"
                            type="primary"
                            size="large"
                            class="modal-evento__ver"
                            @click="verCompleto"
                        >
                            Ver completo
                            <ArrowRightOutlined />
                        </a-button>
                    </div>
                </div>
            </div>
        </a-modal>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Barra de controles
   ========================================================== */
.cal-controles {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
    padding: 10px 14px;
    background: linear-gradient(180deg, #ffffff 0%, #fafbfd 100%);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.05);
}

.cal-nav {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.cal-nav__btn {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    cursor: pointer;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.14s ease, border-color 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.cal-nav__btn:hover {
    background: #eef4fb;
    border-color: #cfe4f5;
    color: #0d84c9;
    transform: translateY(-1px);
}

.cal-nav__hoy {
    height: 32px;
    padding: 0 12px;
    border-radius: 9px;
    border: 1px solid #cfe4f5;
    background: #eef4fb;
    color: #0f6fb0;
    font-weight: 800;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
}

.cal-nav__hoy:hover {
    background: #e0ecf7;
    border-color: #0d84c9;
    transform: translateY(-1px);
}

.cal-nav__mes {
    font-size: 15px;
    font-weight: 800;
    color: #173a5f;
    text-transform: capitalize;
    letter-spacing: -0.2px;
    margin-left: 4px;
    white-space: nowrap;
}

.cal-filtros {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}

.cal-filtros__select {
    min-width: 130px;
}

.cal-filtros__limpiar {
    color: #d64545;
    border-color: #f4dede;
    background: #fdf4f4;
    font-weight: 700;
    transition: background 0.14s ease, border-color 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.cal-filtros__limpiar:hover {
    background: #fdecec !important;
    border-color: #d64545 !important;
    color: #a83232 !important;
    transform: translateY(-1px);
}

@media (max-width: 767px) {
    .cal-controles {
        flex-direction: column;
        align-items: stretch;
    }

    .cal-filtros {
        justify-content: flex-start;
    }

    .cal-filtros__select {
        flex: 1 1 120px;
        min-width: 0;
    }
}

/* KPIs (3 tarjetas) */
.ctarjs {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 11px;
    margin-bottom: 10px;
}

@media (max-width: 767px) {
    .ctarjs {
        grid-template-columns: 1fr;
    }
}

.ctarj {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    box-shadow: 0 2px 6px -3px rgba(15, 37, 71, 0.1);
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.ctarj:hover {
    transform: translateY(-2px);
    border-color: var(--acc);
    box-shadow: 0 8px 20px -10px rgba(15, 37, 71, 0.32);
}

.ctarj::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, var(--acc) 0%, var(--acc2) 100%);
}

.ctarj__glow {
    position: absolute;
    right: -30px;
    top: -30px;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: radial-gradient(circle, var(--soft) 0%, transparent 70%);
    opacity: 0.9;
    pointer-events: none;
}

.ctarj__ic {
    position: relative;
    z-index: 1;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: linear-gradient(135deg, var(--acc), var(--acc2));
    font-size: 17px;
    box-shadow: 0 4px 10px -4px color-mix(in srgb, var(--acc) 60%, transparent);
    flex-shrink: 0;
}

.ctarj__txt {
    position: relative;
    z-index: 1;
    min-width: 0;
}

.ctarj__v {
    font-size: 20px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.05;
    letter-spacing: -0.4px;
}

.ctarj__e {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
    color: #7b8a9c;
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Calendario compacto */
.cal-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    padding: 8px 10px 10px;
    display: flex;
    flex-direction: column;
}

.cal-card :deep(.ant-picker-calendar) {
    background: transparent;
    order: 1;
}

.cal-card :deep(.ant-picker-calendar-header) {
    display: none;
}

.cal-card :deep(.ant-picker-content thead) {
    display: none;
}

.cal-card :deep(.ant-picker-cell) {
    padding: 2px !important;
}

.cal-card :deep(.ant-picker-calendar-date) {
    display: none;
}

.cal-semana {
    order: 0;
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    padding: 4px 2px 8px;
    margin-bottom: 2px;
    border-bottom: 1px solid #eef2f7;
}

.cal-semana__d {
    text-align: center;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #7b8a9c;
}

.cal-semana__d:nth-child(6),
.cal-semana__d:nth-child(7) {
    color: #e08a1e;
}

.cel {
    min-height: 76px;
    border: 1px solid #eef2f7;
    border-radius: 8px;
    padding: 4px 6px;
    transition: background 0.14s ease, border-color 0.14s ease, box-shadow 0.14s ease;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.cel:hover {
    background: #fafbfd;
    border-color: #dbe3ec;
    box-shadow: 0 2px 8px -4px rgba(15, 37, 71, 0.12);
}

.cel--otro {
    opacity: 0.35;
    background: #fbfcfe;
}

.cel--finde .cel__n {
    color: #e08a1e;
}

.cel--hoy {
    border-color: #1f9e86;
    background: linear-gradient(180deg, #f4fbf8 0%, #ecf8f3 100%);
    box-shadow: inset 0 0 0 1px rgba(31, 158, 134, 0.22);
}

.cel--hoy:hover {
    background: linear-gradient(180deg, #ecf8f3 0%, #e4f4ec 100%);
    border-color: #1f9e86;
}

.cel__n {
    font-size: 11.5px;
    font-weight: 800;
    color: #475569;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 4px;
    border-radius: 6px;
    align-self: flex-start;
}

.cel--hoy .cel__n {
    color: #fff;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 3px 8px -3px rgba(31, 158, 134, 0.55);
}

.eventos {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-height: 0;
    overflow: hidden;
}

.evento {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    line-height: 1.15;
    padding: 2px 5px 2px 4px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 700;
    background: linear-gradient(135deg,
            color-mix(in srgb, var(--c) 14%, #fff) 0%,
            color-mix(in srgb, var(--c) 6%, #fff) 100%);
    color: color-mix(in srgb, var(--c2) 90%, #1c2b3a);
    border: 1px solid color-mix(in srgb, var(--c) 22%, transparent);
    border-left: 2.5px solid var(--c);
    transition: background 0.14s ease, transform 0.14s ease;
    overflow: hidden;
}

.evento:hover {
    background: linear-gradient(135deg,
            color-mix(in srgb, var(--c) 22%, #fff) 0%,
            color-mix(in srgb, var(--c) 12%, #fff) 100%);
    transform: translateX(1px);
}

.evento__ic {
    width: 13px;
    height: 13px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    color: #fff;
    background: linear-gradient(135deg, var(--c), var(--c2));
    flex-shrink: 0;
}

.evento__t {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
    font-size: 10px;
}

.evento--tarea {
    border-left-style: dashed;
}

.evento-mas {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 5px;
    border-radius: 5px;
    font-size: 9.5px;
    font-weight: 800;
    color: #475569;
    background: #f1f5f9;
    border: 1px dashed #cbd5e1;
    cursor: pointer;
    transition: background 0.14s ease, color 0.14s ease;
}

.evento-mas:hover {
    background: #e2e8f0;
    color: #0f2d50;
}

/* Modal */
.modal-evento :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-evento :deep(.ant-modal-body) {
    padding: 0;
}

.modal-evento__wrap {
    display: flex;
    flex-direction: column;
    background: #fff;
}

.modal-evento__head {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: linear-gradient(135deg,
            color-mix(in srgb, var(--ec) 22%, #ffffff) 0%,
            color-mix(in srgb, var(--ec2) 12%, #ffffff) 60%,
            #ffffff 100%);
    border-bottom: 1px solid color-mix(in srgb, var(--ec) 25%, #e2e8f0);
    overflow: hidden;
}

.modal-evento__head-glow {
    position: absolute;
    right: -60px;
    top: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: radial-gradient(circle, color-mix(in srgb, var(--ec) 32%, transparent) 0%, transparent 70%);
    pointer-events: none;
}

.modal-evento__ico {
    position: relative;
    z-index: 1;
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--ec) 0%, var(--ec2) 100%);
    box-shadow: 0 8px 20px -8px color-mix(in srgb, var(--ec) 80%, transparent);
}

.modal-evento__meta {
    position: relative;
    z-index: 1;
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.modal-evento__titulo-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.modal-evento__tipo {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: color-mix(in srgb, var(--ec2) 90%, #173a5f);
}

.modal-evento__estado-inline {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 9px 2px 7px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: color-mix(in srgb, var(--ec) 12%, #fff);
    border: 1px solid color-mix(in srgb, var(--ec) 30%, transparent);
    color: color-mix(in srgb, var(--ec2) 90%, #173a5f);
    line-height: 1.4;
}

.modal-evento__estado-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--ec) 18%, transparent);
}

.modal-evento__titulo {
    font-size: 16px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.25;
    letter-spacing: -0.2px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.modal-evento__close {
    position: relative;
    z-index: 1;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.14s ease, color 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
}

.modal-evento__close:hover {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.modal-evento__body {
    padding: 16px 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #fff;
}

.modal-evento__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

@media (max-width: 575px) {
    .modal-evento__grid {
        grid-template-columns: 1fr;
    }
}

.modal-evento__mini {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 11px;
    background: linear-gradient(135deg,
            color-mix(in srgb, var(--mc) 6%, #fff) 0%,
            #fff 100%);
    border: 1px solid color-mix(in srgb, var(--mc) 20%, #e2e8f0);
    min-width: 0;
    transition: border-color 0.14s ease, box-shadow 0.14s ease;
}

.modal-evento__mini:hover {
    border-color: color-mix(in srgb, var(--mc) 40%, transparent);
    box-shadow: 0 4px 12px -8px color-mix(in srgb, var(--mc) 60%, transparent);
}

.modal-evento__mini-ic {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--mc), color-mix(in srgb, var(--mc) 70%, #000 10%));
    box-shadow: 0 4px 10px -4px color-mix(in srgb, var(--mc) 60%, transparent);
}

.modal-evento__mini-t {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    flex: 1;
}

.modal-evento__mini-l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
    color: #7b8a9c;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.modal-evento__mini-v {
    font-size: 13px;
    font-weight: 700;
    color: #173a5f;
    word-break: break-word;
    line-height: 1.25;
}

.modal-evento__full {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 11px;
    background: linear-gradient(135deg,
            color-mix(in srgb, var(--mc) 6%, #fff) 0%,
            #fff 100%);
    border: 1px solid color-mix(in srgb, var(--mc) 20%, #e2e8f0);
    min-width: 0;
}

.modal-evento__full-ic {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, var(--mc), color-mix(in srgb, var(--mc) 70%, #000 10%));
    box-shadow: 0 4px 10px -4px color-mix(in srgb, var(--mc) 60%, transparent);
}

.modal-evento__full-t {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
    flex: 1;
}

.modal-evento__full-l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
    color: #7b8a9c;
    line-height: 1.1;
}

.modal-evento__full-v {
    font-size: 13.5px;
    font-weight: 700;
    color: #173a5f;
    line-height: 1.35;
    word-break: break-word;
}

.modal-evento__footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid #eef2f7;
    margin-top: 2px;
}

.modal-evento__ver {
    background: linear-gradient(135deg, var(--ec) 0%, var(--ec2) 100%) !important;
    border-color: var(--ec) !important;
    font-weight: 800;
    color: #fff !important;
    box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--ec) 70%, transparent);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.modal-evento__ver:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px color-mix(in srgb, var(--ec) 80%, transparent);
}
</style>