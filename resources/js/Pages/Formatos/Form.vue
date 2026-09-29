<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDownOutlined,
    ArrowUpOutlined,
    CheckCircleFilled,
    DeleteOutlined,
    ExclamationCircleFilled,
    FileTextOutlined,
    PlusOutlined,
    SaveOutlined,
    TagOutlined,
    UnorderedListOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';

const props = defineProps({
    formato: { type: Object, default: null },
    tiposCampo: { type: Array, default: () => [] },
});

const editando = computed(() => !!props.formato);

const ETIQUETAS_TIPO = {
    texto: 'Texto corto',
    area_texto: 'Texto largo',
    numero: 'Número',
    fecha: 'Fecha',
    seleccion: 'Selección (lista)',
    checkbox: 'Casilla (sí/no)',
    foto: 'Fotografía',
    firma: 'Firma',
};
const etiquetaTipo = (t) => ETIQUETAS_TIPO[t] ?? t;
const opcionesTipo = computed(() => props.tiposCampo.map((t) => ({ label: etiquetaTipo(t), value: t })));

const form = useForm({
    nombre: props.formato?.nombre ?? '',
    descripcion: props.formato?.descripcion ?? '',
    version: props.formato?.version ?? '1.0',
    // El estado ya no se edita, siempre se conserva o se crea como activo
    estado: props.formato?.estado ?? 'activo',
    campos: (props.formato?.campos ?? []).map((c) => ({
        etiqueta: c.etiqueta,
        tipo: c.tipo,
        obligatorio: !!c.obligatorio,
        ayuda: c.ayuda ?? '',
        opciones: [...(c.opciones ?? [])],
    })),
});

