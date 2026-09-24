<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BarcodeOutlined,
    CalendarOutlined,
    CameraOutlined,
    DollarOutlined,
    EnvironmentOutlined,
    InboxOutlined,
    ProfileOutlined,
    SafetyCertificateOutlined,
    SaveOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoEspecificaciones from '@/Components/CampoEspecificaciones.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import LectorCodigoBarras from '@/Components/LectorCodigoBarras.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';
import {
    hoyISO,
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
    codigoSugerido: { type: String, default: '' },
    catalogos: { type: Object, default: () => ({}) },
});

const editando = computed(() => !!props.equipo);
const ubicacionOriginal = props.equipo?.ubicacion_id ?? null;

const form = useForm({
    // Al crear, el código se sugiere solo (EQ-001, EQ-002...) — se puede
    // aceptar tal cual, escribir uno propio, o llenarlo con el lector de
    // código de barras (por ejemplo si el equipo ya trae una etiqueta física).
    codigo_activo: props.equipo?.codigo_activo ?? props.codigoSugerido ?? '',
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
    foto_referencia: null,
    plan_preventivo: false,
    plan_tipo_mantenimiento_id: undefined,
    plan_tipo_frecuencia: 'mensual',
    plan_valor_frecuencia: 1,
});

const fotoLista = ref([]);
const fotoActual = computed(() => props.equipo?.foto_referencia ?? null);
const cambiandoFoto = ref(false);
const antesDeSubirFoto = (file) => {
    fotoLista.value = [file];
    form.foto_referencia = file;
    return false;
};
const quitarFoto = () => {
    fotoLista.value = [];
    form.foto_referencia = null;
};

const opcionesFrecuenciaPlan = [
    { value: 'dias', label: 'Cada N días' },
    { value: 'semanal', label: 'Semanal' },
    { value: 'mensual', label: 'Mensual' },
    { value: 'bimestral', label: 'Bimestral' },
    { value: 'trimestral', label: 'Trimestral' },
    { value: 'semestral', label: 'Semestral' },
    { value: 'anual', label: 'Anual' },
];

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

// --- Lector de código de barras ------------------------------------
const lectorActivo = ref('codigo_activo');
const lector = ref(null);
const abrirLector = (campo) => {
    lectorActivo.value = campo;
    lector.value.abrir();
};
const onDetectado = (texto) => {
    form[lectorActivo.value] = texto;
};

// --- Pestaña activa: salta a la primera con error al enviar --------
const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas({
    id: ['codigo_activo', 'codigo_barras', 'descripcion', 'modelo', 'numero_serie'],
    ubi: ['sucursal_id', 'motivo_cambio_ubicacion'],
    tec: [],
    adq: ['fecha_adquisicion', 'numero_factura', 'valor_adquisicion', 'garantia_hasta', 'vida_util'],
}, 'id');

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor, forceFormData: true };
    if (editando.value) {
        form.transform((datos) => ({ ...datos, _method: 'put' }));
        form.post(route('equipos.update', props.equipo.id), opciones);
    } else {
        form.post(route('equipos.store'), opciones);
    }
};

const cancelar = () =>
    router.visit(editando.value ? route('equipos.show', props.equipo.id) : route('equipos.por_sucursal'));
</script>

