<script setup>
import { computed, ref, watch } from 'vue';
import { EnvironmentOutlined, ToolOutlined } from '@ant-design/icons-vue';

/**
 * Selector "Equipo o Instalación" — una orden/solicitud/plan de mantenimiento
 * apunta a uno de los dos, nunca a ambos. Centraliza en un solo componente
 * el toggle + los dos `<a-select>` que antes se repetían sueltos en cada
 * formulario, para que el patrón sea siempre el mismo en todo el sistema.
 */
const props = defineProps({
    equipoId: { type: [String, Number], default: undefined },
    ubicacionId: { type: [String, Number], default: undefined },
    equipos: { type: Array, default: () => [] },
    ubicaciones: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:equipoId', 'update:ubicacionId']);

// Ref propio (no derivado solo de ubicacionId): si fuera un computed sobre
// ubicacionId, al limpiar equipoId para cambiar a "instalación" el getter
// regresaba a "equipo" de inmediato (ubicacionId seguía vacío hasta elegir
// una), y el radio nunca lograba quedarse en "Instalación".
const tipo = ref(props.ubicacionId ? 'ubicacion' : 'equipo');

watch(tipo, (v) => {
    if (v === 'equipo') emit('update:ubicacionId', undefined);
    else emit('update:equipoId', undefined);
});

const opcionesEquipo = computed(() =>
    props.equipos.map((e) => ({ value: e.id, label: `${e.codigo_activo} · ${e.descripcion}` })),
);
const opcionesUbicacion = computed(() =>
    props.ubicaciones.map((u) => ({ value: u.id, label: `${'— '.repeat(u.profundidad ?? 0)}${u.nombre}` })),
);
</script>

<template>
    <div class="som">
        <a-radio-group v-model:value="tipo" button-style="solid" :disabled="disabled" class="som__tipo">
            <a-radio-button value="equipo"><ToolOutlined /> Equipo</a-radio-button>
            <a-radio-button value="ubicacion"><EnvironmentOutlined /> Instalación</a-radio-button>
        </a-radio-group>

        <a-select
            v-if="tipo === 'equipo'"
            :value="equipoId"
            :options="opcionesEquipo"
            placeholder="Selecciona el equipo"
            :disabled="disabled"
            class="som__select"
            @update:value="(v) => emit('update:equipoId', v)"
        />
        <a-select
            v-else
            :value="ubicacionId"
            :options="opcionesUbicacion"
            placeholder="Selecciona la instalación"
            :disabled="disabled"
            class="som__select"
            @update:value="(v) => emit('update:ubicacionId', v)"
        />
    </div>
</template>

<style scoped>
.som {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.som__tipo {
    align-self: flex-start;
}
.som__select {
    width: 100%;
}
</style>
