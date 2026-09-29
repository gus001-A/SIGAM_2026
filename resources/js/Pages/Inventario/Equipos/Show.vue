<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ApartmentOutlined, BankOutlined, BarcodeOutlined, CalendarOutlined,
    ClockCircleOutlined, DeleteOutlined, DollarOutlined, EditOutlined, EllipsisOutlined,
    EnvironmentOutlined, EyeOutlined, FieldNumberOutlined, FileDoneOutlined, FileTextOutlined,
    FormOutlined, HistoryOutlined, QrcodeOutlined, SafetyCertificateOutlined,
    ShopOutlined, StopOutlined, TagOutlined, ToolOutlined, UndoOutlined, UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import ModalQr from '@/Components/ModalQr.vue';
import ModalBajaEquipo from '@/Components/ModalBajaEquipo.vue';
import ModalRestaurarEquipo from '@/Components/ModalRestaurarEquipo.vue';
import ModalFicha from '@/Components/ModalFicha.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    equipo: { type: Object, required: true },
    estados: { type: Array, default: () => [] },
    mantenimientos: { type: Array, default: () => [] },
    solicitudes: { type: Array, default: () => [] },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const confirmar = ref(null);
const modalQr = ref(null);
const modalBaja = ref(null);
const modalRestaurar = ref(null);
const modalHistorial = ref(false);
const modalMantenimientos = ref(false);
const modalPlan = ref(false);
const modalDocumentos = ref(false);
const modalSolicitudes = ref(false);
const modalFoto = ref(false);
const modalEvidencia = ref(null);

const dadoDeBaja = computed(() => !!props.equipo.deleted_at);
const NA = 'No especificado';
const moneda = (v) => v == null ? NA : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v);
const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : NA);
const dato = (v) => v || NA;

