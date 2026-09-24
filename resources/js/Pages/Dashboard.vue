<script setup>
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    ArrowRightOutlined,
    CalendarOutlined,
    CheckSquareOutlined,
    ClockCircleOutlined,
    DollarOutlined,
    EnvironmentOutlined,
    ExclamationCircleOutlined,
    FileTextOutlined,
    ThunderboltOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';

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

const kpis = computed(() => [
    { titulo: 'Equipos', valor: props.tarjetas.total_equipos ?? 0, icon: ToolOutlined, color: '#0d84c9', ruta: 'equipos.por_sucursal' },
    { titulo: 'Valor de inventario', valor: moneda(props.tarjetas.valor_inventario), icon: DollarOutlined, color: '#1f9e86' },
    { titulo: 'Mantenimientos pendientes', valor: props.tarjetas.mantenimientos_pendientes ?? 0, icon: ClockCircleOutlined, color: '#173a5f', ruta: 'mantenimientos.index' },
    { titulo: 'Órdenes urgentes', valor: props.tarjetas.urgentes ?? 0, icon: ThunderboltOutlined, color: '#d64545' },
    { titulo: 'Preventivos vencidos', valor: props.tarjetas.preventivos_vencidos ?? 0, icon: CalendarOutlined, color: '#e08a1e', ruta: 'planes.index' },
    { titulo: 'Solicitudes abiertas', valor: props.tarjetas.solicitudes_abiertas ?? 0, icon: FileTextOutlined, color: '#6b4bc9', ruta: 'solicitudes.index' },
    { titulo: 'Tareas pendientes', valor: props.tarjetas.tareas_pendientes ?? 0, icon: CheckSquareOutlined, color: '#0d84c9', ruta: 'tareas.index' },
    { titulo: 'Tareas vencidas', valor: props.tarjetas.tareas_vencidas ?? 0, icon: ExclamationCircleOutlined, color: '#d64545', ruta: 'tareas.index' },
]);

const alertasHero = computed(() =>
    [
        props.tarjetas.urgentes ? { texto: `${props.tarjetas.urgentes} orden${props.tarjetas.urgentes === 1 ? '' : 'es'} urgente${props.tarjetas.urgentes === 1 ? '' : 's'}`, ruta: 'mantenimientos.index' } : null,
        props.tarjetas.preventivos_vencidos ? { texto: `${props.tarjetas.preventivos_vencidos} preventivo${props.tarjetas.preventivos_vencidos === 1 ? '' : 's'} vencido${props.tarjetas.preventivos_vencidos === 1 ? '' : 's'}`, ruta: 'planes.index' } : null,
        props.tarjetas.tareas_vencidas ? { texto: `${props.tarjetas.tareas_vencidas} tarea${props.tarjetas.tareas_vencidas === 1 ? '' : 's'} vencida${props.tarjetas.tareas_vencidas === 1 ? '' : 's'}`, ruta: 'tareas.index' } : null,
    ].filter(Boolean),
);

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
    if (d <= 30) return `En ${d} días`;

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
    if (meses > 0) partes.push(`${meses} ${meses === 1 ? 'mes' : 'meses'}`);
    if (dias > 0) partes.push(`${dias} ${dias === 1 ? 'día' : 'días'}`);

    return `En ${partes.join(' y ')}`;
};
const colorDias = (v) => {
    const d = diasRestantes(v);
    if (d === null) return 'default';
    if (d <= 3) return 'error';
    if (d <= 7) return 'warning';
    return 'success';
};

