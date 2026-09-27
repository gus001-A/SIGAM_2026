<script setup>
import { computed } from 'vue';
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
    SafetyCertificateOutlined,
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

/* ------------------------------------------------------------------ */
/* KPIs                                                                */
/* ------------------------------------------------------------------ */
const kpis = computed(() => [
    { titulo: 'Equipos', valor: props.tarjetas.total_equipos ?? 0, icon: ToolOutlined, color: '#0d84c9', grad: 'linear-gradient(135deg, #0d84c9, #38a8e8)', bg: 'rgba(13, 132, 201, 0.14)', ruta: 'equipos.por_sucursal', trend: null },
    { titulo: 'Inventario', valor: moneda(props.tarjetas.valor_inventario), icon: DollarOutlined, color: '#1f9e86', grad: 'linear-gradient(135deg, #1f9e86, #4fd4b6)', bg: 'rgba(31, 158, 134, 0.14)', ruta: null, trend: 'up' },
    { titulo: 'Mant. pendientes', valor: props.tarjetas.mantenimientos_pendientes ?? 0, icon: ClockCircleOutlined, color: '#173a5f', grad: 'linear-gradient(135deg, #173a5f, #3f6b9c)', bg: 'rgba(23, 58, 95, 0.14)', ruta: 'mantenimientos.index', trend: null },
    { titulo: 'Urgentes', valor: props.tarjetas.urgentes ?? 0, icon: ThunderboltOutlined, color: '#d64545', grad: 'linear-gradient(135deg, #d64545, #ff7a7a)', bg: 'rgba(214, 69, 69, 0.14)', ruta: 'mantenimientos.index', trend: 'alert' },
    { titulo: 'Prev. vencidos', valor: props.tarjetas.preventivos_vencidos ?? 0, icon: CalendarOutlined, color: '#e08a1e', grad: 'linear-gradient(135deg, #e08a1e, #ffb85c)', bg: 'rgba(224, 138, 30, 0.16)', ruta: 'planes.index', trend: 'alert' },
    { titulo: 'Solicitudes', valor: props.tarjetas.solicitudes_abiertas ?? 0, icon: FileTextOutlined, color: '#6b4bc9', grad: 'linear-gradient(135deg, #6b4bc9, #a48bff)', bg: 'rgba(107, 75, 201, 0.14)', ruta: 'solicitudes.index', trend: null },
    { titulo: 'Tareas pend.', valor: props.tarjetas.tareas_pendientes ?? 0, icon: CheckSquareOutlined, color: '#0d84c9', grad: 'linear-gradient(135deg, #0d84c9, #38a8e8)', bg: 'rgba(13, 132, 201, 0.14)', ruta: 'tareas.index', trend: null },
    { titulo: 'Tareas venc.', valor: props.tarjetas.tareas_vencidas ?? 0, icon: ExclamationCircleOutlined, color: '#d64545', grad: 'linear-gradient(135deg, #d64545, #ff7a7a)', bg: 'rgba(214, 69, 69, 0.14)', ruta: 'tareas.index', trend: 'alert' },
]);

/* Límite de cards visibles por panel (6 por panel = grid 3×2) */
const LIMITE = 6;
const agendaVisibles = computed(() => props.agenda.slice(0, LIMITE));
const agendaResto = computed(() => Math.max(0, props.agenda.length - LIMITE));
const urgenciasVisibles = computed(() => props.urgencias.slice(0, LIMITE));
const urgenciasResto = computed(() => Math.max(0, props.urgencias.length - LIMITE));
const preventivosVisibles = computed(() => props.preventivos_proximos.slice(0, LIMITE));
const preventivosResto = computed(() => Math.max(0, props.preventivos_proximos.length - LIMITE));
const tareasVisibles = computed(() => props.tareas_proximas.slice(0, LIMITE));
const tareasResto = computed(() => Math.max(0, props.tareas_proximas.length - LIMITE));

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const ir = (ruta, params) => ruta && router.visit(route(ruta, params));

