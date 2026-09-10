<script setup>
/**
 * Lista de datos con icono de color por fila — patrón de ficha unificado.
 * `datos`: [{ icono, label, valor, color }]  (valor null/'' → "No especificado")
 * Slot con nombre = `f.slot` para contenido rico (tags, enlaces, etc.).
 */
defineProps({
    datos: { type: Array, default: () => [] },
    compacto: { type: Boolean, default: false },
});
</script>

<template>
    <ul class="ld" :class="{ 'ld--compacto': compacto }">
        <li v-for="f in datos" :key="f.label">
            <span class="ld__ic" :style="{ color: f.color || 'var(--sigam-navy)', background: (f.color || '#173a5f') + '18' }">
                <component :is="f.icono" />
            </span>
            <span class="ld__t">
                <span class="ld__l">{{ f.label }}</span>
                <span class="ld__v">
                    <slot :name="f.slot || '_'" :fila="f">{{ f.valor || 'No especificado' }}</slot>
                </span>
            </span>
        </li>
    </ul>
</template>

<style scoped>
.ld {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
}
.ld li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.ld--compacto li {
    padding: 7px 0;
    gap: 10px;
}
.ld li:last-child {
    border-bottom: none;
}
.ld__ic {
    width: 32px;
    height: 32px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.ld__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}
.ld__l {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
}
.ld__v {
    font-size: 13.5px;
    color: var(--sigam-texto);
    font-weight: 500;
    word-break: break-word;
}
</style>
