<script setup>
import { computed, h } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertOutlined,
    BellOutlined,
    CalendarOutlined,
    CheckOutlined,
    ClockCircleOutlined,
    SafetyCertificateOutlined,
    ToolOutlined,
    UserAddOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';

const props = defineProps({
    notificaciones: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    noLeidas: { type: Number, default: 0 },
    tipos: { type: Array, default: () => [] },
});

const META = {
    mantenimiento_proximo: { label: 'Preventivo próximo', icon: CalendarOutlined, color: '#1e5eb8' },
    mantenimiento_vencido: { label: 'Preventivo vencido', icon: ClockCircleOutlined, color: '#d32f2f' },
    urgencia: { label: 'Urgencia', icon: AlertOutlined, color: '#d32f2f' },
    asignacion: { label: 'Asignación', icon: UserAddOutlined, color: '#0b6b60' },
    trabajo_terminado: { label: 'Trabajo terminado', icon: CheckOutlined, color: '#2e7d32' },
    pendiente_supervision: { label: 'Pendiente de supervisión', icon: ToolOutlined, color: '#ed6c02' },
    garantia_por_vencer: { label: 'Garantía por vencer', icon: SafetyCertificateOutlined, color: '#ed6c02' },
};
const meta = (t) => META[t] ?? { label: t, icon: BellOutlined, color: '#64748b' };

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

const fechaRel = (v) => {
    if (!v) return '';
    const d = new Date(v);
    const min = Math.round((Date.now() - d) / 60000);
    if (min < 1) return 'ahora';
    if (min < 60) return `hace ${min} min`;
    if (min < 1440) return `hace ${Math.round(min / 60)} h`;
    return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' });
};

const columns = [{ key: 'n', title: 'Notificación' }];
</script>

<template>
    <Head title="Notificaciones" />

    <AppLayout
        titulo="Notificaciones"
        descripcion="Avisos de asignaciones, urgencias y mantenimientos próximos o vencidos."
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

        <DataTableInertia :paginador="notificaciones" :columns="columns" class="tabla-notis" @cambio="cambiarPagina">
            <template #bodyCell="{ record }">
                <div class="noti" :class="{ 'noti--leida': record.leida }" @click="abrir(record)">
                    <span class="noti__ic" :style="{ background: meta(record.tipo).color + '1a', color: meta(record.tipo).color }">
                        <component :is="meta(record.tipo).icon" />
                    </span>
                    <div class="noti__cuerpo">
                        <div class="noti__top">
                            <span class="noti__titulo">{{ record.titulo }}</span>
                            <a-tag :bordered="false">{{ meta(record.tipo).label }}</a-tag>
                            <span class="noti__fecha">{{ fechaRel(record.created_at) }}</span>
                        </div>
                        <div v-if="record.cuerpo" class="noti__texto">{{ record.cuerpo }}</div>
                    </div>
                    <span v-if="!record.leida" class="noti__punto" />
                </div>
            </template>
        </DataTableInertia>
    </AppLayout>
</template>

<style scoped>
.noti {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 4px 2px;
    cursor: pointer;
}
.noti--leida { opacity: 0.62; }
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
.noti__titulo { font-weight: 600; color: #0f172a; }
.noti__fecha { font-size: 12px; color: #94a3b8; margin-left: auto; }
.noti__texto { font-size: 13px; color: #64748b; margin-top: 2px; }
.noti__punto {
    flex: none;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #1e5eb8;
    margin-top: 15px;
}
</style>
