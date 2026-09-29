<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRightOutlined,
    CalendarOutlined,
    CheckSquareOutlined,
    ClockCircleOutlined,
    DollarOutlined,
    EnvironmentOutlined,
    ExclamationCircleOutlined,
    FileTextOutlined,
    RiseOutlined,
    SafetyCertificateOutlined,
    ScheduleOutlined,
    ThunderboltOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tarjetas: { type: Object, default: () => ({}) },
    agenda: { type: Array, default: () => [] },
    urgencias: { type: Array, default: () => [] },
    preventivos_proximos: { type: Array, default: () => [] },
    tareas_proximas: { type: Array, default: () => [] },
});

const page = usePage();
const nombre = computed(() => (page.props.auth.user?.nombre ?? page.props.auth.user?.name ?? '').split(' ')[0]);

const saludo = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Buenos días';
    if (h < 19) return 'Buenas tardes';
    return 'Buenas noches';
});

const hoy = computed(() =>
    new Date().toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
);

const moneda = (v) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(v || 0);

/* ==========================================================
   KPIs (sin %)
   ========================================================== */
const kpis = computed(() => [
    {
        titulo: 'Equipos',
        valor: props.tarjetas.total_equipos ?? 0,
        icon: ToolOutlined,
        color: '#0d84c9',
        grad: 'linear-gradient(135deg, #0d84c9, #38a8e8)',
        bg: 'rgba(13, 132, 201, 0.12)',
        ruta: 'equipos.por_sucursal',
    },
    {
        titulo: 'Inventario',
        valor: moneda(props.tarjetas.valor_inventario),
        icon: DollarOutlined,
        color: '#1f9e86',
        grad: 'linear-gradient(135deg, #1f9e86, #4fd4b6)',
        bg: 'rgba(31, 158, 134, 0.12)',
        ruta: null,
    },
    {
        titulo: 'Mant. pend.',
        valor: props.tarjetas.mantenimientos_pendientes ?? 0,
        icon: ClockCircleOutlined,
        color: '#475569',
        grad: 'linear-gradient(135deg, #475569, #94a3b8)',
        bg: 'rgba(71, 85, 105, 0.12)',
        ruta: 'mantenimientos.index',
    },
    {
        titulo: 'Urgentes',
        valor: props.tarjetas.urgentes ?? 0,
        icon: ThunderboltOutlined,
        color: '#d64545',
        grad: 'linear-gradient(135deg, #d64545, #ff7a7a)',
        bg: 'rgba(214, 69, 69, 0.12)',
        ruta: 'mantenimientos.index',
        alert: true,
    },
    {
        titulo: 'Prev. vencidos',
        valor: props.tarjetas.preventivos_vencidos ?? 0,
        icon: CalendarOutlined,
        color: '#e08a1e',
        grad: 'linear-gradient(135deg, #e08a1e, #ffb85c)',
        bg: 'rgba(224, 138, 30, 0.14)',
        ruta: 'planes.index',
        alert: true,
    },
    {
        titulo: 'Solicitudes',
        valor: props.tarjetas.solicitudes_abiertas ?? 0,
        icon: FileTextOutlined,
        color: '#6b4bc9',
        grad: 'linear-gradient(135deg, #6b4bc9, #a48bff)',
        bg: 'rgba(107, 75, 201, 0.12)',
        ruta: 'solicitudes.index',
    },
    {
        titulo: 'Tareas pend.',
        valor: props.tarjetas.tareas_pendientes ?? 0,
        icon: CheckSquareOutlined,
        color: '#0891b2',
        grad: 'linear-gradient(135deg, #0891b2, #22d3ee)',
        bg: 'rgba(8, 145, 178, 0.12)',
        ruta: 'tareas.index',
    },
    {
        titulo: 'Tareas venc.',
        valor: props.tarjetas.tareas_vencidas ?? 0,
        icon: ExclamationCircleOutlined,
        color: '#b91c1c',
        grad: 'linear-gradient(135deg, #b91c1c, #ef4444)',
        bg: 'rgba(185, 28, 28, 0.14)',
        ruta: 'tareas.index',
        alert: true,
    },
]);

/* Sparkline decorativo */
const sparkline = (i) => {
    const puntos = [
        [2, 12, 4, 18, 6, 15, 8, 22, 10, 19, 12, 26, 14, 24],
        [2, 20, 4, 17, 6, 22, 8, 19, 10, 24, 12, 21, 14, 26],
        [2, 24, 4, 20, 6, 22, 8, 17, 10, 19, 12, 15, 14, 18],
        [2, 10, 4, 14, 6, 12, 8, 18, 10, 15, 12, 22, 14, 20],
        [2, 18, 4, 15, 6, 20, 8, 17, 10, 22, 12, 19, 14, 24],
        [2, 22, 4, 19, 6, 24, 8, 20, 10, 26, 12, 22, 14, 28],
        [2, 14, 4, 18, 6, 12, 8, 16, 10, 14, 12, 20, 14, 18],
        [2, 6, 4, 12, 6, 10, 8, 16, 10, 14, 12, 20, 14, 18],
    ];
    return puntos[i % puntos.length].join(' ');
};

