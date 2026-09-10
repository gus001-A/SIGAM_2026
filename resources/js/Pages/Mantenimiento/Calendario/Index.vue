<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/es';
import {
    CalendarOutlined,
    FilterOutlined,
    LeftOutlined,
    RightOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

dayjs.locale('es');

const props = defineProps({
    eventos: { type: Array, default: () => [] },
    rango: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const mesActual = ref(dayjs(props.rango.desde));

const filtros = ref({
    sucursal_id: props.filtros.sucursal_id ?? undefined,
    tipo_id: props.filtros.tipo_id ?? undefined,
    prioridad_id: props.filtros.prioridad_id ?? undefined,
    estado_id: props.filtros.estado_id ?? undefined,
    tecnico_id: props.filtros.tecnico_id ?? undefined,
});

const hayFiltros = computed(() => Object.values(filtros.value).some((v) => v !== undefined && v !== null && v !== ''));

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
    filtros.value = { sucursal_id: undefined, tipo_id: undefined, prioridad_id: undefined, estado_id: undefined, tecnico_id: undefined };
    navegar(mesActual.value);
};

// --- Eventos por día ------------------------------------------------
const eventosPorDia = computed(() => {
    const mapa = {};
    for (const ev of props.eventos) {
        if (!ev.inicio) continue;
        const clave = dayjs(ev.inicio).format('YYYY-MM-DD');
        (mapa[clave] ??= []).push(ev);
    }
    return mapa;
});

const colorCategoria = (cat) =>
    ({ preventivo: '#1f9e86', correctivo: '#e08a1e', predictivo: '#0d84c9' })[cat] ?? '#64748b';

const hoy = dayjs().format('YYYY-MM-DD');

const opciones = (l, label = 'nombre') => (l ?? []).map((o) => ({ label: o[label], value: o.id }));

const abrirEvento = (ev) => {
    if (ev.tipo_evento === 'orden') router.visit(route('mantenimientos.show', ev.id));
    else if (ev.plan_id) router.visit(route('planes.show', ev.plan_id));
};

const resumen = computed(() => {
    const total = props.eventos.length;
    const preventivos = props.eventos.filter((e) => e.categoria === 'preventivo').length;
    const correctivos = props.eventos.filter((e) => e.categoria === 'correctivo').length;
    return { total, preventivos, correctivos };
});

const tarjetas = computed(() => [
    { etq: 'Eventos del mes', val: resumen.value.total, color: '#173a5f', icono: CalendarOutlined },
    { etq: 'Preventivos', val: resumen.value.preventivos, color: '#1f9e86', icono: CalendarOutlined },
    { etq: 'Correctivos', val: resumen.value.correctivos, color: '#e08a1e', icono: ToolOutlined },
]);
</script>

<template>
    <Head title="Calendario" />

    <AppLayout
        titulo="Calendario de trabajos"
        descripcion="Vista mensual de órdenes programadas y ocurrencias preventivas pendientes."
    >
        <template #acciones>
            <a-button v-if="hayFiltros" @click="limpiarFiltros">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
        </template>

        <a-card size="small" class="mb-3">
            <a-row :gutter="[12, 12]" align="middle">
                <a-col :xs="24" :md="8">
                    <a-space>
                        <a-button @click="cambiarMes(-1)"><template #icon><LeftOutlined /></template></a-button>
                        <a-button @click="irHoy">Hoy</a-button>
                        <a-button @click="cambiarMes(1)"><template #icon><RightOutlined /></template></a-button>
                        <span class="text-lg font-semibold capitalize">{{ mesActual.format('MMMM YYYY') }}</span>
                    </a-space>
                </a-col>
                <a-col :xs="24" :md="16">
                    <div class="filtros">
                        <a-select v-model:value="filtros.sucursal_id" :options="opciones(catalogos.sucursales)" allow-clear placeholder="Sucursal" size="small" @change="aplicarFiltros" />
                        <a-select v-model:value="filtros.tipo_id" :options="opciones(catalogos.tipos)" allow-clear placeholder="Tipo" size="small" @change="aplicarFiltros" />
                        <a-select v-model:value="filtros.prioridad_id" :options="opciones(catalogos.prioridades)" allow-clear placeholder="Prioridad" size="small" @change="aplicarFiltros" />
                        <a-select v-model:value="filtros.estado_id" :options="opciones(catalogos.estados)" allow-clear placeholder="Estado" size="small" @change="aplicarFiltros" />
                        <a-select v-model:value="filtros.tecnico_id" :options="opciones(catalogos.tecnicos)" allow-clear placeholder="Técnico" size="small" @change="aplicarFiltros" />
                    </div>
                </a-col>
            </a-row>
        </a-card>

        <a-row :gutter="12" class="mb-3">
            <a-col v-for="t in tarjetas" :key="t.etq" :xs="8">
                <div class="ctarj" :style="{ '--acc': t.color }">
                    <span class="ctarj__ic"><component :is="t.icono" /></span>
                    <div>
                        <div class="ctarj__v">{{ t.val }}</div>
                        <div class="ctarj__e">{{ t.etq }}</div>
                    </div>
                </div>
            </a-col>
        </a-row>

        <a-card :body-style="{ padding: '0 8px 8px' }" class="cal-card">
            <a-calendar v-model:value="mesActual" :fullscreen="true">
                <template #headerRender><span /></template>
                <template #dateFullCellRender="{ current }">
                    <div
                        class="cel"
                        :class="{ 'cel--hoy': current.format('YYYY-MM-DD') === hoy, 'cel--otro': current.month() !== mesActual.month() }"
                    >
                        <span class="cel__n">{{ current.date() }}</span>
                        <ul class="eventos">
                            <li
                                v-for="ev in (eventosPorDia[current.format('YYYY-MM-DD')] || [])"
                                :key="ev.tipo_evento + ev.id"
                                class="evento"
                                :style="{ '--c': ev.color || colorCategoria(ev.categoria) }"
                                @click.stop="abrirEvento(ev)"
                            >
                                <component :is="ev.tipo_evento === 'orden' ? ToolOutlined : CalendarOutlined" />
                                <span class="evento__t">{{ ev.titulo }}</span>
                            </li>
                        </ul>
                    </div>
                </template>
            </a-calendar>
        </a-card>
    </AppLayout>
</template>

<style scoped>
.filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}
.filtros :deep(.ant-select) {
    min-width: 140px;
}

.ctarj {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 14px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    box-shadow: var(--sigam-sombra-sm);
    position: relative;
    overflow: hidden;
}
.ctarj::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}
.ctarj__ic {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--acc);
    background: color-mix(in srgb, var(--acc) 13%, #fff);
    font-size: 15px;
}
.ctarj__v {
    font-size: 18px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1;
}
.ctarj__e {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
}

.cal-card :deep(.ant-picker-calendar-date) {
    display: none;
}
.cel {
    min-height: 92px;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 9px;
    padding: 5px 6px;
    margin: 2px;
    transition: background 0.12s ease;
}
.cel:hover {
    background: #fafbfd;
}
.cel--otro {
    opacity: 0.4;
}
.cel--hoy {
    border-color: var(--sigam-teal);
    background: var(--sigam-teal-050);
}
.cel__n {
    font-size: 12px;
    font-weight: 700;
    color: var(--sigam-tenue);
}
.cel--hoy .cel__n {
    color: var(--sigam-teal-700);
}
.eventos {
    list-style: none;
    margin: 4px 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.evento {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 10.5px;
    line-height: 1.3;
    padding: 2px 5px;
    border-left: 3px solid var(--c);
    background: color-mix(in srgb, var(--c) 12%, #fff);
    color: color-mix(in srgb, var(--c) 75%, #1c2b3a);
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}
.evento .anticon {
    font-size: 9px;
    flex: none;
}
.evento__t {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.evento:hover {
    filter: brightness(0.96);
}
</style>
