<script setup>
import { Head, router } from '@inertiajs/vue3';
import {
    AppstoreOutlined,
    ArrowRightOutlined,
    BarChartOutlined,
    DollarOutlined,
    FileTextOutlined,
    SafetyCertificateOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    grupos: { type: Array, default: () => [] },
});

/* ==========================================================
   Estilo por grupo: color principal, secundario, soft, ícono
   ========================================================== */
const ESTILO_GRUPO = {
    Inventario: {
        icon: AppstoreOutlined,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
    },
    Mantenimiento: {
        icon: ToolOutlined,
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e4f4ec',
    },
    'Desempeño y costos': {
        icon: DollarOutlined,
        color: '#e08a1e',
        color2: '#a86717',
        soft: '#fdf3e6',
    },
    Control: {
        icon: SafetyCertificateOutlined,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
    },
};

const estilo = (g) =>
    ESTILO_GRUPO[g] ?? {
        icon: BarChartOutlined,
        color: '#173a5f',
        color2: '#0f2d50',
        soft: '#eef4fb',
    };

const abrir = (clave) => router.visit(route('reportes.generar', clave));
</script>

<template>
    <Head title="Reportes" />

    <AppLayout
        titulo="Reportes"
        descripcion="Indicadores y listados de inventario y mantenimiento, con exportación a Excel y PDF."
    >
        <!-- ==========================================================
             Grupos de reportes
             ========================================================== -->
        <div
            v-for="g in grupos"
            :key="g.grupo"
            class="grupo"
            :style="{
                '--acc': estilo(g.grupo).color,
                '--acc2': estilo(g.grupo).color2,
                '--soft': estilo(g.grupo).soft,
            }"
        >
            <!-- Header del grupo -->
            <div class="grupo__head">
                <div class="grupo__ic">
                    <component :is="estilo(g.grupo).icon" />
                </div>
                <div class="grupo__meta">
                    <span class="grupo__titulo">{{ g.grupo }}</span>
                    <span class="grupo__sub">
                        {{ g.reportes?.length ?? 0 }}
                        reporte{{ (g.reportes?.length ?? 0) === 1 ? '' : 's' }}
                        disponible{{ (g.reportes?.length ?? 0) === 1 ? '' : 's' }}
                    </span>
                </div>
                <span class="grupo__badge">
                    {{ g.reportes?.length ?? 0 }}
                </span>
            </div>

            <!-- Grid de reportes -->
            <div class="rpt-grid">
                <button
                    v-for="r in g.reportes"
                    :key="r.clave"
                    type="button"
                    class="rpt"
                    @click="abrir(r.clave)"
                >
                    <div class="rpt__glow"></div>

                    <div class="rpt__ic">
                        <component :is="estilo(g.grupo).icon" />
                    </div>

                    <div class="rpt__body">
                        <div class="rpt__nombre">{{ r.nombre }}</div>
                        <div class="rpt__meta">
                            <span class="rpt__chip">
                                <FileTextOutlined /> Ver reporte
                            </span>
                        </div>
                    </div>

                    <div class="rpt__arrow">
                        <ArrowRightOutlined />
                    </div>
                </button>
            </div>
        </div>

        <!-- Vacío -->
        <div v-if="!grupos.length" class="vacio-box">
            <BarChartOutlined />
            <span>No hay reportes disponibles por ahora.</span>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Grupo
   ========================================================== */
