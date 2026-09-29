<script setup>
import { computed, ref, watch } from 'vue';
import { EnvironmentOutlined, ToolOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    equipoId: { type: [Number, String], default: undefined },
    ubicacionId: { type: [Number, String], default: undefined },
    equipos: { type: Array, default: () => [] },
    ubicaciones: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:equipoId', 'update:ubicacionId']);

/* ==========================================================
   Estado local del modo (para que el switch responda al clic
   inmediatamente, sin esperar a que el padre actualice las props)
   ========================================================== */
const modoLocal = ref(props.ubicacionId ? 'instalacion' : 'equipo');

// Sincroniza si el padre cambia las props desde fuera
watch(
    () => [props.equipoId, props.ubicacionId],
    ([eq, ub]) => {
        if (ub) modoLocal.value = 'instalacion';
        else if (eq) modoLocal.value = 'equipo';
    },
);

const modo = computed(() => modoLocal.value);

/* ==========================================================
   Opciones
   ========================================================== */
const opcionesEquipos = computed(() =>
    props.equipos.map((e) => ({
        value: e.id,
        label: `${e.codigo_activo} — ${e.descripcion}`,
    })),
);

const opcionesUbicaciones = computed(() =>
    props.ubicaciones.map((u) => ({
        value: u.id,
        label: u.nombre,
    })),
);

const valorActual = computed(() =>
    modo.value === 'equipo' ? props.equipoId : props.ubicacionId,
);

const opcionesActuales = computed(() =>
    modo.value === 'equipo' ? opcionesEquipos.value : opcionesUbicaciones.value,
);

const placeholderActual = computed(() =>
    modo.value === 'equipo' ? 'Selecciona un equipo' : 'Selecciona una instalación',
);

/* ==========================================================
   Handlers
   ========================================================== */
const onSelectChange = (v) => {
    if (modo.value === 'equipo') {
        emit('update:equipoId', v);
    } else {
        emit('update:ubicacionId', v);
    }
};

const cambiarModo = (nuevoModo) => {
    if (modoLocal.value === nuevoModo) return;
    modoLocal.value = nuevoModo;

    if (nuevoModo === 'equipo') {
        emit('update:ubicacionId', undefined);
    } else {
        emit('update:equipoId', undefined);
    }
};
</script>

<template>
    <div class="selector" :class="`selector--${modo}`">
        <div class="switch" role="tablist" aria-label="Tipo de objetivo">
            <!-- Slider -->
            <span class="switch__slider" aria-hidden="true"></span>

            <!-- Botón Equipo -->
            <button
                type="button"
                role="tab"
                class="switch__opt switch__opt--equipo"
                :class="{ 'switch__opt--active': modo === 'equipo' }"
                :aria-selected="modo === 'equipo'"
                @click="cambiarModo('equipo')"
            >
                <ToolOutlined />
                <span>Equipo</span>
            </button>

            <!-- Botón Instalación -->
            <button
                type="button"
                role="tab"
                class="switch__opt switch__opt--instalacion"
                :class="{ 'switch__opt--active': modo === 'instalacion' }"
                :aria-selected="modo === 'instalacion'"
                @click="cambiarModo('instalacion')"
            >
                <EnvironmentOutlined />
                <span>Instalación</span>
            </button>
        </div>

        <!-- Select -->
        <a-select
            :value="valorActual"
            :options="opcionesActuales"
            :placeholder="placeholderActual"
            show-search
            option-filter-prop="label"
            allow-clear
            size="large"
            class="selector__select"
            @change="onSelectChange"
        >
            <template #suffixIcon>
                <ToolOutlined v-if="modo === 'equipo'" />
                <EnvironmentOutlined v-else />
            </template>
        </a-select>
    </div>
</template>

<style scoped>
/* ==========================================================
   Contenedor
   ========================================================== */
.selector {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

/* ==========================================================
   Switch pill
   ========================================================== */
.switch {
    position: relative;
    display: inline-flex;
    flex-shrink: 0;
    padding: 3px;
    background: linear-gradient(180deg, #eef2f7 0%, #e6ecf3 100%);
    border: 1px solid #dbe3ec;
    border-radius: 999px;
    box-shadow: inset 0 1px 2px rgba(15, 45, 80, 0.08);
    user-select: none;
    transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

.selector--instalacion .switch {
    background: linear-gradient(180deg, #edf8f5 0%, #e4f4ec 100%);
    border-color: #cbe9dd;
    box-shadow: inset 0 1px 2px rgba(31, 158, 134, 0.12);
}

/* ==========================================================
   Slider — posicionado con `left` absoluto, NO con translateX
   ========================================================== */
.switch__slider {
    position: absolute;
    top: 3px;
    bottom: 3px;
    /* Ocupa exactamente la mitad menos el padding izquierdo */
    width: calc(50% - 3px);
    border-radius: 999px;
    background: linear-gradient(180deg, #ffffff, #f6f9fc);
    box-shadow: 0 2px 6px rgba(15, 45, 80, 0.12), 0 1px 2px rgba(15, 45, 80, 0.08);
    /* Posición inicial: pegado a la izquierda */
    left: 3px;
    /* Transición de `left` en lugar de transform (más predecible aquí) */
    transition: left 0.36s cubic-bezier(0.34, 1.4, 0.4, 1),
                background 0.3s ease,
                box-shadow 0.3s ease;
    z-index: 0;
    pointer-events: none;
}

/* Cuando es instalación, se mueve al 50% del contenedor */
.selector--instalacion .switch__slider {
    left: 50%;
    background: linear-gradient(180deg, #ffffff, #f4fbf8);
    box-shadow: 0 2px 8px rgba(31, 158, 134, 0.28), 0 1px 2px rgba(31, 158, 134, 0.15);
}

/* ==========================================================
   Botones
   ========================================================== */
.switch__opt {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 14px;
    min-width: 110px;
    border: 0;
    background: transparent;
    border-radius: 999px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 800;
    color: #64748b;
    cursor: pointer;
    white-space: nowrap;
    transition: color 0.28s ease, transform 0.2s ease;
}

.switch__opt .anticon {
    font-size: 13px;
    transition: transform 0.28s ease;
}

.switch__opt:hover {
    color: #0f2d50;
}

.switch__opt:hover .anticon {
    transform: scale(1.15);
}

.switch__opt--equipo.switch__opt--active {
    color: #0d84c9;
}

.switch__opt--instalacion.switch__opt--active {
    color: #16806c;
}

.switch__opt--active .anticon {
    transform: scale(1.08);
}

/* ==========================================================
   Select
   ========================================================== */
.selector__select {
    flex: 1;
    min-width: 0;
}

.selector__select :deep(.ant-select-selector) {
    border-radius: 11px !important;
    border-color: #e2e8f0 !important;
    transition: border-color 0.14s ease, box-shadow 0.14s ease;
}

.selector--equipo .selector__select :deep(.ant-select-focused .ant-select-selector),
.selector--equipo .selector__select :deep(.ant-select-selector:hover) {
    border-color: #0d84c9 !important;
    box-shadow: 0 0 0 2px rgba(13, 132, 201, 0.12) !important;
}

.selector--instalacion .selector__select :deep(.ant-select-focused .ant-select-selector),
.selector--instalacion .selector__select :deep(.ant-select-selector:hover) {
    border-color: #1f9e86 !important;
    box-shadow: 0 0 0 2px rgba(31, 158, 134, 0.14) !important;
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 575px) {
    .selector {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .switch {
        width: 100%;
        justify-content: center;
    }

    .switch__opt {
        flex: 1;
        min-width: 0;
    }

    .selector__select {
        width: 100%;
    }
}
</style>