const fechaHora = (v) => {
    if (!v) return NA;
    return new Date(v).toLocaleString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const enGarantia = computed(() => props.equipo.garantia_hasta && new Date(props.equipo.garantia_hasta) > new Date());

const datosIdentificacion = computed(() => [
    { icono: TagOutlined, label: 'Tipo', valor: dato(props.equipo.tipo?.nombre), color: '#0d84c9' },
    { icono: ShopOutlined, label: 'Marca', valor: dato(props.equipo.marca?.nombre), color: '#6b4bc9' },
    { icono: FieldNumberOutlined, label: 'Modelo', valor: dato(props.equipo.modelo), color: '#1f9e86' },
    { icono: BarcodeOutlined, label: 'N.º serie', valor: dato(props.equipo.numero_serie), color: '#173a5f' },
]);

const datosAdquisicion = computed(() => [
    { icono: ShopOutlined, label: 'Proveedor', valor: dato(props.equipo.proveedor?.razon_social), color: '#6b4bc9' },
    { icono: CalendarOutlined, label: 'Adquirido', valor: fecha(props.equipo.fecha_adquisicion), color: '#0d84c9' },
    { icono: FileDoneOutlined, label: 'Factura', valor: dato(props.equipo.numero_factura), color: '#173a5f' },
    { icono: DollarOutlined, label: 'Valor', valor: moneda(props.equipo.valor_adquisicion), color: '#1f9e86' },
    { icono: HistoryOutlined, label: 'Vida útil', valor: dato(props.equipo.vida_util), color: '#e08a1e' },
    { icono: SafetyCertificateOutlined, label: 'Garantía', valor: fecha(props.equipo.garantia_hasta), color: enGarantia.value ? '#1f9e86' : '#d64545' },
]);

const datosUbicacion = computed(() => [
    { icono: BankOutlined, label: 'Sucursal', valor: dato(props.equipo.sucursal?.nombre), color: '#0d84c9' },
    { icono: ApartmentOutlined, label: 'Ubicación', valor: dato(props.equipo.ubicacion?.nombre), color: '#1f9e86' },
    { icono: UserOutlined, label: 'Responsable', valor: dato(props.equipo.responsable?.nombre), color: '#e08a1e' },
]);

const especificaciones = computed(() => {
    const entries = Object.entries(props.equipo.especificaciones ?? {});
    return entries.map(([clave, valor]) => ({
        clave: clave.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
        valor: typeof valor === 'object' ? JSON.stringify(valor) : String(valor),
    }));
});

const fotoReferencia = computed(() => (props.equipo.documentos ?? []).find((d) => d.pivot?.rol === 'foto_referencia'));

const evidenciasBaja = computed(() =>
    (props.equipo.documentos ?? []).filter((d) => {
        const rol = d.pivot?.rol ?? d.categoria ?? '';
        return rol === 'baja' || rol === 'evidencia_baja' || rol === 'evidencia';
    }),
);

const abrirEvidencia = (doc) => {
    modalEvidencia.value = doc;
};

const accesos = computed(() => [
    { clave: 'historial', etiqueta: 'Historial', valor: props.equipo.historialUbicacion?.length ?? 0, icono: EnvironmentOutlined, color: '#0d84c9', abrir: () => (modalHistorial.value = true) },
    { clave: 'mant', etiqueta: 'Mantenim.', valor: props.mantenimientos.length, icono: ToolOutlined, color: '#1f9e86', abrir: () => (modalMantenimientos.value = true) },
    { clave: 'plan', etiqueta: 'Plan prev.', valor: props.equipo.planes?.length ?? 0, icono: CalendarOutlined, color: '#e08a1e', abrir: () => (modalPlan.value = true) },
    { clave: 'doc', etiqueta: 'Documentos', valor: props.equipo.documentos?.length ?? 0, icono: FileTextOutlined, color: '#6b4bc9', abrir: () => (modalDocumentos.value = true) },
    { clave: 'sol', etiqueta: 'Solicitudes', valor: props.solicitudes.length, icono: FormOutlined, color: '#a86717', abrir: () => (modalSolicitudes.value = true) },
]);

const irA = (nombre, params) => router.visit(route(nombre, params));

const reactivar = () => modalRestaurar.value.abrir(props.equipo);

const menuAcciones = [{ key: 'baja', label: 'Dar de baja', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && modalBaja.value.abrir(props.equipo);
</script>

<template>

    <Head :title="`Equipo ${equipo.codigo_activo}`" />

    <AppLayout>
        <div class="ficha-compacta">
            <FichaEncabezado :titulo="equipo.codigo_activo" :subtitulo="equipo.descripcion" :icono="ToolOutlined"
                :color="equipo.estado?.color || '#1e5eb8'" volver="equipos.por_sucursal"
                :volver-params="{ sucursal_id: equipo.sucursal_id }" :sello="sello">
                <template #tags>
                    <a-tag v-if="dadoDeBaja" color="error">
                        <StopOutlined /> Dado de baja
                    </a-tag>
                    <a-tag v-else-if="equipo.estado" :color="equipo.estado.color || 'default'">
                        {{ equipo.estado.nombre }}
                    </a-tag>
                    <a-tag v-if="enGarantia" color="green">
                        <SafetyCertificateOutlined /> En garantía
                    </a-tag>
                </template>
                <template #acciones>
                    <template v-if="dadoDeBaja">
                        <!-- Botón Reactivar -->
                        <button
                            v-if="puede('equipos.editar')"
                            type="button"
                            class="btn-hero btn-hero--primary"
                            style="--hc: #1f9e86; --hc2: #16806c"
                            @click="reactivar"
                        >
                            <span class="btn-hero__ic">
                                <UndoOutlined />
                            </span>
                            <span class="btn-hero__txt">
                                <span class="btn-hero__l">Reactivar</span>
                                <span class="btn-hero__s">Equipo</span>
                            </span>
                        </button>
                    </template>
                    <template v-else>
                        <!-- Botón QR -->
                        <button
                            type="button"
                            class="btn-hero btn-hero--default"
                            style="--hc: #6b4bc9; --hc2: #563a9e"
                            @click="modalQr.abrir(equipo.id)"
                        >
                            <span class="btn-hero__ic">
                                <QrcodeOutlined />
                            </span>
                            <span class="btn-hero__txt">
                                <span class="btn-hero__l">QR</span>
                                <span class="btn-hero__s">Código</span>
                            </span>
                        </button>

                        <!-- Botón Solicitar -->
                        <button
                            v-if="puede('solicitudes.crear')"
                            type="button"
                            class="btn-hero btn-hero--default"
                            style="--hc: #e08a1e; --hc2: #a86717"
                            @click="router.visit(route('solicitudes.create', { equipo_id: equipo.id }))"
                        >
                            <span class="btn-hero__ic">
                                <FormOutlined />
                            </span>
                            <span class="btn-hero__txt">
                                <span class="btn-hero__l">Solicitar</span>
                                <span class="btn-hero__s">Servicio</span>
                            </span>
                        </button>

                        <!-- Botón Editar -->
                        <button
                            v-if="puede('equipos.editar')"
                            type="button"
                            class="btn-hero btn-hero--primary"
                            style="--hc: #0d84c9; --hc2: #0a6ba6"
                            @click="irA('equipos.edit', equipo.id)"
                        >
                            <span class="btn-hero__ic">
                                <EditOutlined />
                            </span>
                            <span class="btn-hero__txt">
                                <span class="btn-hero__l">Editar</span>
                                <span class="btn-hero__s">Equipo</span>
                            </span>
                        </button>

                        <!-- Dropdown de acciones -->
                        <a-dropdown v-if="puede('equipos.desactivar')">
                            <button type="button" class="btn-more">
                                <EllipsisOutlined />
                            </button>
                            <template #overlay>
                                <a-menu :items="menuAcciones" @click="onMenuAccion" />
                            </template>
                        </a-dropdown>
                    </template>
                </template>
            </FichaEncabezado>

            <!-- KPIs con colores sólidos -->
            <div class="kpis">
                <button v-for="a in accesos" :key="a.clave" type="button" class="kpi" :style="{ '--acc': a.color }"
                    @click="a.abrir()">
                    <div class="kpi__icono">
                        <component :is="a.icono" />
                    </div>
                    <div class="kpi__txt">
                        <div class="kpi__valor">{{ a.valor }}</div>
                        <div class="kpi__etq">{{ a.etiqueta }}</div>
                    </div>
                </button>
            </div>

            <!-- Grid principal -->
            <div class="grid-ficha">
                <!-- COLUMNA IZQUIERDA -->
                <div class="col-izq">
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <TagOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Identificación</div>
                                <div class="card__sub">Datos técnicos del equipo</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="ident">
                                <button v-if="fotoReferencia" type="button" class="ident__foto"
                                    @click="modalFoto = true">
                                    <img :src="route('documentos.ver', fotoReferencia.id)" alt="Foto del equipo" />
                                    <span class="ident__zoom">
                                        <FileTextOutlined />
                                    </span>
                                </button>
                                <div v-else class="ident__foto ident__foto--vacia">
                                    <ToolOutlined />
                                </div>
                                <div class="mini-grid mini-grid--2">
                                    <div v-for="d in datosIdentificacion" :key="d.label" class="mini"
                                        :style="{ '--c': d.color }">
                                        <span class="mini__ic">
                                            <component :is="d.icono" />
                                        </span>
                                        <span class="mini__t">
                                            <span class="mini__l">{{ d.label }}</span>
                                            <span class="mini__v">{{ d.valor }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <EnvironmentOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Ubicación y responsable</div>
                                <div class="card__sub">Dónde está y quién lo resguarda</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--3">
                                <div v-for="d in datosUbicacion" :key="d.label" class="mini"
                                    :style="{ '--c': d.color }">
                                    <span class="mini__ic">
                                        <component :is="d.icono" />
                                    </span>
                                    <span class="mini__t">
                                        <span class="mini__l">{{ d.label }}</span>
                                        <span class="mini__v">{{ d.valor }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #e08a1e">
                                <DollarOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Adquisición</div>
                                <div class="card__sub">Compra, valor y garantía</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--3">
                                <div v-for="d in datosAdquisicion" :key="d.label" class="mini"
                                    :style="{ '--c': d.color }">
                                    <span class="mini__ic">
                                        <component :is="d.icono" />
                                    </span>
                                    <span class="mini__t">
                                        <span class="mini__l">{{ d.label }}</span>
                                        <span class="mini__v">{{ d.valor }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="col-der">
                    <div class="card card--flex">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <ToolOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Especificaciones
                                    <span v-if="especificaciones.length" class="badge">{{ especificaciones.length }}</span>
                                </div>
                                <div class="card__sub">Detalles técnicos</div>
                            </div>
                        </div>
                        <div class="card__body card__body--scroll">
                            <div v-if="especificaciones.length" class="specs">
                                <div v-for="(s, i) in especificaciones" :key="s.clave" class="specs__row"
                                    :class="{ 'specs__row--alt': i % 2 === 1 }">
                                    <span class="specs__k">
                                        <span class="specs__bullet"></span>
                                        {{ s.clave }}
                                    </span>
                                    <span class="specs__v">{{ s.valor }}</span>
                                </div>
                            </div>
                            <div v-else class="vacio-box">
                                <ToolOutlined />
                                <span>Sin especificaciones</span>
                            </div>
                        </div>
                    </div>

                    <div class="card card--flex">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <SafetyCertificateOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Normas aplicables
                                    <span v-if="equipo.normas?.length" class="badge badge--green">{{ equipo.normas.length }}</span>
                                </div>
                                <div class="card__sub">Certificaciones asociadas</div>
                            </div>
                        </div>
                        <div class="card__body card__body--scroll">
                            <div v-if="equipo.normas?.length" class="normas">
                                <div v-for="n in equipo.normas" :key="n.id" class="norma">
                                    <div class="norma__ico">
                                        <SafetyCertificateOutlined />
                                    </div>
                                    <div class="norma__t">
                                        <div class="norma__codigo">{{ n.codigo }}</div>
                                        <div v-if="n.nombre" class="norma__nombre">{{ n.nombre }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="vacio-box">
                                <SafetyCertificateOutlined />
                                <span>Sin normas asociadas</span>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN DE BAJA — debajo de Normas aplicables -->
                    <div v-if="dadoDeBaja" class="card card--baja">
                        <div class="card__head card__head--baja">
                            <div class="card__ico" style="--c: #d64545">
                                <StopOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo card__titulo--baja">
                                    Registro de baja
                                </div>
                                <div class="card__sub card__sub--baja">
                                    Información del retiro del equipo
                                </div>
                            </div>
                            <a-button v-if="puede('equipos.editar')" type="primary" class="baja-head__btn"
                                @click="reactivar">
                                <template #icon>
                                    <UndoOutlined />
                                </template>
                                Reactivar equipo
                            </a-button>
                        </div>

                        <div class="card__body card__body--baja">
                            <!-- Meta en UNA SOLA LÍNEA -->
                            <div class="baja-meta-line">
                                <div class="baja-meta">
                                    <span class="baja-meta__ic baja-meta__ic--fecha">
                                        <ClockCircleOutlined />
                                    </span>
                                    <span class="baja-meta__t">
                                        <span class="baja-meta__l">Fecha y hora</span>
                                        <span class="baja-meta__v">{{ fechaHora(equipo.baja_en) }}</span>
                                    </span>
                                </div>

                                <div class="baja-meta__sep"></div>

                                <div class="baja-meta">
                                    <span class="baja-meta__ic baja-meta__ic--user">
                                        <UserOutlined />
                                    </span>
                                    <span class="baja-meta__t">
                                        <span class="baja-meta__l">Registrada por</span>
                                        <span class="baja-meta__v">{{ equipo.baja_por?.nombre || 'Sistema' }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Motivo -->
                            <div class="baja-motivo">
                                <div class="baja-motivo__label">
                                    <FileTextOutlined /> Motivo
                                </div>
                                <p class="baja-motivo__txt">
                                    {{ equipo.motivo_baja || 'Sin motivo registrado.' }}
                                </p>
                            </div>

                            <!-- Evidencia -->
                            <div v-if="evidenciasBaja.length" class="baja-evidencia">
                                <div class="baja-evidencia__label">
                                    <FileTextOutlined /> Evidencia adjunta ({{ evidenciasBaja.length }})
                                </div>
                                <div class="baja-evidencia__grid">
                                    <button v-for="doc in evidenciasBaja" :key="doc.id" type="button"
                                        class="baja-evidencia__item" @click="abrirEvidencia(doc)">
                                        <img :src="route('documentos.ver', doc.id)" :alt="doc.nombre_original" />
                                        <span class="baja-evidencia__zoom">
                                            <EyeOutlined />
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="equipo.notas" class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #173a5f">
                                <FormOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Observaciones</div>
                                <div class="card__sub">Notas del equipo</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <p class="notas">{{ equipo.notas }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL FOTO -->
        <ModalFicha :show="modalFoto" titulo="Foto de referencia"
            :subtitulo="fotoReferencia?.nombre_original ?? 'Imagen del equipo'" :icono="FileTextOutlined"
            color="#0d84c9" max-width="lg" @close="modalFoto = false">
            <div v-if="fotoReferencia" class="foto-modal">
                <img :src="route('documentos.ver', fotoReferencia.id)" alt="Foto del equipo" />
            </div>
            <template #footer>
                <a v-if="fotoReferencia" :href="route('documentos.download', fotoReferencia.id)"
                    class="ant-btn ant-btn-default" target="_blank">Descargar</a>
                <a v-if="fotoReferencia" :href="route('documentos.ver', fotoReferencia.id)"
                    class="ant-btn ant-btn-primary" target="_blank">Abrir en nueva pestaña</a>
            </template>
        </ModalFicha>

        <!-- MODAL EVIDENCIA DE BAJA -->
        <ModalFicha :show="!!modalEvidencia" titulo="Evidencia de baja"
            :subtitulo="modalEvidencia?.nombre_original ?? 'Imagen adjunta'" :icono="FileTextOutlined" color="#d64545"
            max-width="lg" @close="modalEvidencia = null">
            <div v-if="modalEvidencia" class="foto-modal">
                <img :src="route('documentos.ver', modalEvidencia.id)" :alt="modalEvidencia.nombre_original" />
            </div>
            <template #footer>
                <a v-if="modalEvidencia" :href="route('documentos.download', modalEvidencia.id)"
                    class="ant-btn ant-btn-default" target="_blank">
                    Descargar
                </a>
                <a v-if="modalEvidencia" :href="route('documentos.ver', modalEvidencia.id)"
                    class="ant-btn ant-btn-primary" target="_blank">
                    Abrir en nueva pestaña
                </a>
            </template>
        </ModalFicha>

        <ModalFicha :show="modalHistorial" titulo="Historial de ubicación"
            :subtitulo="`${equipo.historialUbicacion?.length ?? 0} movimiento(s)`" :icono="EnvironmentOutlined"
            color="#0d84c9" @close="modalHistorial = false">
            <a-timeline v-if="equipo.historialUbicacion?.length">
                <a-timeline-item v-for="hh in equipo.historialUbicacion" :key="hh.id">
                    <div class="hist">
                        <div>
                            <strong>{{ hh.ubicacion_destino?.nombre ?? 'Sin ubicación' }}</strong>
                            <span v-if="hh.ubicacion_origen" class="hist__sub"> (desde {{ hh.ubicacion_origen.nombre }})</span>
                            <div class="hist__meta">{{ hh.motivo }} · {{ hh.cambiado_por?.nombre ?? 'Sistema' }}</div>
                        </div>
                        <div class="hist__fecha">{{ fecha(hh.cambiado_at) }}</div>
                    </div>
                </a-timeline-item>
            </a-timeline>
            <a-empty v-else description="Sin movimientos de ubicación" />
        </ModalFicha>

        <ModalFicha :show="modalMantenimientos" titulo="Mantenimientos"
            :subtitulo="`${mantenimientos.length} registro(s)`" :icono="ToolOutlined" color="#1f9e86"
            @close="modalMantenimientos = false">
            <a-list v-if="mantenimientos.length" :data-source="mantenimientos">
                <template #renderItem="{ item }">
                    <a-list-item class="cursor-pointer" @click="irA('mantenimientos.show', item.id)">
                        <a-list-item-meta :title="`${item.folio} · ${item.tipo?.nombre ?? ''}`"
                            :description="`Programado ${fecha(item.programado_inicio)} — Completado ${fecha(item.completado_at)}`">
                            <template #avatar>
                                <ToolOutlined />
                            </template>
                        </a-list-item-meta>
                        <a-tag>{{ item.estado?.nombre }}</a-tag>
                    </a-list-item>
                </template>
            </a-list>
            <a-empty v-else description="Sin mantenimientos registrados" />
        </ModalFicha>

        <ModalFicha :show="modalPlan" titulo="Plan preventivo" :subtitulo="`${equipo.planes?.length ?? 0} plan(es)`"
            :icono="CalendarOutlined" color="#e08a1e" @close="modalPlan = false">
            <div v-if="!equipo.planes?.length" class="sin-plan">
                <span class="sin-plan__ic">
                    <CalendarOutlined />
                </span>
                <div class="sin-plan__txt">
                    <div class="sin-plan__t">Sin plan preventivo</div>
                    <div class="sin-plan__s">Este equipo no tiene programa de mantenimiento periódico.</div>
                </div>
                <a-button v-if="puede('mantenimientos.crear')" type="primary"
                    @click="router.visit(route('planes.create', { equipo_id: equipo.id }))">
                    <template #icon>
                        <CalendarOutlined />
                    </template>
                    Crear plan
                </a-button>
            </div>
            <div v-else class="ordenes">
                <button v-for="item in equipo.planes" :key="item.id" type="button" class="orden"
                    @click="irA('planes.show', item.id)">
                    <span class="orden__ic">
                        <CalendarOutlined />
                    </span>
                    <span class="orden__t">
                        <span class="orden__folio">{{ item.nombre || item.tipo?.nombre }}</span>
                        <span class="orden__estado">Frecuencia: {{ item.tipo_frecuencia }} ({{ item.valor_frecuencia }}) · Próxima: {{ fecha(item.proxima_fecha) }}</span>
                    </span>
                </button>
            </div>
        </ModalFicha>

        <ModalFicha :show="modalDocumentos" titulo="Documentos"
            :subtitulo="`${equipo.documentos?.length ?? 0} archivo(s)`" :icono="FileTextOutlined" color="#6b4bc9"
            @close="modalDocumentos = false">
            <ListaDocumentos :documentos="equipo.documentos ?? []" relacionable-tipo="equipo"
                :relacionable-id="equipo.id" :roles="['identificacion', 'manual', 'garantia', 'factura', 'certificado']"
                :puede-subir="puede('documentos.crear')" :puede-eliminar="puede('documentos.desactivar')" />
        </ModalFicha>

        <ModalFicha :show="modalSolicitudes" titulo="Solicitudes" :subtitulo="`${solicitudes.length} registro(s)`"
            :icono="FormOutlined" color="#a86717" @close="modalSolicitudes = false">
            <a-list v-if="solicitudes.length" :data-source="solicitudes">
                <template #renderItem="{ item }">
                    <a-list-item class="cursor-pointer" @click="irA('solicitudes.show', item.id)">
                        <a-list-item-meta :title="item.folio" :description="item.descripcion">
                            <template #avatar>
                                <FormOutlined />
                            </template>
                        </a-list-item-meta>
                        <a-tag>{{ item.estado?.nombre }}</a-tag>
                    </a-list-item>
                </template>
            </a-list>
            <a-empty v-else description="Sin solicitudes para este equipo" />
        </ModalFicha>

        <ConfirmarDialog ref="confirmar" />
        <ModalQr ref="modalQr" />
        <ModalBajaEquipo ref="modalBaja" />
        <ModalRestaurarEquipo ref="modalRestaurar" :estados="estados" />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   BOTONES HERO (acciones del header)
   ========================================================== */
.btn-hero {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    height: 42px;
    padding: 0 14px 0 8px;
    border-radius: 11px;
    border: 1px solid transparent;
    font-family: inherit;
    font-weight: 800;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease, background 0.16s ease;
    flex-shrink: 0;
}

.btn-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.24) 50%, transparent 100%);
    transform: translateX(-100%) skewX(-20deg);
    transition: transform 0.6s ease;
    pointer-events: none;
}

.btn-hero:hover:not(:disabled)::after {
    transform: translateX(200%) skewX(-20deg);
}

.btn-hero:active:not(:disabled) {
    transform: translateY(0) scale(0.98);
}

.btn-hero:disabled {
    cursor: not-allowed;
    opacity: 0.55;
    filter: grayscale(0.4);
}

.btn-hero__ic {
    width: 28px;
    height: 28px;
    flex: none;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    background: rgba(255, 255, 255, 0.24);
    color: #fff;
    transition: transform 0.2s ease;
    position: relative;
    z-index: 1;
}

.btn-hero:hover:not(:disabled) .btn-hero__ic {
    transform: scale(1.1) rotate(-6deg);
}

.btn-hero__txt {
    display: flex;
    flex-direction: column;
    gap: 1px;
    line-height: 1.1;
    text-align: left;
    color: #fff;
    position: relative;
    z-index: 1;
}

.btn-hero__l {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    white-space: nowrap;
}

.btn-hero__s {
    font-size: 9.5px;
    font-weight: 600;
    opacity: 0.82;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.btn-hero--primary {
    background: linear-gradient(135deg, var(--hc, #1f9e86) 0%, var(--hc2, #16806c) 100%);
    box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--hc, #1f9e86) 65%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--primary:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px color-mix(in srgb, var(--hc, #1f9e86) 75%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-hero--default {
    background: linear-gradient(135deg, var(--hc, #0d84c9) 0%, var(--hc2, #0f6fb0) 100%);
    box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--hc, #0d84c9) 55%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--default:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px color-mix(in srgb, var(--hc, #0d84c9) 70%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-hero--danger {
    background: linear-gradient(135deg, #d64545 0%, #b91c1c 100%);
    box-shadow: 0 6px 16px -6px rgba(214, 69, 69, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--danger:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(214, 69, 69, 0.75), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-more {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    border: 1px solid var(--sigam-borde);
    background: #fff;
    color: var(--sigam-tenue);
    cursor: pointer;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.14s ease, color 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
    flex-shrink: 0;
}

.btn-more:hover {
    background: #eef4fb;
    border-color: #cfe4f5;
    color: #0d84c9;
    transform: translateY(-1px);
}

/* ==========================================================
   Layout compacto
   ========================================================== */
.ficha-compacta {
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-height: 0;
    padding-bottom: 8px;
}

/* ==========================================================
   KPIs con color sólido
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 11px;
}

.kpi {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    position: relative;
    overflow: hidden;
    cursor: pointer;
    width: 100%;
    text-align: left;
    font-family: inherit;
    transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}

.kpi:hover {
    box-shadow: 0 6px 16px -10px rgba(15, 37, 71, 0.28);
    transform: translateY(-2px);
    border-color: var(--acc);
}

.kpi:active {
    transform: translateY(0) scale(0.98);
}

.kpi__icono {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: var(--acc);
    flex-shrink: 0;
}

.kpi__valor {
    font-size: 18px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.1;
}

.kpi__etq {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ==========================================================
   Grid
   ========================================================== */
.grid-ficha {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 14px;
    align-items: start;
}

.col-izq,
.col-der {
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-width: 0;
    padding-bottom: 4px;
}

/* ==========================================================
   Cards
   ========================================================== */
.card {
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 14px -10px rgba(15, 37, 71, 0.18);
    transition: box-shadow 0.18s ease;
}

.card:hover {
    box-shadow: 0 8px 22px -14px rgba(15, 37, 71, 0.28);
}

.card--flex {
    display: flex;
    flex-direction: column;
}

.card__head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: var(--sigam-navy-050);
}

.card__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: var(--c);
    flex-shrink: 0;
}

.card__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    flex: 1;
}

.card__titulo {
    font-weight: 800;
    font-size: 13.5px;
    color: var(--sigam-navy);
    letter-spacing: -0.1px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.card__sub {
    font-size: 11px;
    color: var(--sigam-tenue);
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    background: #efe9fb;
    color: #6b4bc9;
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: 0;
}

.badge--green {
    background: #e4f4ec;
    color: #16806c;
}

.card__body {
    padding: 13px 16px;
}

/* ==========================================================
   Identificación
   ========================================================== */
.ident {
    display: flex;
    gap: 14px;
    align-items: flex-start;
}

.ident__foto {
    display: block;
    width: 122px;
    height: 122px;
    flex-shrink: 0;
    border-radius: 13px;
    overflow: hidden;
    border: 1px solid #cfe3f2;
    background: #e6f2fb;
    padding: 0;
    cursor: zoom-in;
    position: relative;
    transition: transform 0.14s ease, box-shadow 0.14s ease;
    outline: none;
    -webkit-tap-highlight-color: transparent;
}

.ident__foto:focus,
.ident__foto:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(13, 132, 201, 0.25);
}

.ident__foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border: none;
    outline: none;
}

.ident__foto:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 16px rgba(13, 132, 201, 0.25);
}

.ident__zoom {
    position: absolute;
    bottom: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    border-radius: 8px;
    background: #0d84c9;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    opacity: 0;
    transition: opacity 0.14s ease;
}

.ident__foto:hover .ident__zoom {
    opacity: 1;
}

.ident__foto--vacia {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
    color: #cfe3f2;
    cursor: default;
}

.ident>.mini-grid {
    flex: 1;
}

/* ==========================================================
   Mini-cards con colores sólidos
   ========================================================== */
.mini-grid {
    display: grid;
    gap: 9px;
}

.mini-grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.mini-grid--3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.mini {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px -6px rgba(15, 37, 71, 0.22);
    border-color: var(--c);
}

.mini__ic {
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    background: var(--c);
}

.mini__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 0;
}

.mini__l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    line-height: 1.1;
}

.mini__v {
    font-size: 13px;
    font-weight: 700;
    color: var(--sigam-texto);
    word-break: break-word;
    line-height: 1.25;
}

/* ==========================================================
   Especificaciones
   ========================================================== */
.specs {
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 11px;
    overflow: hidden;
    background: #fff;
}

.specs__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 9px 13px;
    font-size: 12.5px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    transition: background 0.12s ease;
}

.specs__row:last-child {
    border-bottom: none;
}

.specs__row--alt {
    background: #fafbfd;
}

.specs__row:hover {
    background: #eef4fb;
}

.specs__k {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--sigam-tenue);
    font-weight: 600;
    text-transform: capitalize;
    min-width: 0;
    flex-shrink: 0;
}

.specs__bullet {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #6b4bc9;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(107, 75, 201, 0.15);
}

.specs__v {
    color: var(--sigam-texto);
    text-align: right;
    font-weight: 700;
    word-break: break-word;
    font-size: 12.5px;
}

/* ==========================================================
   Normas
   ========================================================== */
.normas {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.norma {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #cbeadd;
    background: #f3fbf7;
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.norma:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 158, 134, 0.15);
    border-color: #1f9e86;
}

.norma__ico {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #fff;
    background: #1f9e86;
    box-shadow: 0 3px 8px rgba(31, 158, 134, 0.3);
}

.norma__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 1px;
}

.norma__codigo {
    font-weight: 800;
    color: var(--sigam-navy);
    font-size: 13px;
    letter-spacing: 0.02em;
    line-height: 1.2;
}

.norma__nombre {
    font-size: 11px;
    color: var(--sigam-tenue);
    line-height: 1.25;
}

/* ==========================================================
   Card de baja
   ========================================================== */
.card--baja {
    border: 1px solid #f5c2c7;
    background: linear-gradient(180deg, #fff5f5 0%, #ffffff 55%);
    box-shadow: 0 6px 18px -12px rgba(214, 69, 69, 0.35);
    animation: bajaIn 0.32s ease both;
}

@keyframes bajaIn {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card__head--baja {
    background: linear-gradient(90deg, #fdecec 0%, #fbd7d7 100%);
    border-bottom: 1px solid #f5c2c7;
}

.card__titulo--baja {
    color: #a12626;
}

.card__sub--baja {
    color: #a15656;
}

.baja-head__btn {
    background: #1f9e86 !important;
    border-color: #1f9e86 !important;
    color: #fff !important;
    box-shadow: 0 3px 10px rgba(31, 158, 134, 0.32);
    flex-shrink: 0;
    margin-left: auto;
}

.baja-head__btn:hover {
    background: #16806c !important;
    border-color: #16806c !important;
    box-shadow: 0 5px 14px rgba(31, 158, 134, 0.45) !important;
    transform: translateY(-1px);
}

.card__body--baja {
    padding: 16px 16px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.baja-meta-line {
    display: flex;
    align-items: stretch;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 11px;
    background: #fff;
    border: 1px solid #f5c2c7;
    flex-wrap: wrap;
}

.baja-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1 1 220px;
    min-width: 0;
}

.baja-meta__sep {
    width: 1px;
    background: linear-gradient(180deg, transparent 0%, #f5c2c7 30%, #f5c2c7 70%, transparent 100%);
    flex-shrink: 0;
}

.baja-meta__ic {
    width: 34px;
    height: 34px;
    flex: none;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
}

.baja-meta__ic--fecha {
    background: #d64545;
    box-shadow: 0 3px 8px rgba(214, 69, 69, 0.3);
}

.baja-meta__ic--user {
    background: #6b4bc9;
    box-shadow: 0 3px 8px rgba(107, 75, 201, 0.3);
}

.baja-meta__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 1px;
}

.baja-meta__l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: #a15656;
    line-height: 1.1;
}

.baja-meta__v {
    font-size: 13px;
    font-weight: 700;
    color: #7a1c1c;
    word-break: break-word;
    line-height: 1.25;
}

.baja-motivo {
    border-radius: 11px;
    background: #fff;
    border: 1px dashed #f5c2c7;
    padding: 12px 14px;
}

.baja-motivo__label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 800;
    color: #a12626;
    margin-bottom: 6px;
}

.baja-motivo__txt {
    margin: 0;
    font-size: 13px;
    line-height: 1.55;
    color: var(--sigam-texto);
    white-space: pre-line;
}

.baja-evidencia {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.baja-evidencia__label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 800;
    color: #a12626;
}

.baja-evidencia__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px;
}

.baja-evidencia__item {
    position: relative;
    padding: 0;
    border: 1px solid #f5c2c7;
    border-radius: 11px;
    overflow: hidden;
    background: #fff;
    cursor: zoom-in;
    aspect-ratio: 1 / 1;
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
    outline: none;
    -webkit-tap-highlight-color: transparent;
}

.baja-evidencia__item:hover {
    transform: scale(1.02);
    border-color: #d64545;
    box-shadow: 0 6px 16px -8px rgba(214, 69, 69, 0.4);
}

.baja-evidencia__item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border: none;
    outline: none;
}

.baja-evidencia__zoom {
    position: absolute;
    bottom: 6px;
    right: 6px;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: #d64545;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.14s ease;
}

.baja-evidencia__item:hover .baja-evidencia__zoom {
    opacity: 1;
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
    padding: 20px 12px;
    border-radius: 11px;
    border: 1px dashed var(--sigam-borde);
    background: #f5f8fb;
    color: var(--sigam-tenue);
    font-size: 12px;
}

.vacio-box .anticon {
    font-size: 20px;
    opacity: 0.5;
}

/* ==========================================================
   Notas
   ========================================================== */
.notas {
    font-size: 13px;
    color: var(--sigam-texto);
    background: #f0f4f8;
    border-left: 3px solid #173a5f;
    border-radius: 10px;
    padding: 11px 13px;
    margin: 0;
    line-height: 1.5;
    white-space: pre-line;
}

/* ==========================================================
   Modal foto
   ========================================================== */
.foto-modal {
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    padding: 0;
    margin: 0;
    max-height: 65vh;
    overflow: hidden;
}

.foto-modal img {
    max-width: 100%;
    max-height: 65vh;
    object-fit: contain;
    display: block;
    border: none;
    outline: none;
    border-radius: 8px;
}

/* Historial */
.hist {
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

.hist__sub {
    opacity: 0.6;
}

.hist__meta {
    font-size: 12px;
    color: var(--sigam-tenue);
}

.hist__fecha {
    font-size: 12px;
    color: var(--sigam-tenue);
    white-space: nowrap;
}

/* Plan */
.sin-plan {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    padding: 18px;
    border: 1px dashed #fde4b6;
    border-radius: 13px;
    background: #fffaf0;
}

.sin-plan__ic {
    width: 42px;
    height: 42px;
    flex: none;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    color: #a86717;
    background: #fdf3e6;
}

.sin-plan__txt {
    flex: 1;
    min-width: 160px;
}

.sin-plan__t {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13.5px;
}

.sin-plan__s {
    font-size: 12px;
    color: var(--sigam-tenue);
}

.ordenes {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.orden {
    display: flex;
    align-items: center;
    gap: 11px;
    width: 100%;
    text-align: left;
    padding: 10px 12px;
    border: 1px solid #cbeadd;
    border-radius: 10px;
    background: #f3fbf7;
    cursor: pointer;
    transition: border-color 0.14s ease, box-shadow 0.14s ease, transform 0.14s ease;
}

.orden:hover {
    border-color: #1f9e86;
    box-shadow: 0 4px 12px rgba(31, 158, 134, 0.2);
    transform: translateX(2px);
}

.orden__ic {
    width: 32px;
    height: 32px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: #1f9e86;
}

.orden__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.orden__folio {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13px;
}

.orden__estado {
    font-size: 11.5px;
    color: var(--sigam-tenue);
}

/* ==========================================================
   Scroll interno tipo iOS
   ========================================================== */
@media (min-width: 1200px) {
    .ficha-compacta {
        max-height: calc(100vh - 130px);
    }

    .card__body--scroll {
        max-height: 260px;
        overflow-y: auto;
        padding-right: 18px;
        scrollbar-width: thin;
        scrollbar-color: rgba(15, 37, 71, 0.28) transparent;
    }

    .card__body--scroll::-webkit-scrollbar {
        width: 10px;
    }

    .card__body--scroll::-webkit-scrollbar-track {
        background: transparent;
        margin: 6px 0;
    }

    .card__body--scroll::-webkit-scrollbar-thumb {
        background: rgba(15, 37, 71, 0.28);
        border-radius: 999px;
        border: 2px solid transparent;
        background-clip: padding-box;
        transition: background 0.15s ease;
    }

    .card__body--scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(15, 37, 71, 0.48);
        background-clip: padding-box;
    }

    .card__body--scroll::-webkit-scrollbar-thumb:active {
        background: rgba(15, 37, 71, 0.6);
        background-clip: padding-box;
    }
}

*::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

*::-webkit-scrollbar-track {
    background: transparent;
}

*::-webkit-scrollbar-thumb {
    background: rgba(15, 37, 71, 0.25);
    border-radius: 999px;
    border: 2px solid transparent;
    background-clip: padding-box;
}

*::-webkit-scrollbar-thumb:hover {
    background: rgba(15, 37, 71, 0.45);
    background-clip: padding-box;
}

* {
    scrollbar-width: thin;
    scrollbar-color: rgba(15, 37, 71, 0.28) transparent;
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 1199px) {
    .grid-ficha {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 991px) {
    .kpis {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 767px) {
    .btn-hero {
        width: 100%;
        justify-content: flex-start;
    }

    .btn-more {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .baja-meta-line {
        flex-direction: column;
        gap: 12px;
    }

    .baja-meta__sep {
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, #f5c2c7 30%, #f5c2c7 70%, transparent 100%);
    }
}

@media (max-width: 575px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ident {
        flex-direction: column;
    }

    .ident__foto {
        width: 100%;
        height: 180px;
    }

    .mini-grid--2,
    .mini-grid--3 {
        grid-template-columns: 1fr;
    }
}
</style>