<script setup>
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    ArrowRightOutlined,
    BarChartOutlined,
    CalendarOutlined,
    ClockCircleOutlined,
    DollarOutlined,
    FileTextOutlined,
    FormOutlined,
    PlusOutlined,
    RiseOutlined,
    ThunderboltOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    tarjetas: { type: Object, default: () => ({}) },
    por_estado: { type: Object, default: () => ({}) },
    agenda: { type: Array, default: () => [] },
    urgencias: { type: Array, default: () => [] },
    preventivos_proximos: { type: Array, default: () => [] },
});

const { puede } = usePermisos();
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

const kpis = computed(() => [
    { titulo: 'Equipos', valor: props.tarjetas.total_equipos ?? 0, icon: ToolOutlined, color: '#0d84c9', ruta: 'equipos.index' },
    { titulo: 'Valor de inventario', valor: moneda(props.tarjetas.valor_inventario), icon: DollarOutlined, color: '#1f9e86' },
    { titulo: 'Mantenimientos pendientes', valor: props.tarjetas.mantenimientos_pendientes ?? 0, icon: ClockCircleOutlined, color: '#173a5f', ruta: 'mantenimientos.index' },
    { titulo: 'Órdenes urgentes', valor: props.tarjetas.urgentes ?? 0, icon: ThunderboltOutlined, color: '#d64545' },
    { titulo: 'Preventivos vencidos', valor: props.tarjetas.preventivos_vencidos ?? 0, icon: CalendarOutlined, color: '#e08a1e', ruta: 'planes.index' },
    { titulo: 'Solicitudes abiertas', valor: props.tarjetas.solicitudes_abiertas ?? 0, icon: FileTextOutlined, color: '#6b4bc9', ruta: 'solicitudes.index' },
]);

const accesos = computed(() =>
    [
        { label: 'Nuevo equipo', icon: ToolOutlined, ruta: 'equipos.create', permiso: 'equipos.crear', color: '#0d84c9' },
        { label: 'Nueva solicitud', icon: FormOutlined, ruta: 'solicitudes.index', permiso: 'solicitudes.crear', color: '#6b4bc9' },
        { label: 'Nueva orden', icon: ToolOutlined, ruta: 'mantenimientos.index', permiso: 'mantenimientos.crear', color: '#1f9e86' },
        { label: 'Calendario', icon: CalendarOutlined, ruta: 'calendario.index', permiso: 'mantenimientos.ver', color: '#e08a1e' },
        { label: 'Reportes', icon: BarChartOutlined, ruta: 'reportes.index', permiso: 'reportes.ver', color: '#173a5f' },
    ].filter((a) => puede(a.permiso)),
);

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : '—');
const ir = (ruta, params) => ruta && router.visit(route(ruta, params));

const estados = computed(() => {
    const entradas = Object.entries(props.por_estado || {});
    const max = Math.max(1, ...entradas.map(([, n]) => n));
    const PALETA = ['#0d84c9', '#1f9e86', '#6b4bc9', '#e08a1e', '#d64545', '#173a5f', '#16806c'];
    return entradas
        .sort(([, a], [, b]) => b - a)
        .map(([nombre, total], i) => ({ nombre, total, pct: (100 * total) / max, color: PALETA[i % PALETA.length] }));
});
</script>

