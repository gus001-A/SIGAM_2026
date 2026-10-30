<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/es';
import {
    ApartmentOutlined,
    ArrowLeftOutlined,
    ArrowRightOutlined,
    BankOutlined,
    BranchesOutlined,
    CalendarOutlined,
    CheckSquareOutlined,
    ClearOutlined,
    CloseOutlined,
    DownOutlined,
    EnvironmentOutlined,
    FileTextOutlined,
    FilterOutlined,
    FlagOutlined,
    FolderOutlined,
    GlobalOutlined,
    LoadingOutlined,
    PlusOutlined,
    RightOutlined,
    SearchOutlined,
    TeamOutlined,
    ToolOutlined,
    UserOutlined,
    WarningOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ModalOrden from '@/Components/ModalOrden.vue';
import ModalSolicitud from '@/Components/ModalSolicitud.vue';
import ModalTarea from '@/Components/ModalTarea.vue';
import { usePermisos } from '@/composables/usePermisos';

dayjs.locale('es');

const props = defineProps({
    eventos: { type: Array, default: () => [] },
    rango: { type: Object, required: true },
    sucursalId: { type: [Number, String], default: null },
    puedeVerTodas: { type: Boolean, default: false },
    filtros: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
    // Catálogos de los modales de alta; llegan solo al pedirlos (prop opcional).
    formularios: { type: Object, default: null },
});

const { puede } = usePermisos();

const mesActual = ref(dayjs(props.rango.desde).startOf('month'));

/* Semana fija en Lunes */
const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

const filtros = ref({
    sucursal_id: props.sucursalId ?? 'todas',
    tipo_id: props.filtros.tipo_id ? Number(props.filtros.tipo_id) : undefined,
    prioridad_id: props.filtros.prioridad_id ? Number(props.filtros.prioridad_id) : undefined,
    estado_id: props.filtros.estado_id ? Number(props.filtros.estado_id) : undefined,
    tecnico_id: props.filtros.tecnico_id ? Number(props.filtros.tecnico_id) : undefined,
});

const hayFiltros = computed(() =>
    filtros.value.sucursal_id !== 'todas'
    || Object.entries(filtros.value).some(([k, v]) => k !== 'sucursal_id' && v !== undefined && v !== null && v !== '')
    || tiposOcultos.value.length > 0,
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

const hoy = dayjs().format('YYYY-MM-DD');

/* Día seleccionado: hoy si cae en el mes visible, si no el día 1 */
const diaInicial = (mes) => (dayjs().isSame(mes, 'month') ? hoy : mes.startOf('month').format('YYYY-MM-DD'));
const diaSeleccionado = ref(diaInicial(mesActual.value));

const cambiarMes = (delta) => {
    mesActual.value = mesActual.value.add(delta, 'month');
    diaSeleccionado.value = diaInicial(mesActual.value);
    navegar(mesActual.value);
};
const irHoy = () => {
    mesActual.value = dayjs().startOf('month');
    diaSeleccionado.value = hoy;
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
    tiposOcultos.value = [];
    navegar(mesActual.value);
};

/* ==========================================================
   Tipos de actividad (leyenda, color e ícono de cada evento)
   ========================================================== */
const TIPOS = [
    { clave: 'preventivo', etq: 'Mantenimiento preventivo', corto: 'Preventivo', color: '#1e6fd9', soft: '#eaf2fd', icono: ToolOutlined },
    { clave: 'correctivo', etq: 'Mantenimiento correctivo', corto: 'Correctivo', color: '#ef7d12', soft: '#fff3e6', icono: ToolOutlined },
    { clave: 'limpieza', etq: 'Limpieza / Sanitización', corto: 'Limpieza', color: '#0ea5b7', soft: '#e6f7f9', icono: ClearOutlined },
    { clave: 'tarea', etq: 'Tarea', corto: 'Tarea', color: '#7c4dd8', soft: '#f1ebfc', icono: CheckSquareOutlined },
    { clave: 'verificacion', etq: 'Verificación / Inspección', corto: 'Verificación', color: '#1f9e5a', soft: '#e8f6ee', icono: SearchOutlined },
    { clave: 'incidencia', etq: 'Incidencia / Urgente', corto: 'Incidencia', color: '#e03131', soft: '#fdecec', icono: WarningOutlined },
];
const TIPO_POR_CLAVE = Object.fromEntries(TIPOS.map((t) => [t.clave, t]));

const CATEGORIA_A_TIPO = {
    preventivo: 'preventivo',
    predictivo: 'preventivo',
    correctivo: 'correctivo',
    limpieza: 'limpieza',
    inspeccion: 'verificacion',
    verificacion: 'verificacion',
    urgente: 'incidencia',
    tarea: 'tarea',
    incidencia: 'incidencia',
};

const tipoDe = (ev) => {
    if (ev?.tipo_evento === 'tarea') return TIPO_POR_CLAVE.tarea;
    return TIPO_POR_CLAVE[CATEGORIA_A_TIPO[ev?.categoria] ?? 'preventivo'];
};

const tiposOcultos = ref([]);
const alternarTipo = (clave) => {
    tiposOcultos.value = tiposOcultos.value.includes(clave)
        ? tiposOcultos.value.filter((c) => c !== clave)
        : [...tiposOcultos.value, clave];
};

/* Eventos por día (ordenados por hora) */
const eventosPorDia = computed(() => {
    const mapa = {};
    for (const ev of props.eventos) {
        if (!ev.inicio) continue;
        if (tiposOcultos.value.includes(tipoDe(ev).clave)) continue;
        const clave = dayjs(ev.inicio).format('YYYY-MM-DD');
        (mapa[clave] ??= []).push(ev);
    }
    Object.values(mapa).forEach((l) => l.sort((a, b) => String(a.inicio).localeCompare(String(b.inicio))));
    return mapa;
});

/* Cuadrícula del mes: semanas de Lunes a Domingo */
const semanas = computed(() => {
    const inicioMes = mesActual.value.startOf('month');
    const desfase = (inicioMes.day() + 6) % 7;
    const inicio = inicioMes.subtract(desfase, 'day');
    const total = Math.ceil((desfase + inicioMes.daysInMonth()) / 7) * 7;
    const dias = Array.from({ length: total }, (_, i) => {
        const d = inicio.add(i, 'day');
        return { fecha: d, clave: d.format('YYYY-MM-DD'), otroMes: d.month() !== inicioMes.month() };
    });
    return Array.from({ length: total / 7 }, (_, i) => dias.slice(i * 7, i * 7 + 7));
});

// Eventos visibles por celda: dependen del alto de cada semana (el resto va en "+N más").
const maxPorCelda = computed(() => {
    if (!altoLayout.value) return 2;
    const fila = (altoLayout.value - 36) / semanas.value.length;
    return Math.min(3, Math.max(1, Math.floor((fila - 50) / 36)));
});
const seleccionarDia = (dia) => {
    if (dia.otroMes) {
        mesActual.value = dia.fecha.startOf('month');
        diaSeleccionado.value = dia.clave;
        navegar(mesActual.value);
        return;
    }
    diaSeleccionado.value = dia.clave;
};

const eventosDelDia = computed(() => eventosPorDia.value[diaSeleccionado.value] ?? []);
const tituloDia = computed(() => {
    const t = dayjs(diaSeleccionado.value).format('dddd D [de] MMMM [de] YYYY');
    return t.charAt(0).toUpperCase() + t.slice(1);
});

const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));
const opcionesSucursal = computed(() => [
    ...(props.puedeVerTodas ? [{ value: 'todas', label: 'Todas las sucursales' }] : []),
    ...opciones(props.catalogos.sucursales),
]);

