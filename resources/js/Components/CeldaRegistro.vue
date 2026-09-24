<script setup>
/**
 * Celda "Registrado por" reutilizable en las tablas: usuario + fecha/hora de
 * alta, en una sola consulta masiva desde el controller (Auditoria::creadoPorMasivo)
 * — buenas prácticas: quién dio de alta cada cosa, a la vista, sin abrir el registro.
 */
defineProps({
    usuario: { type: String, default: null },
    fecha: { type: String, default: null },
});

const formatear = (v) =>
    v
        ? new Date(v).toLocaleString('es-MX', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
        : null;
</script>

<template>
    <div class="cr">
        <span class="cr__u">{{ usuario ?? 'Sin registro' }}</span>
        <span v-if="fecha" class="cr__f">{{ formatear(fecha) }}</span>
    </div>
</template>

<style scoped>
.cr {
    display: flex;
    flex-direction: column;
    gap: 1px;
    line-height: 1.3;
}
.cr__u {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--sigam-texto);
}
.cr__f {
    font-size: 11px;
    color: var(--sigam-tenue);
}
</style>
