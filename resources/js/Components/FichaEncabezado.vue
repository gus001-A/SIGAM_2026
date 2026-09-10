<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeftOutlined } from '@ant-design/icons-vue';

/**
 * Encabezado reutilizable para las páginas de ficha (Show).
 * Botón volver + avatar/ícono + título + etiquetas (slot) + acciones (slot).
 */
const props = defineProps({
    titulo: { type: String, required: true },
    subtitulo: { type: String, default: '' },
    volver: { type: String, default: '' },
    // Componente de ícono de @ant-design/icons-vue.
    icono: { type: [Object, Function], default: null },
    // Alternativa al ícono: iniciales para el avatar.
    iniciales: { type: String, default: '' },
    // Color de acento del avatar/ícono.
    color: { type: String, default: '#1e5eb8' },
});

const atenuar = computed(() => `${props.color}1a`);
</script>

<template>
    <div class="fe">
        <button v-if="volver" type="button" class="fe__volver" @click="router.visit(route(volver))">
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
.fe__acciones {
    flex: none;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
</style>