const diasRestantes = (v) => {
    if (!v) return null;
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const objetivo = new Date(v);
    objetivo.setHours(0, 0, 0, 0);
    return Math.round((objetivo - hoy) / 86400000);
};

const etiquetaDias = (v) => {
    const d = diasRestantes(v);
    if (d === null) return '';
    if (d < 0) return 'Vencido';
    if (d === 0) return 'Hoy';
    if (d === 1) return 'Mañana';
    if (d <= 30) return `${d} D`;

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const objetivo = new Date(v);
    objetivo.setHours(0, 0, 0, 0);

    let meses = (objetivo.getFullYear() - hoy.getFullYear()) * 12 + (objetivo.getMonth() - hoy.getMonth());
    let dias = objetivo.getDate() - hoy.getDate();
    if (dias < 0) {
        meses -= 1;
        dias += new Date(objetivo.getFullYear(), objetivo.getMonth(), 0).getDate();
    }
    const partes = [];
    if (meses > 0) partes.push(`${meses} M`);
    if (dias > 0) partes.push(`${dias} D`);
    return partes.join(' ') || '0 D';
};

const colorDias = (v) => {
    const d = diasRestantes(v);
    if (d === null) return 'default';
    if (d <= 3) return 'error';
    if (d <= 7) return 'warning';
    return 'success';
};

const COLOR_ESTADO_TAREA = { pendiente: 'gold', en_proceso: 'blue', realizada: 'green', cancelada: 'red' };
const ETIQUETA_ESTADO_TAREA = { pendiente: 'Pendiente', en_proceso: 'En proceso', realizada: 'Realizada', cancelada: 'Cancelada' };

const PANEL_COLORS = {
    agenda: '#0d84c9',
    urgencias: '#d64545',
    preventivos: '#e08a1e',
    tareas: '#6b4bc9',
};
const PANEL_GRADS = {
    agenda: 'linear-gradient(135deg, #0d84c9, #38a8e8)',
    urgencias: 'linear-gradient(135deg, #d64545, #ff7a7a)',
    preventivos: 'linear-gradient(135deg, #e08a1e, #ffb85c)',
    tareas: 'linear-gradient(135deg, #6b4bc9, #a48bff)',
};
</script>