/* Botón "Agendar nueva actividad": cada opción abre su formulario en un modal */
const modalOrden = ref(null);
const modalTarea = ref(null);
const modalSolicitud = ref(null);
const cargandoFormulario = ref(false);

const nuevasActividades = computed(() => [
    ...(puede('mantenimientos.crear') ? [{ key: 'orden', label: 'Orden de mantenimiento', icono: ToolOutlined, color: '#ef7d12' }] : []),
    ...(puede('tareas.crear') ? [{ key: 'tarea', label: 'Tarea', icono: CheckSquareOutlined, color: '#7c4dd8' }] : []),
    ...(puede('solicitudes.crear') ? [{ key: 'solicitud', label: 'Solicitud de mantenimiento', icono: WarningOutlined, color: '#e03131' }] : []),
]);

const abrirModalAlta = (key) => {
    const fecha = diaSeleccionado.value;
    if (key === 'orden') modalOrden.value?.abrir(fecha);
    else if (key === 'tarea') modalTarea.value?.abrir(fecha);
    else if (key === 'solicitud') modalSolicitud.value?.abrir(null, fecha);
};

const nuevaActividad = ({ key }) => {
    if (props.formularios) return abrirModalAlta(key);
    cargandoFormulario.value = true;
    router.reload({
        only: ['formularios'],
        onSuccess: () => nextTick(() => abrirModalAlta(key)),
        onFinish: () => (cargandoFormulario.value = false),
    });
};

