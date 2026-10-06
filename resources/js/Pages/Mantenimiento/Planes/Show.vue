<script setup>
import { computed, h, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/es';
import {
    CalendarOutlined,
    CheckCircleFilled,
    CheckCircleOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    EnvironmentOutlined,
    FieldTimeOutlined,
    FileProtectOutlined,
    HourglassOutlined,
    PlusOutlined,
    SnippetsOutlined,
    ThunderboltOutlined,
    ToolOutlined,
    UserOutlined,
    EllipsisOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    plan: { type: Object, required: true },
    ocurrencias: { type: Array, default: () => [] },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const confirmar = ref(null);

const inactivo = computed(() => props.plan.estado !== 'activo');
const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');

const fechaCorta = (v) => {
    if (!v) return '';
    const d = new Date(v);
    return {
        dia: d.toLocaleDateString('es-MX', { day: '2-digit' }),
        mes: d.toLocaleDateString('es-MX', { month: 'short' }).replace('.', '').toUpperCase(),
        anio: d.getFullYear(),
        completa: d.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
    };
};

/* ==========================================================
   Etiquetas de frecuencia
   ========================================================== */
const etiquetaFrecuenciaBase = (f) =>
    ({
        dias: 'Cada N días',
        semanal: 'Semanal',
        mensual: 'Mensual',
        bimestral: 'Bimestral',
        trimestral: 'Trimestral',
        semestral: 'Semestral',
        anual: 'Anual',
        personalizada: 'Personalizada',
    })[f] ?? f;

const etiquetaFrecuencia = (tipo, valor) => {
    const v = Math.max(1, Number(valor) || 1);

    if (tipo === 'dias') {
        return `Cada ${v} día${v === 1 ? '' : 's'}`;
    }

    if (tipo === 'personalizada') {
        return 'Personalizada';
    }

    const base = etiquetaFrecuenciaBase(tipo);
    return v > 1 ? `${base} (${v})` : base;
};

const etiquetaFrecuenciaPlan = computed(() =>
    etiquetaFrecuencia(props.plan.tipo_frecuencia, props.plan.valor_frecuencia),
);

const datosObjetivo = computed(() => [
    props.plan.equipo
        ? { label: 'Equipo', valor: `${props.plan.equipo.codigo_activo} · ${props.plan.equipo.descripcion}`, icono: ToolOutlined, color: '#0d84c9' }
        : { label: 'Instalación', valor: props.plan.ubicacion?.nombre ?? 'No especificado', icono: EnvironmentOutlined, color: '#0d84c9' },
    { label: 'Tipo de mantenimiento', valor: props.plan.tipo?.nombre ?? 'No especificado', icono: ToolOutlined, color: '#0d84c9' },
]);

const datosFrecuencia = computed(() => [
    { label: 'Frecuencia', valor: etiquetaFrecuenciaPlan.value, icono: CalendarOutlined, color: '#e08a1e' },
    { label: 'Fecha de inicio', valor: fecha(props.plan.fecha_inicio), icono: CalendarOutlined, color: '#e08a1e' },
    { label: 'Próxima fecha', valor: fecha(props.plan.proxima_fecha), icono: CalendarOutlined, color: '#e08a1e' },
    { label: 'Días de aviso', valor: props.plan.dias_aviso_anticipado ?? 'No especificado', icono: ClockCircleOutlined, color: '#e08a1e' },
]);

const datosReferencias = computed(() => [
    { label: 'Norma', valor: props.plan.norma?.codigo ?? 'No especificado', icono: FileProtectOutlined, color: '#6b4bc9' },
    { label: 'Formato', valor: props.plan.formato?.nombre ?? 'No especificado', icono: SnippetsOutlined, color: '#6b4bc9' },
    { label: 'Técnico sugerido', valor: props.plan.tecnico?.nombre ?? 'No especificado', icono: UserOutlined, color: '#6b4bc9' },
]);

/* ==========================================================
   Estilos por estado de ocurrencia
   ========================================================== */
const ESTADOS_OCURRENCIA = {
    pendiente: {
        label: 'Pendiente',
        color: '#e08a1e',
        color2: '#a86717',
        soft: '#fdf3e6',
        icono: HourglassOutlined,
    },
    generada: {
        label: 'Generada',
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e7f7f2',
        icono: CheckCircleOutlined,
    },
    omitida: {
        label: 'Omitida',
        color: '#94a3b8',
        color2: '#64748b',
        soft: '#f1f5f9',
        icono: ClockCircleOutlined,
    },
};

const estadoOcurrencia = (o) => ESTADOS_OCURRENCIA[o.estado] ?? ESTADOS_OCURRENCIA.omitida;

/* ==========================================================
   Orden de ocurrencias:
   1) Pendientes, de la más cercana a hoy hacia arriba.
   2) Luego el resto, de la más cercana a hoy hacia abajo.
   ========================================================== */
const ocurrenciasOrdenadas = computed(() => {
    const hoy = new Date().setHours(0, 0, 0, 0);

    const pendientes = [];
    const otras = [];

    for (const o of props.ocurrencias) {
        if (o.estado === 'pendiente') pendientes.push(o);
        else otras.push(o);
    }

    const dist = (o) => {
        const d = new Date(o.fecha_programada).setHours(0, 0, 0, 0);
        return Math.abs(d - hoy);
    };

    pendientes.sort((a, b) => dist(a) - dist(b));
    otras.sort((a, b) => new Date(a.fecha_programada) - new Date(b.fecha_programada));

    return [...pendientes, ...otras];
});

/* Próxima ocurrencia pendiente (la más cercana a hoy) */
const proximaOcurrenciaId = computed(() => {
    const pendiente = ocurrenciasOrdenadas.value.find((o) => o.estado === 'pendiente');
    return pendiente?.id ?? null;
});

/* ¿Está vencida? */
const esVencida = (o) =>
    o.estado === 'pendiente' && new Date(o.fecha_programada) < new Date(new Date().setHours(0, 0, 0, 0));

/* ¿Ya tiene orden generada? (no se puede eliminar) */
const tieneOrden = (o) => !!o.mantenimiento_id || !!o.mantenimiento;

// --- KPIs -----------------------------------------------------
const ocurrenciasPendientes = computed(() => props.ocurrencias.filter((o) => o.estado === 'pendiente').length);
const ocurrenciasGeneradas = computed(() => props.ocurrencias.filter((o) => o.estado === 'generada').length);
const diasProxima = computed(() => {
    if (!props.plan.proxima_fecha) return null;
    const ms = new Date(props.plan.proxima_fecha).setHours(0, 0, 0, 0) - new Date().setHours(0, 0, 0, 0);
    return Math.round(ms / 86400000);
});
const etiquetaProxima = computed(() => {
    const d = diasProxima.value;
    if (d == null) return 'No especificado';
    if (d < 0) return `Vencida hace ${Math.abs(d)} d.`;
    if (d === 0) return 'Hoy';
    return `En ${d} día${d === 1 ? '' : 's'}`;
});

const kpis = computed(() => [
    { clave: 'total', etiqueta: 'Ocurrencias', valor: props.ocurrencias.length, icono: CalendarOutlined, color: '#0d84c9' },
    { clave: 'pendientes', etiqueta: 'Pendientes', valor: ocurrenciasPendientes.value, icono: HourglassOutlined, color: '#e08a1e' },
    { clave: 'generadas', etiqueta: 'Órdenes generadas', valor: ocurrenciasGeneradas.value, icono: CheckCircleOutlined, color: '#1f9e86' },
    { clave: 'proxima', etiqueta: 'Próxima fecha', valor: etiquetaProxima.value, icono: FieldTimeOutlined, color: '#6b4bc9' },
]);

const irA = (n, p) => router.visit(route(n, p));

// --- Generar ocurrencias ------------------------------------------
const modalGenerar = reactive({ abierto: false, cantidad: 6, procesando: false });

const PASO_FRECUENCIA = {
    dias: [1, 'day'],
    semanal: [1, 'week'],
    mensual: [1, 'month'],
    bimestral: [2, 'month'],
    trimestral: [3, 'month'],
    semestral: [6, 'month'],
    anual: [1, 'year'],
    personalizada: [1, 'month'],
};

const previewOcurrencias = computed(() => {
    const paso = PASO_FRECUENCIA[props.plan.tipo_frecuencia];
    if (!paso) return [];

    const base = props.plan.proxima_fecha
        ? dayjs(props.plan.proxima_fecha).subtract(1, 'day')
        : dayjs(props.plan.fecha_inicio ?? new Date());

    if (!base.isValid()) return [];

    const valor = Math.max(1, Number(props.plan.valor_frecuencia) || 1);
    const cantidad = Math.max(1, Math.min(36, Number(modalGenerar.cantidad) || 1));

    const visibles = Math.min(cantidad, 6);

    return Array.from({ length: visibles }, (_, i) =>
        base
            .add(paso[0] * valor * i, paso[1])
            .locale('es')
            .format('ddd D MMM YYYY'),
    );
});

const rangoTexto = computed(() => {
    const cantidad = Math.max(1, Number(modalGenerar.cantidad) || 1);
    if (cantidad <= 6) return `${cantidad} fecha${cantidad === 1 ? '' : 's'}`;
    return `6 de ${cantidad} fechas`;
});

const generarOcurrencias = () => {
    modalGenerar.procesando = true;
    router.post(route('planes.ocurrencias.generar', props.plan.id), { cantidad: modalGenerar.cantidad }, {
        preserveScroll: true,
        onFinish: () => { modalGenerar.procesando = false; modalGenerar.abierto = false; },
    });
};

const generarOrden = (ocurrencia) => {
    router.post(route('planes.orden.generar', props.plan.id), { ocurrencia_id: ocurrencia.id }, { preserveScroll: true });
};

const eliminarOcurrencia = async (ocurrencia) => {
    if (tieneOrden(ocurrencia)) return;

    const ok = await confirmar.value.abrir({
        titulo: `Eliminar ocurrencia del ${fecha(ocurrencia.fecha_programada)}`,
        mensaje: 'Se quita del calendario de este plan.',
        confirmar: 'Eliminar',
        peligro: true,
    });
    if (ok) router.delete(route('planes.ocurrencias.destroy', [props.plan.id, ocurrencia.id]), { preserveScroll: true });
};

const desactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: 'Desactivar plan preventivo',
        mensaje: 'El plan dejará de generar avisos. Las órdenes ya generadas se conservan.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('planes.destroy', props.plan.id));
};

const menuAcciones = [{ key: 'baja', label: 'Desactivar plan', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && desactivar();
</script>

<template>
    <Head :title="plan.nombre || 'Plan preventivo'" />

    <AppLayout>
        <FichaEncabezado
            :titulo="plan.nombre || 'Plan preventivo'"
            :subtitulo="plan.equipo ? `${plan.equipo.codigo_activo} · ${plan.equipo.descripcion}` : (plan.ubicacion?.nombre ?? '')"
            :icono="CalendarOutlined"
            volver="planes.index"
            :sello="sello"
        >
            <template #tags>
                <a-tag :color="inactivo ? 'default' : 'green'">{{ inactivo ? 'Inactivo' : 'Activo' }}</a-tag>
                <a-tag v-if="plan.proxima_fecha && new Date(plan.proxima_fecha) < new Date()" color="error">Vencido</a-tag>
            </template>
            <template v-if="!inactivo" #acciones>
                <!-- Botón Generar ocurrencias -->
                <button
                    v-if="puede('mantenimientos.editar')"
                    type="button"
                    class="btn-hero btn-hero--primary"
                    style="--hc: #1f9e86; --hc2: #16806c"
                    @click="modalGenerar.abierto = true"
                >
                    <span class="btn-hero__ic">
                        <CalendarOutlined />
                    </span>
                    <span class="btn-hero__txt">
                        <span class="btn-hero__l">Generar</span>
                        <span class="btn-hero__s">Ocurrencias</span>
                    </span>
                </button>

                <!-- Botón Editar -->
                <button
                    v-if="puede('mantenimientos.editar')"
                    type="button"
                    class="btn-hero btn-hero--default"
                    style="--hc: #0d84c9; --hc2: #0a6ba6"
                    @click="irA('planes.edit', plan.id)"
                >
                    <span class="btn-hero__ic">
                        <EditOutlined />
                    </span>
                    <span class="btn-hero__txt">
                        <span class="btn-hero__l">Editar</span>
                        <span class="btn-hero__s">Plan</span>
                    </span>
                </button>

                <!-- Dropdown de acciones -->
                <a-dropdown v-if="puede('mantenimientos.editar')">
                    <button type="button" class="btn-more">
                        <EllipsisOutlined />
                    </button>
                    <template #overlay>
                        <a-menu :items="menuAcciones" @click="onMenuAccion" />
                    </template>
                </a-dropdown>
            </template>
        </FichaEncabezado>

        <!-- KPIs -->
        <div class="kpis">
            <div v-for="k in kpis" :key="k.clave" class="kpi" :style="{ '--acc': k.color }">
                <div class="kpi__icono">
                    <component :is="k.icono" />
                </div>
                <div class="kpi__txt">
                    <div class="kpi__valor">{{ k.valor }}</div>
                    <div class="kpi__etq">{{ k.etiqueta }}</div>
                </div>
            </div>
        </div>

        <div class="grid-ficha">
            <!-- COLUMNA IZQUIERDA -->
            <div class="col-izq">
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #0d84c9">
                            <ToolOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Objetivo</div>
                            <div class="card__sub">A qué aplica este plan</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="mini-grid mini-grid--2">
                            <div v-for="d in datosObjetivo" :key="d.label" class="mini mini--lg" :style="{ '--c': d.color }">
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
                            <CalendarOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Frecuencia</div>
                            <div class="card__sub">Cada cuándo se repite</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="mini-grid mini-grid--4">
                            <div v-for="d in datosFrecuencia" :key="d.label" class="mini mini--lg" :style="{ '--c': d.color }">
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
                        <div class="card__ico" style="--c: #6b4bc9">
                            <FileProtectOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Referencias y asignación</div>
                            <div class="card__sub">Norma, checklist y técnico</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="mini-grid mini-grid--3">
                            <div v-for="d in datosReferencias" :key="d.label" class="mini mini--lg" :style="{ '--c': d.color }">
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
                <div class="card card--ocurrencias">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #1f9e86">
                            <ThunderboltOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Ocurrencias programadas</div>
                            <div class="card__sub">
                                {{ ocurrencias.length }} registrada{{ ocurrencias.length === 1 ? '' : 's' }}
                                <template v-if="ocurrenciasPendientes"> · {{ ocurrenciasPendientes }} pendiente{{ ocurrenciasPendientes === 1 ? '' : 's' }}</template>
                            </div>
                        </div>
                        <a-tag v-if="ocurrenciasPendientes" color="orange" class="card__badge">
                            {{ ocurrenciasPendientes }}
                        </a-tag>
                    </div>

                    <div class="card__body card__body--flush">
                        <div v-if="ocurrenciasOrdenadas.length" class="ocur-scroll">
                            <ul class="ocur-list">
                                <li
                                    v-for="o in ocurrenciasOrdenadas"
                                    :key="o.id"
                                    class="ocur"
                                    :class="[
                                        `ocur--${o.estado}`,
                                        { 'ocur--vencida': esVencida(o) },
                                        { 'ocur--next': o.id === proximaOcurrenciaId },
                                        { 'ocur--locked': tieneOrden(o) },
                                    ]"
                                    :style="{
                                        '--oc': estadoOcurrencia(o).color,
                                        '--oc2': estadoOcurrencia(o).color2,
                                        '--soft': estadoOcurrencia(o).soft,
                                    }"
                                >
                                    <div class="ocur__fecha">
                                        <span class="ocur__dia">{{ fechaCorta(o.fecha_programada).dia }}</span>
                                        <span class="ocur__mes">{{ fechaCorta(o.fecha_programada).mes }}</span>
                                        <span class="ocur__anio">{{ fechaCorta(o.fecha_programada).anio }}</span>
                                    </div>

                                    <div class="ocur__contenido">
                                        <div class="ocur__linea">
                                            <span class="ocur__estado" :title="estadoOcurrencia(o).label">
                                                <component :is="estadoOcurrencia(o).icono" />
                                                {{ estadoOcurrencia(o).label }}
                                            </span>
                                            <span
                                                v-if="o.id === proximaOcurrenciaId && !esVencida(o)"
                                                class="ocur__badge ocur__badge--next"
                                            >
                                                <ThunderboltOutlined /> Próxima
                                            </span>
                                            <span
                                                v-else-if="esVencida(o)"
                                                class="ocur__badge ocur__badge--vencida"
                                            >
                                                Vencida
                                            </span>
                                            <span
                                                v-if="tieneOrden(o)"
                                                class="ocur__badge ocur__badge--locked"
                                                title="Ya tiene orden generada; no se puede eliminar"
                                            >
                                                🔒 Bloqueada
                                            </span>
                                        </div>

                                        <div class="ocur__fecha-texto" :title="fechaCorta(o.fecha_programada).completa">
                                            {{ fechaCorta(o.fecha_programada).completa }}
                                        </div>

                                        <div v-if="o.mantenimiento" class="ocur__orden">
                                            <CheckCircleFilled class="ocur__orden-ic" />
                                            Orden
                                            <a
                                                class="ocur__orden-link"
                                                @click="irA('mantenimientos.show', o.mantenimiento.id)"
                                            >
                                                {{ o.mantenimiento.folio }}
                                            </a>
                                        </div>
                                    </div>

                                    <div class="ocur__acciones">
                                        <a-button
                                            v-if="o.estado === 'pendiente' && !tieneOrden(o) && puede('mantenimientos.crear')"
                                            class="ocur__btn ocur__btn--primary"
                                            size="small"
                                            @click="generarOrden(o)"
                                        >
                                            <template #icon><ThunderboltOutlined /></template>
                                            Generar
                                        </a-button>

                                        <a-tooltip
                                            v-if="!tieneOrden(o) && puede('mantenimientos.editar')"
                                            title="Eliminar esta ocurrencia"
                                        >
                                            <a-button
                                                class="ocur__btn ocur__btn--danger"
                                                size="small"
                                                @click="eliminarOcurrencia(o)"
                                            >
                                                <template #icon><DeleteOutlined /></template>
                                            </a-button>
                                        </a-tooltip>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div v-else class="vacio-box">
                            <div class="vacio-box__ico">
                                <CalendarOutlined />
                            </div>
                            <div class="vacio-box__txt">
                                <span class="vacio-box__titulo">Sin ocurrencias todavía</span>
                                <span class="vacio-box__sub">
                                    Genera las próximas fechas para calendarizar este plan.
                                </span>
                            </div>
                            <button
                                v-if="puede('mantenimientos.editar')"
                                type="button"
                                class="btn-generar"
                                @click="modalGenerar.abierto = true"
                            >
                                <span class="btn-generar__ic">
                                    <ThunderboltOutlined />
                                </span>
                                <span>Generar ahora</span>
                                <PlusOutlined class="btn-generar__plus" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal generar ocurrencias -->
        <a-modal
            v-model:open="modalGenerar.abierto"
            :footer="null"
            :closable="false"
            :width="560"
            centered
            class="modal-generar"
            :mask-closable="!modalGenerar.procesando"
        >
            <div class="modal-generar__wrap">
                <header class="modal-generar__head">
                    <span class="modal-generar__ico">
                        <CalendarOutlined />
                    </span>
                    <div class="modal-generar__meta">
                        <h3 class="modal-generar__titulo">Generar ocurrencias</h3>
                        <p class="modal-generar__sub">
                            Se crearán las próximas fechas según la frecuencia del plan.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="modal-generar__close"
                        title="Cerrar"
                        :disabled="modalGenerar.procesando"
                        @click="modalGenerar.abierto = false"
                    >
                        ✕
                    </button>
                </header>

                <div class="modal-generar__body">
                    <div class="modal-generar__resumen">
                        <div class="modal-generar__resumen-ico">
                            <ClockCircleOutlined />
                        </div>
                        <div class="modal-generar__resumen-txt">
                            <span class="modal-generar__resumen-titulo">
                                {{ etiquetaFrecuenciaPlan }}
                            </span>
                            <span class="modal-generar__resumen-sub">
                                Base: {{ plan.proxima_fecha ? `próxima fecha ${fecha(plan.proxima_fecha)}` : `inicio ${fecha(plan.fecha_inicio)}` }}
                            </span>
                        </div>
                    </div>

                    <div class="modal-generar__campo">
                        <label class="modal-generar__label">
                            ¿Cuántas ocurrencias generar?
                        </label>
                        <a-input-number
                            v-model:value="modalGenerar.cantidad"
                            :min="1"
                            :max="36"
                            size="large"
                            class="modal-generar__input"
                        >
                            <template #prefix>
                                <CalendarOutlined />
                            </template>
                        </a-input-number>
                        <span class="modal-generar__hint">Entre 1 y 36 fechas.</span>
                    </div>

                    <div v-if="previewOcurrencias.length" class="modal-generar__preview">
                        <div class="modal-generar__preview-head">
                            <ThunderboltOutlined />
                            <span>Vista previa · {{ rangoTexto }}</span>
                        </div>
                        <div class="modal-generar__preview-list">
                            <span
                                v-for="(f, i) in previewOcurrencias"
                                :key="i"
                                class="modal-generar__chip"
                            >
                                {{ f }}
                            </span>
                        </div>
                    </div>
                </div>

                <footer class="modal-generar__footer">
                    <a-button size="large" :disabled="modalGenerar.procesando" @click="modalGenerar.abierto = false">
                        Cancelar
                    </a-button>
                    <a-button
                        type="primary"
                        size="large"
                        class="modal-generar__submit"
                        :loading="modalGenerar.procesando"
                        @click="generarOcurrencias"
                    >
                        <template #icon><ThunderboltOutlined /></template>
                        Generar
                    </a-button>
                </footer>
            </div>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
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
   KPIs
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
    margin-bottom: 14px;
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
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
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
   Cards base
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
}