const ir = (ruta, params) => ruta && router.visit(route(ruta, params));

/* ==========================================================
   AGENDA SEMANAL UNIFICADA
   ========================================================== */
const EVENTO_TIPOS = {
    orden: {
        label: 'Orden',
        color: '#0d84c9',
        grad: 'linear-gradient(135deg, #0d84c9, #38a8e8)',
        icon: ToolOutlined,
    },
    preventivo: {
        label: 'Preventivo',
        color: '#e08a1e',
        grad: 'linear-gradient(135deg, #e08a1e, #ffb85c)',
        icon: CalendarOutlined,
    },
    tarea: {
        label: 'Tarea',
        color: '#6b4bc9',
        grad: 'linear-gradient(135deg, #6b4bc9, #a48bff)',
        icon: CheckSquareOutlined,
    },
};

const eventosNormalizados = computed(() => {
    const lista = [];

    for (const o of props.agenda ?? []) {
        if (!o.programado_inicio) continue;
        lista.push({
            id: `orden-${o.id}`,
            tipo: 'orden',
            fecha: o.programado_inicio.slice(0, 10),
            titulo: o.folio ?? 'Orden',
            subtitulo: o.equipo?.codigo_activo ?? o.ubicacion?.nombre ?? '—',
            meta: o.prioridad?.nombre ?? o.estado?.nombre ?? 'Programada',
            ruta: ['mantenimientos.show', o.id],
        });
    }

    for (const p of props.preventivos_proximos ?? []) {
        if (!p.fecha_programada) continue;
        lista.push({
            id: `prev-${p.plan?.id ?? ''}-${p.fecha_programada}`,
            tipo: 'preventivo',
            fecha: p.fecha_programada.slice(0, 10),
            titulo: p.plan?.equipo?.codigo_activo ?? p.plan?.ubicacion?.nombre ?? 'Preventivo',
            subtitulo: p.plan?.tipo?.nombre ?? 'Preventivo',
            meta: p.plan?.sucursal?.nombre ?? '',
            ruta: p.plan?.id ? ['planes.show', p.plan.id] : null,
        });
    }

    for (const t of props.tareas_proximas ?? []) {
        if (!t.fecha_limite) continue;
        lista.push({
            id: `tarea-${t.id}`,
            tipo: 'tarea',
            fecha: t.fecha_limite.slice(0, 10),
            titulo: t.titulo ?? t.descripcion ?? 'Tarea',
            subtitulo: (t.responsables ?? []).map((r) => r.nombre).join(', ') || 'Sin responsable',
            meta: t.estado ?? 'Pendiente',
            ruta: ['tareas.show', t.id],
        });
    }

    return lista;
});

const rangoSemana = computed(() => {
    const dias = [];
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    for (let i = 0; i < 7; i++) {
        const d = new Date(hoy);
        d.setDate(hoy.getDate() + i);
        dias.push(d);
    }
    return dias;
});

const claveFecha = (d) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

const eventosPorDia = computed(() => {
    const clavesSemana = new Set(rangoSemana.value.map(claveFecha));
    const mapa = {};

    for (const ev of eventosNormalizados.value) {
        if (!clavesSemana.has(ev.fecha)) continue;
        (mapa[ev.fecha] ??= []).push(ev);
    }

    const ordenTipo = { orden: 0, preventivo: 1, tarea: 2 };
    for (const k of Object.keys(mapa)) {
        mapa[k].sort((a, b) => ordenTipo[a.tipo] - ordenTipo[b.tipo]);
    }

    return mapa;
});

const diasConEventos = computed(() =>
    rangoSemana.value.filter((d) => (eventosPorDia.value[claveFecha(d)] ?? []).length > 0),
);

const totalHoy = computed(() => (eventosPorDia.value[claveFecha(new Date())] ?? []).length);

const totalManana = computed(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() + 1);
    return (eventosPorDia.value[claveFecha(d)] ?? []).length;
});

const totalSemana = computed(() =>
    Object.values(eventosPorDia.value).reduce((acc, arr) => acc + arr.length, 0),
);

const filtroAgenda = ref('semana');

/* MÁXIMO 8 EVENTOS POR VISTA */
const LIMITE = 8;

const eventosConDia = computed(() => {
    const hoyClave = claveFecha(new Date());
    const manana = new Date();
    manana.setHours(0, 0, 0, 0);
    manana.setDate(manana.getDate() + 1);
    const mananaClave = claveFecha(manana);

    let claves = [];

    if (filtroAgenda.value === 'hoy') {
        claves = (eventosPorDia.value[hoyClave] ?? []).length ? [hoyClave] : [];
    } else if (filtroAgenda.value === 'manana') {
        claves = (eventosPorDia.value[mananaClave] ?? []).length ? [mananaClave] : [];
    } else {
        claves = diasConEventos.value.map(claveFecha);
    }

    const aplanado = [];
    for (const k of claves) {
        for (const ev of eventosPorDia.value[k] ?? []) {
            aplanado.push({ fechaClave: k, evento: ev });
        }
    }
    return aplanado;
});

