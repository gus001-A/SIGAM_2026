<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDownOutlined,
    ArrowUpOutlined,
    DeleteOutlined,
    PlusOutlined,
    SaveOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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
    const opciones = { preserveScroll: true };
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
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
            <a-row :gutter="16">
                <a-col :xs="24" :lg="16">
                    <a-card title="Datos del formato" size="small" class="mb-4">
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="16">
                                <a-form-item label="Nombre" name="nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" placeholder="p. ej. Checklist de mantenimiento preventivo" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Versión" :validate-status="est('version')" :help="form.errors.version">
                                    <a-input v-model:value="form.version" />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Descripción" :validate-status="est('descripcion')" :help="form.errors.descripcion">
                                    <a-textarea v-model:value="form.descripcion" :auto-size="{ minRows: 2, maxRows: 4 }" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>

                    <a-card size="small" class="mb-4">
                        <template #title>Campos del formato ({{ form.campos.length }})</template>
                        <template #extra>
                            <a-button type="primary" ghost size="small" @click="agregarCampo">
                                <template #icon><PlusOutlined /></template>
                                Agregar campo
                            </a-button>
                        </template>

                        <a-empty v-if="!form.campos.length" description="Aún no hay campos. Agrega el primero." />

                        <div v-for="(campo, i) in form.campos" :key="i" class="campo">
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
                                    <a-form-item label="Texto de ayuda (opcional)">
                                        <a-input v-model:value="campo.ayuda" />
                                    </a-form-item>
                                </a-col>
                                <a-col :xs="24" :sm="10" class="flex items-end pb-4">
                                    <a-checkbox v-model:checked="campo.obligatorio">Respuesta obligatoria</a-checkbox>
                                </a-col>

                                <a-col v-if="campo.tipo === 'seleccion'" :span="24">
                                    <div class="opciones">
                                        <div class="text-sm font-medium mb-1">Opciones de la lista</div>
                                        <div v-for="(_, k) in campo.opciones" :key="k" class="opciones__fila">
                                            <a-input v-model:value="campo.opciones[k]" size="small" :placeholder="`Opción ${k + 1}`" />
                                            <a-button type="text" size="small" danger @click="quitarOpcion(campo, k)">
                                                <template #icon><DeleteOutlined /></template>
                                            </a-button>
                                        </div>
                                        <a-button type="link" size="small" class="px-0" @click="agregarOpcion(campo)">
                                            <template #icon><PlusOutlined /></template>
                                            Añadir opción
                                        </a-button>
                                    </div>
                                </a-col>
                            </a-row>
                        </div>
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="8">
                    <a-card title="Estado" size="small" class="mb-4">
                        <a-form-item name="estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activo</a-radio-button>
                                <a-radio-button value="inactivo">Inactivo</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-card>

                    <a-card size="small">
                        <a-button type="primary" size="large" block html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Crear formato' }}
                        </a-button>
                        <a-button type="text" block class="mt-2" @click="cancelar">Cancelar</a-button>
                    </a-card>
                </a-col>
            </a-row>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.campo {
    border: 1px solid #e8edf2;
    border-radius: 10px;
    padding: 12px 14px 0;
    margin-bottom: 12px;
    background: #fafbfc;
}
.campo__cab {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
}
.campo__num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #1e5eb8;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.opciones {
    background: #fff;
    border: 1px dashed #d0d9e2;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 14px;
}
.opciones__fila {
    display: flex;
    gap: 6px;
    margin-bottom: 6px;
}
</style>
