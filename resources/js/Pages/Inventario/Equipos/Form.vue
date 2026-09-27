<script setup>
import { computed, reactive, ref, watch, onBeforeUnmount } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BarcodeOutlined,
    CalendarOutlined,
    CameraOutlined,
    CheckCircleFilled,
    CloseCircleFilled,
    DollarOutlined,
    EnvironmentOutlined,
    ExclamationCircleFilled,
    InboxOutlined,
    ProfileOutlined,
    SaveOutlined,
    SafetyCertificateOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CampoEspecificaciones from '@/Components/CampoEspecificaciones.vue';
import CampoFechaHora from '@/Components/CampoFechaHora.vue';
import SelectCatalogo from '@/Components/SelectCatalogo.vue';
import LectorCodigoBarras from '@/Components/LectorCodigoBarras.vue';
import BotonTomarFoto from '@/Components/BotonTomarFoto.vue';
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

// ==========================================================
// Foto de referencia con PREVISUALIZACIÓN inmediata
// ==========================================================
const fotoLista = ref([]);
const fotoActual = computed(() => props.equipo?.foto_referencia ?? null);
const cambiandoFoto = ref(false);
const fotoPreviewUrl = ref(null);

const antesDeSubirFoto = (file) => {
    fotoLista.value = [file];
    form.foto_referencia = file;

    if (fotoPreviewUrl.value) URL.revokeObjectURL(fotoPreviewUrl.value);
    fotoPreviewUrl.value = URL.createObjectURL(file);

    return false;
};

const quitarFoto = () => {
    fotoLista.value = [];
    form.foto_referencia = null;
    if (fotoPreviewUrl.value) {
        URL.revokeObjectURL(fotoPreviewUrl.value);
        fotoPreviewUrl.value = null;
    }
};

const fotoMostrada = computed(() => {
    if (fotoPreviewUrl.value) return fotoPreviewUrl.value;
    if (fotoActual.value) return route('documentos.ver', fotoActual.value.id);
    return null;
});

onBeforeUnmount(() => {
    if (fotoPreviewUrl.value) URL.revokeObjectURL(fotoPreviewUrl.value);
});

watch(cambiandoFoto, (val) => {
    if (!val && fotoPreviewUrl.value) {
        URL.revokeObjectURL(fotoPreviewUrl.value);
        fotoPreviewUrl.value = null;
        fotoLista.value = [];
        form.foto_referencia = null;
    }
});

// ==========================================================

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

// --- Pestaña activa ------------------------------------------------
const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas({
    id: ['codigo_activo', 'codigo_barras', 'descripcion', 'modelo', 'numero_serie'],
    ubi: ['sucursal_id', 'motivo_cambio_ubicacion'],
    tec: [],
    adq: ['fecha_adquisicion', 'numero_factura', 'valor_adquisicion', 'garantia_hasta', 'vida_util'],
}, 'id');

const tabs = computed(() => [
    {
        key: 'id',
        icono: ProfileOutlined,
        titulo: 'Identificación',
        subtitulo: 'Códigos y datos básicos',
        color: '#0d84c9',
        campos: ['codigo_activo', 'codigo_barras', 'descripcion', 'modelo', 'numero_serie'],
    },
    {
        key: 'ubi',
        icono: EnvironmentOutlined,
        titulo: 'Ubicación',
        subtitulo: 'Sucursal y responsable',
        color: '#1f9e86',
        campos: ['sucursal_id', 'ubicacion_id', 'responsable_id', 'motivo_cambio_ubicacion'],
    },
    {
        key: 'tec',
        icono: SafetyCertificateOutlined,
        titulo: 'Técnicos',
        subtitulo: 'Especificaciones y normas',
        color: '#6b4bc9',
        campos: ['especificaciones', 'normas', 'notas'],
    },
    {
        key: 'adq',
        icono: DollarOutlined,
        titulo: 'Adquisición',
        subtitulo: 'Compra y garantía',
        color: '#e08a1e',
        campos: ['proveedor_id', 'fecha_adquisicion', 'numero_factura', 'valor_adquisicion', 'garantia_hasta', 'vida_util'],
    },
]);