.card__sub {
    font-size: 11px;
    color: var(--sigam-tenue);
}

.card__badge {
    margin: 0;
    font-weight: 800;
    border: none;
}

.card__body {
    padding: 13px 16px;
}

.card__body--flush {
    padding: 10px 12px 12px;
}

/* ==========================================================
   Mini-cards — versión GRANDE
   ========================================================== */
.mini-grid {
    display: grid;
    gap: 10px;
}

.mini-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.mini-grid--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.mini-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

.mini {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 14px;
    border-radius: 11px;
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
    min-width: 0;
}

.mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px -8px rgba(15, 37, 71, 0.22);
    border-color: var(--c);
}

.mini--lg {
    padding: 14px 16px;
    gap: 12px;
}

.mini__ic {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    background: var(--c);
    box-shadow: 0 3px 8px -3px rgba(15, 37, 71, 0.28);
}

.mini__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 1px;
}

.mini__l {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 800;
    color: var(--sigam-tenue);
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mini__v {
    font-size: 14px;
    font-weight: 700;
    color: var(--sigam-texto);
    word-break: break-word;
    line-height: 1.3;
}

/* ==========================================================
   Lista de ocurrencias con SCROLL propio
   ========================================================== */
.card--ocurrencias {
    display: flex;
    flex-direction: column;
}

.ocur-scroll {
    max-height: 560px;
    overflow-y: auto;
    padding-right: 4px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.ocur-scroll::-webkit-scrollbar {
    width: 8px;
}

.ocur-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.ocur-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
    border: 2px solid transparent;
    background-clip: content-box;
}

.ocur-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
    background-clip: content-box;
}

