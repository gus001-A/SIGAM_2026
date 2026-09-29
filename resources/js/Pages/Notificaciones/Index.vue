<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BellOutlined, CheckOutlined, InboxOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import { fechaRelativa, metaNotificacion } from '@/utils/notificaciones';

const props = defineProps({
    notificaciones: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    noLeidas: { type: Number, default: 0 },
    tipos: { type: Array, default: () => [] },
});

const meta = metaNotificacion;

const tarjetas = computed(() => [
    { label: 'Total', valor: props.notificaciones.total ?? 0, icono: BellOutlined, color: '#0d84c9' },
    { label: 'No leídas', valor: props.noLeidas ?? 0, icono: InboxOutlined, color: '#d64545' },
]);

const ver = computed(() => props.filtros.ver || 'todas');

const navegar = (params) => {
    const limpio = {};
    for (const [k, v] of Object.entries({ ...props.filtros, ...params })) {
        if (v && v !== 'todas') limpio[k] = v;
    }
    router.get(route('notificaciones.index'), limpio, { preserveScroll: true, preserveState: true });
};

const cambiarPagina = (pag) => navegar({ page: pag?.current ?? 1 });

const abrir = (n) => {
    if (!n.leida) {
        router.put(route('notificaciones.leida', n.id), {}, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => n.url && router.visit(n.url),
        });
    } else if (n.url) {
        router.visit(n.url);
    }
};

const marcarTodas = () => router.put(route('notificaciones.leer_todas'), {}, { preserveScroll: true });

const columns = [{ key: 'n', title: 'Notificación' }];
</script>

<template>
    <Head title="Notificaciones" />

    <AppLayout
        titulo="Notificaciones"
        descripcion="Avisos de tareas, órdenes, planes preventivos y solicitudes según tu rol."
    >
        <template #acciones>
            <a-radio-group :value="ver" button-style="solid" @change="(e) => navegar({ ver: e.target.value, page: 1 })">
                <a-radio-button value="todas">Todas</a-radio-button>
                <a-radio-button value="no_leidas">No leídas <a-badge v-if="noLeidas" :count="noLeidas" :offset="[6, -2]" /></a-radio-button>
                <a-radio-button value="leidas">Leídas</a-radio-button>
            </a-radio-group>
            <a-select
                :value="filtros.tipo || undefined"
                :options="tipos.map((t) => ({ label: meta(t).label, value: t }))"
                allow-clear
                placeholder="Todos los tipos"
                style="min-width: 190px"
                @change="(v) => navegar({ tipo: v, page: 1 })"
            />
            <a-button v-if="noLeidas" @click="marcarTodas">
                <template #icon><CheckOutlined /></template>
                Marcar todas como leídas
            </a-button>
        </template>

        <div class="kpis">
            <div v-for="k in tarjetas" :key="k.label" class="kpi" :style="{ '--acc': k.color }">
                <div class="kpi__icono">
                    <component :is="k.icono" />
                </div>
                <div class="kpi__txt">
                    <div class="kpi__valor">{{ k.valor }}</div>
                    <div class="kpi__etq">{{ k.label }}</div>
                </div>
            </div>
        </div>

        <DataTableInertia :paginador="notificaciones" :columns="columns" class="tabla-notis" @cambio="cambiarPagina">
            <template #bodyCell="{ record }">
                <div class="noti" :class="{ 'noti--leida': record.leida }" :style="{ '--c': meta(record.tipo).color }"
                    @click="abrir(record)">
                    <span class="noti__ic" :style="{ background: meta(record.tipo).color + '1a', color: meta(record.tipo).color }">
                        <component :is="meta(record.tipo).icon" />
                    </span>
                    <div class="noti__cuerpo">
                        <div class="noti__top">
                            <span class="noti__titulo">{{ record.titulo }}</span>
                            <span class="noti__chip" :style="{ background: meta(record.tipo).color + '1a', color: meta(record.tipo).color }">
                                {{ meta(record.tipo).label }}
                            </span>
                            <span class="noti__fecha">{{ fechaRelativa(record.created_at) }}</span>
                        </div>
                        <div v-if="record.cuerpo" class="noti__texto">{{ record.cuerpo }}</div>
                    </div>
                    <span v-if="!record.leida" class="noti__punto" />
                </div>
            </template>

            <template #emptyText>
                <a-empty description="Sin notificaciones" />
            </template>
        </DataTableInertia>
    </AppLayout>
</template>

<style scoped>
/* ---------- KPIs ---------- */
.kpis {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 220px));
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
.kpi__valor { font-size: 18px; font-weight: 800; color: var(--sigam-navy); line-height: 1.1; }
.kpi__etq {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    white-space: nowrap;
}

/* ---------- Fila de notificación ---------- */
.noti {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 12px 14px;
    margin: 4px 0;
    border-radius: 12px;
    border-left: 3px solid var(--c);
    background: color-mix(in srgb, var(--c) 4%, #fff);
    cursor: pointer;
    transition: box-shadow 0.14s ease, transform 0.14s ease;
}
.noti:hover { box-shadow: 0 3px 10px rgba(15, 37, 71, 0.08); transform: translateY(-1px); }
.noti--leida { opacity: 0.6; }
.noti__ic {
    flex: none;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}
.noti__cuerpo { flex: 1; min-width: 0; }
.noti__top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.noti__chip {
    font-size: 10.5px;
    font-weight: 700;
    padding: 1px 9px;
    border-radius: 999px;
}
.noti__titulo { font-weight: 600; color: #0f172a; }
.noti__fecha { font-size: 12px; color: #94a3b8; margin-left: auto; }
.noti__texto { font-size: 13px; color: #64748b; margin-top: 2px; }
.noti__punto {
    flex: none;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--sigam-teal);
    margin-top: 15px;
}
</style>