const reglas = reactive({
    nombre: [{ required: true, message: 'El nombre es obligatorio.' }],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);
const errorCampo = (i, prop) => form.errors[`campos.${i}.${prop}`];

/* ==========================================================
   Tabs
   ========================================================== */
const TABS = {
    datos: {
        titulo: 'Datos del formato',
        subtitulo: 'Nombre y versión',
        color: '#0d84c9',
        campos: ['nombre', 'version', 'descripcion'],
    },
    campos: {
        titulo: 'Campos del formato',
        subtitulo: 'Preguntas del checklist',
        color: '#6b4bc9',
        campos: [],
    },
};

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas(
    Object.fromEntries(Object.entries(TABS).map(([k, t]) => [k, t.campos])),
    'datos',
);

const estadoTab = (key) => {
    if (TABS[key].campos.some((c) => form.errors[c])) return 'error';
    if (key === 'datos' && form.nombre) return 'ok';
    if (key === 'campos' && form.campos.length) return 'ok';
    return null;
};

/* ==========================================================
   Campos
   ========================================================== */
const agregarCampo = () => {
    form.campos.push({ etiqueta: '', tipo: 'texto', obligatorio: false, ayuda: '', opciones: [] });
};
const quitarCampo = (i) => form.campos.splice(i, 1);
const mover = (i, delta) => {
    const j = i + delta;
    if (j < 0 || j >= form.campos.length) return;
    const [item] = form.campos.splice(i, 1);
    form.campos.splice(j, 0, item);
};
const agregarOpcion = (campo) => campo.opciones.push('');
const quitarOpcion = (campo, k) => campo.opciones.splice(k, 1);

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor };
    if (editando.value) form.put(route('formatos.update', props.formato.id), opciones);
    else form.post(route('formatos.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('formatos.show', props.formato.id) : route('formatos.index'));
</script>

<template>
    <Head :title="editando ? `Editar ${formato.nombre}` : 'Nuevo formato'" />

    <AppLayout
        :titulo="editando ? 'Editar formato' : 'Nuevo formato'"
        descripcion="Arma el checklist agregando campos y definiendo su tipo, orden y obligatoriedad."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva" class="form-tabs">
                    <!-- ============================
                         TAB: DATOS DEL FORMATO
                         ============================ -->
                    <a-tab-pane key="datos">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.datos.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><FileTextOutlined /></span>
                                    <span v-if="estadoTab('datos') === 'ok'" class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                    <span v-else-if="estadoTab('datos') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.datos.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.datos.subtitulo }}</span>
                                </span>
                            </span>
                        </template>
                        <p class="tab-ayuda">Identifica el formato con un nombre claro y define su versión.</p>

                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="16">
                                <a-form-item label="Nombre" name="nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" placeholder="p. ej. Checklist de mantenimiento preventivo" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Versión" :validate-status="est('version')" :help="form.errors.version">
                                    <a-input v-model:value="form.version" placeholder="1.0" />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Descripción" extra="Opcional — para qué sirve este formato."
                                    :validate-status="est('descripcion')" :help="form.errors.descripcion">
                                    <a-textarea v-model:value="form.descripcion" :auto-size="{ minRows: 2, maxRows: 4 }" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <!-- ============================
                         TAB: CAMPOS DEL FORMATO
                         ============================ -->
                    <a-tab-pane key="campos">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.campos.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><UnorderedListOutlined /></span>
                                    <span v-if="estadoTab('campos') === 'ok'" class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.campos.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.campos.subtitulo }}</span>
                                </span>
                            </span>
                        </template>

                        <div class="campos-head">
                            <div class="campos-head__meta">
                                <span class="campos-head__title">
                                    {{ form.campos.length }} campo{{ form.campos.length === 1 ? '' : 's' }}
                                </span>
                                <span class="campos-head__sub">
                                    Arrastra el orden con las flechas ↑↓
                                </span>
                            </div>
                            <a-button type="primary" class="btn-agregar" @click="agregarCampo">
                                <template #icon><PlusOutlined /></template>
                                Agregar campo
                            </a-button>
                        </div>

                        <div v-if="!form.campos.length" class="vacio-box">
                            <UnorderedListOutlined />
                            <span>Aún no hay campos. Agrega el primero.</span>
                        </div>

                        <div v-for="(campo, i) in form.campos" :key="i" class="campo" :style="{ '--c': '#6b4bc9' }">
                            <div class="campo__cab">
                                <span class="campo__num">{{ i + 1 }}</span>
                                <a-space :size="2">
                                    <a-button type="text" size="small" :disabled="i === 0" @click="mover(i, -1)">
                                        <template #icon><ArrowUpOutlined /></template>
                                    </a-button>
                                    <a-button type="text" size="small" :disabled="i === form.campos.length - 1" @click="mover(i, 1)">
                                        <template #icon><ArrowDownOutlined /></template>
                                    </a-button>
                                    <a-button type="text" size="small" danger @click="quitarCampo(i)">
                                        <template #icon><DeleteOutlined /></template>
                                    </a-button>
                                </a-space>
                            </div>

                            <a-row :gutter="12">
                                <a-col :xs="24" :sm="14">
                                    <a-form-item label="Etiqueta" :validate-status="errorCampo(i, 'etiqueta') ? 'error' : undefined" :help="errorCampo(i, 'etiqueta')">
                                        <a-input v-model:value="campo.etiqueta" placeholder="¿Qué se pregunta?" />
                                    </a-form-item>
                                </a-col>
                                <a-col :xs="24" :sm="10">
                                    <a-form-item label="Tipo de campo">
                                        <a-select v-model:value="campo.tipo" :options="opcionesTipo" />
                                    </a-form-item>
                                </a-col>
                                <a-col :xs="24" :sm="14">
                                    <a-form-item label="Texto de ayuda" extra="Opcional">
                                        <a-input v-model:value="campo.ayuda" placeholder="Instrucciones para quien llena el checklist" />
                                    </a-form-item>
                                </a-col>
                                <a-col :xs="24" :sm="10" class="flex items-end pb-4">
                                    <a-checkbox v-model:checked="campo.obligatorio">Respuesta obligatoria</a-checkbox>
                                </a-col>

                                <a-col v-if="campo.tipo === 'seleccion'" :span="24">
                                    <div class="opciones">
                                        <div class="opciones__title">
                                            <TagOutlined /> Opciones de la lista
                                        </div>
                                        <div v-for="(_, k) in campo.opciones" :key="k" class="opciones__fila">
                                            <span class="opciones__num">{{ k + 1 }}</span>
                                            <a-input v-model:value="campo.opciones[k]" size="small" :placeholder="`Opción ${k + 1}`" />
                                            <a-button type="text" size="small" danger @click="quitarOpcion(campo, k)">
                                                <template #icon><DeleteOutlined /></template>
                                            </a-button>
                                        </div>
                                        <a-button type="link" size="small" class="opciones__add" @click="agregarOpcion(campo)">
                                            <template #icon><PlusOutlined /></template>
                                            Añadir opción
                                        </a-button>
                                    </div>
                                </a-col>
                            </a-row>
                        </div>
                    </a-tab-pane>
                </a-tabs>

                <!-- Footer con botones a la derecha -->
                <div class="form-footer">
                    <a-space>
                        <a-button size="large" @click="cancelar">
                            Cancelar
                        </a-button>
                        <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Crear formato' }}
                        </a-button>
                    </a-space>
                </div>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Card + tabs (mismo look que Norma)
   ========================================================== */
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