<template>
    <Head title="Panel de control" />

    <AppLayout titulo="Panel de control">
        <div class="dash">
            <!-- ============ HERO ============ -->
            <div class="hero">
                <div class="hero__deco hero__deco--1"></div>
                <div class="hero__deco hero__deco--2"></div>
                <div class="hero__shine"></div>

                <div class="hero__txt">
                    <div class="hero__saludo">{{ saludo }}, {{ nombre || 'bienvenido' }}</div>
                    <div class="hero__fecha">
                        <CalendarOutlined /> {{ hoy }}
                    </div>
                </div>

                <div class="hero__ok">
                    <SafetyCertificateOutlined /> Todo en orden
                </div>
            </div>

            <!-- ============ KPIs ============ -->
            <div class="kpis">
                <button
                    v-for="(kpi, i) in kpis"
                    :key="kpi.titulo"
                    type="button"
                    class="kpi"
                    :class="{ 'kpi--link': kpi.ruta, 'kpi--alert': kpi.trend === 'alert' }"
                    :style="{ '--acc': kpi.color, '--acc-bg': kpi.bg, '--acc-grad': kpi.grad, animationDelay: `${i * 0.04}s` }"
                    @click="ir(kpi.ruta)"
                >
                    <span class="kpi__ic"><component :is="kpi.icon" /></span>
                    <span class="kpi__t">
                        <span class="kpi__v">{{ kpi.valor }}</span>
                        <span class="kpi__l">{{ kpi.titulo }}</span>
                    </span>
                    <span v-if="kpi.trend === 'alert'" class="kpi__pulse"></span>
                </button>
            </div>

            <!-- ============ PANELES ============ -->
            <div class="paneles">
                <!-- Agenda -->
                <section class="panel-card" :style="{ '--acc': PANEL_COLORS.agenda, '--acc-grad': PANEL_GRADS.agenda, '--acc-bg': 'rgba(13, 132, 201, 0.14)' }">
                    <header class="panel-card__head">
                        <span class="panel-card__ic"><CalendarOutlined /></span>
                        <div class="panel-card__tt">
                            <h3 class="panel-card__t">Agenda próximos</h3>
                            <span class="panel-card__s">Trabajos programados</span>
                        </div>
                        <span class="panel-card__contador">{{ agenda.length }}</span>
                        <a class="panel-card__link" @click="ir('mantenimientos.index')">
                            Ver <ArrowRightOutlined />
                        </a>
                    </header>
                    <div class="panel-card__body">
                        <div v-if="agenda.length" class="mini-grid">
                            <button
                                v-for="(item, i) in agendaVisibles"
                                :key="item.id"
                                type="button"
                                class="mini-card"
                                :style="{ '--c': PANEL_COLORS.agenda, '--cg': PANEL_GRADS.agenda, animationDelay: `${i * 0.03}s` }"
                                @click="router.visit(route('mantenimientos.show', item.id))"
                            >
                                <span class="mini-card__top">
                                    <span class="mini-card__ic"><ToolOutlined /></span>
                                    <a-tag class="mini-card__tag" :color="item.prioridad?.color || 'default'">
                                        {{ item.prioridad?.nombre }}
                                    </a-tag>
                                </span>
                                <span class="mini-card__t1">{{ item.folio }}</span>
                                <span class="mini-card__t2">
                                    {{ item.equipo?.codigo_activo ?? item.ubicacion?.nombre ?? '—' }}
                                </span>
                                <span class="mini-card__meta">
                                    <ClockCircleOutlined /> {{ fecha(item.programado_inicio) }}
                                </span>
                            </button>
                            <button v-if="agendaResto" type="button" class="mini-mas" @click="ir('mantenimientos.index')">
                                +{{ agendaResto }} más <ArrowRightOutlined />
                            </button>
                        </div>
                        <div v-else class="vacio-mini"><CalendarOutlined /> Sin trabajos</div>
                    </div>
                </section>

                <!-- Urgencias -->
                <section class="panel-card" :style="{ '--acc': PANEL_COLORS.urgencias, '--acc-grad': PANEL_GRADS.urgencias, '--acc-bg': 'rgba(214, 69, 69, 0.14)' }">
                    <header class="panel-card__head">
                        <span class="panel-card__ic"><ThunderboltOutlined /></span>
                        <div class="panel-card__tt">
                            <h3 class="panel-card__t">Urgencias abiertas</h3>
                            <span class="panel-card__s">Requieren atención</span>
                        </div>
                        <span class="panel-card__contador">{{ urgencias.length }}</span>
                    </header>
                    <div class="panel-card__body">
                        <div v-if="urgencias.length" class="mini-grid">
                            <button
                                v-for="(item, i) in urgenciasVisibles"
                                :key="item.id"
                                type="button"
                                class="mini-card mini-card--alerta"
                                :style="{ '--c': PANEL_COLORS.urgencias, '--cg': PANEL_GRADS.urgencias, animationDelay: `${i * 0.03}s` }"
                                @click="router.visit(route('mantenimientos.show', item.id))"
                            >
                                <span class="mini-card__top">
                                    <span class="mini-card__ic"><ThunderboltOutlined /></span>
                                    <a-tag class="mini-card__tag" :color="item.prioridad?.color || 'red'">
                                        {{ item.prioridad?.nombre ?? 'Urgente' }}
                                    </a-tag>
                                </span>
                                <span class="mini-card__t1">{{ item.folio }}</span>
                                <span class="mini-card__t2">
                                    {{ item.equipo?.codigo_activo ?? item.ubicacion?.nombre ?? '—' }}
                                </span>
                                <span class="mini-card__meta">
                                    <ThunderboltOutlined /> {{ item.estado?.nombre ?? 'Abierta' }}
                                </span>
                            </button>
                            <button v-if="urgenciasResto" type="button" class="mini-mas mini-mas--alert" @click="ir('mantenimientos.index')">
                                +{{ urgenciasResto }} más <ArrowRightOutlined />
                            </button>
                        </div>
                        <div v-else class="vacio-mini vacio-mini--ok"><SafetyCertificateOutlined /> Sin urgencias</div>
                    </div>
                </section>

                <!-- Preventivos -->
                <section class="panel-card" :style="{ '--acc': PANEL_COLORS.preventivos, '--acc-grad': PANEL_GRADS.preventivos, '--acc-bg': 'rgba(224, 138, 30, 0.16)' }">
                    <header class="panel-card__head">
                        <span class="panel-card__ic"><CalendarOutlined /></span>
                        <div class="panel-card__tt">
                            <h3 class="panel-card__t">Preventivos próximos</h3>
                            <span class="panel-card__s">Mantenimiento programado</span>
                        </div>
                        <span class="panel-card__contador">{{ preventivos_proximos.length }}</span>
                    </header>
                    <div class="panel-card__body">
                        <div v-if="preventivos_proximos.length" class="mini-grid">
                            <button
                                v-for="(item, i) in preventivosVisibles"
                                :key="i"
                                type="button"
                                class="mini-card"
                                :style="{ '--c': PANEL_COLORS.preventivos, '--cg': PANEL_GRADS.preventivos, animationDelay: `${i * 0.03}s` }"
                                @click="router.visit(route('planes.show', item.plan.id))"
                            >
                                <span class="mini-card__top">
                                    <span class="mini-card__ic">
                                        <ToolOutlined v-if="item.plan?.equipo" />
                                        <EnvironmentOutlined v-else />
                                    </span>
                                    <a-tag class="mini-card__tag" :color="colorDias(item.fecha_programada)">
                                        {{ etiquetaDias(item.fecha_programada) }}
                                    </a-tag>
                                </span>
                                <span class="mini-card__t1">
                                    {{ item.plan?.equipo?.codigo_activo ?? item.plan?.ubicacion?.codigo ?? '—' }}
                                </span>
                                <span class="mini-card__t2">{{ item.plan?.tipo?.nombre ?? 'Preventivo' }}</span>
                                <span class="mini-card__meta">
                                    <EnvironmentOutlined /> {{ item.plan?.sucursal?.nombre ?? fecha(item.fecha_programada) }}
                                </span>
                            </button>
                            <button v-if="preventivosResto" type="button" class="mini-mas mini-mas--warn" @click="ir('planes.index')">
                                +{{ preventivosResto }} más <ArrowRightOutlined />
                            </button>
                        </div>
                        <div v-else class="vacio-mini"><CalendarOutlined /> Sin preventivos</div>
                    </div>
                </section>

                <!-- Tareas -->
                <section class="panel-card" :style="{ '--acc': PANEL_COLORS.tareas, '--acc-grad': PANEL_GRADS.tareas, '--acc-bg': 'rgba(107, 75, 201, 0.14)' }">
                    <header class="panel-card__head">
                        <span class="panel-card__ic"><CheckSquareOutlined /></span>
                        <div class="panel-card__tt">
                            <h3 class="panel-card__t">Tareas próximas</h3>
                            <span class="panel-card__s">Pendientes por vencer</span>
                        </div>
                        <span class="panel-card__contador">{{ tareas_proximas.length }}</span>
                        <a class="panel-card__link" @click="ir('tareas.index')">
                            Ver <ArrowRightOutlined />
                        </a>
                    </header>
                    <div class="panel-card__body">
                        <div v-if="tareas_proximas.length" class="mini-grid">
                            <button
                                v-for="(item, i) in tareasVisibles"
                                :key="item.id"
                                type="button"
                                class="mini-card"
                                :class="{ 'mini-card--alerta': diasRestantes(item.fecha_limite) < 0 }"
                                :style="{ '--c': PANEL_COLORS.tareas, '--cg': PANEL_GRADS.tareas, animationDelay: `${i * 0.03}s` }"
                                @click="router.visit(route('tareas.show', item.id))"
                            >
                                <span class="mini-card__top">
                                    <span class="mini-card__ic"><CheckSquareOutlined /></span>
                                    <a-tag class="mini-card__tag" :color="COLOR_ESTADO_TAREA[item.estado] ?? 'default'">
                                        {{ ETIQUETA_ESTADO_TAREA[item.estado] ?? item.estado }}
                                    </a-tag>
                                </span>
                                <span class="mini-card__t1">{{ item.titulo || item.descripcion }}</span>
                                <span class="mini-card__t2">
                                    <UserOutlined />
                                    {{ item.responsables?.map((r) => r.nombre).join(', ') || 'Sin responsable' }}
                                </span>
                                <span class="mini-card__meta">
                                    <ClockCircleOutlined /> {{ etiquetaDias(item.fecha_limite) }}
                                </span>
                            </button>
                            <button v-if="tareasResto" type="button" class="mini-mas mini-mas--purple" @click="ir('tareas.index')">
                                +{{ tareasResto }} más <ArrowRightOutlined />
                            </button>
                        </div>
                        <div v-else class="vacio-mini vacio-mini--ok"><CheckSquareOutlined /> Sin tareas</div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ================================================================
   ANIMACIONES GLOBALES
   ================================================================ */
