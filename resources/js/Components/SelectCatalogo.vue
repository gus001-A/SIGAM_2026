<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { message } from 'ant-design-vue';
import {
    CheckOutlined,
    DeleteOutlined,
    EditOutlined,
    PlusOutlined,
    SaveOutlined,
    StopOutlined,
} from '@ant-design/icons-vue';
import { usePermisos } from '@/composables/usePermisos';

/**
 * Select de un catálogo configurable + botón «+» para darlo de alta,
 * modificarlo o desactivarlo sin salir del formulario donde se está usando.
 *
 * Resuelve la queja de que si el concepto que necesitas no existe en el
 * catálogo, tenías que abandonar el formulario, ir a Administración › Catálogos,
 * crearlo y volver a empezar.
 */
const props = defineProps({
    value: { type: [String, Number], default: undefined },
    options: { type: Array, default: () => [] },
    fieldNames: { type: Object, default: () => ({ label: 'nombre', value: 'id' }) },
    // Prefijo de ruta Ziggy del catálogo, p. ej. "catalogos.tipos_equipo".
    ruta: { type: String, required: true },
    // Nombre legible en singular/plural para los textos del modal.
    etiqueta: { type: String, required: true },
    etiquetaPlural: { type: String, default: '' },
    // Campos extra además de "nombre" — mismo formato que <CatalogoTabla>.
    campos: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Selecciona una opción' },
    disabled: { type: Boolean, default: false },
    allowClear: { type: Boolean, default: true },
});

const emit = defineEmits(['update:value']);

// Distingue la alta rápida (JSON, sin abandonar el formulario) de una
// visita normal de Inertia a la página de administración del catálogo.
const ALTA_RAPIDA = { headers: { 'X-Alta-Rapida': '1' } };

const { puede } = usePermisos();
const puedeCrear = computed(() => puede('catalogos.crear'));
const puedeEditar = computed(() => puede('catalogos.editar'));
const puedeDesactivar = computed(() => puede('catalogos.desactivar'));
const puedeGestionar = computed(() => puedeCrear.value || puedeEditar.value || puedeDesactivar.value);

const plural = computed(() => props.etiquetaPlural || `${props.etiqueta}s`);
const clave = (r) => r[props.fieldNames.value];
const texto = (r) => r[props.fieldNames.label];

// Copia local editable — se sincroniza con el prop pero vive su propia vida
// mientras el modal está abierto (altas/bajas no deben recargar la página).
const lista = ref([...props.options]);
watch(
    () => props.options,
    (v) => {
        if (!abierto.value) lista.value = [...v];
    },
);

const opcionesSelect = computed(() =>
    lista.value.map((r) => ({ label: texto(r), value: clave(r) })),
);

const abierto = ref(false);
const guardando = ref(false);
const editandoId = ref(null);

const valoresVacios = () => {
    const v = { nombre: '' };
    props.campos.forEach((c) => (v[c.name] = c.tipo === 'switch' ? false : c.tipo === 'number' ? null : ''));
    return v;
};
const nuevo = reactive(valoresVacios());
const edicion = reactive(valoresVacios());

const abrir = () => {
    lista.value = [...props.options];
    Object.assign(nuevo, valoresVacios());
    editandoId.value = null;
    abierto.value = true;
};

const cerrar = () => {
    abierto.value = false;
    editandoId.value = null;
};

const agregar = async () => {
    if (!nuevo.nombre?.trim()) {
        message.error('Escribe un nombre.');
        return;
    }
    guardando.value = true;
    try {
        const { data } = await window.axios.post(route(`${props.ruta}.store`), { ...nuevo }, ALTA_RAPIDA);
        lista.value = [...lista.value, data];
        emit('update:value', clave(data));
        Object.assign(nuevo, valoresVacios());
        message.success(`«${texto(data)}» se agregó y se seleccionó.`);
    } catch (e) {
        const err = e.response?.data?.errors;
        message.error(err ? Object.values(err).flat()[0] : 'No se pudo guardar.');
    } finally {
        guardando.value = false;
    }
};

