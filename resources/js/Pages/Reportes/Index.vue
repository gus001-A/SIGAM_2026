<script setup>
import { Head, router } from '@inertiajs/vue3';
import {
    AppstoreOutlined,
    BarChartOutlined,
    DollarOutlined,
    RightOutlined,
    SafetyCertificateOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    grupos: { type: Array, default: () => [] },
});

const ESTILO_GRUPO = {
    Inventario: { icon: AppstoreOutlined, color: '#0d84c9' },
    Mantenimiento: { icon: ToolOutlined, color: '#1f9e86' },
    'Desempeño y costos': { icon: DollarOutlined, color: '#e08a1e' },
    Control: { icon: SafetyCertificateOutlined, color: '#6b4bc9' },
};
const estilo = (g) => ESTILO_GRUPO[g] ?? { icon: BarChartOutlined, color: '#173a5f' };

const abrir = (clave) => router.visit(route('reportes.generar', clave));
</script>

<template>
    <Head title="Reportes" />

    <AppLayout
        titulo="Reportes"
        descripcion="Indicadores y listados de inventario y mantenimiento, con exportación a Excel y PDF."
    >
        <div v-for="g in grupos" :key="g.grupo" class="grupo">
            <div class="grupo__titulo" :style="{ '--acc': estilo(g.grupo).color }">
                <span class="grupo__ic"><component :is="estilo(g.grupo).icon" /></span>
                {{ g.grupo }}
            </div>
            <a-row :gutter="[16, 16]">
                <a-col v-for="r in g.reportes" :key="r.clave" :xs="24" :sm="12" :lg="8">
                    <div class="rpt" :style="{ '--acc': estilo(g.grupo).color }" @click="abrir(r.clave)">
                        <div class="rpt__ic"><component :is="estilo(g.grupo).icon" /></div>
                        <div class="rpt__body">
                            <div class="rpt__nombre">{{ r.nombre }}</div>
                        </div>
                        <RightOutlined class="rpt__arrow" />
                    </div>
                </a-col>
            </a-row>
        </div>
    </AppLayout>
</template>

<style scoped>
.grupo {
    margin-bottom: 26px;
}
.grupo__titulo {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sigam-navy);
    margin-bottom: 13px;
}
.grupo__ic {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 13px;
    background: var(--acc);
}
.rpt {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 15px 16px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    height: 100%;
    position: relative;
    overflow: hidden;
}
.rpt::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 3px;
    background: var(--acc);
    opacity: 0;
    transition: opacity 0.15s;
}
.rpt:hover {
    border-color: var(--acc);
    box-shadow: 0 10px 26px -14px color-mix(in srgb, var(--acc) 55%, transparent);
    transform: translateY(-2px);
}
.rpt:hover::before {
    opacity: 1;
}
.rpt__ic {
    width: 42px;
    height: 42px;
    flex: none;
    border-radius: 11px;
    background: color-mix(in srgb, var(--acc) 12%, #fff);
    color: var(--acc);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.rpt__body {
    flex: 1;
    min-width: 0;
}
.rpt__nombre {
    font-weight: 600;
    font-size: 13.5px;
    color: var(--sigam-texto);
    line-height: 1.35;
}
.rpt__arrow {
    color: #cbd5e1;
    flex: none;
    transition: color 0.15s, transform 0.15s;
}
.rpt:hover .rpt__arrow {
    color: var(--acc);
    transform: translateX(2px);
}
</style>