.ocur-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ocur {
    position: relative;
    display: flex;
    align-items: stretch;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    transition: border-color 0.16s ease, box-shadow 0.16s ease, transform 0.16s ease, background 0.16s ease;
    overflow: hidden;
}

.ocur::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 3px;
    background: var(--oc);
}

.ocur:hover {
    border-color: var(--oc);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -10px rgba(15, 37, 71, 0.28);
}

.ocur--next {
    background: linear-gradient(135deg, #ffffff 0%, color-mix(in srgb, var(--oc) 5%, #ffffff) 100%);
    box-shadow: 0 4px 14px -10px color-mix(in srgb, var(--oc) 60%, transparent);
}

.ocur--vencida::before {
    background: #d64545;
}

.ocur--locked {
    background: linear-gradient(135deg, #ffffff 0%, #fafbfd 100%);
}

.ocur__fecha {
    flex: 0 0 52px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--soft);
    border: 1px solid color-mix(in srgb, var(--oc) 22%, transparent);
    padding: 6px 4px;
    line-height: 1;
    color: var(--oc2);
}

.ocur__dia {
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -0.5px;
    line-height: 1;
}

.ocur__mes {
    font-size: 9.5px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-top: 2px;
    color: var(--oc);
}

.ocur__anio {
    font-size: 9px;
    font-weight: 600;
    color: var(--sigam-tenue);
    margin-top: 1px;
}

.ocur__contenido {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 3px;
}

.ocur__linea {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.ocur__estado {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, var(--oc) 0%, var(--oc2) 100%);
    box-shadow: 0 2px 6px -2px color-mix(in srgb, var(--oc) 60%, transparent);
    line-height: 1.4;
}

.ocur__badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    line-height: 1.4;
}