const iniciarEdicion = (registro) => {
    editandoId.value = clave(registro);
    Object.assign(edicion, valoresVacios());
    Object.keys(edicion).forEach((k) => (edicion[k] = registro[k] ?? edicion[k]));
};

const guardarEdicion = async (registro) => {
    guardando.value = true;
    try {
        const { data } = await window.axios.put(route(`${props.ruta}.update`, clave(registro)), { ...edicion }, ALTA_RAPIDA);
        lista.value = lista.value.map((r) => (clave(r) === clave(registro) ? data : r));
        editandoId.value = null;
        message.success('Cambios guardados.');
    } catch (e) {
        const err = e.response?.data?.errors;
        message.error(err ? Object.values(err).flat()[0] : 'No se pudo guardar.');
    } finally {
        guardando.value = false;
    }
};

const desactivar = async (registro) => {
    guardando.value = true;
    try {
        await window.axios.delete(route(`${props.ruta}.destroy`, clave(registro)), ALTA_RAPIDA);
        lista.value = lista.value.filter((r) => clave(r) !== clave(registro));
        if (props.value === clave(registro)) emit('update:value', undefined);
        message.success(`«${texto(registro)}» se desactivó.`);
    } catch (e) {
        message.error('No se pudo desactivar.');
    } finally {
        guardando.value = false;
    }
};
</script>

<template>
    <div class="sc">
        <a-select
            :value="value"
            :options="opcionesSelect"
            :placeholder="placeholder"
            :disabled="disabled"
            :allow-clear="allowClear"
            class="sc__select"
            @update:value="(v) => emit('update:value', v)"
        />
        <a-tooltip v-if="puedeGestionar" :title="`Agregar / gestionar ${plural}`">
            <button type="button" class="sc__mas" @click="abrir">
                <PlusOutlined />
            </button>
        </a-tooltip>

        <a-modal
            v-model:open="abierto"
            :title="`Gestionar ${plural}`"
            :footer="null"
            :width="480"
            wrap-class-name="sc-modal"
            @cancel="cerrar"
        >
            <div v-if="puedeCrear" class="sc-alta">
                <a-input
                    v-model:value="nuevo.nombre"
                    :placeholder="`Nombre de ${etiqueta} nuevo`"
                    @press-enter="agregar"
                />
                <template v-for="c in campos" :key="c.name">
                    <a-input-number
                        v-if="c.tipo === 'number'"
                        v-model:value="nuevo[c.name]"
                        :placeholder="c.label"
                        :min="c.min ?? 0"
                        :max="c.max"
                        class="sc-alta__campo"
                    />
                    <a-select
                        v-else-if="c.tipo === 'select'"
                        v-model:value="nuevo[c.name]"
                        :options="c.opciones"
                        :placeholder="c.label"
                        class="sc-alta__campo"
                    />
                    <div v-else-if="c.tipo === 'color'" class="sc-alta__color">
                        <input type="color" v-model="nuevo[c.name]" />
                        <a-input v-model:value="nuevo[c.name]" :placeholder="c.label" />
                    </div>
                    <label v-else-if="c.tipo === 'switch'" class="sc-alta__switch">
                        <a-switch v-model:checked="nuevo[c.name]" size="small" />
                        {{ c.label }}
                    </label>
                    <a-input v-else v-model:value="nuevo[c.name]" :placeholder="c.label" class="sc-alta__campo" />
                </template>
                <a-button type="primary" :loading="guardando" @click="agregar">
                    <template #icon><PlusOutlined /></template>
                    Agregar
                </a-button>
            </div>

            <div class="sc-lista">
                <div v-if="!lista.length" class="sc-lista__vacio">Sin opciones registradas todavía.</div>
                <div v-for="r in lista" :key="clave(r)" class="sc-fila">
                    <template v-if="editandoId === clave(r)">
                        <a-input v-model:value="edicion.nombre" size="small" class="sc-fila__input" @press-enter="guardarEdicion(r)" />
                        <template v-for="c in campos" :key="c.name">
                            <a-input-number
                                v-if="c.tipo === 'number'"
                                v-model:value="edicion[c.name]"
                                size="small"
                                :min="c.min ?? 0"
                                :max="c.max"
                                class="sc-fila__input"
                            />
                            <a-select
                                v-else-if="c.tipo === 'select'"
                                v-model:value="edicion[c.name]"
                                :options="c.opciones"
                                size="small"
                                class="sc-fila__input"
                            />
                            <label v-else-if="c.tipo === 'switch'" class="sc-alta__switch">
                                <a-switch v-model:checked="edicion[c.name]" size="small" />
                                {{ c.label }}
                            </label>
                            <a-input v-else v-model:value="edicion[c.name]" size="small" class="sc-fila__input" />
                        </template>
                        <a-tooltip title="Guardar">
                            <a-button type="text" size="small" :loading="guardando" @click="guardarEdicion(r)">
                                <template #icon><SaveOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip title="Cancelar">
                            <a-button type="text" size="small" @click="editandoId = null">
                                <template #icon><StopOutlined /></template>
                            </a-button>
                        </a-tooltip>
                    </template>
                    <template v-else>
                        <span class="sc-fila__nombre">{{ texto(r) }}</span>
                        <a-tooltip v-if="puedeEditar" title="Editar">
                            <a-button type="text" size="small" @click="iniciarEdicion(r)">
                                <template #icon><EditOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="puedeDesactivar" title="Desactivar">
                            <a-button type="text" size="small" danger @click="desactivar(r)">
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-tooltip>
                    </template>
                </div>
            </div>

            <div class="sc-pie">
                <a :href="route(`${ruta}.index`)" target="_blank" class="sc-pie__link">Ver catálogo completo</a>
                <a-button type="primary" @click="cerrar">
                    <template #icon><CheckOutlined /></template>
                    Listo
                </a-button>
            </div>
        </a-modal>
    </div>