const estadoTab = (tab) => {
    const conError = tab.campos.some((c) => form.errors[c]);
    if (conError) return 'error';
    if (tab.key === 'id' && form.codigo_activo && form.descripcion) return 'ok';
    if (tab.key === 'ubi' && form.sucursal_id) return 'ok';
    return null;
};

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
        <a-form :model="form" :rules="reglas" layout="vertical" class="equipo-form" @finish="enviar"
            @finish-failed="onFinishFailed">

            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva" class="form-tabs">
                    <a-tab-pane v-for="t in tabs" :key="t.key">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': t.color }">
                                <!-- Icono con badge anclado a esquina inferior-derecha -->
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico">
                                        <component :is="t.icono" />
                                    </span>
                                    <span v-if="estadoTab(t) === 'ok'"
                                        class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                    <span v-else-if="estadoTab(t) === 'error'"
                                        class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>

                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ t.titulo }}</span>
                                    <span class="tab-label__sub">{{ t.subtitulo }}</span>
                                </span>
                            </span>
                        </template>

                        <!-- =============================================
                             IDENTIFICACIÓN
                             ============================================= -->
                        <div v-if="t.key === 'id'" class="tab-body">
                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <BarcodeOutlined /> Códigos del activo
                                </div>
                                <div class="codigos-grid">
                                    <!-- FOTO a la izquierda -->
                                    <div class="codigos-grid__foto">
                                        <div class="foto-mini">
                                            <div class="foto-mini__preview"
                                                :class="{ 'foto-mini__preview--vacia': !fotoMostrada }">
                                                <img v-if="fotoMostrada" :src="fotoMostrada"
                                                    alt="Foto de referencia" />
                                                <template v-else>
                                                    <CameraOutlined />
                                                    <span class="foto-mini__hint">Sin foto</span>
                                                </template>
                                            </div>

                                            <template v-if="fotoActual && !cambiandoFoto && !fotoPreviewUrl">
                                                <a-button size="small" block @click="cambiandoFoto = true">
                                                    Cambiar foto
                                                </a-button>
                                            </template>

                                            <template v-else>
                                                <a-upload :file-list="fotoLista" :max-count="1"
                                                    accept="image/*" :show-upload-list="false"
                                                    :before-upload="antesDeSubirFoto">
                                                    <a-button size="small" block>
                                                        <template #icon><InboxOutlined /></template>
                                                        {{ fotoMostrada ? 'Cambiar' : 'Subir foto' }}
                                                    </a-button>
                                                </a-upload>

                                                <BotonTomarFoto block titulo="Foto del equipo"
                                                    @capturada="antesDeSubirFoto" />

                                                <div v-if="fotoPreviewUrl || cambiandoFoto"
                                                    class="foto-mini__acciones">
                                                    <a-button v-if="fotoPreviewUrl" size="small" type="text"
                                                        danger @click="quitarFoto">
                                                        Quitar
                                                    </a-button>
                                                    <a-button v-if="cambiandoFoto" size="small" type="text"
                                                        @click="cambiandoFoto = false">
                                                        Cancelar
                                                    </a-button>
                                                </div>
                                            </template>

                                            <div v-if="form.errors.foto_referencia" class="mi-error">
                                                {{ form.errors.foto_referencia }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- INPUTS a la derecha -->
                                    <div class="codigos-grid__inputs">
                                        <a-row :gutter="12">
                                            <a-col :xs="24" :sm="12">
                                                <a-form-item label="Código del activo" name="codigo_activo"
                                                    :extra="!editando ? 'Sugerido automáticamente; puedes escanearlo.' : null"
                                                    :validate-status="est('codigo_activo')"
                                                    :help="form.errors.codigo_activo">
                                                    <a-input v-model:value="form.codigo_activo"
                                                        placeholder="p. ej. EQ-001" @keypress="soloCodigo"
                                                        @paste="limpiarPegado('codigo')">
                                                        <template #prefix>
                                                            <BarcodeOutlined class="op-40" />
                                                        </template>
                                                        <template #suffix>
                                                            <a-tooltip title="Escanear código de barras">
                                                                <CameraOutlined class="lector-btn"
                                                                    @click="abrirLector('codigo_activo')" />
                                                            </a-tooltip>
                                                        </template>
                                                    </a-input>
                                                </a-form-item>
                                            </a-col>
                                            <a-col :xs="24" :sm="12">
                                                <a-form-item label="Código de barras" name="codigo_barras"
                                                    extra="Solo si el equipo trae etiqueta del fabricante."
                                                    :validate-status="est('codigo_barras')"
                                                    :help="form.errors.codigo_barras">
                                                    <a-input v-model:value="form.codigo_barras"
                                                        @keypress="soloCodigo"
                                                        @paste="limpiarPegado('codigo')">
                                                        <template #suffix>
                                                            <a-tooltip title="Escanear código de barras">
                                                                <CameraOutlined class="lector-btn"
                                                                    @click="abrirLector('codigo_barras')" />
                                                            </a-tooltip>
                                                        </template>
                                                    </a-input>
                                                </a-form-item>
                                            </a-col>
                                        </a-row>

                                        <a-row :gutter="12">
                                            <a-col :xs="24" :sm="12">
                                                <a-form-item label="Número de serie" name="numero_serie"
                                                    :validate-status="est('numero_serie')"
                                                    :help="form.errors.numero_serie">
                                                    <a-input v-model:value="form.numero_serie"
                                                        @keypress="soloCodigo"
                                                        @paste="limpiarPegado('codigo')" />
                                                </a-form-item>
                                            </a-col>
                                            <a-col :xs="24" :sm="12">
                                                <a-form-item label="Estado del equipo"
                                                    extra="Si no cuenta como operativo, no se contará en los KPI.">
                                                    <SelectCatalogo v-model:value="form.estado_id"
                                                        :options="catalogos.estados"
                                                        ruta="catalogos.estados_equipo"
                                                        etiqueta="estado de equipo"
                                                        etiqueta-plural="estados de equipo" :campos="[
                                                            { name: 'color', label: 'Color', tipo: 'color' },
                                                            { name: 'es_operativo', label: '¿Es operativo?', tipo: 'switch' },
                                                        ]" />
                                                </a-form-item>
                                            </a-col>
                                        </a-row>
                                    </div>
                                </div>
                            </div>

                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <ProfileOutlined /> Descripción y clasificación
                                </div>
                                <a-row :gutter="12">
                                    <a-col :span="24">
                                        <a-form-item label="Descripción" name="descripcion"
                                            :validate-status="est('descripcion')"
                                            :help="form.errors.descripcion">
                                            <a-input v-model:value="form.descripcion"
                                                placeholder="Nombre o descripción del equipo" @keypress="soloTexto"
                                                @paste="limpiarPegado('texto')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="8">
                                        <a-form-item label="Tipo">
                                            <SelectCatalogo v-model:value="form.tipo_id"
                                                :options="catalogos.tipos" ruta="catalogos.tipos_equipo"
                                                etiqueta="tipo de equipo" etiqueta-plural="tipos de equipo" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="8">
                                        <a-form-item label="Marca">
                                            <SelectCatalogo v-model:value="form.marca_id"
                                                :options="catalogos.marcas" ruta="catalogos.marcas"
                                                etiqueta="marca" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="8">
                                        <a-form-item label="Modelo" name="modelo" :validate-status="est('modelo')"
                                            :help="form.errors.modelo">
                                            <a-input v-model:value="form.modelo" @keypress="soloCodigo"
                                                @paste="limpiarPegado('codigo')" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </div>
                        </div>

                        <!-- =============================================
                             UBICACIÓN
                             ============================================= -->
                        <div v-else-if="t.key === 'ubi'" class="tab-body">
                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <EnvironmentOutlined /> Ubicación física
                                </div>
                                <a-row :gutter="12">
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item label="Sucursal" name="sucursal_id"
                                            :validate-status="est('sucursal_id')"
                                            :help="form.errors.sucursal_id">
                                            <a-select v-model:value="form.sucursal_id"
                                                :options="catalogos.sucursales"
                                                :field-names="{ label: 'nombre', value: 'id' }"
                                                @change="form.ubicacion_id = undefined" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item label="Ubicación"
                                            extra="Primero elige la sucursal para ver sus áreas."
                                            :validate-status="est('ubicacion_id')"
                                            :help="form.errors.ubicacion_id">
                                            <a-select v-model:value="form.ubicacion_id"
                                                :options="ubicacionesDeSucursal"
                                                :field-names="{ label: 'nombre', value: 'id' }"
                                                :disabled="!form.sucursal_id" allow-clear />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </div>

                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <ProfileOutlined /> Resguardo
                                </div>
                                <a-row :gutter="12">
                                    <a-col :xs="24" :sm="cambioUbicacion ? 12 : 24">
                                        <a-form-item label="Responsable"
                                            extra="Persona de contacto del equipo — opcional.">
                                            <a-select v-model:value="form.responsable_id"
                                                :options="responsablesOpciones" allow-clear />
                                        </a-form-item>
                                    </a-col>
                                    <a-col v-if="cambioUbicacion" :xs="24" :sm="12">
                                        <a-form-item label="Motivo del cambio de ubicación"
                                            name="motivo_cambio_ubicacion"
                                            extra="Se registrará en el historial del equipo."
                                            :validate-status="est('motivo_cambio_ubicacion')"
                                            :help="form.errors.motivo_cambio_ubicacion">
                                            <a-input v-model:value="form.motivo_cambio_ubicacion"
                                                @keypress="soloTexto" @paste="limpiarPegado('texto')" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </div>
                        </div>

                        <!-- =============================================
                             TÉCNICOS
                             ============================================= -->
                        <div v-else-if="t.key === 'tec'" class="tab-body">
                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <ToolOutlined /> Especificaciones del fabricante
                                </div>
                                <CampoEspecificaciones v-model="form.especificaciones" />
                            </div>

                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <SafetyCertificateOutlined /> Normas y observaciones
                                </div>
                                <a-form-item label="Normas aplicables"
                                    extra="Normas o procedimientos que este equipo debe cumplir.">
                                    <a-select v-model:value="form.normas" :options="normasOpciones"
                                        mode="multiple" :show-search="false" allow-clear />
                                </a-form-item>
                                <a-form-item label="Observaciones" name="notas"
                                    :validate-status="est('notas')" :help="form.errors.notas">
                                    <a-textarea v-model:value="form.notas"
                                        :auto-size="{ minRows: 2, maxRows: 6 }" show-count :maxlength="2000" />
                                </a-form-item>
                            </div>

                            <div v-if="!editando" class="campo-grupo campo-grupo--destacado">
                                <div class="campo-grupo__titulo">
                                    <CalendarOutlined /> Plan de mantenimiento preventivo
                                </div>
                                <a-form-item class="plan-checkbox">
                                    <a-checkbox v-model:checked="form.plan_preventivo">
                                        Asignar un plan de mantenimiento preventivo desde ahora, calendarizado a partir de hoy
                                    </a-checkbox>
                                </a-form-item>
                                <a-row v-if="form.plan_preventivo" :gutter="12">
                                    <a-col :xs="24" :sm="10">
                                        <a-form-item label="Tipo de mantenimiento"
                                            :validate-status="est('plan_tipo_mantenimiento_id')"
                                            :help="form.errors.plan_tipo_mantenimiento_id">
                                            <SelectCatalogo v-model:value="form.plan_tipo_mantenimiento_id"
                                                :options="catalogos.tiposMantenimiento"
                                                ruta="catalogos.tipos_mantenimiento"
                                                etiqueta="tipo de mantenimiento" :campos="[{
                                                    name: 'categoria',
                                                    label: 'Categoría',
                                                    tipo: 'select',
                                                    opciones: [{ value: 'preventivo', label: 'Preventivo' }],
                                                }]" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="14" :sm="9">
                                        <a-form-item label="Periodicidad"
                                            :validate-status="est('plan_tipo_frecuencia')"
                                            :help="form.errors.plan_tipo_frecuencia">
                                            <a-select v-model:value="form.plan_tipo_frecuencia"
                                                :options="opcionesFrecuenciaPlan" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="10" :sm="5">
                                        <a-form-item label="Cada" extra="N unidades"
                                            :validate-status="est('plan_valor_frecuencia')"
                                            :help="form.errors.plan_valor_frecuencia">
                                            <a-input-number v-model:value="form.plan_valor_frecuencia" :min="1"
                                                :max="365" class="w-full" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </div>
                        </div>

                        <!-- =============================================
                             ADQUISICIÓN
                             ============================================= -->
                        <div v-else-if="t.key === 'adq'" class="tab-body">
                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <DollarOutlined /> Compra
                                </div>
                                <a-row :gutter="12">
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item label="Proveedor">
                                            <a-select v-model:value="form.proveedor_id"
                                                :options="catalogos.proveedores"
                                                :field-names="{ label: 'razon_social', value: 'id' }"
                                                allow-clear />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item label="Fecha de adquisición" name="fecha_adquisicion"
                                            :validate-status="est('fecha_adquisicion')"
                                            :help="form.errors.fecha_adquisicion">
                                            <CampoFechaHora v-model="form.fecha_adquisicion" solo-fecha
                                                :max-fecha="hoyISO()" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </div>

                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <ProfileOutlined /> Documentación y valor
                                </div>
                                <a-row :gutter="12">
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item label="Número de factura" name="numero_factura"
                                            :validate-status="est('numero_factura')"
                                            :help="form.errors.numero_factura">
                                            <a-input v-model:value="form.numero_factura" @keypress="soloCodigo"
                                                @paste="limpiarPegado('codigo')" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item label="Valor de adquisición"
                                            extra="Se usa para el KPI de valor de inventario por sucursal."
                                            :validate-status="est('valor_adquisicion')"
                                            :help="form.errors.valor_adquisicion">
                                            <a-input-number v-model:value="form.valor_adquisicion" class="w-full"
                                                :min="0" :max="99999999999" :precision="2" :step="100" prefix="$"
                                                :formatter="(v) => `${v}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                                                :parser="(v) => v.replace(/,/g, '')" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </div>

                            <div class="campo-grupo">
                                <div class="campo-grupo__titulo">
                                    <SafetyCertificateOutlined /> Garantía y vida útil
                                </div>
                                <a-row :gutter="12">
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item label="Garantía hasta" name="garantia_hasta"
                                            :validate-status="est('garantia_hasta')"
                                            :help="form.errors.garantia_hasta">
                                            <CampoFechaHora v-model="form.garantia_hasta" solo-fecha
                                                :min-fecha="form.fecha_adquisicion || undefined" />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item label="Vida útil estimada" name="vida_util"
                                            :validate-status="est('vida_util')" :help="form.errors.vida_util">
                                            <a-input v-model:value="form.vida_util"
                                                placeholder="p. ej. 10 años" @keypress="soloTexto"
                                                @paste="limpiarPegado('texto')" />
                                        </a-form-item>
                                    </a-col>
                                </a-row>
                            </div>
                        </div>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <div class="form-acciones">
                <div class="form-acciones__info">
                    <template v-if="editando">
                        <CheckCircleFilled class="form-acciones__check" />
                        Estás editando <strong>{{ equipo.codigo_activo }}</strong>
                    </template>
                    <template v-else>
                        Completa los pasos y guarda para registrar el equipo.
                    </template>
                </div>
                <a-space>
                    <a-button size="large" @click="cancelar">
                        <template #icon><CloseCircleFilled /></template>
                        Cancelar
                    </a-button>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon><SaveOutlined /></template>
                        {{ editando ? 'Guardar cambios' : 'Registrar equipo' }}
                    </a-button>
                </a-space>
            </div>
        </a-form>

        <LectorCodigoBarras ref="lector" @detectado="onDetectado" />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Global
   ========================================================== */