.ocur__badge--next {
    color: #a86717;
    background: #fdf3e6;
    border: 1px solid #f5ddb8;
}

.ocur__badge--vencida {
    color: #b91c1c;
    background: #fdecec;
    border: 1px solid #fecaca;
    animation: pulseVencida 2s ease infinite;
}

.ocur__badge--locked {
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
}

@keyframes pulseVencida {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.75; }
}

.ocur__fecha-texto {
    font-size: 12px;
    font-weight: 600;
    color: var(--sigam-texto);
    text-transform: capitalize;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ocur__orden {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    color: var(--sigam-tenue);
    font-weight: 600;
}

.ocur__orden-ic {
    color: #1f9e86;
    font-size: 12px;
}

.ocur__orden-link {
    color: #0d84c9;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    border-bottom: 1px dashed transparent;
    transition: color 0.14s ease, border-color 0.14s ease;
}

.ocur__orden-link:hover {
    color: #0f6fb0;
    border-bottom-color: #0f6fb0;
}

.ocur__acciones {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    align-self: center;
}

.ocur__btn {
    height: 28px;
    padding: 0 10px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 12px;
    transition: all 0.14s ease;
}

.ocur__btn--primary {
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%) !important;
    border-color: #1f9e86 !important;
    color: #fff !important;
    box-shadow: 0 3px 8px -3px rgba(31, 158, 134, 0.55);
}