/* Alto del calendario: ocupa lo que queda de la pantalla, sin scroll de página */
const layoutRef = ref(null);
const altoLayout = ref(null);
const medirAlto = () => {
    const el = layoutRef.value;
    if (!el || window.innerWidth < 1200) {
        altoLayout.value = null;
        return;
    }
    const top = el.getBoundingClientRect().top + window.scrollY;
    altoLayout.value = Math.max(460, window.innerHeight - top - 16);
};
onMounted(() => {
    nextTick(medirAlto);
    window.addEventListener('resize', medirAlto);
});
onUnmounted(() => window.removeEventListener('resize', medirAlto));

/* ==========================================================
   Modal de detalle de una actividad
   ========================================================== */
const eventoSeleccionado = ref(null);
const abrirEvento = (ev) => (eventoSeleccionado.value = ev);
const cerrarEvento = () => (eventoSeleccionado.value = null);

const estadoEvento = (ev) => ev?.estado_nombre ?? ev?.estado ?? '';

/* Personas a cargo (responsables de la tarea o técnicos de la orden) */
const personasDe = (ev) => ev?.personas ?? [];
const textoPersonas = (ev) => {
    const p = personasDe(ev);
    if (!p.length) return ev?.tipo_evento === 'tarea' ? 'Sin responsable' : 'Sin técnico asignado';
    return p.length > 2 ? `${p.slice(0, 2).join(', ')} +${p.length - 2}` : p.join(', ');
};
const iniciales = (nombre) =>
    String(nombre ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((x) => x[0])
        .join('')
        .toUpperCase();

const fechaLarga = (v) => {
    if (!v) return null;
    const t = dayjs(v).format('dddd D [de] MMMM [de] YYYY');
    return t.charAt(0).toUpperCase() + t.slice(1);
};

const ETIQUETA_TIPO_EVENTO = {
    orden: 'Orden de mantenimiento',
    ocurrencia: 'Preventivo programado',
    tarea: 'Tarea',
};

const CLASIFICACION_TAREA = {
    general: { etq: 'General', icono: GlobalOutlined, color: '#0f6fb0' },
    proyecto: { etq: 'Proyecto', icono: FolderOutlined, color: '#7c4dd8' },
    categoria: { etq: 'Categoría', icono: ApartmentOutlined, color: '#1f9e5a' },
};

const detalle = computed(() => {
    const ev = eventoSeleccionado.value;
    if (!ev) return null;
    const tipo = tipoDe(ev);
    const esTarea = ev.tipo_evento === 'tarea';

    const clasif = esTarea ? (CLASIFICACION_TAREA[ev.clasificacion] ?? CLASIFICACION_TAREA.general) : null;

    const datos = esTarea
        ? [
            { icono: CalendarOutlined, label: 'Fecha límite', valor: fechaLarga(ev.inicio) },
            {
                icono: clasif.icono,
                label: 'Clasificación',
                valor: ev.clasificacion_nombre ? `${clasif.etq}: ${ev.clasificacion_nombre}` : clasif.etq,
            },
        ]
        : [
            { icono: CalendarOutlined, label: 'Fecha programada', valor: fechaLarga(ev.inicio) },
            { icono: ToolOutlined, label: 'Tipo de mantenimiento', valor: ev.tipo_nombre },
            {
                icono: ev.objetivo_tipo === 'ubicacion' ? EnvironmentOutlined : ToolOutlined,
                label: ev.objetivo_tipo === 'ubicacion' ? 'Instalación' : 'Equipo',
                valor: ev.objetivo,
            },
            { icono: BankOutlined, label: 'Sucursal', valor: ev.sucursal_nombre },
            { icono: BranchesOutlined, label: 'Origen', valor: ev.origen },
        ];

    return {
        tipo,
        tipoEtq: ETIQUETA_TIPO_EVENTO[ev.tipo_evento] ?? 'Actividad',
        folio: ev.folio ?? null,
        titulo: ev.nombre || ev.titulo,
        estado: estadoEvento(ev),
        prioridad: ev.prioridad_nombre ? { nombre: ev.prioridad_nombre, color: ev.prioridad_color || '#64748b' } : null,
        clasif: clasif ? { ...clasif, nombre: ev.clasificacion_nombre } : null,
        datos: datos.filter((d) => d.valor),
        personasLabel: esTarea ? 'Responsables' : ev.tipo_evento === 'ocurrencia' ? 'Técnico sugerido' : 'Técnicos asignados',
        personas: personasDe(ev),
        descripcionLabel: esTarea ? 'Descripción' : 'Problema reportado',
        descripcion: ev.descripcion || null,
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
</script>

<template>
    <Head title="Calendario" />

    <AppLayout
        titulo="Calendario de trabajos"
        descripcion="Vista mensual de mantenimientos, limpiezas, tareas y actividades programadas."
    >
        <template v-if="nuevasActividades.length" #acciones>
            <a-dropdown :trigger="['click']" placement="bottomRight">
                <button type="button" class="btn-nueva" :disabled="cargandoFormulario">
                    <LoadingOutlined v-if="cargandoFormulario" />
                    <PlusOutlined v-else />
                    <span>Agendar nueva actividad</span>
                    <DownOutlined class="btn-nueva__caret" />
                </button>
                <template #overlay>
                    <a-menu @click="nuevaActividad">
                        <a-menu-item v-for="a in nuevasActividades" :key="a.key">
                            <span class="menu-nueva">
                                <component :is="a.icono" :style="{ color: a.color }" />
                                {{ a.label }}
                            </span>
                        </a-menu-item>
                    </a-menu>
                </template>
            </a-dropdown>
        </template>

        <!-- Barra de controles -->
        <div class="cal-controles">
            <div class="cal-nav">
                <button type="button" class="cal-nav__btn" title="Mes anterior" @click="cambiarMes(-1)">
                    <ArrowLeftOutlined />
                </button>
                <button type="button" class="cal-nav__hoy" @click="irHoy">Hoy</button>
                <button type="button" class="cal-nav__btn" title="Mes siguiente" @click="cambiarMes(1)">
                    <ArrowRightOutlined />
                </button>
                <span class="cal-nav__mes">{{ mesActual.format('MMMM YYYY') }}</span>
            </div>

            <div class="cal-filtros">
                <a-select
                    v-model:value="filtros.sucursal_id"
                    :options="opcionesSucursal"
                    placeholder="Sucursal"
                    class="cal-filtros__select cal-filtros__select--sucursal"
                    @change="aplicarFiltros"
                />
                <a-select
                    v-model:value="filtros.tipo_id"
                    :options="opciones(catalogos.tipos)"
                    allow-clear
                    placeholder="Tipo"
                    class="cal-filtros__select"
                    @change="aplicarFiltros"
                />
                <a-select
                    v-model:value="filtros.estado_id"
                    :options="opciones(catalogos.estados)"
                    allow-clear
                    placeholder="Estado"
                    class="cal-filtros__select"
                    @change="aplicarFiltros"
                />
                <a-select
                    v-model:value="filtros.tecnico_id"
                    :options="opciones(catalogos.tecnicos)"
                    allow-clear
                    show-search
                    option-filter-prop="label"
                    placeholder="Responsable"
                    class="cal-filtros__select"
                    @change="aplicarFiltros"
                />
                <a-tooltip v-if="hayFiltros" title="Limpiar filtros">
                    <button type="button" class="cal-filtros__limpiar" @click="limpiarFiltros">
                        <FilterOutlined />
                    </button>
                </a-tooltip>
            </div>
        </div>

        <div ref="layoutRef" class="cal-layout" :style="altoLayout ? { height: altoLayout + 'px' } : null">
            <!-- Calendario -->
            <div class="cal-card">
                <div class="cal-grid cal-grid--head">
                    <div v-for="d in diasSemana" :key="d" class="cal-head">{{ d }}</div>
                </div>
                <div v-for="(semana, i) in semanas" :key="i" class="cal-grid">
                    <div
                        v-for="dia in semana"
                        :key="dia.clave"
                        class="cel"
                        :class="{
                            'cel--otro': dia.otroMes,
                            'cel--hoy': dia.clave === hoy,
                            'cel--sel': dia.clave === diaSeleccionado,
                        }"
                        @click="seleccionarDia(dia)"
                    >
                        <span class="cel__n">{{ dia.fecha.date() }}</span>

                        <div class="cel__eventos">
                            <button
                                v-for="ev in (eventosPorDia[dia.clave] || []).slice(0, maxPorCelda)"
                                :key="ev.tipo_evento + ev.id"
                                type="button"
                                class="evento"
                                :style="{ '--c': tipoDe(ev).color, '--soft': tipoDe(ev).soft }"
                                :title="`${tipoDe(ev).corto} - ${ev.nombre || ev.titulo}`"
                                @click.stop="abrirEvento(ev)"
                            >
                                <span class="evento__ic"><component :is="tipoDe(ev).icono" /></span>
                                <span class="evento__txt">
                                    <span class="evento__t">
                                        <b>{{ tipoDe(ev).corto }}</b> - {{ ev.nombre || ev.titulo }}
                                    </span>
                                    <span class="evento__h">{{ textoPersonas(ev) }}</span>
                                </span>
                            </button>
                            <button
                                v-if="(eventosPorDia[dia.clave] || []).length > maxPorCelda"
                                type="button"
                                class="evento-mas"
                                @click.stop="seleccionarDia(dia)"
                            >
                                +{{ (eventosPorDia[dia.clave] || []).length - maxPorCelda }} más
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <aside class="cal-side">
                <section class="side-card side-card--dia">
                    <h3 class="side-card__t">{{ tituloDia }}</h3>
                    <p class="side-card__sub">
                        {{ eventosDelDia.length }}
                        {{ eventosDelDia.length === 1 ? 'actividad programada' : 'actividades programadas' }}
                    </p>

                    <div v-if="eventosDelDia.length" class="side-lista">
                        <button
                            v-for="ev in eventosDelDia"
                            :key="ev.tipo_evento + ev.id"
                            type="button"
                            class="side-ev"
                            :style="{ '--c': tipoDe(ev).color, '--soft': tipoDe(ev).soft }"
                            @click="abrirEvento(ev)"
                        >
                            <span class="side-ev__ic"><component :is="tipoDe(ev).icono" /></span>
                            <span class="side-ev__cuerpo">
                                <span class="side-ev__t">{{ tipoDe(ev).corto }} - {{ ev.nombre || ev.titulo }}</span>
                                <span class="side-ev__h"><TeamOutlined /> {{ textoPersonas(ev) }}</span>
                                <span class="side-ev__pie">
                                    <span class="side-ev__lugar">{{ ev.lugar || ev.sucursal_nombre || '' }}</span>
                                    <span v-if="estadoEvento(ev)" class="side-ev__estado">{{ estadoEvento(ev) }}</span>
                                </span>
                            </span>
                            <RightOutlined class="side-ev__flecha" />
                        </button>
                    </div>
                    <div v-else class="side-vacio">
                        <CalendarOutlined />
                        <span>Sin actividades este día</span>
                    </div>
                </section>

                <section class="side-card">
                    <h3 class="side-card__t">Simbología</h3>
                    <ul class="side-leyenda">
                        <li v-for="t in TIPOS" :key="t.clave">
                            <button
                                type="button"
                                class="side-leyenda__item"
                                :class="{ 'side-leyenda__item--off': tiposOcultos.includes(t.clave) }"
                                :style="{ '--c': t.color }"
                                :title="tiposOcultos.includes(t.clave) ? 'Mostrar en el calendario' : 'Ocultar del calendario'"
                                @click="alternarTipo(t.clave)"
                            >
                                <span class="side-leyenda__dot"></span>
                                <component :is="t.icono" class="side-leyenda__ic" />
                                {{ t.etq }}
                            </button>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>

        <!-- Modal de detalle -->
        <a-modal
            :open="!!eventoSeleccionado"
            :footer="null"
            :closable="false"
            centered
            :width="560"
            class="modal-evento"
            @cancel="cerrarEvento"
        >
            <div v-if="detalle" class="md" :style="{ '--c': detalle.tipo.color, '--soft': detalle.tipo.soft }">
                <header class="md__head">
                    <span class="md__ic"><component :is="detalle.tipo.icono" /></span>
                    <div class="md__head-txt">
                        <div class="md__kicker">
                            <span>{{ detalle.tipoEtq }}</span>
                            <span v-if="detalle.folio" class="md__folio">{{ detalle.folio }}</span>
                        </div>
                        <h3 class="md__titulo">{{ detalle.titulo }}</h3>
                        <div class="md__chips">
                            <span v-if="detalle.estado" class="md-chip md-chip--estado">
                                <span class="md-chip__dot"></span>{{ detalle.estado }}
                            </span>
                            <span
                                v-if="detalle.prioridad"
                                class="md-chip"
                                :style="{ '--pc': detalle.prioridad.color }"
                            >
                                <FlagOutlined class="md-chip__flag" />{{ detalle.prioridad.nombre }}
                            </span>
                            <span v-if="detalle.clasif" class="md-chip" :style="{ '--pc': detalle.clasif.color }">
                                <component :is="detalle.clasif.icono" class="md-chip__flag" />
                                {{ detalle.clasif.etq }}<template v-if="detalle.clasif.nombre">: {{ detalle.clasif.nombre }}</template>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="md__cerrar" title="Cerrar" @click="cerrarEvento">
                        <CloseOutlined />
                    </button>
                </header>

                <div class="md__body">
                    <dl class="md__datos">
                        <div v-for="d in detalle.datos" :key="d.label" class="md__dato">
                            <dt><component :is="d.icono" /> {{ d.label }}</dt>
                            <dd>{{ d.valor }}</dd>
                        </div>
                    </dl>

                    <section class="md__seccion">
                        <h4 class="md__sub"><TeamOutlined /> {{ detalle.personasLabel }}</h4>
                        <div v-if="detalle.personas.length" class="md__personas">
                            <span v-for="n in detalle.personas" :key="n" class="md-persona">
                                <span class="md-persona__av">{{ iniciales(n) }}</span>
                                {{ n }}
                            </span>
                        </div>
                        <p v-else class="md__vacio">Nadie asignado todavía.</p>
                    </section>

                    <section v-if="detalle.descripcion" class="md__seccion">
                        <h4 class="md__sub"><FileTextOutlined /> {{ detalle.descripcionLabel }}</h4>
                        <p class="md__desc">{{ detalle.descripcion }}</p>
                    </section>
                </div>

                <footer class="md__pie">
                    <a-button @click="cerrarEvento">Cerrar</a-button>
                    <a-button v-if="puedeVerCompleto" type="primary" class="md__ver" @click="verCompleto">
                        {{ eventoSeleccionado.tipo_evento === 'ocurrencia' ? 'Ver plan' : 'Ver completo' }}
                        <ArrowRightOutlined />
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <!-- Altas desde "Agendar nueva actividad" -->
        <template v-if="formularios">
            <ModalOrden
                v-if="puede('mantenimientos.crear')"
                ref="modalOrden"
                :equipos="formularios.equipos ?? []"
                :ubicaciones="formularios.ubicaciones ?? []"
                :catalogos="formularios"
            />
            <ModalTarea
                v-if="puede('tareas.crear')"
                ref="modalTarea"
                :usuarios="formularios.usuarios ?? []"
                :prioridades="formularios.prioridades ?? []"
                :proyectos="formularios.proyectos ?? []"
                :categorias="formularios.categorias ?? []"
            />
            <ModalSolicitud
                v-if="puede('solicitudes.crear')"
                ref="modalSolicitud"
                :equipos="formularios.equipos ?? []"
                :ubicaciones="formularios.ubicaciones ?? []"
                :prioridades="formularios.prioridades ?? []"
            />
        </template>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Botón "Nueva actividad"
   ========================================================== */
.btn-nueva {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 38px;
    padding: 0 14px;
    border: 0;
    border-radius: 10px;
    background: #173a5f;
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    box-shadow: 0 6px 14px -8px rgba(23, 58, 95, 0.7);
    transition: background 0.14s ease, transform 0.14s ease;
}

.btn-nueva:disabled {
    opacity: 0.7;
    cursor: wait;
}

.btn-nueva:hover {
    background: #0f2d50;
    transform: translateY(-1px);
}

.btn-nueva__caret {
    font-size: 10px;
    margin-left: 2px;
}

.menu-nueva {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

/* ==========================================================
   Barra de controles
   ========================================================== */
.cal-controles {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.05);
}

.cal-nav {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.cal-nav__btn,
.cal-nav__hoy {
    height: 32px;
    border-radius: 8px;
    border: 1px solid #dbe3ec;
    background: #fff;
    color: #334155;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.14s ease, border-color 0.14s ease, color 0.14s ease;
}

.cal-nav__btn {
    width: 34px;
    font-size: 13px;
}

.cal-nav__hoy {
    padding: 0 14px;
    font-weight: 600;
    font-size: 13px;
}

.cal-nav__btn:hover,
.cal-nav__hoy:hover {
    background: #f1f5f9;
    border-color: #c5d1de;
    color: #0f2d50;
}

.cal-nav__mes {
    font-size: 17px;
    font-weight: 800;
    color: #0f2d50;
    text-transform: capitalize;
    margin-left: 8px;
    white-space: nowrap;
}

.cal-filtros {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.cal-filtros__select {
    width: 150px;
}

.cal-filtros__select--sucursal {
    width: 190px;
}

.cal-filtros__limpiar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #f4dede;
    background: #fdf4f4;
    color: #d64545;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.cal-filtros__limpiar:hover {
    background: #fdecec;
    border-color: #d64545;
}

@media (max-width: 767px) {
    .cal-controles {
        flex-direction: column;
        align-items: stretch;
    }

    .cal-filtros__select,
    .cal-filtros__select--sucursal {
        flex: 1 1 140px;
        width: auto;
    }
}

/* ==========================================================
   Layout: calendario + panel lateral
   ========================================================== */
.cal-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 14px;
    align-items: stretch;
    min-height: 0;
}

/* Con alto fijo (escritorio): las semanas se reparten el espacio */
.cal-layout[style] .cal-card {
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.cal-layout[style] .cal-grid:not(.cal-grid--head) {
    flex: 1 1 0;
    min-height: 0;
}

.cal-layout[style] .cel {
    min-height: 0;
    overflow: hidden;
}

.cal-layout[style] .cal-side {
    min-height: 0;
}

.cal-layout[style] .side-card--dia {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.cal-layout[style] .side-lista {
    max-height: none;
    flex: 1 1 auto;
    min-height: 0;
}

@media (max-width: 1199px) {
    .cal-layout {
        grid-template-columns: 1fr;
        height: auto !important;
    }
}

/* ==========================================================
   Cuadrícula
   ========================================================== */
.cal-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    overflow: hidden;
}

.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
}

.cal-grid + .cal-grid {
    border-top: 1px solid #e8edf3;
}

.cal-head {
    padding: 9px 4px;
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    background: #fafbfd;
}

.cal-head + .cal-head,
.cel + .cel {
    border-left: 1px solid #e8edf3;
}

.cel {
    position: relative;
    min-height: 92px;
    padding: 6px 6px 6px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    cursor: pointer;
    transition: background 0.14s ease;
}

.cel:hover {
    background: #f8fafc;
}

.cel__n {
    align-self: flex-end;
    font-size: 12px;
    font-weight: 600;
    color: #1e293b;
    min-width: 22px;
    height: 22px;
    padding: 0 5px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.cel--otro .cel__n {
    color: #b6c1cd;
}

.cel--otro {
    background: #fcfdfe;
}

.cel--hoy .cel__n {
    background: #1e6fd9;
    color: #fff;
}

.cel--sel {
    background: #f2f8ff;
    box-shadow: inset 0 0 0 1.5px #5aa2ee;
    border-radius: 8px;
}

.cel--sel:hover {
    background: #eaf3fe;
}

.cel__eventos {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.evento {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    width: 100%;
    padding: 5px 6px;
    border: 0;
    border-radius: 6px;
    background: var(--soft);
    text-align: left;
    cursor: pointer;
    min-width: 0;
    transition: filter 0.14s ease, transform 0.14s ease;
}

.evento:hover {
    filter: brightness(0.97);
    transform: translateY(-1px);
}

.evento__ic {
    flex: none;
    font-size: 13px;
    line-height: 1;
    color: var(--c);
    margin-top: 1px;
}

.evento__txt {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.evento__t {
    font-size: 11px;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

.evento__t b {
    color: var(--c);
    font-weight: 700;
}

.evento__h {
    font-size: 10px;
    color: #64748b;
    line-height: 1.3;
}

.evento-mas {
    align-self: flex-start;
    padding: 1px 6px;
    border: 0;
    border-radius: 5px;
    background: #f1f5f9;
    color: #475569;
    font-size: 10.5px;
    font-weight: 700;
    cursor: pointer;
}

.evento-mas:hover {
    background: #e2e8f0;
}

@media (max-width: 767px) {
    .cel {
        min-height: 64px;
        padding: 4px 3px;
    }

    .evento__txt {
        display: none;
    }

    .evento {
        justify-content: center;
        padding: 4px;
    }
}

/* ==========================================================
   Panel lateral
   ========================================================== */
.cal-side {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.side-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    padding: 14px;
}

.side-card__t {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: #0f2d50;
}

.side-card__sub {
    margin: 2px 0 12px;
    font-size: 12px;
    color: #64748b;
}

.side-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 420px;
    overflow-y: auto;
}

.side-ev {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 10px 10px 12px;
    border: 1px solid #e2e8f0;
    border-left: 3px solid var(--c);
    border-radius: 8px;
    background: #fff;
    text-align: left;
    cursor: pointer;
    transition: box-shadow 0.14s ease, border-color 0.14s ease;
}

.side-ev:hover {
    box-shadow: 0 6px 16px -10px rgba(15, 37, 71, 0.4);
    border-color: color-mix(in srgb, var(--c) 40%, #e2e8f0);
    border-left-color: var(--c);
}

.side-ev__ic {
    flex: none;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: var(--c);
    background: var(--soft);
}

.side-ev__cuerpo {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.side-ev__t {
    font-size: 13px;
    font-weight: 700;
    color: #0f2d50;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.side-ev__h {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    color: #475569;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.side-ev__pie {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    margin-top: 2px;
}

.side-ev__lugar {
    font-size: 11px;
    color: #64748b;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.side-ev__estado {
    flex: none;
    padding: 1px 9px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 600;
    color: var(--c);
    background: var(--soft);
}

.side-ev__flecha {
    flex: none;
    font-size: 11px;
    color: #94a3b8;
}

.side-vacio {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 18px 0 8px;
    color: #94a3b8;
    font-size: 12px;
}

.side-vacio .anticon {
    font-size: 22px;
}

.side-leyenda {
    list-style: none;
    margin: 10px 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.side-leyenda__item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 2px 4px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    font-size: 12.5px;
    color: #334155;
    text-align: left;
    cursor: pointer;
    transition: background 0.14s ease, opacity 0.14s ease;
}

.side-leyenda__item:hover {
    background: #f1f5f9;
}

.side-leyenda__item--off {
    opacity: 0.4;
    text-decoration: line-through;
}

.side-leyenda__dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--c);
    flex: none;
}

.side-leyenda__ic {
    color: var(--c);
    font-size: 15px;
    width: 18px;
}

/* ==========================================================
   Modal de detalle
   ========================================================== */
.modal-evento :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 16px;
}

.modal-evento :deep(.ant-modal-body) {
    padding: 0;
}

.md__head {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 18px 20px 16px;
    background: linear-gradient(180deg, var(--soft) 0%, #fff 100%);
    border-top: 4px solid var(--c);
    border-bottom: 1px solid #eef2f7;
}

.md__ic {
    flex: none;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    background: var(--c);
    box-shadow: 0 6px 14px -6px var(--c);
}

.md__head-txt {
    flex: 1;
    min-width: 0;
}

.md__kicker {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--c);
}

.md__folio {
    padding: 1px 7px;
    border-radius: 5px;
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #475569;
    letter-spacing: 0.02em;
}

.md__titulo {
    margin: 3px 0 8px;
    font-size: 17px;
    font-weight: 800;
    line-height: 1.3;
    color: #0f2d50;
    word-break: break-word;
}

.md__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.md-chip {
    --pc: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    background: #fff;
    border: 1px solid #e2e8f0;
}

.md-chip__flag {
    color: var(--pc);
}

.md-chip--estado {
    color: var(--c);
    background: var(--soft);
    border-color: transparent;
}

.md-chip__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--c);
}

.md__cerrar {
    flex: none;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.14s ease, color 0.14s ease;
}

.md__cerrar:hover {
    background: #fdecec;
    color: #d64545;
}

.md__body {
    padding: 16px 20px 6px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-height: 60vh;
    overflow-y: auto;
}

.md__datos {
    margin: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 18px;
}

.md__dato {
    min-width: 0;
}

.md__dato dt {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 2px;
}

.md__dato dt .anticon {
    color: var(--c);
}

.md__dato dd {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: #0f2d50;
    word-break: break-word;
}

@media (max-width: 575px) {
    .md__datos {
        grid-template-columns: 1fr;
    }
}

.md__seccion {
    padding-top: 14px;
    border-top: 1px dashed #e2e8f0;
}

.md__sub {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0 0 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
}

.md__personas {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.md-persona {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 3px 10px 3px 3px;
    border-radius: 999px;
    background: #f1f5f9;
    font-size: 12.5px;
    font-weight: 600;
    color: #1e293b;
}

.md-persona__av {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 800;
    color: #fff;
    background: var(--c);
}

.md__vacio {
    margin: 0;
    font-size: 12.5px;
    color: #94a3b8;
}

.md__desc {
    margin: 0;
    padding: 10px 12px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
    font-size: 13px;
    line-height: 1.5;
    color: #334155;
    white-space: pre-line;
    word-break: break-word;
}

.md__pie {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 20px 16px;
}

.md__ver {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--c) !important;
    border-color: var(--c) !important;
    font-weight: 700;
}

.md__ver:hover {
    filter: brightness(1.08);
}
</style>