const eventosVisibles = computed(() => eventosConDia.value.slice(0, LIMITE));
const eventosResto = computed(() => Math.max(0, eventosConDia.value.length - LIMITE));

const diasVisibles = computed(() => {
    const claves = new Set(eventosVisibles.value.map((e) => e.fechaClave));
    return rangoSemana.value.filter((d) => claves.has(claveFecha(d)));
});

const eventosPorDiaVisible = computed(() => {
    const mapa = {};
    for (const { fechaClave, evento } of eventosVisibles.value) {
        (mapa[fechaClave] ??= []).push(evento);
    }
    return mapa;
});

/* Etiquetas */
const etiquetaDia = (d) => {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const objetivo = new Date(d);
    objetivo.setHours(0, 0, 0, 0);
    const diff = Math.round((objetivo - hoy) / 86400000);

    if (diff === 0) return 'Hoy';
    if (diff === 1) return 'Mañana';
    if (diff === 2) return 'Pasado mañana';

    const dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    return dias[objetivo.getDay()];
};

const fechaCorta = (d) => {
    const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return `${d.getDate()} ${meses[d.getMonth()]}`;
};

const esHoy = (d) => {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const obj = new Date(d);
    obj.setHours(0, 0, 0, 0);
    return hoy.getTime() === obj.getTime();
};

const irEvento = (ev) => {
    if (!ev.ruta) return;
    router.visit(route(ev.ruta[0], ev.ruta[1]));
};

const irCalendario = () => router.visit(route('calendario.index'));
</script>