.ocur__btn--primary:hover {
    filter: brightness(1.06);
    transform: translateY(-1px);
    box-shadow: 0 5px 12px -3px rgba(31, 158, 134, 0.65);
}

.ocur__btn--danger {
    width: 28px;
    padding: 0;
    border-color: #f4dede !important;
    background: #fdf4f4 !important;
    color: #d64545 !important;
}

.ocur__btn--danger:hover {
    background: #fdecec !important;
    border-color: #fecaca !important;
    color: #b91c1c !important;
    transform: translateY(-1px);
}

/* ==========================================================
   Estado vacío
   ========================================================== */
.vacio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 24px 16px;
    border-radius: 12px;
    border: 1px dashed var(--sigam-borde);
    background: linear-gradient(180deg, #f5f8fb 0%, #eef3f9 100%);
    text-align: center;
}

.vacio-box__ico {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #94a3b8;
    background: #fff;
    box-shadow: 0 4px 10px -6px rgba(15, 37, 71, 0.22);
}

.vacio-box__txt {
    display: flex;
    flex-direction: column;
    gap: 2px;
    max-width: 260px;
}

.vacio-box__titulo {
    font-size: 13px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.2;
}

.vacio-box__sub {
    font-size: 11.5px;
    color: var(--sigam-tenue);
    line-height: 1.3;
}