</template>

<style scoped>
.sc {
    display: flex;
    align-items: center;
    gap: 6px;
}
.sc__select {
    flex: 1;
    min-width: 0;
}
.sc__mas {
    flex: none;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    border: 1px solid var(--sigam-borde);
    background: #fff;
    color: var(--sigam-navy);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.14s ease, border-color 0.14s ease, color 0.14s ease, transform 0.12s ease;
}
.sc__mas:hover {
    background: var(--sigam-teal);
    border-color: var(--sigam-teal);
    color: #fff;
}
.sc__mas:active {
    transform: scale(0.93);
}

.sc-alta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    padding-bottom: 14px;
    margin-bottom: 14px;
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.sc-alta .ant-input {
    flex: 1;
    min-width: 140px;
}
.sc-alta__campo {
    width: 110px;
}
.sc-alta__color {
    display: flex;
    align-items: center;
    gap: 6px;
}
.sc-alta__color input[type='color'] {
    width: 32px;
    height: 32px;
    padding: 0;
    border: 1px solid var(--sigam-borde);
    border-radius: 6px;
    cursor: pointer;
}
.sc-alta__switch {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
    cursor: pointer;
}

.sc-lista {
    max-height: 320px;
    overflow-y: auto;
}
.sc-lista__vacio {
    padding: 18px 0;
    text-align: center;
    color: var(--sigam-tenue);
    font-size: 13px;
}
.sc-fila {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 4px;
    border-radius: 8px;
    transition: background 0.12s ease;
}
.sc-fila:hover {
    background: var(--sigam-navy-050);
}
.sc-fila__nombre {
    flex: 1;
    min-width: 0;
    font-size: 13px;
    color: var(--sigam-texto);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sc-fila__input {
    flex: 1;
    min-width: 0;
}

.sc-pie {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--sigam-borde-suave);
}
.sc-pie__link {
    font-size: 12.5px;
    color: var(--sigam-teal-700);
    font-weight: 600;
}
.sc-pie__link:hover {
    text-decoration: underline;
}
</style>