<template>
    <Head title="Panel de control" />

    <AppLayout titulo="Panel de control">
        <div class="hero">
            <div class="hero__txt">
                <div class="hero__saludo">{{ saludo }}, {{ nombre || 'bienvenido' }}</div>
                <div class="hero__fecha">{{ hoy }}</div>
            </div>
            <div v-if="accesos.length" class="hero__acc">
                <button
                    v-for="a in accesos"
                    :key="a.label"
                    type="button"
                    class="hero__btn"
                    @click="ir(a.ruta)"
                >
                    <span class="hero__btn-ic"><component :is="a.icon" /></span>
                    {{ a.label }}
                </button>
            </div>
        </div>

        <div class="kpis">
            <button
                v-for="kpi in kpis"
                :key="kpi.titulo"
                type="button"
                class="kpi"
                :class="{ 'kpi--link': kpi.ruta }"
                :style="{ '--acc': kpi.color }"
                @click="ir(kpi.ruta)"
            >
                <span class="kpi__ic"><component :is="kpi.icon" /></span>
                <span class="kpi__t">
                    <span class="kpi__v">{{ kpi.valor }}</span>
                    <span class="kpi__l">{{ kpi.titulo }}</span>
                </span>
                <ArrowRightOutlined v-if="kpi.ruta" class="kpi__arrow" />
            </button>
        </div>

        <a-row :gutter="[16, 16]" class="mt-4">
            <a-col :xs="24" :lg="12">
                <a-card size="small" class="panel">
                    <SeccionFicha titulo="Agenda de próximos trabajos" :icono="CalendarOutlined" color="#0d84c9">
                        <template #extra>
                            <a class="panel__link" @click="ir('mantenimientos.index')">Ver todos <ArrowRightOutlined /></a>
                        </template>
                        <div v-if="agenda.length" class="filas">
                            <button
                                v-for="item in agenda"
                                :key="item.id"
                                type="button"
                                class="fila"
                                @click="router.visit(route('mantenimientos.show', item.id))"
                            >
                                <span class="fila__ic" style="--c: #0d84c9"><ToolOutlined /></span>
                                <span class="fila__c">
                                    <span class="fila__t">{{ item.folio }} · {{ item.equipo?.codigo_activo ?? '' }}</span>
                                    <span class="fila__s">{{ item.tipo?.nombre }} — {{ fecha(item.programado_inicio) }}</span>
                                </span>
                                <a-tag :color="item.prioridad?.color || 'default'">{{ item.prioridad?.nombre }}</a-tag>
                            </button>
                        </div>
                        <div v-else class="vacio-mini">
                            <CalendarOutlined /> Sin trabajos programados
                        </div>
                    </SeccionFicha>
                </a-card>
            </a-col>

            <a-col :xs="24" :lg="12">
                <a-card size="small" class="panel">
                    <SeccionFicha titulo="Urgencias abiertas" :icono="ThunderboltOutlined" color="#d64545">
                        <div v-if="urgencias.length" class="filas">
                            <button
                                v-for="item in urgencias"
                                :key="item.id"
                                type="button"
                                class="fila fila--alerta"
                                @click="router.visit(route('mantenimientos.show', item.id))"
                            >
                                <span class="fila__ic" style="--c: #d64545"><ThunderboltOutlined /></span>
                                <span class="fila__c">
                                    <span class="fila__t">{{ item.folio }} · {{ item.equipo?.codigo_activo ?? '' }}</span>
                                    <span class="fila__s">{{ item.estado?.nombre }}</span>
                                </span>
                                <ArrowRightOutlined class="fila__go" />
                            </button>
                        </div>
                        <div v-else class="vacio-mini vacio-mini--ok">
                            <ThunderboltOutlined /> Sin urgencias pendientes
                        </div>
                    </SeccionFicha>
                </a-card>
            </a-col>

            <a-col :xs="24" :lg="12">
                <a-card size="small" class="panel">
                    <SeccionFicha titulo="Preventivos próximos" :icono="CalendarOutlined" color="#e08a1e">
                        <div v-if="preventivos_proximos.length" class="filas">
                            <div v-for="(item, i) in preventivos_proximos" :key="i" class="fila fila--plano">
                                <span class="fila__ic" style="--c: #e08a1e"><CalendarOutlined /></span>
                                <span class="fila__c">
                                    <span class="fila__t">{{ item.plan?.equipo?.codigo_activo ?? 'Equipo' }}</span>
                                    <span class="fila__s">Programado: {{ fecha(item.fecha_programada) }}</span>
                                </span>
                            </div>
                        </div>
                        <div v-else class="vacio-mini">
                            <CalendarOutlined /> Sin preventivos programados
                        </div>
                    </SeccionFicha>
                </a-card>
            </a-col>

            <a-col :xs="24" :lg="12">
                <a-card size="small" class="panel">
                    <SeccionFicha titulo="Órdenes por estado" :icono="RiseOutlined" color="#1f9e86">
                        <div v-if="estados.length" class="barras">
                            <div v-for="e in estados" :key="e.nombre" class="barra" :style="{ '--c': e.color }">
                                <span class="barra__nom">{{ e.nombre }}</span>
                                <span class="barra__track">
                                    <span class="barra__fill" :style="{ width: e.pct + '%' }" />
                                </span>
                                <span class="barra__val">{{ e.total }}</span>
                            </div>
                        </div>
                        <div v-else class="vacio-mini">
                            <RiseOutlined /> Sin órdenes registradas
                        </div>
                    </SeccionFicha>
                </a-card>
            </a-col>
        </a-row>
    </AppLayout>
</template>