/* Botón Generar ahora */
.btn-generar {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 9px 16px 9px 12px;
    border-radius: 11px;
    border: 0;
    font-family: inherit;
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    cursor: pointer;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow:
        0 6px 16px -6px rgba(31, 158, 134, 0.65),
        inset 0 1px 0 rgba(255, 255, 255, 0.18);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
    overflow: hidden;
}

.btn-generar::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.18) 50%, transparent 100%);
    transform: translateX(-100%);
    transition: transform 0.5s ease;
    pointer-events: none;
}

.btn-generar:hover {
    transform: translateY(-1px);
    filter: brightness(1.05);
    box-shadow:
        0 10px 22px -8px rgba(31, 158, 134, 0.75),
        inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-generar:hover::after {
    transform: translateX(100%);
}

.btn-generar__ic {
    width: 24px;
    height: 24px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    background: rgba(255, 255, 255, 0.18);
    flex-shrink: 0;
}

.btn-generar__plus {
    font-size: 11px;
    opacity: 0.85;
    transition: transform 0.2s ease;
}

.btn-generar:hover .btn-generar__plus {
    transform: rotate(90deg);
}

/* ==========================================================
   Modal Generar ocurrencias
   ========================================================== */
.modal-generar :deep(.ant-modal-content) {
    padding: 0;
    overflow: hidden;
    border-radius: 16px;
    box-shadow: 0 30px 80px -20px rgba(15, 37, 71, 0.45);
}

.modal-generar :deep(.ant-modal-body) {
    padding: 0;
}

.modal-generar__wrap {
    display: flex;
    flex-direction: column;
    background: #f5f8fb;
}

.modal-generar__head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f8fb 100%);
    border-bottom: 1px solid #e2e8f0;
}