<template>
    <Head title="Panel de control" />

    <AppLayout titulo="Panel de control">
        <div class="dash">
            <!-- HERO -->
            <div class="hero">
                <div class="hero__deco hero__deco--1"></div>
                <div class="hero__deco hero__deco--2"></div>
                <div class="hero__deco hero__deco--3"></div>

                <div class="hero__txt">
                    <div class="hero__saludo">{{ saludo }}, <span>{{ nombre || 'bienvenido' }}</span></div>
                    <div class="hero__fecha">
                        <CalendarOutlined /> {{ hoy }}
                    </div>
                    <div class="hero__badges">
                        <span class="hero__badge hero__badge--green">
                            <SafetyCertificateOutlined /> Sistema operativo
                        </span>
                        <span class="hero__badge hero__badge--blue">
                            <RiseOutlined /> Panel actualizado
                        </span>
                    </div>
                </div>

                <button type="button" class="hero__cta" @click="irCalendario">
                    <span class="hero__cta-ic"><CalendarOutlined /></span>
                    <span class="hero__cta-txt">
                        <span class="hero__cta-l">Ver calendario</span>
                        <span class="hero__cta-s">Agenda y programación</span>
                    </span>
                    <span class="hero__cta-arrow"><ArrowRightOutlined /></span>
                </button>
            </div>

            <!-- KPIs -->
            <div class="kpis">
                <button
                    v-for="(kpi, i) in kpis"
                    :key="kpi.titulo"
                    type="button"
                    class="kpi"
                    :class="{ 'kpi--link': kpi.ruta, 'kpi--alert': kpi.alert }"
                    :style="{ '--acc': kpi.color, '--acc-bg': kpi.bg, '--acc-grad': kpi.grad, animationDelay: `${i * 0.06}s` }"
                    @click="ir(kpi.ruta)"
                >
                    <span class="kpi__ic"><component :is="kpi.icon" /></span>
                    <span class="kpi__t">
                        <span class="kpi__v">{{ kpi.valor }}</span>
                        <span class="kpi__l">{{ kpi.titulo }}</span>
                    </span>
                    <svg viewBox="0 0 16 28" class="kpi__spark" preserveAspectRatio="none">
                        <polyline
                            :points="sparkline(i)"
                            fill="none"
                            :stroke="kpi.color"
                            stroke-width="1.2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            opacity="0.55"
                        />
                    </svg>
                </button>
            </div>

            <!-- AGENDA -->
            <section class="agenda">
                <header class="agenda__head">
                    <div class="agenda__head-l">
                        <span class="agenda__ico"><CalendarOutlined /></span>
                        <div class="agenda__tt">
                            <h3 class="agenda__t">Agenda de la semana</h3>
                            <span class="agenda__s">Próximos trabajos · {{ totalSemana }} en total</span>
                        </div>
                    </div>

                    <div class="agenda__switch" role="tablist">
                        <span
                            class="agenda__switch-slider"
                            :class="{
                                'is-hoy': filtroAgenda === 'hoy',
                                'is-manana': filtroAgenda === 'manana',
                                'is-semana': filtroAgenda === 'semana',
                            }"
                            aria-hidden="true"
                        ></span>

                        <button
                            type="button"
                            class="agenda__tab"
                            :class="{ 'agenda__tab--active': filtroAgenda === 'hoy' }"
                            @click="filtroAgenda = 'hoy'"
                        >
                            <ScheduleOutlined class="agenda__tab-ic" />
                            <span class="agenda__tab-lbl">Hoy</span>
                            <span class="agenda__tab-badge">{{ totalHoy }}</span>
                        </button>
                        <button
                            type="button"
                            class="agenda__tab"
                            :class="{ 'agenda__tab--active': filtroAgenda === 'manana' }"
                            @click="filtroAgenda = 'manana'"
                        >
                            <ClockCircleOutlined class="agenda__tab-ic" />
                            <span class="agenda__tab-lbl">Mañana</span>
                            <span class="agenda__tab-badge">{{ totalManana }}</span>
                        </button>
                        <button
                            type="button"
                            class="agenda__tab"
                            :class="{ 'agenda__tab--active': filtroAgenda === 'semana' }"
                            @click="filtroAgenda = 'semana'"
                        >
                            <CalendarOutlined class="agenda__tab-ic" />
                            <span class="agenda__tab-lbl">Esta semana</span>
                            <span class="agenda__tab-badge">{{ totalSemana }}</span>
                        </button>
                    </div>
                </header>

                <div class="agenda__body">
                    <div v-if="!eventosVisibles.length" class="agenda__vacio">
                        <span class="agenda__vacio-ic"><CalendarOutlined /></span>
                        <div class="agenda__vacio-t">Sin actividades próximas</div>
                        <div class="agenda__vacio-s">No hay trabajos programados en este rango.</div>
                        <button type="button" class="agenda__vacio-btn" @click="irCalendario">
                            <CalendarOutlined /> Ir al calendario
                        </button>
                    </div>

                    <div v-else class="agenda__dias">
                        <div
                            v-for="(d, di) in diasVisibles"
                            :key="claveFecha(d)"
                            class="dia"
                            :class="{ 'dia--hoy': esHoy(d) }"
                            :style="{ animationDelay: `${di * 0.08}s` }"
                        >
                            <header class="dia__head">
                                <span class="dia__fecha">
                                    <span class="dia__dia">{{ etiquetaDia(d) }}</span>
                                    <span class="dia__num">{{ fechaCorta(d) }}</span>
                                </span>
                                <span class="dia__contador">
                                    {{ (eventosPorDiaVisible[claveFecha(d)] ?? []).length }}
                                    {{ (eventosPorDiaVisible[claveFecha(d)] ?? []).length === 1 ? 'evento' : 'eventos' }}
                                </span>
                            </header>

                            <div class="dia__eventos">
                                <button
                                    v-for="(ev, ei) in (eventosPorDiaVisible[claveFecha(d)] ?? [])"
                                    :key="ev.id"
                                    type="button"
                                    class="ev"
                                    :style="{
                                        '--c': EVENTO_TIPOS[ev.tipo].color,
                                        '--cg': EVENTO_TIPOS[ev.tipo].grad,
                                        animationDelay: `${ei * 0.05}s`,
                                    }"
                                    @click="irEvento(ev)"
                                >
                                    <span class="ev__ic"><component :is="EVENTO_TIPOS[ev.tipo].icon" /></span>
                                    <span class="ev__body">
                                        <span class="ev__t1">{{ ev.titulo }}</span>
                                        <span class="ev__t2">{{ ev.subtitulo }}</span>
                                    </span>
                                    <span class="ev__meta">
                                        <span class="ev__tipo">{{ EVENTO_TIPOS[ev.tipo].label }}</span>
                                        <span v-if="ev.meta" class="ev__tag">{{ ev.meta }}</span>
                                    </span>
                                    <ArrowRightOutlined class="ev__arrow" />
                                </button>
                            </div>
                        </div>

                        <button v-if="eventosResto" type="button" class="agenda__mas" @click="irCalendario">
                            <span class="agenda__mas-ico"><CalendarOutlined /></span>
                            <span class="agenda__mas-txt">
                                Hay <strong>{{ eventosResto }}</strong>
                                {{ eventosResto === 1 ? 'evento más' : 'eventos más' }} en la semana
                            </span>
                            <span class="agenda__mas-link">
                                Ver todo en el calendario <ArrowRightOutlined />
                            </span>
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ================================================================
   ANIMACIONES
   ================================================================ */