.equipo-form {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding-bottom: 90px;
}

.op-40 {
    opacity: 0.4;
}

.w-full {
    width: 100%;
}

.mi-error {
    color: #d64545;
    font-size: 12px;
    margin-top: 6px;
    text-align: center;
}

.lector-btn {
    cursor: pointer;
    color: var(--sigam-tenue);
    transition: color 0.15s ease, transform 0.15s ease;
}

.lector-btn:hover {
    color: #0d84c9;
    transform: scale(1.15);
}

/* ==========================================================
   Scroll global tipo iOS
   ========================================================== */
*::-webkit-scrollbar {
    width: 12px;
    height: 12px;
}

*::-webkit-scrollbar-track {
    background: transparent;
}

*::-webkit-scrollbar-thumb {
    background: rgba(15, 37, 71, 0.32);
    border-radius: 999px;
    border: 3px solid transparent;
    background-clip: padding-box;
    transition: background 0.15s ease;
}

*::-webkit-scrollbar-thumb:hover {
    background: rgba(15, 37, 71, 0.5);
    background-clip: padding-box;
}

*::-webkit-scrollbar-thumb:active {
    background: rgba(15, 37, 71, 0.65);
    background-clip: padding-box;
}

* {
    scrollbar-width: thin;
    scrollbar-color: rgba(15, 37, 71, 0.32) transparent;
}

