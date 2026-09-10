<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BarcodeOutlined,
    DollarOutlined,
    EnvironmentOutlined,
    ProfileOutlined,
    SafetyCertificateOutlined,
    SaveOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoEspecificaciones from '@/Components/CampoEspecificaciones.vue';
import {
    limpiarPegado,
    reglaCodigo,
    reglaDespuesDe,
    reglaNoFutura,
    reglaRequerido,
    reglaTexto,
    soloCodigo,
    soloTexto,
} from '@/utils/restricciones';

const props = defineProps({
    equipo: { type: Object, default: null },
    catalogos: { type: Object, default: () => ({}) },
});

const editando = computed(() => !!props.equipo);
const ubicacionOriginal = props.equipo?.ubicacion_id ?? null;

const form = useForm({
    codigo_activo: props.equipo?.codigo_activo ?? '',
    codigo_barras: props.equipo?.codigo_barras ?? '',
    descripcion: props.equipo?.descripcion ?? '',
    tipo_id: props.equipo?.tipo_id ?? undefined,
    marca_id: props.equipo?.marca_id ?? undefined,
    modelo: props.equipo?.modelo ?? '',
    numero_serie: props.equipo?.numero_serie ?? '',
    sucursal_id: props.equipo?.sucursal_id ?? undefined,
    ubicacion_id: props.equipo?.ubicacion_id ?? undefined,
    proveedor_id: props.equipo?.proveedor_id ?? undefined,
    responsable_id: props.equipo?.responsable_id ?? undefined,
    estado_id: props.equipo?.estado_id ?? undefined,
    fecha_adquisicion: props.equipo?.fecha_adquisicion?.slice(0, 10) ?? '',
    numero_factura: props.equipo?.numero_factura ?? '',
    valor_adquisicion: props.equipo?.valor_adquisicion ?? null,
    garantia_hasta: props.equipo?.garantia_hasta?.slice(0, 10) ?? '',
    especificaciones: props.equipo?.especificaciones ?? {},
    vida_util: props.equipo?.vida_util ?? '',
    notas: props.equipo?.notas ?? '',
    normas: (props.equipo?.normas ?? []).map((n) => n.id),
    motivo_cambio_ubicacion: '',
});

const reglas = reactive({
    codigo_activo: [reglaRequerido('El código del activo es obligatorio.'), reglaCodigo()],
    codigo_barras: [reglaCodigo()],
    descripcion: [reglaRequerido('La descripción es obligatoria.'), reglaTexto()],
    modelo: [reglaCodigo()],
    numero_serie: [reglaCodigo()],
    numero_factura: [reglaCodigo()],
    vida_util: [reglaTexto()],
    sucursal_id: [reglaRequerido('Selecciona la sucursal.')],
    fecha_adquisicion: [reglaNoFutura('La fecha de adquisición no puede ser futura.')],
    garantia_hasta: [
        reglaDespuesDe(
            () => form.fecha_adquisicion,
            'La garantía debe vencer en o después de la fecha de adquisición.',
        ),
    ],
    motivo_cambio_ubicacion: [reglaTexto()],
});

