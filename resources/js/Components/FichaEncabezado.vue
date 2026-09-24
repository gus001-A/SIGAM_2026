<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeftOutlined, HistoryOutlined } from '@ant-design/icons-vue';

/**
 * Encabezado reutilizable para las páginas de ficha (Show).
 * Botón volver + avatar/ícono + título + etiquetas (slot) + acciones (slot).
 */
const props = defineProps({
    titulo: { type: String, required: true },
    subtitulo: { type: String, default: '' },
    volver: { type: String, default: '' },
    // Parámetros opcionales para la ruta de "volver" (p. ej. { sucursal_id }).
    volverParams: { type: Object, default: () => ({}) },
    // Componente de ícono de @ant-design/icons-vue.
    icono: { type: [Object, Function], default: null },
    // Alternativa al ícono: iniciales para el avatar.
    iniciales: { type: String, default: '' },
    // Color de acento del avatar/ícono.
    color: { type: String, default: '#1e5eb8' },
    // Trazabilidad: { creado_por, creado_en, modificado_por, modificado_en } — de EsAuditable::selloAuditoria().
    sello: { type: Object, default: null },
});

const atenuar = computed(() => `${props.color}1a`);

const fechaHora = (v) =>
    v
        ? new Date(v).toLocaleString('es-MX', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
        : '';

const textoSello = computed(() => {
    if (!props.sello) return '';
    const partes = [];
    if (props.sello.creado_por) partes.push(`Creado por ${props.sello.creado_por} · ${fechaHora(props.sello.creado_en)}`);
    if (props.sello.modificado_por) partes.push(`Última edición: ${props.sello.modificado_por} · ${fechaHora(props.sello.modificado_en)}`);
    return partes.join('  ·  ');
});
</script>

<template>
    <div class="fe">
        <button v-if="volver" type="button" class="fe__volver" @click="router.visit(route(volver, volverParams))">
            <ArrowLeftOutlined />
        </button>

        <span
            v-if="icono || iniciales"
            class="fe__avatar"
            :style="{ background: atenuar, color }"
        >
            <component :is="icono" v-if="icono" />
            <template v-else>{{ iniciales }}</template>
        </span>

        <div class="fe__texto">
            <div class="fe__titulo-fila">
                <h1 class="fe__titulo">{{ titulo }}</h1>
                <slot name="tags" />
            </div>
            <div v-if="subtitulo || $slots.subtitulo" class="fe__sub">
                <slot name="subtitulo">{{ subtitulo }}</slot>
            </div>
            <div v-if="textoSello" class="fe__sello">
                <HistoryOutlined />
                <span>{{ textoSello }}</span>
            </div>
        </div>

        <div v-if="$slots.acciones" class="fe__acciones">
            <slot name="acciones" />
        </div>
    </div>
</template>

<style scoped>
.fe {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    padding: 14px 16px;
    margin-bottom: 16px;
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 62%);
    border: 1px solid var(--sigam-borde);
    border-radius: 15px;
    box-shadow: var(--sigam-sombra-sm);
    animation: sigam-fade-up 0.32s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.fe__volver {
    flex: none;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid var(--sigam-borde);
    background: #fff;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease,
        transform 0.12s ease;
}
.fe__volver:hover {
    background: var(--sigam-navy-050);
    color: var(--sigam-navy);
    border-color: var(--sigam-navy-100);
}
.fe__volver:active {
    transform: scale(0.94);
}
.fe__avatar {
    flex: none;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: 0.02em;
    box-shadow: 0 8px 18px -8px color-mix(in srgb, currentColor 60%, transparent);
}
.fe__texto {
    flex: 1;
    min-width: 0;
}
.fe__titulo-fila {
    display: flex;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
}
.fe__titulo {
    font-size: 22px;
    font-weight: 800;
    margin: 0;
    color: #0f172a;
    letter-spacing: -0.015em;
    line-height: 1.15;
}
.fe__sub {
    margin-top: 3px;
    font-size: 13px;
    color: var(--sigam-tenue);
}
.fe__sello {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    font-size: 11.5px;
    color: #94a3b8;
}
.fe__sello .anticon {
    font-size: 11px;
    flex: none;
}
.fe__sello span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fe__acciones {
    flex: none;
    max-width: 100%;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
</style>