/* ==========================================================
   Card principal
   ========================================================== */
.form-card {
    border-radius: 14px;
    border: 1px solid var(--sigam-borde-suave);
    box-shadow: 0 2px 10px -6px rgba(15, 37, 71, 0.15);
}

.form-card :deep(.ant-card-body) {
    padding: 0;
}

/* ==========================================================
   TABS — alineación perfecta
   ========================================================== */
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

/* Cada tab: altura fija, contenido centrado verticalmente */
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

/* Label completo */
.tab-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    height: 100%;
    line-height: 1;
}

/* 👇 Wrapper relativo para anclar el badge correctamente */
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

/* 👇 Badge anclado a la esquina inferior-derecha del icono */
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

/* Columna de textos */
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

/* ==========================================================
   Cuerpo de cada pestaña
   ========================================================== */
.tab-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    animation: fadeTab 0.28s ease;
}

@keyframes fadeTab {
    from {
        opacity: 0;
        transform: translateY(4px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ==========================================================
   Grupos de campos
   ========================================================== */
.campo-grupo {
    padding: 14px 16px;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 12px;
    background: #fbfcfe;
}

.campo-grupo--destacado {
    background: linear-gradient(180deg, #f0f7fd 0%, #eaf3fb 100%);
    border-color: #cfe3f2;
}

.campo-grupo__titulo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #0d6fae;
}

.campo-grupo__titulo .anticon {
    font-size: 14px;
    color: #0d84c9;
}

.campo-grupo :deep(.ant-form-item) {
    margin-bottom: 12px;
}

.campo-grupo :deep(.ant-form-item:last-child) {
    margin-bottom: 0;
}

.campo-grupo :deep(.ant-form-item-label > label) {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 12.5px;
}

.campo-grupo :deep(.ant-form-item-extra) {
    font-size: 11.5px;
    color: var(--sigam-tenue);
    margin-top: 3px;
}

.plan-checkbox {
    margin-bottom: 8px;
}

/* ==========================================================
   Grid: FOTO a la izquierda + INPUTS a la derecha
   ========================================================== */
.codigos-grid {
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: 18px;
    align-items: start;
}

.codigos-grid__foto {
    min-width: 0;
}

.codigos-grid__inputs {
    min-width: 0;
}

.foto-mini {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 180px;
}

.foto-mini__preview {
    position: relative;
    width: 180px;
    height: 180px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #cbeadd;
    background: linear-gradient(180deg, #f0f9f6 0%, #eaf4fb 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: inset 0 1px 3px rgba(15, 37, 71, 0.06);
    transition: box-shadow 0.2s ease;
}

.foto-mini__preview:hover {
    box-shadow: inset 0 1px 3px rgba(15, 37, 71, 0.06), 0 4px 12px -6px rgba(13, 132, 201, 0.25);
}

.foto-mini__preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.foto-mini__preview--vacia {
    color: #94a3b8;
    font-size: 38px;
    gap: 6px;
}

.foto-mini__hint {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
}

.foto-mini__acciones {
    display: flex;
    gap: 4px;
    justify-content: center;
    flex-wrap: wrap;
}

/* ==========================================================
   Footer sticky
   ========================================================== */
.form-acciones {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-top: 1px solid var(--sigam-borde-suave);
    box-shadow: 0 -6px 20px -12px rgba(15, 37, 71, 0.25);
}

.form-acciones__info {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}

.form-acciones__info strong {
    color: var(--sigam-navy);
    font-weight: 800;
}

.form-acciones__check {
    color: #1f9e86;
    font-size: 15px;
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 991px) {
    .tab-label__sub {
        display: none;
    }
}

@media (max-width: 640px) {
    .form-acciones {
        flex-direction: column;
        align-items: stretch;
        padding: 10px 12px;
        gap: 10px;
    }

    .form-acciones__info {
        justify-content: center;
        font-size: 12px;
    }

    .tab-body {
        padding: 14px 12px;
    }

    .campo-grupo {
        padding: 12px;
    }

    .tab-label__col {
        display: none;
    }

    .form-tabs :deep(.ant-tabs-tab) {
        height: 50px;
    }

    .form-tabs :deep(.ant-tabs-tab-btn) {
        padding: 0 10px !important;
    }

    /* Foto arriba, campos abajo solo en móvil real — en desktop/tablet
       (>640px) la foto se queda a la izquierda y los campos a la derecha. */
    .codigos-grid {
        grid-template-columns: 1fr;
    }

    .codigos-grid__foto {
        max-width: 180px;
        margin: 0 auto;
    }
}
</style>