const ubicacionesDeSucursal = computed(() =>
    (props.catalogos.ubicaciones ?? []).filter((u) => u.sucursal_id === Number(form.sucursal_id)),
);
const cambioUbicacion = computed(
    () => editando.value && ubicacionOriginal !== null && form.ubicacion_id !== ubicacionOriginal,
);
const normasOpciones = computed(() =>
    (props.catalogos.normas ?? []).map((n) => ({ value: n.id, label: `${n.codigo} — ${n.nombre}` })),
);
const responsablesOpciones = computed(() =>
    (props.catalogos.responsables ?? []).map((u) => ({ value: u.id, label: `${u.nombre} ${u.apellidos ?? ''}`.trim() })),
);

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const enviar = () => {
    const opciones = { preserveScroll: true };
    if (editando.value) form.put(route('equipos.update', props.equipo.id), opciones);
    else form.post(route('equipos.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('equipos.show', props.equipo.id) : route('equipos.index'));
</script>

<template>
    <Head :title="editando ? `Editar ${equipo.codigo_activo}` : 'Nuevo equipo'" />

    <AppLayout
        :titulo="editando ? `Editar equipo ${equipo.codigo_activo}` : 'Nuevo equipo'"
        :descripcion="editando ? 'Actualiza los datos del activo; los cambios de ubicación quedan en el historial.' : 'Registra un activo nuevo con su identificación, ubicación y datos de adquisición.'"
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
            <a-row :gutter="16">
                <a-col :xs="24" :lg="16">
                    <a-card size="small" class="mb-4 sec sec--id">
                        <template #title><ProfileOutlined /> Identificación</template>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Código del activo" name="codigo_activo" :validate-status="est('codigo_activo')" :help="form.errors.codigo_activo">
                                    <a-input
                                        v-model:value="form.codigo_activo"
                                        placeholder="p. ej. EQ-0001"
                                        @keypress="soloCodigo"
                                        @paste="limpiarPegado('codigo')"
                                    >
                                        <template #prefix><BarcodeOutlined class="op-40" /></template>
                                    </a-input>
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Código de barras" name="codigo_barras" :validate-status="est('codigo_barras')" :help="form.errors.codigo_barras">
                                    <a-input
                                        v-model:value="form.codigo_barras"
                                        @keypress="soloCodigo"
                                        @paste="limpiarPegado('codigo')"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item label="Descripción" name="descripcion" :validate-status="est('descripcion')" :help="form.errors.descripcion">
                                    <a-input
                                        v-model:value="form.descripcion"
                                        placeholder="Nombre o descripción del equipo"
                                        @keypress="soloTexto"
                                        @paste="limpiarPegado('texto')"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Tipo">
                                    <a-select v-model:value="form.tipo_id" :options="catalogos.tipos" :field-names="{ label: 'nombre', value: 'id' }" allow-clear />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Marca">
                                    <a-select v-model:value="form.marca_id" :options="catalogos.marcas" :field-names="{ label: 'nombre', value: 'id' }" allow-clear />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Modelo" name="modelo" :validate-status="est('modelo')" :help="form.errors.modelo">
                                    <a-input v-model:value="form.modelo" @keypress="soloCodigo" @paste="limpiarPegado('codigo')" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Número de serie" name="numero_serie" :validate-status="est('numero_serie')" :help="form.errors.numero_serie">
                                    <a-input v-model:value="form.numero_serie" @keypress="soloCodigo" @paste="limpiarPegado('codigo')" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Estado del equipo">
                                    <a-select v-model:value="form.estado_id" :options="catalogos.estados" :field-names="{ label: 'nombre', value: 'id' }" allow-clear />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>

                    <a-card size="small" class="mb-4 sec sec--ubi">
                        <template #title><EnvironmentOutlined /> Ubicación y responsable</template>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Sucursal" name="sucursal_id" :validate-status="est('sucursal_id')" :help="form.errors.sucursal_id">
                                    <a-select
                                        v-model:value="form.sucursal_id"
                                        :options="catalogos.sucursales"
                                        :field-names="{ label: 'nombre', value: 'id' }"
                                        @change="form.ubicacion_id = undefined"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Ubicación" :validate-status="est('ubicacion_id')" :help="form.errors.ubicacion_id">
                                    <a-select
                                        v-model:value="form.ubicacion_id"
                                        :options="ubicacionesDeSucursal"
                                        :field-names="{ label: 'nombre', value: 'id' }"
                                        :disabled="!form.sucursal_id"
                                        allow-clear
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Responsable">
                                    <a-select v-model:value="form.responsable_id" :options="responsablesOpciones" allow-clear />
                                </a-form-item>
                            </a-col>
                            <a-col v-if="cambioUbicacion" :xs="24" :sm="12">
                                <a-form-item label="Motivo del cambio de ubicación" name="motivo_cambio_ubicacion" extra="Se registrará en el historial del equipo" :validate-status="est('motivo_cambio_ubicacion')" :help="form.errors.motivo_cambio_ubicacion">
                                    <a-input v-model:value="form.motivo_cambio_ubicacion" @keypress="soloTexto" @paste="limpiarPegado('texto')" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>

                    <a-card size="small" class="mb-4 sec sec--tec">
                        <template #title><SafetyCertificateOutlined /> Datos técnicos y normas</template>
                        <CampoEspecificaciones v-model="form.especificaciones" class="mb-4" />

                        <a-form-item label="Normas aplicables">
                            <a-select v-model:value="form.normas" :options="normasOpciones" mode="multiple" :show-search="false" allow-clear />
                        </a-form-item>
                        <a-form-item label="Observaciones" name="notas" :validate-status="est('notas')" :help="form.errors.notas">
                            <a-textarea v-model:value="form.notas" :auto-size="{ minRows: 2, maxRows: 6 }" show-count :maxlength="2000" />
                        </a-form-item>
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="8">
                    <a-card size="small" class="mb-4 sec sec--adq">
                        <template #title><DollarOutlined /> Adquisición</template>
                        <a-form-item label="Proveedor">
                            <a-select v-model:value="form.proveedor_id" :options="catalogos.proveedores" :field-names="{ label: 'razon_social', value: 'id' }" allow-clear />
                        </a-form-item>
                        <a-form-item label="Fecha de adquisición" name="fecha_adquisicion" :validate-status="est('fecha_adquisicion')" :help="form.errors.fecha_adquisicion">
                            <a-input v-model:value="form.fecha_adquisicion" type="date" :max="new Date().toISOString().slice(0, 10)" />
                        </a-form-item>
                        <a-form-item label="Número de factura" name="numero_factura" :validate-status="est('numero_factura')" :help="form.errors.numero_factura">
                            <a-input v-model:value="form.numero_factura" @keypress="soloCodigo" @paste="limpiarPegado('codigo')" />
                        </a-form-item>
                        <a-form-item label="Valor de adquisición" :validate-status="est('valor_adquisicion')" :help="form.errors.valor_adquisicion">
                            <a-input-number
                                v-model:value="form.valor_adquisicion"
                                class="w-full"
                                :min="0"
                                :max="99999999999"
                                :precision="2"
                                :step="100"
                                prefix="$"
                                :formatter="(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                                :parser="(v) => v.replace(/,/g, '')"
                            />
                        </a-form-item>
                        <a-form-item label="Garantía hasta" name="garantia_hasta" :validate-status="est('garantia_hasta')" :help="form.errors.garantia_hasta">
                            <a-input v-model:value="form.garantia_hasta" type="date" />
                        </a-form-item>
                        <a-form-item label="Vida útil estimada" name="vida_util" :validate-status="est('vida_util')" :help="form.errors.vida_util">
                            <a-input v-model:value="form.vida_util" placeholder="p. ej. 10 años" @keypress="soloTexto" @paste="limpiarPegado('texto')" />
                        </a-form-item>
                    </a-card>

                    <a-card size="small">
                        <a-button type="primary" size="large" block html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Registrar equipo' }}
                        </a-button>
                        <a-button type="text" block class="mt-2" @click="cancelar">Cancelar</a-button>
                    </a-card>
                </a-col>
            </a-row>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.op-40 {
    opacity: 0.4;
}
.w-full {
    width: 100%;
}
.sec :deep(.ant-card-head-title) {
    display: flex;
    align-items: center;
    gap: 8px;
}
.sec :deep(.ant-card-head-title)::before {
    display: none !important;
}
.sec :deep(.ant-card-head-title .anticon) {
    font-size: 15px;
    color: var(--sigam-teal);
}
.sec {
    border-top: 3px solid var(--sigam-navy-100);
}
.sec--id {
    border-top-color: #0d84c9;
}
.sec--ubi {
    border-top-color: var(--sigam-teal);
}
.sec--tec {
    border-top-color: #6b4bc9;
}
.sec--adq {
    border-top-color: #e08a1e;
}
</style>