@keyframes sigam-fade-up {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes sigam-grad-shift {
    0%   { background-position: 0% 50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
@keyframes sigam-pulse-soft {
    0%, 100% { opacity: 1; }
    50%      { opacity: 0.35; }
}
@keyframes sigam-slide-in {
    from { opacity: 0; transform: translateY(8px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes sigam-icon-pop {
    0%   { transform: scale(0) rotate(-180deg); opacity: 0; }
    60%  { transform: scale(1.15) rotate(10deg); opacity: 1; }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
}
@keyframes sigam-float {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-3px); }
}
@keyframes sigam-badge-pulse {
    0%, 100% { box-shadow: 0 3px 8px -3px rgba(13, 132, 201, 0.55); }
    50%      { box-shadow: 0 3px 14px -3px rgba(13, 132, 201, 0.85); }
}

/* ================================================================
   LAYOUT
   ================================================================ */
.dash {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ================================================================
   HERO
   ================================================================ */
.hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px 26px;
    border-radius: 18px;
    background: linear-gradient(135deg, #0a2a4a 0%, #103f6b 45%, #0d84c9 100%);
    background-size: 200% 200%;
    animation: sigam-grad-shift 14s ease infinite;
    box-shadow:
        0 20px 44px -22px rgba(10, 42, 74, 0.7),
        inset 0 0 0 1px rgba(255, 255, 255, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.14);
    position: relative;
    overflow: hidden;
    min-height: 120px;
}

.hero__deco {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.hero__deco--1 {
    right: -60px;
    top: -80px;
    width: 260px;
    height: 260px;
    background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.16), transparent 65%);
    animation: sigam-float 8s ease-in-out infinite;
}
.hero__deco--2 {
    left: -50px;
    bottom: -70px;
    width: 180px;
    height: 180px;
    background: radial-gradient(circle at 60% 40%, rgba(120, 230, 200, 0.25), transparent 70%);
    animation: sigam-float 10s ease-in-out infinite reverse;
}
.hero__deco--3 {
    top: 50%;
    right: 30%;
    width: 4px;
    height: 4px;
    box-shadow:
        -180px -30px 0 0 rgba(255, 255, 255, 0.6),
        240px 40px 0 0 rgba(255, 255, 255, 0.45),
        80px -40px 0 0 rgba(120, 230, 200, 0.65),
        -60px 60px 0 0 rgba(255, 255, 255, 0.4),
        320px -10px 0 0 rgba(120, 230, 200, 0.55);
    border-radius: 50%;
    animation: sigam-pulse-soft 3s ease-in-out infinite;
}

.hero__txt {
    position: relative;
    z-index: 1;
    min-width: 260px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    animation: sigam-slide-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.hero__saludo {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
    line-height: 1.15;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.22);
}
.hero__saludo::first-letter { text-transform: uppercase; }
.hero__saludo span {
    background: linear-gradient(135deg, #78e6c8, #38a8e8);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero__fecha {
    font-size: 12.5px;
    color: rgba(255, 255, 255, 0.88);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.hero__fecha::first-letter { text-transform: uppercase; }

.hero__badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}

.hero__badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hero__badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px -6px rgba(0, 0, 0, 0.35);
}

.hero__badge--green {
    background: rgba(31, 158, 134, 0.28);
    color: #b8f5e4;
    border-color: rgba(120, 230, 200, 0.4);
}
.hero__badge--blue {
    background: rgba(56, 168, 232, 0.22);
    color: #d6efff;
    border-color: rgba(120, 200, 245, 0.4);
}

.hero__cta {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px 12px 14px;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.28);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.16) 0%, rgba(255, 255, 255, 0.08) 100%);
    backdrop-filter: blur(12px);
    cursor: pointer;
    font-family: inherit;
    text-align: left;
    overflow: hidden;
    box-shadow:
        0 10px 24px -12px rgba(0, 0, 0, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.22);
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    flex-shrink: 0;
    animation: sigam-slide-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;
}

.hero__cta::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(120, 230, 200, 0.22) 50%, transparent 100%);
    transform: translateX(-100%) skewX(-20deg);
    transition: transform 0.7s ease;
    pointer-events: none;
}

.hero__cta:hover {
    transform: translateY(-2px);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0.12) 100%);
    box-shadow:
        0 14px 32px -14px rgba(0, 0, 0, 0.45),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
}
.hero__cta:hover::before { transform: translateX(200%) skewX(-20deg); }
.hero__cta:active { transform: translateY(0) scale(0.98); }

.hero__cta-ic {
    width: 38px;
    height: 38px;
    flex: none;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #0a2a4a;
    background: linear-gradient(135deg, #78e6c8 0%, #38a8e8 100%);
    box-shadow:
        0 6px 14px -6px rgba(120, 230, 200, 0.6),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
    transition: transform 0.25s cubic-bezier(0.34, 1.4, 0.4, 1);
}
.hero__cta:hover .hero__cta-ic {
    transform: scale(1.12) rotate(-8deg);
}

.hero__cta-txt {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}
.hero__cta-l {
    font-size: 14px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.01em;
    line-height: 1.15;
}
.hero__cta-s {
    font-size: 11px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.78);
    line-height: 1.15;
}
.hero__cta-arrow {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.85);
    transition: transform 0.2s ease, color 0.2s ease;
}
.hero__cta:hover .hero__cta-arrow {
    transform: translateX(4px);
    color: #78e6c8;
}

/* ================================================================
   KPIs (HORIZONTAL)
   ================================================================ */