.modal-generar__ico {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.55);
}

.modal-generar__meta {
    flex: 1;
    min-width: 0;
}

.modal-generar__titulo {
    font-size: 16px;
    font-weight: 800;
    color: #173a5f;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.2px;
}

.modal-generar__sub {
    margin: 2px 0 0;
    font-size: 12px;
    color: #7b8a9c;
    line-height: 1.3;
}

.modal-generar__close {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.14s ease, color 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
}

.modal-generar__close:hover:not(:disabled) {
    background: #fdecec;
    border-color: #fecaca;
    color: #d64545;
    transform: rotate(90deg);
}

.modal-generar__close:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.modal-generar__body {
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: #fff;
}

.modal-generar__resumen {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 11px;
    background: linear-gradient(135deg, #eef4fb 0%, #e6f2fb 100%);
    border: 1px solid #cfe4f5;
}

.modal-generar__resumen-ico {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%);
    box-shadow: 0 4px 10px -4px rgba(13, 132, 201, 0.55);
}

.modal-generar__resumen-txt {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.modal-generar__resumen-titulo {
    font-size: 13px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.2;
}

.modal-generar__resumen-sub {
    font-size: 11.5px;
    color: #7b8a9c;
    line-height: 1.2;
}

.modal-generar__campo {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.modal-generar__label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #173a5f;
}

.modal-generar__input {
    width: 100% !important;
    height: 42px !important;
}

.modal-generar__input :deep(.ant-input-number-input) {
    height: 40px !important;
    line-height: 40px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
}

.modal-generar__hint {
    font-size: 11.5px;
    color: #7b8a9c;
}

.modal-generar__preview {
    padding: 12px 12px 10px;
    border-radius: 11px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px dashed #dbe3ec;
}

.modal-generar__preview-head {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #1f9e86;
    margin-bottom: 8px;
}

.modal-generar__preview-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.modal-generar__chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 9px;
    border-radius: 999px;
    background: color-mix(in srgb, #1f9e86 12%, transparent);
    color: #16806c;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: capitalize;
    line-height: 1.2;
}

.modal-generar__footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 18px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    box-shadow: 0 -6px 16px -12px rgba(15, 37, 71, 0.18);
}

.modal-generar__submit {
    background: linear-gradient(135deg, #0d84c9 0%, #0f6fb0 100%) !important;
    border-color: #0d84c9 !important;
    font-weight: 800;
    box-shadow: 0 6px 16px -6px rgba(13, 132, 201, 0.65);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
}

.modal-generar__submit:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(13, 132, 201, 0.75);
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
    .mini-grid--4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .mini-grid--3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 767px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .mini-grid--4,
    .mini-grid--3,
    .mini-grid--2 {
        grid-template-columns: 1fr;
    }

    .ocur {
        flex-wrap: wrap;
    }

    .ocur__acciones {
        width: 100%;
        justify-content: flex-end;
        margin-top: 4px;
    }

    .ocur-scroll {
        max-height: 480px;
    }

    .btn-hero {
        width: 100%;
        justify-content: flex-start;
    }

    .btn-more {
        width: 100%;
    }
}
</style>