@keyframes sigam-fade-up {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes sigam-pop {
    0%   { opacity: 0; transform: scale(0.94); }
    60%  { opacity: 1; transform: scale(1.02); }
    100% { opacity: 1; transform: scale(1); }
}
@keyframes sigam-shine {
    0%   { transform: translateX(-120%) skewX(-20deg); opacity: 0; }
    40%  { opacity: 0.55; }
    100% { transform: translateX(220%) skewX(-20deg); opacity: 0; }
}
@keyframes sigam-pulse {
    0%   { box-shadow: 0 0 0 0 color-mix(in srgb, var(--acc) 55%, transparent); }
    70%  { box-shadow: 0 0 0 8px color-mix(in srgb, var(--acc) 0%, transparent); }
    100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--acc) 0%, transparent); }
}
@keyframes sigam-blink {
    0%, 100% { opacity: 1; }
    50%      { opacity: 0.35; }
}

/* ================================================================
   LAYOUT GENERAL (sin scroll)
   ================================================================ */
.dash {
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: 100%;
    min-height: 0;
}

/* ================================================================
   HERO
   ================================================================ */
.hero {
    flex: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 16px 22px;
    border-radius: 16px;
    background: var(--sigam-grad);
    background-size: 200% 200%;
    animation: sigam-grad-shift 12s ease infinite;
    box-shadow: 0 18px 38px -20px rgba(15, 44, 74, 0.65), inset 0 0 0 1px rgba(255, 255, 255, 0.06);
    position: relative;
    overflow: hidden;
}
.hero__deco {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.hero__deco--1 {
    right: -50px;
    top: -70px;
    width: 210px;
    height: 210px;
    background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.02) 60%);
}
.hero__deco--2 {
    left: -40px;
    bottom: -60px;
    width: 140px;
    height: 140px;
    background: radial-gradient(circle at 60% 40%, rgba(120, 230, 200, 0.22), rgba(255, 255, 255, 0.02) 70%);
}
.hero__shine {
    position: absolute;
    top: 0;
    left: 0;
    width: 60%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
    transform: translateX(-120%) skewX(-20deg);
    animation: sigam-shine 6s ease-in-out infinite;
    pointer-events: none;
}
.hero__txt {
    position: relative;
    z-index: 1;
    min-width: 220px;
}
.hero__saludo {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.015em;
    line-height: 1.15;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
}
.hero__saludo::first-letter {
    text-transform: uppercase;
}
.hero__fecha {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.9);
    margin-top: 3px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.hero__fecha::first-letter {
    text-transform: uppercase;
}