.grupo {
    margin-bottom: 22px;
    padding: 14px;
    border-radius: 16px;
    background: linear-gradient(180deg,
            color-mix(in srgb, var(--soft) 45%, #fff) 0%,
            #ffffff 100%);
    border: 1px solid color-mix(in srgb, var(--acc) 18%, #e2e8f0);
}

.grupo__head {
    display: flex;
    align-items: center;
    gap: 11px;
    margin-bottom: 12px;
    padding: 6px 4px;
}

.grupo__ic {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    background: linear-gradient(135deg, var(--acc) 0%, var(--acc2) 100%);
    box-shadow: 0 4px 10px -4px color-mix(in srgb, var(--acc) 60%, transparent);
}

.grupo__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    flex: 1;
}

.grupo__titulo {
    font-size: 13.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sigam-navy);
    line-height: 1.2;
}

.grupo__sub {
    font-size: 11px;
    color: var(--sigam-tenue);
    font-weight: 500;
}

.grupo__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 22px;
    padding: 0 9px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--acc) 12%, #fff);
    color: color-mix(in srgb, var(--acc2) 90%, #173a5f);
    font-size: 11.5px;
    font-weight: 800;
    border: 1px solid color-mix(in srgb, var(--acc) 25%, transparent);
}

/* ==========================================================
   Grid de reportes
   ========================================================== */
.rpt-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

@media (max-width: 991px) {
    .rpt-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575px) {
    .rpt-grid {
        grid-template-columns: 1fr;
    }
}

/* ==========================================================
   Tarjeta de reporte
   ========================================================== */
.rpt {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    height: 100%;
    overflow: hidden;
    transition: border-color 0.16s ease, box-shadow 0.16s ease, transform 0.16s ease;
}

.rpt::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 3px;
    background: linear-gradient(180deg, var(--acc) 0%, var(--acc2) 100%);
    opacity: 0;
    transition: opacity 0.16s ease;
}

.rpt__glow {
    position: absolute;
    right: -40px;
    top: -40px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: radial-gradient(circle, color-mix(in srgb, var(--acc) 20%, transparent) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

.rpt:hover {
    border-color: var(--acc);
    box-shadow: 0 10px 24px -14px color-mix(in srgb, var(--acc) 60%, transparent);
    transform: translateY(-2px);
}

.rpt:hover::before {
    opacity: 1;
}

.rpt:hover .rpt__glow {
    opacity: 1;
}

.rpt:hover .rpt__arrow {
    color: var(--acc);
    transform: translateX(3px);
}

.rpt:hover .rpt__nombre {
    color: color-mix(in srgb, var(--acc2) 90%, #173a5f);
}

/* Ícono */
.rpt__ic {
    width: 40px;
    height: 40px;
    flex: none;
    border-radius: 11px;
    background: linear-gradient(135deg,
            color-mix(in srgb, var(--acc) 14%, #fff) 0%,
            color-mix(in srgb, var(--acc) 6%, #fff) 100%);
    color: var(--acc);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    border: 1px solid color-mix(in srgb, var(--acc) 25%, transparent);
    transition: background 0.16s ease, color 0.16s ease;
}

.rpt:hover .rpt__ic {
    color: #fff;
    background: linear-gradient(135deg, var(--acc) 0%, var(--acc2) 100%);
    box-shadow: 0 4px 10px -3px color-mix(in srgb, var(--acc) 55%, transparent);
}

/* Cuerpo */
.rpt__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.rpt__nombre {
    font-weight: 700;
    font-size: 13px;
    color: var(--sigam-texto);
    line-height: 1.3;
    transition: color 0.16s ease;
}

.rpt__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.rpt__chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--acc) 10%, #fff);
    color: color-mix(in srgb, var(--acc2) 85%, #173a5f);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    border: 1px solid color-mix(in srgb, var(--acc) 20%, transparent);
}

.rpt__chip .anticon {
    font-size: 10px;
}

/* Flecha */
.rpt__arrow {
    flex: none;
    color: #cbd5e1;
    font-size: 13px;
    transition: color 0.16s ease, transform 0.16s ease;
}

/* ==========================================================
   Vacío
   ========================================================== */
.vacio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 30px 16px;
    border-radius: 12px;
    border: 1px dashed var(--sigam-borde);
    background: var(--sigam-navy-050);
    color: var(--sigam-tenue);
    font-size: 12.5px;
}

.vacio-box .anticon {
    font-size: 22px;
    opacity: 0.5;
}
</style>