.kpis {
    display: grid;
    grid-template-columns: repeat(8, minmax(0, 1fr));
    gap: 9px;
}
@media (max-width: 1400px) {
    .kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 700px) {
    .kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

.kpi {
    position: relative;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.05);
    text-align: left;
    cursor: default;
    overflow: hidden;
    min-height: 62px;
    transition: box-shadow 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
    animation: sigam-fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 3px;
    background: var(--acc-grad);
    transition: width 0.25s ease;
}
.kpi::after {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 130px;
    height: 130px;
    border-radius: 50%;
    background: radial-gradient(circle, var(--acc-bg) 0%, transparent 70%);
    pointer-events: none;
    transition: transform 0.5s ease;
}
.kpi--link { cursor: pointer; }
.kpi--link:hover {
    box-shadow: 0 14px 28px -16px color-mix(in srgb, var(--acc) 70%, transparent);
    transform: translateY(-4px);
    border-color: color-mix(in srgb, var(--acc) 40%, var(--sigam-borde));
}
.kpi--link:hover::before { width: 5px; }
.kpi--link:hover::after { transform: scale(1.3) rotate(15deg); }
.kpi--alert {
    background: linear-gradient(180deg, #fff 0%, #fff8f8 100%);
    border-color: color-mix(in srgb, var(--acc) 25%, var(--sigam-borde));
}
.kpi__ic {
    width: 36px;
    height: 36px;
    flex: none;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: var(--acc-grad);
    box-shadow: 0 5px 12px -6px color-mix(in srgb, var(--acc) 70%, transparent);
    transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.4, 1), box-shadow 0.2s ease;
    position: relative;
    z-index: 1;
}
.kpi--link:hover .kpi__ic {
    transform: scale(1.15) rotate(-8deg);
    box-shadow: 0 8px 18px -6px color-mix(in srgb, var(--acc) 85%, transparent);
}
.kpi__t {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    position: relative;
    z-index: 1;
    flex: 1;
}
.kpi__v {
    font-size: 17px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.1;
    letter-spacing: -0.02em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.18s ease;
}
.kpi--link:hover .kpi__v {
    color: var(--acc);
}
.kpi__l {
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--sigam-tenue);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.18s ease;
}
.kpi--link:hover .kpi__l {
    color: var(--sigam-navy);
}
.kpi__spark {
    position: absolute;
    right: 6px;
    bottom: 6px;
    width: 30px;
    height: 20px;
    opacity: 0.55;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.kpi--link:hover .kpi__spark {
    opacity: 1;
    transform: translateX(2px);
}

/* ================================================================
   AGENDA
   ================================================================ */
.agenda {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.05);
    overflow: hidden;
    position: relative;
    animation: sigam-fade-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.agenda::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #0d84c9, #38a8e8, #78e6c8);
    opacity: 0.9;
    background-size: 200% 200%;
    animation: sigam-grad-shift 6s ease infinite;
}

.agenda__head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(180deg, #f8fbfe 0%, #ffffff 100%);
}

