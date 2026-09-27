<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    CheckCircleOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';

const props = defineProps({
    estados: { type: Array, default: () => [] },
});

const abierto = ref(false);
const equipo = ref(null);

const form = useForm({ estado_id: undefined });

// Estado "Operativo" por defecto (si existe en el catálogo)
const estadoOperativo = computed(() =>
    props.estados.find((e) => /operativ/i.test(e.nombre ?? '')),
);

const abrir = (eq) => {
    equipo.value = eq;
    form.reset();
    form.clearErrors();
    // Preselecciona "Operativo" si existe, si no, deja vacío.
    form.estado_id = estadoOperativo.value?.id ?? undefined;
    abierto.value = true;
};

const enviar = () => {
    if (!form.estado_id) {
        form.setError('estado_id', 'Selecciona el estado con el que se reactivará el equipo.');
        return;
    }
    form.put(route('equipos.restore', equipo.value.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
};

const cancelar = () => {
    abierto.value = false;
    form.reset();
    form.clearErrors();
    equipo.value = null;
};

defineExpose({ abrir });
</script>

<template>
    <a-modal v-model:open="abierto" title="Reactivar equipo" :width="500" :confirm-loading="form.processing"
        ok-text="Reactivar" cancel-text="Cancelar" centered class="modal-restaurar" @ok="enviar" @cancel="cancelar">
        <!-- Aviso con el equipo -->
        <div class="mr-alerta">
            <div class="mr-alerta__icono">
                <UndoOutlined />
            </div>
            <div class="mr-alerta__txt">
                <div class="mr-alerta__t">
                    Vas a reactivar <b>{{ equipo?.codigo_activo }}</b>
                </div>
                <div class="mr-alerta__s">{{ equipo?.descripcion }}</div>
            </div>
        </div>

        <!-- Formulario -->
        <a-form layout="vertical" class="pt-2">
            <a-form-item label="Estado con el que se reactivará"
                :validate-status="form.errors.estado_id ? 'error' : undefined" :help="form.errors.estado_id">
                <a-select v-model:value="form.estado_id" placeholder="Selecciona un estado"
                    :options="estados.map((e) => ({ value: e.id, label: e.nombre }))" allow-clear show-search
                    :filter-option="(input, option) =>
                        (option.label ?? '').toLowerCase().includes(input.toLowerCase())">
                    <template #suffixIcon>
                        <CheckCircleOutlined style="color: #1f9e86" />
                    </template>
                </a-select>
            </a-form-item>

            <div class="mr-nota">
                Una vez reactivado, el equipo volverá a aparecer en el listado activo y podrás
                verlo, editarlo y consultar su código QR.
            </div>
        </a-form>
    </a-modal>
</template>

<style scoped>
/* ==========================================================
   Aviso con el equipo a reactivar
   ========================================================== */
.mr-alerta {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: #e4f4ec;
    border: 1px solid #cbeadd;
    margin-bottom: 6px;
}

.mr-alerta__icono {
    width: 42px;
    height: 42px;
    flex: none;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    color: #fff;
    background: #1f9e86;
    box-shadow: 0 4px 12px rgba(31, 158, 134, 0.35);
}

.mr-alerta__txt {
    min-width: 0;
}

.mr-alerta__t {
    font-size: 13.5px;
    color: #16806c;
    font-weight: 600;
    line-height: 1.25;
}

.mr-alerta__s {
    font-size: 12px;
    color: #0f766e;
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ==========================================================
   Nota informativa
   ========================================================== */
.mr-nota {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 10px 12px;
    border-radius: 11px;
    background: var(--sigam-navy-050);
    color: var(--sigam-tenue);
    font-size: 12.5px;
    line-height: 1.5;
    margin-top: 6px;
}

/* ==========================================================
   Botón principal del modal en azul sólido
   ========================================================== */
.modal-restaurar :deep(.ant-btn-primary) {
    background: #0d84c9;
    border-color: #0d84c9;
}

.modal-restaurar :deep(.ant-btn-primary:hover) {
    background: #0f6fb0;
    border-color: #0f6fb0;
}
</style>