<template>
    <Head :title="editando ? `Editar ${equipo.codigo_activo}` : 'Nuevo equipo'" />

    <AppLayout
        :titulo="editando ? `Editar equipo ${equipo.codigo_activo}` : 'Nuevo equipo'"
        :descripcion="editando ? 'Actualiza los datos del activo; los cambios de ubicación quedan en el historial.' : 'Registra un activo nuevo con su identificación, ubicación y datos de adquisición.'"
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="foto-card">
                <div class="foto-card__fila">
                    <div v-if="fotoActual && !cambiandoFoto" class="foto-actual foto-actual--grande">
                        <img :src="route('documentos.ver', fotoActual.id)" alt="Foto de referencia actual" />
                        <div class="foto-actual__t">
                            <span class="foto-actual__l">Foto de referencia</span>
                            <a-button size="small" @click="cambiandoFoto = true">Cambiar foto</a-button>
                        </div>
                    </div>
                    <template v-else>
                        <span class="foto-card__ic"><CameraOutlined /></span>
                        <div class="foto-card__t">
                            <span class="foto-card__l">Foto de referencia <span class="foto-card__op">(opcional)</span></span>
                            <a-upload
                                :file-list="fotoLista"
                                :max-count="1"
                                accept="image/*"
                                :show-upload-list="false"
                                :before-upload="antesDeSubirFoto"
                            >
                                <a-button size="small">
                                    <template #icon><InboxOutlined /></template>
                                    {{ fotoLista.length ? fotoLista[0].name : 'Subir foto' }}
                                </a-button>
                            </a-upload>
                            <span class="foto-card__hint">JPG, PNG o WEBP · máx. 8 MB — ayuda a identificar el equipo de un vistazo en su expediente.</span>
                        </div>
                        <a-button v-if="cambiandoFoto" size="small" type="text" @click="cambiandoFoto = false">Cancelar</a-button>
                    </template>
                </div>
                <div v-if="form.errors.foto_referencia" class="mi-error">{{ form.errors.foto_referencia }}</div>
            </a-card>

            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva">
                    <a-tab-pane key="id">
                        <template #tab><span><ProfileOutlined /> Identificación</span></template>
                        <p class="tab-ayuda">Cómo se identifica el activo dentro del sistema — el código es único y es lo que se busca en todos los listados.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    label="Código del activo"
                                    name="codigo_activo"
                                    :extra="!editando ? 'Sugerido automáticamente; puedes aceptarlo, escribir otro o escanearlo.' : null"
                                    :validate-status="est('codigo_activo')"
                                    :help="form.errors.codigo_activo"
                                >
                                    <a-input
                                        v-model:value="form.codigo_activo"
                                        placeholder="p. ej. EQ-001"
                                        @keypress="soloCodigo"
                                        @paste="limpiarPegado('codigo')"
                                    >
                                        <template #prefix><BarcodeOutlined class="op-40" /></template>
                                        <template #suffix>
                                            <a-tooltip title="Escanear código de barras">
                                                <CameraOutlined class="lector-btn" @click="abrirLector('codigo_activo')" />
                                            </a-tooltip>
                                        </template>
                                    </a-input>
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item
                                    label="Código de barras"
                                    name="codigo_barras"
                                    extra="Solo si el equipo trae una etiqueta de barras propia del fabricante, distinta del código de activo."
                                    :validate-status="est('codigo_barras')"
                                    :help="form.errors.codigo_barras"
                                >
                                    <a-input
                                        v-model:value="form.codigo_barras"
                                        @keypress="soloCodigo"
                                        @paste="limpiarPegado('codigo')"
                                    >
                                        <template #suffix>
                                            <a-tooltip title="Escanear código de barras">
                                                <CameraOutlined class="lector-btn" @click="abrirLector('codigo_barras')" />
                                            </a-tooltip>
                                        </template>
                                    </a-input>
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
                                    <SelectCatalogo v-model:value="form.tipo_id" :options="catalogos.tipos" ruta="catalogos.tipos_equipo" etiqueta="tipo de equipo" etiqueta-plural="tipos de equipo" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="8">
                                <a-form-item label="Marca">
                                    <SelectCatalogo v-model:value="form.marca_id" :options="catalogos.marcas" ruta="catalogos.marcas" etiqueta="marca" />
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
                                <a-form-item label="Estado del equipo" extra="Si no cuenta como operativo, no se contará en los KPI de 'equipos operativos'.">
                                    <SelectCatalogo
                                        v-model:value="form.estado_id"
                                        :options="catalogos.estados"
                                        ruta="catalogos.estados_equipo"
                                        etiqueta="estado de equipo"
                                        etiqueta-plural="estados de equipo"
                                        :campos="[
                                            { name: 'color', label: 'Color', tipo: 'color' },
                                            { name: 'es_operativo', label: '¿Es operativo?', tipo: 'switch' },
                                        ]"
                                    />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="ubi">
                        <template #tab><span><EnvironmentOutlined /> Ubicación y responsable</span></template>
                        <p class="tab-ayuda">Dónde vive el equipo y quién responde por él — cambiar la ubicación después queda guardado en su historial.</p>
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
                                <a-form-item label="Ubicación" extra="Primero elige la sucursal para ver sus áreas." :validate-status="est('ubicacion_id')" :help="form.errors.ubicacion_id">
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
                                <a-form-item label="Responsable" extra="Persona de contacto del equipo — opcional.">
                                    <a-select v-model:value="form.responsable_id" :options="responsablesOpciones" allow-clear />
                                </a-form-item>
                            </a-col>
                            <a-col v-if="cambioUbicacion" :xs="24" :sm="12">
                                <a-form-item label="Motivo del cambio de ubicación" name="motivo_cambio_ubicacion" extra="Se registrará en el historial del equipo." :validate-status="est('motivo_cambio_ubicacion')" :help="form.errors.motivo_cambio_ubicacion">
                                    <a-input v-model:value="form.motivo_cambio_ubicacion" @keypress="soloTexto" @paste="limpiarPegado('texto')" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="tec">
                        <template #tab><span><SafetyCertificateOutlined /> Datos técnicos y normas</span></template>
                        <p class="tab-ayuda">Especificaciones libres del fabricante y las normas/procedimientos que aplican a este equipo.</p>
                        <CampoEspecificaciones v-model="form.especificaciones" class="mb-4" />

                        <a-form-item label="Normas aplicables" extra="Normas o procedimientos que este equipo debe cumplir.">
                            <a-select v-model:value="form.normas" :options="normasOpciones" mode="multiple" :show-search="false" allow-clear />
                        </a-form-item>
                        <a-form-item label="Observaciones" name="notas" :validate-status="est('notas')" :help="form.errors.notas">
                            <a-textarea v-model:value="form.notas" :auto-size="{ minRows: 2, maxRows: 6 }" show-count :maxlength="2000" />
                        </a-form-item>

                        <a-form-item v-if="!editando" class="plan-checkbox">
                            <a-checkbox v-model:checked="form.plan_preventivo">
                                Asignar un plan de mantenimiento preventivo desde ahora, calendarizado a partir de hoy
                            </a-checkbox>
                        </a-form-item>
                        <a-row v-if="!editando && form.plan_preventivo" :gutter="12">
                            <a-col :xs="24" :sm="10">
                                <a-form-item
                                    label="Tipo de mantenimiento"
                                    :validate-status="est('plan_tipo_mantenimiento_id')"
                                    :help="form.errors.plan_tipo_mantenimiento_id"
                                >
                                    <SelectCatalogo
                                        v-model:value="form.plan_tipo_mantenimiento_id"
                                        :options="catalogos.tiposMantenimiento"
                                        ruta="catalogos.tipos_mantenimiento"
                                        etiqueta="tipo de mantenimiento"
                                        :campos="[{ name: 'categoria', label: 'Categoría', tipo: 'select', opciones: [{ value: 'preventivo', label: 'Preventivo' }] }]"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="14" :sm="9">
                                <a-form-item
                                    label="Periodicidad"
                                    :validate-status="est('plan_tipo_frecuencia')"
                                    :help="form.errors.plan_tipo_frecuencia"
                                >
                                    <a-select v-model:value="form.plan_tipo_frecuencia" :options="opcionesFrecuenciaPlan" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="10" :sm="5">
                                <a-form-item
                                    label="Cada"
                                    extra="N unidades"
                                    :validate-status="est('plan_valor_frecuencia')"
                                    :help="form.errors.plan_valor_frecuencia"
                                >
                                    <a-input-number v-model:value="form.plan_valor_frecuencia" :min="1" :max="365" class="w-full" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="adq">
                        <template #tab><span><DollarOutlined /> Adquisición</span></template>
                        <p class="tab-ayuda">De dónde vino el equipo, cuánto costó y hasta cuándo tiene garantía — útil para reportes de inventario y vencimientos.</p>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Proveedor">
                                    <a-select v-model:value="form.proveedor_id" :options="catalogos.proveedores" :field-names="{ label: 'razon_social', value: 'id' }" allow-clear />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Fecha de adquisición" name="fecha_adquisicion" :validate-status="est('fecha_adquisicion')" :help="form.errors.fecha_adquisicion">
                                    <CampoFechaHora v-model="form.fecha_adquisicion" solo-fecha :max-fecha="hoyISO()" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Número de factura" name="numero_factura" :validate-status="est('numero_factura')" :help="form.errors.numero_factura">
                                    <a-input v-model:value="form.numero_factura" @keypress="soloCodigo" @paste="limpiarPegado('codigo')" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Valor de adquisición" extra="Se usa para el KPI de valor de inventario por sucursal." :validate-status="est('valor_adquisicion')" :help="form.errors.valor_adquisicion">
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
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Garantía hasta" name="garantia_hasta" :validate-status="est('garantia_hasta')" :help="form.errors.garantia_hasta">
                                    <CampoFechaHora v-model="form.garantia_hasta" solo-fecha :min-fecha="form.fecha_adquisicion || undefined" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Vida útil estimada" name="vida_util" :validate-status="est('vida_util')" :help="form.errors.vida_util">
                                    <a-input v-model:value="form.vida_util" placeholder="p. ej. 10 años" @keypress="soloTexto" @paste="limpiarPegado('texto')" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <a-card size="small" class="form-acciones">
                <a-space>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon><SaveOutlined /></template>
                        {{ editando ? 'Guardar cambios' : 'Registrar equipo' }}
                    </a-button>
                    <a-button size="large" @click="cancelar">Cancelar</a-button>
                </a-space>
            </a-card>
        </a-form>

        <LectorCodigoBarras ref="lector" @detectado="onDetectado" />
    </AppLayout>
</template>

<style scoped>
.op-40 {
    opacity: 0.4;
}
.foto-card {
    margin-bottom: 10px;
}
.foto-card__fila {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.foto-card__ic {
    flex: none;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--sigam-teal-700);
    background: var(--sigam-teal-050);
}
.foto-card__t {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
    min-width: 200px;
}
.foto-card__l {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--sigam-navy);
}
.foto-card__op {
    font-weight: 500;
    color: var(--sigam-tenue);
    text-transform: none;
}
.foto-card__hint {
    font-size: 11.5px;
    color: var(--sigam-tenue);
}
.foto-actual {
    display: flex;
    align-items: center;
    gap: 12px;
}
.foto-actual img {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--sigam-borde);
}
.foto-actual--grande {
    flex: 1;
}
.foto-actual__t {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.foto-actual__l {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--sigam-navy);
}
.plan-checkbox {
    margin-bottom: 6px;
}
.w-full {
    width: 100%;
}
.lector-btn {
    cursor: pointer;
    color: var(--sigam-tenue);
    transition: color 0.15s ease;
}
.lector-btn:hover {
    color: var(--sigam-teal);
}
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
.mi-error {
    color: #d64545;
    font-size: 12.5px;
    margin-top: 6px;
}
</style>