.agenda__head-l {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.agenda__ico {
    width: 40px;
    height: 40px;
    flex: none;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 6px 14px -6px rgba(13, 132, 201, 0.6);
    transition: transform 0.35s cubic-bezier(0.34, 1.4, 0.4, 1);
}
.agenda:hover .agenda__ico {
    transform: rotate(-8deg) scale(1.1);
}

.agenda__tt {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.agenda__t {
    margin: 0;
    font-size: 15.5px;
    font-weight: 800;
    color: var(--sigam-navy);
    letter-spacing: -0.01em;
    line-height: 1.15;
}
.agenda__s {
    font-size: 11.5px;
    color: var(--sigam-tenue);
    line-height: 1.15;
}

/* SWITCH */
.agenda__switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    padding: 4px;
    border-radius: 999px;
    background: linear-gradient(180deg, #eef3f9 0%, #e2eaf3 100%);
    border: 1px solid #d5e0ec;
    box-shadow: inset 0 2px 4px rgba(15, 45, 80, 0.06);
    gap: 0;
    user-select: none;
}

.agenda__switch-slider {
    position: absolute;
    top: 4px;
    bottom: 4px;
    width: calc((100% - 8px) / 3);
    left: 4px;
    border-radius: 999px;
    background: linear-gradient(180deg, #ffffff 0%, #f6f9fc 100%);
    box-shadow:
        0 4px 10px -4px rgba(15, 45, 80, 0.28),
        0 1px 2px rgba(15, 45, 80, 0.12);
    transition: transform 0.4s cubic-bezier(0.34, 1.5, 0.4, 1), background 0.3s ease;
    z-index: 0;
    pointer-events: none;
}
.agenda__switch-slider.is-hoy { transform: translateX(0); }
.agenda__switch-slider.is-manana { transform: translateX(100%); }
.agenda__switch-slider.is-semana { transform: translateX(200%); }

.agenda__switch-slider.is-hoy {
    background: linear-gradient(180deg, #ffffff 0%, #eef7ff 100%);
    box-shadow: 0 4px 12px -4px rgba(13, 132, 201, 0.45), 0 1px 2px rgba(13, 132, 201, 0.15);
}
.agenda__switch-slider.is-manana {
    background: linear-gradient(180deg, #ffffff 0%, #f5f1fd 100%);
    box-shadow: 0 4px 12px -4px rgba(107, 75, 201, 0.45), 0 1px 2px rgba(107, 75, 201, 0.15);
}
.agenda__switch-slider.is-semana {
    background: linear-gradient(180deg, #ffffff 0%, #e4f4ec 100%);
    box-shadow: 0 4px 12px -4px rgba(31, 158, 134, 0.45), 0 1px 2px rgba(31, 158, 134, 0.15);
}

.agenda__tab {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 18px;
    min-width: 130px;
    border: 0;
    background: transparent;
    border-radius: 999px;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 800;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: color 0.25s ease, transform 0.2s ease;
}
.agenda__tab-ic {
    font-size: 14px;
    transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.4, 1);
}
.agenda__tab:hover {
    color: var(--sigam-navy);
}
.agenda__tab:hover .agenda__tab-ic {
    transform: scale(1.2) rotate(-8deg);
}
.agenda__tab-lbl {
    font-size: 12.5px;
    font-weight: 800;
}
.agenda__tab--active {
    color: var(--sigam-navy);
}
.agenda__tab--active:nth-child(2) { color: #0d84c9; }
.agenda__tab--active:nth-child(3) { color: #6b4bc9; }
.agenda__tab--active:nth-child(4) { color: #1f9e86; }
.agenda__tab--active .agenda__tab-ic {
    animation: sigam-icon-pop 0.4s cubic-bezier(0.34, 1.4, 0.4, 1);
}

.agenda__tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 20px;
    padding: 0 7px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    background: #e2e8f0;
    color: #475569;
    line-height: 1;
    transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
}
.agenda__tab--active .agenda__tab-badge {
    background: linear-gradient(135deg, #0d84c9, #0a6ba6);
    color: #fff;
    animation: sigam-badge-pulse 2s ease-in-out infinite;
}
.agenda__tab--active:nth-child(3) .agenda__tab-badge {
    background: linear-gradient(135deg, #6b4bc9, #563a9e);
    box-shadow: 0 3px 8px -3px rgba(107, 75, 201, 0.55);
}
.agenda__tab--active:nth-child(4) .agenda__tab-badge {
    background: linear-gradient(135deg, #1f9e86, #16806c);
    box-shadow: 0 3px 8px -3px rgba(31, 158, 134, 0.55);
}

/* BODY */
.agenda__body {
    max-height: 60vh;
    overflow-y: auto;
    padding: 16px 20px 18px;
    scrollbar-width: thin;
}
.agenda__body::-webkit-scrollbar { width: 8px; }
.agenda__body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
    border: 2px solid transparent;
    background-clip: content-box;
}
.agenda__body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
    background-clip: content-box;
}

.agenda__vacio {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 56px 16px;
    text-align: center;
}
.agenda__vacio-ic {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #94a3b8;
    background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    animation: sigam-icon-pop 0.6s cubic-bezier(0.34, 1.4, 0.4, 1);
}
.agenda__vacio-t {
    font-size: 15px;
    font-weight: 800;
    color: var(--sigam-navy);
}
.agenda__vacio-s {
    font-size: 12.5px;
    color: var(--sigam-tenue);
    max-width: 320px;
}
.agenda__vacio-btn {
    margin-top: 6px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 11px;
    border: 1px solid #cfe4f5;
    background: linear-gradient(135deg, #eef4fb 0%, #e0ecf7 100%);
    color: #0f6fb0;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.14s ease, box-shadow 0.14s ease, background 0.14s ease;
}
.agenda__vacio-btn:hover {
    transform: translateY(-2px);
    background: linear-gradient(135deg, #e0ecf7 0%, #cfe4f5 100%);
    box-shadow: 0 8px 18px -8px rgba(13, 132, 201, 0.55);
}

.dia {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 18px;
    animation: sigam-fade-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.dia:last-child {
    margin-bottom: 0;
}
.dia__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 7px;
    border-bottom: 1px dashed #dbe3ec;
}
.dia--hoy .dia__head {
    border-bottom-color: #cfe4f5;
}
.dia__fecha {
    display: inline-flex;
    align-items: baseline;
    gap: 9px;
    min-width: 0;
}
.dia__dia {
    font-size: 14px;
    font-weight: 800;
    color: var(--sigam-navy);
    text-transform: capitalize;
    letter-spacing: -0.01em;
}
.dia--hoy .dia__dia {
    color: #0d84c9;
    position: relative;
}
.dia--hoy .dia__dia::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -2px;
    width: 100%;
    height: 2px;
    border-radius: 2px;
    background: linear-gradient(90deg, #0d84c9, transparent);
}
.dia__num {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--sigam-tenue);
    text-transform: capitalize;
}
.dia--hoy .dia__num {
    color: #0d84c9;
    background: #e6f2fb;
    padding: 2px 9px;
    border-radius: 999px;
    font-weight: 800;
}
.dia__contador {
    font-size: 11px;
    font-weight: 700;
    color: var(--sigam-tenue);
    background: #f1f5f9;
    padding: 3px 10px;
    border-radius: 999px;
    white-space: nowrap;
}
.dia__eventos {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* EVENTO */
.ev {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid var(--sigam-borde-suave);
    border-left: 4px solid var(--c);
    border-radius: 13px;
    background: #fff;
    cursor: pointer;
    font-family: inherit;
    text-align: left;
    overflow: hidden;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease, background 0.18s ease;
    animation: sigam-slide-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.ev::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, color-mix(in srgb, var(--c) 6%, transparent), transparent 55%);
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}
.ev:hover {
    border-color: color-mix(in srgb, var(--c) 50%, var(--sigam-borde-suave));
    box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--c) 65%, transparent);
    transform: translateY(-3px);
    background: color-mix(in srgb, var(--c) 2%, #fff);
}
.ev:hover::after { opacity: 1; }

.ev__ic {
    width: 42px;
    height: 42px;
    flex: none;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    background: var(--cg);
    box-shadow: 0 6px 14px -5px color-mix(in srgb, var(--c) 75%, transparent);
    transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.4, 1), box-shadow 0.2s ease;
    position: relative;
    z-index: 1;
}
.ev:hover .ev__ic {
    transform: scale(1.15) rotate(-10deg);
    box-shadow: 0 10px 22px -6px color-mix(in srgb, var(--c) 85%, transparent);
}
.ev__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
    position: relative;
    z-index: 1;
}
.ev__t1 {
    font-size: 14px;
    font-weight: 800;
    color: var(--sigam-navy);
    letter-spacing: -0.01em;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.18s ease;
}
.ev:hover .ev__t1 { color: var(--c); }
.ev__t2 {
    font-size: 12.5px;
    color: var(--sigam-tenue);
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ev__meta {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex: none;
    position: relative;
    z-index: 1;
}
.ev__tipo {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 12%, #fff);
    border: 1px solid color-mix(in srgb, var(--c) 25%, transparent);
    padding: 3px 10px;
    border-radius: 999px;
    white-space: nowrap;
    transition: background 0.18s ease, color 0.18s ease;
}
.ev:hover .ev__tipo {
    background: var(--c);
    color: #fff;
}
.ev__tag {
    font-size: 11px;
    font-weight: 600;
    color: var(--sigam-tenue);
    background: #f1f5f9;
    padding: 3px 10px;
    border-radius: 999px;
    white-space: nowrap;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ev__arrow {
    color: #cbd5e1;
    font-size: 13px;
    flex: none;
    transition: transform 0.25s cubic-bezier(0.34, 1.4, 0.4, 1), color 0.2s ease;
    position: relative;
    z-index: 1;
}
.ev:hover .ev__arrow {
    transform: translateX(5px) scale(1.1);
    color: var(--c);
}

/* LEYENDA "+N más" */
.agenda__mas {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 13px;
    border: 1.5px dashed color-mix(in srgb, #0d84c9 35%, #dbe3ec);
    background: linear-gradient(135deg, #f4f9fe 0%, #e6f2fb 100%);
    cursor: pointer;
    font-family: inherit;
    text-align: left;
    overflow: hidden;
    animation: sigam-fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}
.agenda__mas::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(13, 132, 201, 0.16), transparent);
    transition: left 1s ease;
    pointer-events: none;
    transform: skewX(-20deg);
}
.agenda__mas:hover {
    transform: translateY(-3px);
    border-color: #0d84c9;
    background: linear-gradient(135deg, #e6f2fb 0%, #d4e9f9 100%);
    box-shadow: 0 14px 30px -14px rgba(13, 132, 201, 0.65);
}
.agenda__mas:hover::before { left: 100%; }

.agenda__mas-ico {
    width: 38px;
    height: 38px;
    flex: none;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 6px 14px -6px rgba(13, 132, 201, 0.6);
    position: relative;
    z-index: 1;
    transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.4, 1);
}
.agenda__mas:hover .agenda__mas-ico {
    transform: rotate(-10deg) scale(1.1);
}
.agenda__mas-txt {
    flex: 1;
    min-width: 0;
    font-size: 13px;
    color: #0f4f7a;
    position: relative;
    z-index: 1;
}
.agenda__mas-txt strong { color: #0d84c9; font-weight: 800; }
.agenda__mas-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 800;
    color: #0d84c9;
    white-space: nowrap;
    position: relative;
    z-index: 1;
    transition: gap 0.2s ease;
}
.agenda__mas:hover .agenda__mas-link { gap: 10px; }

/* RESPONSIVE */
@media (max-width: 767px) {
    .agenda__head {
        flex-direction: column;
        align-items: stretch;
    }
    .agenda__switch { width: 100%; }
    .agenda__tab {
        flex: 1;
        min-width: 0;
        padding: 8px 10px;
        font-size: 11.5px;
    }
    .agenda__tab-lbl { font-size: 11.5px; }
    .ev__meta { display: none; }
    .ev { padding: 12px 14px; gap: 11px; }
    .ev__ic { width: 38px; height: 38px; font-size: 16px; }
    .ev__t1 { font-size: 13px; }
    .ev__t2 { font-size: 11.5px; }
}
</style>