.hero__ok {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(31, 158, 134, 0.28);
    border: 1px solid rgba(120, 230, 200, 0.5);
    color: #e6fff8;
    font-size: 11.5px;
    font-weight: 700;
    position: relative;
    z-index: 1;
    animation: sigam-pop 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* ================================================================
   KPIs
   ================================================================ */
.kpis {
    flex: none;
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 8px;
}
@media (max-width: 1400px) {
    .kpis {
        grid-template-columns: repeat(4, 1fr);
    }
}
@media (max-width: 700px) {
    .kpis {
        grid-template-columns: repeat(2, 1fr);
    }
}
.kpi {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 12px;
    box-shadow: var(--sigam-sombra-sm);
    text-align: left;
    cursor: default;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.16s ease, transform 0.16s ease, border-color 0.16s ease;
    min-height: 60px;
    animation: sigam-fade-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc-grad);
}
.kpi::after {
    content: '';
    position: absolute;
    right: -30px;
    bottom: -30px;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--acc) 8%, transparent);
    transition: transform 0.3s ease;
    pointer-events: none;
}
.kpi--link {
    cursor: pointer;
}
.kpi--link:hover {
    box-shadow: 0 16px 30px -16px color-mix(in srgb, var(--acc) 65%, transparent);
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--acc) 45%, var(--sigam-borde));
}
.kpi--link:hover::after {
    transform: scale(1.4);
}
.kpi--alert {
    background: linear-gradient(180deg, #fff 0%, #fff5f5 100%);
}
.kpi__ic {
    width: 36px;
    height: 36px;
    flex: none;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: var(--acc-grad);
    box-shadow: 0 6px 14px -6px color-mix(in srgb, var(--acc) 70%, transparent);
    transition: transform 0.2s ease;
}
.kpi--link:hover .kpi__ic {
    transform: scale(1.08) rotate(-3deg);
}
.kpi__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}
.kpi__v {
    font-size: 16px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.05;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kpi__l {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sigam-tenue);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.kpi__pulse {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--acc);
    animation: sigam-blink 1.6s ease-in-out infinite;
}
.kpi__pulse::after {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    border: 2px solid var(--acc);
    opacity: 0.4;
    animation: sigam-pulse 1.8s ease-out infinite;
}

