<script setup>
import { computed, ref, watch } from 'vue';
import { CloseOutlined, PlusOutlined } from '@ant-design/icons-vue';

/**
 * Editor de pares clave/valor para el campo JSON `especificaciones` del equipo.
 * Especificación §5.8.
 */
const props = defineProps({
    modelValue: { type: [Object, Array, null], default: () => ({}) },
    label: { type: String, default: 'Especificaciones técnicas' },
});
const emit = defineEmits(['update:modelValue']);

const aFilas = (obj) =>
    Object.entries(obj ?? {}).map(([clave, valor]) => ({ clave, valor: String(valor ?? '') }));

const filas = ref([...aFilas(props.modelValue), { clave: '', valor: '' }]);

const objeto = computed(() =>
    filas.value.reduce((acc, f) => {
        if (f.clave.trim() !== '') acc[f.clave.trim()] = f.valor;
        return acc;
    }, {}),
);

watch(objeto, (v) => emit('update:modelValue', v), { deep: true });

const agregar = () => filas.value.push({ clave: '', valor: '' });
const quitar = (i) => {
    filas.value.splice(i, 1);
    if (filas.value.length === 0) filas.value.push({ clave: '', valor: '' });
};
</script>

<template>
    <div>
        <div class="text-sm opacity-70 mb-2">{{ label }}</div>

        <div v-for="(fila, i) in filas" :key="i" class="flex gap-2 mb-2">
            <a-input
                v-model:value="fila.clave"
                placeholder="Característica (p.ej. Voltaje)"
                style="max-width: 240px"
            />
            <a-input v-model:value="fila.valor" placeholder="Valor (p.ej. 220 V)" />
            <a-button type="text" @click="quitar(i)">
                <template #icon><CloseOutlined /></template>
            </a-button>
        </div>

        <a-button type="link" size="small" class="px-0" @click="agregar">
            <template #icon><PlusOutlined /></template>
            Agregar característica
        </a-button>
    </div>
</template>
