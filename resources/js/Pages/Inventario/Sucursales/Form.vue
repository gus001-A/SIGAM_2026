<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { FileTextOutlined, SaveOutlined, ShopOutlined, UserOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';
import { reglaCorreo, reglaRequerido, reglaTelefono, soloDigitos } from '@/utils/restricciones';

const props = defineProps({
    sucursal: { type: Object, default: null },
    responsables: { type: Array, default: () => [] },
});

const editando = computed(() => !!props.sucursal);

const form = useForm({
    codigo: props.sucursal?.codigo ?? '',
    nombre: props.sucursal?.nombre ?? '',
    direccion: props.sucursal?.direccion ?? '',
    telefono: props.sucursal?.telefono ?? '',
    correo: props.sucursal?.correo ?? '',
    responsable_id: props.sucursal?.responsable_id ?? undefined,
    estado: props.sucursal?.estado ?? 'activo',
    notas: props.sucursal?.notas ?? '',
});

const reglas = reactive({
    nombre: [reglaRequerido('El nombre es obligatorio.')],
    correo: [reglaCorreo()],
    telefono: [reglaTelefono(10)],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas({
    gen: ['codigo', 'nombre', 'direccion', 'telefono', 'correo'],
    asig: ['responsable_id', 'estado'],
    notas: ['notas'],
}, 'gen');

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor };
    if (editando.value) form.put(route('sucursales.update', props.sucursal.id), opciones);
    else form.post(route('sucursales.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('sucursales.show', props.sucursal.id) : route('sucursales.index'));
</script>

<template>
    <Head :title="editando ? `Editar ${sucursal.nombre}` : 'Nueva sucursal'" />

    <AppLayout
        :titulo="editando ? `Editar sucursal ${sucursal.nombre}` : 'Nueva sucursal'"
        descripcion="Datos de identificación, dirección y responsable de la sede."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva">
                    <a-tab-pane key="gen">
                        <template #tab><span><ShopOutlined /> Datos generales</span></template>
                        <p class="tab-ayuda">Identificación y contacto de la sede — el código se usa en folios y reportes.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="8">
                                <a-form-item
                                    label="Código"
                                    name="codigo"
                                    extra="Déjalo en blanco para generarlo solo (4 letras del nombre + consecutivo, p. ej. HOSP-01)."
                                    :validate-status="est('codigo')"
                                    :help="form.errors.codigo"
                                >
                                    <a-input v-model:value="form.codigo" placeholder="Automático si se deja vacío" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="16">
                                <a-form-item label="Nombre" name="nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Dirección" :validate-status="est('direccion')" :help="form.errors.direccion">
                                    <a-input v-model:value="form.direccion" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Teléfono" name="telefono" :validate-status="est('telefono')" :help="form.errors.telefono">
                                    <a-input
                                        v-model:value="form.telefono"
                                        :maxlength="10"
                                        inputmode="numeric"
                                        placeholder="10 dígitos"
                                        @keypress="soloDigitos"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Correo" name="correo" :validate-status="est('correo')" :help="form.errors.correo">
                                    <a-input v-model:value="form.correo" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="asig">
                        <template #tab><span><UserOutlined /> Asignación</span></template>
                        <p class="tab-ayuda">Quién responde por esta sede y si sigue en operación.</p>
                        <a-form-item label="Responsable" extra="Persona de contacto de la sucursal — opcional." :validate-status="est('responsable_id')" :help="form.errors.responsable_id">
                            <a-select
                                v-model:value="form.responsable_id"
                                :options="responsables"
                                allow-clear
                                placeholder="Sin asignar"
                            />
                        </a-form-item>
                        <a-form-item label="Estado" name="estado" extra="Una sucursal inactiva no aparece como opción al registrar equipos." :validate-status="est('estado')" :help="form.errors.estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activa</a-radio-button>
                                <a-radio-button value="inactivo">Inactiva</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-tab-pane>

                    <a-tab-pane key="notas">
                        <template #tab><span><FileTextOutlined /> Notas</span></template>
                        <p class="tab-ayuda">Información adicional libre, visible en la ficha de la sucursal.</p>
                        <a-form-item :validate-status="est('notas')" :help="form.errors.notas">
                            <a-textarea v-model:value="form.notas" :auto-size="{ minRows: 3, maxRows: 8 }" placeholder="Información adicional de la sucursal" />
                        </a-form-item>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <a-card size="small" class="form-acciones">
                <a-space>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon><SaveOutlined /></template>
                        {{ editando ? 'Guardar cambios' : 'Registrar sucursal' }}
                    </a-button>
                    <a-button size="large" @click="cancelar">Cancelar</a-button>
                </a-space>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.form-card {
    margin-bottom: 10px;
}
.form-card :deep(.ant-tabs-nav) {
    margin-bottom: 10px;
}
.tab-ayuda {
    margin: -4px 0 10px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}
.form-acciones {
    position: sticky;
    bottom: 0;
    z-index: 5;
}
</style>