<style scoped>
/* ---------- Banner de bienvenida ---------- */
.hero {
    flex: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 24px;
    border-radius: 18px;
    margin-bottom: 18px;
    background: var(--sigam-grad);
    background-size: 200% 200%;
    animation: sigam-grad-shift 12s ease infinite;
    box-shadow: 0 18px 40px -20px rgba(15, 44, 74, 0.55);
    position: relative;
    overflow: hidden;
}
.hero::after {
    content: '';
    position: absolute;
    right: -60px;
    top: -80px;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
}
.hero__saludo {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.01em;
}
.hero__saludo::first-letter {
    text-transform: uppercase;
}
.hero__fecha {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.82);
    margin-top: 2px;
}
.hero__fecha::first-letter {
    text-transform: uppercase;
}
.hero__acc {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    position: relative;
}
.hero__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 13px;
    border-radius: 11px;
    border: 1px solid rgba(255, 255, 255, 0.28);
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease;
}
.hero__btn:hover {
    background: rgba(255, 255, 255, 0.24);
    transform: translateY(-2px);
}
.hero__btn-ic {
    font-size: 13px;
    opacity: 0.9;
}

/* ---------- KPIs ---------- */
.kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 14px;
}
.kpi {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 15px 16px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 15px;
    box-shadow: var(--sigam-sombra-sm);
    text-align: left;
    cursor: default;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.16s ease, transform 0.16s ease, border-color 0.16s ease;
}
.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}
.kpi::after {
    content: '';
    position: absolute;
    right: -30px;
    bottom: -30px;
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--acc) 7%, transparent);
}
.kpi--link {
    cursor: pointer;
}
.kpi--link:hover {
    box-shadow: 0 16px 30px -18px color-mix(in srgb, var(--acc) 55%, transparent);
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--acc) 40%, var(--sigam-borde));
}
.kpi__ic {
    width: 46px;
    height: 46px;
    flex: none;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    color: var(--acc);
    background: color-mix(in srgb, var(--acc) 13%, #fff);
    position: relative;
}
.kpi__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
    position: relative;
}
.kpi__v {
    font-size: 20px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.05;
}
.kpi__l {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--sigam-tenue);
    margin-top: 2px;
}
.kpi__arrow {
    color: #cbd5e1;
    flex: none;
    transition: color 0.15s ease, transform 0.15s ease;
    position: relative;
}
.kpi--link:hover .kpi__arrow {
    color: var(--acc);
    transform: translateX(3px);
}

/* ---------- Paneles ---------- */
.panel {
    height: 100%;
}
.panel__link {
    font-size: 12px;
    font-weight: 600;
    color: var(--sigam-teal-700);
    cursor: pointer;
    white-space: nowrap;
}
.filas {
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.fila {
    display: flex;
    align-items: center;
    gap: 11px;
    width: 100%;
    text-align: left;
    padding: 9px 11px;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 11px;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.13s ease, box-shadow 0.13s ease, transform 0.13s ease, background 0.13s ease;
}
.fila--plano {
    cursor: default;
}
.fila--alerta {
    background: #fdf4f4;
    border-color: #f4dede;
}
.fila:not(.fila--plano):hover {
    border-color: var(--sigam-navy-100);
    box-shadow: var(--sigam-sombra-sm);
    transform: translateX(3px);
}
.fila__ic {
    width: 33px;
    height: 33px;
    flex: none;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 14%, #fff);
}
.fila__c {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}
.fila__t {
    font-size: 13px;
    font-weight: 600;
    color: var(--sigam-texto);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fila__s {
    font-size: 11.5px;
    color: var(--sigam-tenue);
}
.fila__go {
    color: #cbd5e1;
    flex: none;
    font-size: 12px;
}
.fila--alerta:hover .fila__go {
    color: #d64545;
}

/* ---------- Barras (órdenes por estado) ---------- */
.barras {
    display: flex;
    flex-direction: column;
    gap: 11px;
}
.barra {
    display: grid;
    grid-template-columns: 120px 1fr 30px;
    align-items: center;
    gap: 10px;
    font-size: 12.5px;
}
.barra__nom {
    color: var(--sigam-texto);
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-transform: capitalize;
}
.barra__track {
    height: 10px;
    border-radius: 999px;
    background: var(--sigam-navy-050);
    overflow: hidden;
}
.barra__fill {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, color-mix(in srgb, var(--c) 55%, #fff), var(--c));
    min-width: 6px;
    animation: barra-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes barra-in {
    from { width: 0 !important; }
}
.barra__val {
    text-align: right;
    font-weight: 800;
    color: var(--sigam-navy);
}

/* ---------- Vacíos ---------- */
.vacio-mini {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 18px 0;
    color: var(--sigam-tenue);
    font-size: 12.5px;
}
.vacio-mini--ok {
    color: var(--sigam-teal-700);
}
</style>
