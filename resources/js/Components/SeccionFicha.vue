<script setup>
/** Bloque de ficha con título e icono — estilo unificado para los "detalles". */
defineProps({
    titulo: { type: String, required: true },
    icono: { type: [Object, Function], default: null },
    color: { type: String, default: 'var(--sigam-teal)' },
});
</script>

<template>
    <section class="sf">
        <h3 class="sf__t" :style="{ '--acc': color }">
            <component :is="icono" v-if="icono" class="sf__ic" />
            {{ titulo }}
            <span class="sf__extra"><slot name="extra" /></span>
        </h3>
        <div class="sf__body">
            <slot />
        </div>
    </section>
</template>

<style scoped>
.sf {
    margin-bottom: 20px;
}
.sf:last-child {
    margin-bottom: 0;
}
.sf__t {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    row-gap: 4px;
    gap: 8px;
    margin: 0 0 10px;
    font-weight: 800;
    font-size: 13px;
    color: var(--sigam-navy);
    padding-left: 10px;
    border-left: 3px solid var(--acc);
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.sf__ic {
    color: var(--acc);
    font-size: 14px;
}
.sf__extra {
    margin-left: auto;
    text-transform: none;
    letter-spacing: 0;
}
.sf__body {
    animation: sigam-fade-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