/* ================================================================
   PANELES (grid 2x2)
   ================================================================ */
.paneles {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 12px;
}
@media (max-width: 900px) {
    .paneles {
        grid-template-columns: 1fr;
        grid-template-rows: none;
        flex: none;
    }
}

.panel-card {
    display: flex;
    flex-direction: column;
    min-height: 0;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-top: 3px solid transparent;
    border-image: var(--acc-grad) 1;
    border-radius: 12px;
    box-shadow: var(--sigam-sombra-sm);
    overflow: hidden;
    transition: box-shadow 0.16s ease, transform 0.16s ease;
    position: relative;
}
.panel-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--acc-grad);
}
.panel-card:hover {
    box-shadow: 0 18px 34px -18px color-mix(in srgb, var(--acc) 55%, transparent);
    transform: translateY(-2px);
}

.panel-card__head {
    flex: none;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(180deg, color-mix(in srgb, var(--acc) 7%, #fff) 0%, #fff 100%);
    position: relative;
}
.panel-card__ic {
    width: 30px;
    height: 30px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: var(--acc-grad);
    box-shadow: 0 6px 12px -6px color-mix(in srgb, var(--acc) 65%, transparent);
    transition: transform 0.2s ease;
}
.panel-card:hover .panel-card__ic {
    transform: rotate(-6deg) scale(1.06);
}
.panel-card__tt {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}
.panel-card__t {
    margin: 0;
    font-size: 12.5px;
    font-weight: 800;
    color: var(--sigam-navy);
    letter-spacing: -0.01em;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.panel-card__s {
    font-size: 10.5px;
    color: var(--sigam-tenue);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.panel-card__contador {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    background: var(--acc-bg);
    color: var(--acc);
    flex: none;
    border: 1px solid color-mix(in srgb, var(--acc) 25%, transparent);
}
.panel-card__link {
    font-size: 11px;
    font-weight: 700;
    color: var(--acc);
    cursor: pointer;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    flex: none;
    transition: gap 0.15s ease, color 0.15s ease;
}
.panel-card__link:hover {
    gap: 6px;
    color: color-mix(in srgb, var(--acc) 80%, #000);
}

.panel-card__body {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    padding: 10px 12px 12px;
}

/* ================================================================
   MINI-CARDS (grid 3×2 = 6 items)
   ================================================================ */
.mini-grid {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(2, 1fr);
    gap: 8px;
}
.mini-card {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 7px 9px;
    border: 1px solid var(--sigam-borde-suave);
    border-left: 3px solid var(--c);
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    text-align: left;
    min-width: 0;
    overflow: hidden;
    position: relative;
    transition: border-color 0.13s ease, box-shadow 0.13s ease, transform 0.13s ease, background 0.13s ease;
    animation: sigam-fade-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.mini-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, color-mix(in srgb, var(--c) 8%, transparent), transparent 60%);
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}
.mini-card:hover {
    border-color: color-mix(in srgb, var(--c) 45%, var(--sigam-borde-suave));
    box-shadow: 0 10px 20px -12px color-mix(in srgb, var(--c) 70%, transparent);
    transform: translateY(-2px);
}
.mini-card:hover::after {
    opacity: 1;
}
.mini-card--alerta {
    background: linear-gradient(180deg, #fff 0%, color-mix(in srgb, var(--c) 6%, #fff) 100%);
    border-color: color-mix(in srgb, var(--c) 30%, var(--sigam-borde-suave));
}
.mini-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    min-width: 0;
    position: relative;
    z-index: 1;
}
.mini-card__ic {
    width: 20px;
    height: 20px;
    flex: none;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #fff;
    background: var(--cg);
    box-shadow: 0 4px 8px -4px color-mix(in srgb, var(--c) 70%, transparent);
    transition: transform 0.2s ease;
}
.mini-card:hover .mini-card__ic {
    transform: scale(1.1) rotate(-4deg);
}
.mini-card__tag {
    margin-inline-end: 0 !important;
    font-size: 9.5px !important;
    line-height: 15px !important;
    height: 17px !important;
    padding-inline: 5px !important;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    border-radius: 999px !important;
}
.mini-card__t1 {
    font-size: 11px;
    font-weight: 800;
    color: var(--sigam-navy);
    letter-spacing: -0.01em;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.15;
    position: relative;
    z-index: 1;
}
.mini-card__t2 {
    font-size: 10px;
    font-weight: 600;
    color: var(--sigam-texto);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    line-height: 1.15;
    position: relative;
    z-index: 1;
}
.mini-card__meta {
    font-size: 9.5px;
    color: var(--sigam-tenue);
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-top: auto;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.15;
    position: relative;
    z-index: 1;
}

.mini-mas {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 10px;
    border: 1px dashed var(--sigam-borde);
    border-radius: 10px;
    background: #fafbfc;
    color: var(--sigam-tenue);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.13s ease, color 0.13s ease, border-color 0.13s ease, transform 0.13s ease;
    animation: sigam-fade-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.mini-mas:hover {
    background: #eff6fc;
    color: var(--sigam-navy);
    border-color: color-mix(in srgb, var(--sigam-navy) 30%, var(--sigam-borde));
    transform: translateY(-1px);
}
.mini-mas--alert:hover {
    background: #fdf4f4;
    color: #d64545;
    border-color: #f4dede;
}
.mini-mas--warn:hover {
    background: #fdf7ec;
    color: #e08a1e;
    border-color: #f7e4c4;
}
.mini-mas--purple:hover {
    background: #f5f1fd;
    color: #6b4bc9;
    border-color: #ddd2f5;
}

/* ================================================================
   VACÍOS
   ================================================================ */
.vacio-mini {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 18px 0;
    color: var(--sigam-tenue);
    font-size: 12px;
    text-align: center;
    flex: 1;
}
.vacio-mini .anticon {
    font-size: 22px;
    opacity: 0.35;
}
.vacio-mini--ok {
    color: var(--sigam-teal-700);
}
</style>