/** Igual que etiquetaDias pero abreviada (11 M 23 D en vez de "En 11 meses y 23 días"), para las tarjetas del panel de preventivos. */
const tiempoAbrev = (v) => {
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

const COLOR_ESTADO_TAREA = { pendiente: 'gold', en_proceso: 'blue', realizada: 'green', cancelada: 'red' };
const ETIQUETA_ESTADO_TAREA = { pendiente: 'Pendiente', en_proceso: 'En proceso', realizada: 'Realizada', cancelada: 'Cancelada' };
</script>

<template>
    <Head title="Panel de control" />

    <AppLayout titulo="Panel de control">
        <div class="hero">
            <div class="hero__txt">
                <div class="hero__saludo">{{ saludo }}, {{ nombre || 'bienvenido' }}</div>
                <div class="hero__fecha">{{ hoy }}</div>
                <div v-if="alertasHero.length" class="hero__alertas">
                    <button v-for="a in alertasHero" :key="a.texto" type="button" class="hero__alerta" @click="ir(a.ruta)">
                        <ExclamationCircleOutlined /> {{ a.texto }}
                    </button>
                </div>
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
                <a-card size="small" class="panel" style="--acc: #0d84c9">
                    <SeccionFicha titulo="Agenda de próximos trabajos" :icono="CalendarOutlined" color="#0d84c9">
                        <template #extra>
                            <span class="panel__extra">
                                <span class="panel-contador" style="--bc: #0d84c9">{{ agenda.length }}</span>
                                <a class="panel__link" @click="ir('mantenimientos.index')">Ver todos <ArrowRightOutlined /></a>
                            </span>
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
                                    <span class="fila__t">{{ item.folio }} · {{ item.equipo?.codigo_activo ?? item.ubicacion?.nombre ?? '' }}</span>
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
                <a-card size="small" class="panel" style="--acc: #d64545">
                    <SeccionFicha titulo="Urgencias abiertas" :icono="ThunderboltOutlined" color="#d64545">
                        <template #extra>
                            <span class="panel-contador" style="--bc: #d64545">{{ urgencias.length }}</span>
                        </template>
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
                                    <span class="fila__t">{{ item.folio }} · {{ item.equipo?.codigo_activo ?? item.ubicacion?.nombre ?? '' }}</span>
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
                <a-card size="small" class="panel" style="--acc: #e08a1e">
                    <SeccionFicha titulo="Preventivos próximos" :icono="CalendarOutlined" color="#e08a1e">
                        <template #extra>
                            <span class="panel-contador" style="--bc: #e08a1e">{{ preventivos_proximos.length }}</span>
                        </template>
                        <div v-if="preventivos_proximos.length" class="filas">
                            <button
                                v-for="(item, i) in preventivos_proximos"
                                :key="i"
                                type="button"
                                class="fila fila--doble"
                                @click="router.visit(route('planes.show', item.plan.id))"
                            >
                                <span class="fila__ic" style="--c: #e08a1e">
                                    <ToolOutlined v-if="item.plan?.equipo" />
                                    <EnvironmentOutlined v-else />
                                </span>
                                <span class="fila__c">
                                    <span class="fila__linea">
                                        <span class="fila__t">
                                            {{ item.plan?.equipo?.codigo_activo ?? item.plan?.ubicacion?.codigo ?? '—' }}
                                            · {{ item.plan?.tipo?.nombre ?? 'Preventivo' }}
                                        </span>
                                        <a-tag class="fila__chip" :color="colorDias(item.fecha_programada)">{{ tiempoAbrev(item.fecha_programada) }}</a-tag>
                                    </span>
                                    <span class="fila__linea">
                                        <span class="fila__s">{{ item.plan?.sucursal?.nombre ?? '—' }}</span>
                                        <span class="fila__s">{{ fecha(item.fecha_programada) }}</span>
                                    </span>
                                </span>
                            </button>
                        </div>
                        <div v-else class="vacio-mini">
                            <CalendarOutlined /> Sin preventivos programados
                        </div>
                    </SeccionFicha>
                </a-card>
            </a-col>

            <a-col :xs="24" :lg="12">
                <a-card size="small" class="panel" style="--acc: #6b4bc9">
                    <SeccionFicha titulo="Tareas próximas" :icono="CheckSquareOutlined" color="#6b4bc9">
                        <template #extra>
                            <span class="panel__extra">
                                <span class="panel-contador" style="--bc: #6b4bc9">{{ tareas_proximas.length }}</span>
                                <a class="panel__link" @click="ir('tareas.index')">Ver todas <ArrowRightOutlined /></a>
                            </span>
                        </template>
                        <div v-if="tareas_proximas.length" class="filas">
                            <button
                                v-for="item in tareas_proximas"
                                :key="item.id"
                                type="button"
                                class="fila"
                                :class="{ 'fila--alerta': diasRestantes(item.fecha_limite) < 0 }"
                                @click="router.visit(route('tareas.show', item.id))"
                            >
                                <span class="fila__ic" style="--c: #6b4bc9"><CheckSquareOutlined /></span>
                                <span class="fila__c">
                                    <span class="fila__t">{{ item.titulo || item.descripcion }}</span>
                                    <span class="fila__s">{{ item.responsables?.map((r) => r.nombre).join(', ') || 'Sin responsable' }}</span>
                                </span>
                                <a-tag :color="COLOR_ESTADO_TAREA[item.estado] ?? 'default'">{{ ETIQUETA_ESTADO_TAREA[item.estado] ?? item.estado }}</a-tag>
                                <a-tag v-if="item.prioridad" :color="item.prioridad.color || 'default'">{{ item.prioridad.nombre }}</a-tag>
                                <a-tag :color="colorDias(item.fecha_limite)">{{ etiquetaDias(item.fecha_limite) }}</a-tag>
                            </button>
                        </div>
                        <div v-else class="vacio-mini vacio-mini--ok">
                            <CheckSquareOutlined /> Sin tareas pendientes
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
.hero__alertas {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}
.hero__alerta {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(0, 0, 0, 0.14);
    color: #fff;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s ease;
    animation: sigam-fade-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.hero__alerta:hover {
    background: rgba(0, 0, 0, 0.26);
}
.hero__alerta .anticon {
    color: #ffd7d7;
}
/* ---------- KPIs ---------- */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}
@media (max-width: 1100px) {
    .kpis {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 480px) {
    .kpis {
        grid-template-columns: 1fr;
    }
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
    border-top: 3px solid var(--acc, var(--sigam-teal));
    border-radius: 10px;
    transition: box-shadow 0.16s ease, transform 0.16s ease;
}
.panel:hover {
    box-shadow: var(--sigam-sombra-sm);
}
.panel__extra {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}
.panel-contador {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 21px;
    height: 21px;
    padding: 0 6px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
    background: color-mix(in srgb, var(--bc, var(--sigam-navy)) 13%, #fff);
    color: var(--bc, var(--sigam-navy));
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

/* ---------- Filas de dos líneas (preventivos: código+tipo / sucursal+fecha) ---------- */
.fila--doble {
    align-items: flex-start;
}
.fila--doble .fila__c {
    gap: 3px;
}
.fila__linea {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.fila__linea .fila__t {
    flex: 1;
    min-width: 0;
}
.fila__linea .fila__s {
    flex: none;
    white-space: nowrap;
}
.fila__linea .fila__s:first-child {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fila__chip {
    flex: none;
    margin-inline-end: 0 !important;
}

/* ---------- Responsivo ---------- */
@media (max-width: 560px) {
    .hero {
        padding: 18px;
    }
    .hero__saludo {
        font-size: 19px;
    }
    .kpi {
        padding: 13px 14px;
    }
}

/* ---------- Vacíos ---------- */
.vacio-mini {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 30px 0 22px;
    color: var(--sigam-tenue);
    font-size: 12.5px;
    text-align: center;
}
.vacio-mini .anticon {
    font-size: 26px;
    opacity: 0.35;
}
.vacio-mini--ok {
    color: var(--sigam-teal-700);
}
</style>
