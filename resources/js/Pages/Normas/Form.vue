<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    CheckCircleFilled,
    ExclamationCircleFilled,
    FileProtectOutlined,
    SaveOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';

const props = defineProps({
    norma: { type: Object, default: null },
});

const editando = computed(() => !!props.norma);

const form = useForm({
    codigo: props.norma?.codigo ?? '',
    nombre: props.norma?.nombre ?? '',
    version: props.norma?.version ?? '',
    descripcion: props.norma?.descripcion ?? '',
    fecha_vigencia: props.norma?.fecha_vigencia?.slice(0, 10) ?? '',
    fecha_revision: props.norma?.fecha_revision?.slice(0, 10) ?? '',
    documento_id: props.norma?.documento_id ?? undefined,
    // El estado ya no se edita, siempre se conserva o se crea como activo
    estado: props.norma?.estado ?? 'activo',
});

const reglas = reactive({
    codigo: [{ required: true, message: 'El código es obligatorio.' }],
    nombre: [{ required: true, message: 'El nombre es obligatorio.' }],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const TABS = {
    id: {
        titulo: 'Identificación',
        subtitulo: 'Código y descripción',
        color: '#0d84c9',
        campos: ['codigo', 'nombre', 'version', 'descripcion'],
    },
    vig: {
        titulo: 'Vigencia',
        subtitulo: 'Fechas de revisión',
        color: '#e08a1e',
        campos: ['fecha_vigencia', 'fecha_revision'],
    },
};

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas(
    Object.fromEntries(Object.entries(TABS).map(([k, t]) => [k, t.campos])),
    'id',
);

const estadoTab = (key) => {
    if (TABS[key].campos.some((c) => form.errors[c])) return 'error';
    if (key === 'id' && form.codigo && form.nombre) return 'ok';
    return null;
};

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor };
    if (editando.value) form.put(route('normas.update', props.norma.id), opciones);
    else form.post(route('normas.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('normas.show', props.norma.id) : route('normas.index'));
</script>

<template>
    <Head :title="editando ? `Editar ${norma.codigo}` : 'Nueva norma'" />

    <AppLayout
        :titulo="editando ? `Editar norma ${norma.codigo}` : 'Nueva norma'"
        descripcion="Código, versión y fechas de vigencia y revisión de la normativa."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva" class="form-tabs">
                    <!-- ============================
                         TAB: IDENTIFICACIÓN
                         ============================ -->
                    <a-tab-pane key="id">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.id.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><FileProtectOutlined /></span>
                                    <span v-if="estadoTab('id') === 'ok'" class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                    <span v-else-if="estadoTab('id') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.id.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.id.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Qué norma es y a qué se refiere.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Código" name="codigo" :validate-status="est('codigo')" :help="form.errors.codigo">
                                    <a-input v-model:value="form.codigo" placeholder="p. ej. NOM-137-SSA1-2008" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Nombre" name="nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="4">
                                <a-form-item label="Versión" :validate-status="est('version')" :help="form.errors.version">
                                    <a-input v-model:value="form.version" placeholder="2008" />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Descripción / alcance" :validate-status="est('descripcion')" :help="form.errors.descripcion">
                                    <a-textarea v-model:value="form.descripcion" :auto-size="{ minRows: 3, maxRows: 8 }" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <!-- ============================
                         TAB: VIGENCIA
                         ============================ -->
                    <a-tab-pane key="vig">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.vig.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><CalendarOutlined /></span>
                                    <span v-if="estadoTab('vig') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.vig.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.vig.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Cuándo entró en vigor y cuándo toca revisarla otra vez.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Fecha de entrada en vigor" :validate-status="est('fecha_vigencia')" :help="form.errors.fecha_vigencia">
                                    <CampoFechaHora v-model="form.fecha_vigencia" solo-fecha />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    label="Próxima revisión"
                                    extra="Se marcará en rojo cuando la fecha haya pasado."
                                    :validate-status="est('fecha_revision')"
                                    :help="form.errors.fecha_revision"
                                >
                                    <CampoFechaHora v-model="form.fecha_revision" solo-fecha />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>
                </a-tabs>

                <!-- ==========================================================
                     Footer con botones a la derecha
                     ========================================================== -->
                <div class="form-footer">
                    <a-space>
                        <a-button size="large" @click="cancelar">
                            Cancelar
                        </a-button>
                        <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Registrar norma' }}
                        </a-button>
                    </a-space>
                </div>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.form-card {
    border-radius: 16px;
    box-shadow: var(--sigam-sombra-sm);
    overflow: hidden;
}

.form-tabs :deep(.ant-tabs-nav) {
    padding: 8px 12px 0;
    margin-bottom: 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid var(--sigam-borde-suave);
}

.form-tabs :deep(.ant-tabs-nav::before) {
    border-bottom: none;
}

.form-tabs :deep(.ant-tabs-nav-wrap) {
    align-items: stretch;
}

.form-tabs :deep(.ant-tabs-nav-list) {
    gap: 4px;
    align-items: stretch;
}

.form-tabs :deep(.ant-tabs-tab) {
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 11px 11px 0 0;
    transition: background 0.2s ease;
    height: 56px;
    display: inline-flex !important;
    align-items: center !important;
}

.form-tabs :deep(.ant-tabs-tab:hover) {
    background: #f3f6fa;
}

.form-tabs :deep(.ant-tabs-tab-active) {
    background: #fff;
}

.form-tabs :deep(.ant-tabs-tab-btn) {
    color: inherit !important;
    height: 100%;
    display: inline-flex !important;
    align-items: center !important;
    padding: 0 14px !important;
    transition: none !important;
}

.form-tabs :deep(.ant-tabs-ink-bar) {
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: #0d84c9;
}

.form-tabs :deep(.ant-tabs-content-holder) {
    padding: 22px 24px 20px;
}

/* Label completo */
.tab-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    height: 100%;
    line-height: 1;
}

.tab-label__ico-wrap {
    position: relative;
    flex: none;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.tab-label__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #94a3b8;
    background: #eef2f7;
    transition: all 0.25s cubic-bezier(0.34, 1.4, 0.4, 1);
    line-height: 1;
}

.tab-label__ico .anticon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}

.form-tabs :deep(.ant-tabs-tab-active) .tab-label__ico {
    color: #fff;
    background: var(--tab-color);
    box-shadow: 0 4px 10px -4px var(--tab-color);
    transform: scale(1.05);
}

.tab-label__badge {
    position: absolute;
    bottom: -3px;
    right: -3px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    line-height: 1;
    border: 2px solid #fff;
    background: #fff;
    pointer-events: none;
    z-index: 2;
}

.tab-label__badge .anticon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}

.tab-label__badge--ok {
    color: #1f9e86;
}

.tab-label__badge--error {
    color: #d64545;
    animation: pulseError 1.6s ease infinite;
}

@keyframes pulseError {

    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.15);
    }
}

.tab-label__col {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 2px;
    min-width: 0;
    text-align: left;
    line-height: 1.15;
}

.tab-label__txt {
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
    transition: color 0.2s ease;
    white-space: nowrap;
    line-height: 1.2;
    display: block;
}

.form-tabs :deep(.ant-tabs-tab-active) .tab-label__txt {
    color: var(--sigam-navy);
}

.tab-label__sub {
    font-size: 10.5px;
    color: var(--sigam-tenue);
    white-space: nowrap;
    line-height: 1.2;
    font-weight: 500;
    display: block;
}

@media (max-width: 991px) {
    .tab-label__sub {
        display: none;
    }
}

@media (max-width: 640px) {
    .tab-label__col {
        display: none;
    }

    .form-tabs :deep(.ant-tabs-tab) {
        height: 50px;
    }
}

.tab-ayuda {
    margin: -4px 0 12px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}

/* Footer con botones a la derecha */
.form-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 14px 24px;
    border-top: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}
</style>