/* ==========================================================
   Tab label
   ========================================================== */
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
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
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
    margin: -4px 0 14px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}

/* ==========================================================
   Header de la pestaña "Campos"
   ========================================================== */
.campos-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    background: linear-gradient(135deg, #f7f4fd 0%, #ffffff 100%);
    border: 1px solid #e0d3f7;
    flex-wrap: wrap;
}

.campos-head__meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.campos-head__title {
    font-size: 13.5px;
    font-weight: 800;
    color: #563a9e;
    line-height: 1.2;
}

.campos-head__sub {
    font-size: 11.5px;
    color: var(--sigam-tenue);
    line-height: 1.2;
}

.btn-agregar {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%) !important;
    border-color: #6b4bc9 !important;
    font-weight: 700;
    box-shadow: 0 4px 12px -4px rgba(107, 75, 201, 0.55);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.btn-agregar:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 6px 16px -4px rgba(107, 75, 201, 0.7);
}

/* ==========================================================
   Campo (mini-card)
   ========================================================== */
.campo {
    position: relative;
    border-radius: 12px;
    padding: 14px 16px 4px;
    margin-bottom: 12px;
    background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
    border: 1px solid #e2e8f0;
    border-left: 3px solid var(--c);
    transition: border-color 0.14s ease, box-shadow 0.14s ease;
}

.campo:hover {
    border-color: #c9b8f0;
    box-shadow: 0 4px 14px -8px rgba(107, 75, 201, 0.35);
}

.campo__cab {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.campo__num {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 8px -3px rgba(107, 75, 201, 0.55);
}

/* ==========================================================
   Opciones
   ========================================================== */
.opciones {
    background: #fff;
    border: 1px dashed #d0d9e2;
    border-radius: 10px;
    padding: 10px 12px 8px;
    margin-bottom: 14px;
}

.opciones__title {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #563a9e;
    margin-bottom: 8px;
}

.opciones__title .anticon {
    font-size: 11px;
}

.opciones__fila {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}

.opciones__num {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 800;
    color: #6b4bc9;
    background: #efe9fb;
}

.opciones__add {
    padding: 0 !important;
    font-weight: 700;
    color: #6b4bc9 !important;
}

.opciones__add:hover {
    color: #563a9e !important;
}

/* ==========================================================
   Vacio
   ========================================================== */
.vacio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px 12px;
    border-radius: 12px;
    border: 1px dashed #cdd8e3;
    background: linear-gradient(180deg, #f5f8fb 0%, #eef3f9 100%);
    color: #7b8a9c;
    font-size: 12.5px;
}

.vacio-box .anticon {
    font-size: 22px;
    opacity: 0.5;
}

/* ==========================================================
   Footer
   ========================================================== */
.form-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 14px 24px;
    border-top: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}
</style>