<script setup>
import { computed } from 'vue';
import { CalendarOutlined, ClockCircleOutlined } from '@ant-design/icons-vue';

/**
 * Fecha y hora en dos campos separados. `modelValue` = "YYYY-MM-DDTHH:mm"
 * (o "YYYY-MM-DD HH:mm:ss"). Si solo hay fecha, la hora se asume 00:00.
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    soloFecha: { type: Boolean, default: false },
    maxFecha: { type: String, default: undefined },
    minFecha: { type: String, default: undefined },
});
const emit = defineEmits(['update:modelValue']);

const partes = computed(() => {
    const v = (props.modelValue || '').replace(' ', 'T');
    const [f = '', h = ''] = v.split('T');
    return { f, h: h.slice(0, 5) };
});

const emitir = (f, h) => {
    if (!f) return emit('update:modelValue', '');
    if (props.soloFecha) return emit('update:modelValue', f);
    emit('update:modelValue', `${f}T${h || '00:00'}`);
};

const setFecha = (e) => emitir(e.target.value, partes.value.h);
const setHora = (e) => emitir(partes.value.f, e.target.value);
</script>

<template>
    <div class="cfh" :class="{ 'cfh--solo': soloFecha }">
        <div class="cfh__campo">
            <CalendarOutlined class="cfh__ic" />
            <input
                type="date"
                class="cfh__input"
                :value="partes.f"
                :max="maxFecha"
                :min="minFecha"
                @input="setFecha"
            />
        </div>
        <div v-if="!soloFecha" class="cfh__campo cfh__campo--hora">
            <ClockCircleOutlined class="cfh__ic" />
            <input type="time" class="cfh__input" :value="partes.h" @input="setHora" />
        </div>
    </div>
</template>

<style scoped>
.cfh {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 10px;
}
.cfh--solo {
    grid-template-columns: 1fr;
}
.cfh__campo {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 11px;
    height: 36px;
    border: 1px solid var(--sigam-borde);
    border-radius: 9px;
    background: #fff;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.cfh__campo:focus-within {
    border-color: var(--sigam-navy);
    box-shadow: 0 0 0 3px rgba(31, 158, 134, 0.16);
}
.cfh__ic {
    color: var(--sigam-teal);
    font-size: 14px;
    flex: none;
}
.cfh__input {
    border: 0;
    outline: 0;
    background: transparent;
    width: 100%;
    font-size: 13px;
    color: var(--sigam-texto);
    font-family: inherit;
}
.cfh__input::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.55;
}
@media (max-width: 480px) {
    .cfh {
        grid-template-columns: 1fr;
    }
}
</style>
