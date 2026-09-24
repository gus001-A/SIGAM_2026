<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    BankOutlined,
    BarcodeOutlined,
    CalendarOutlined,
    DeleteOutlined,
    DollarOutlined,
    EditOutlined,
    EllipsisOutlined,
    EnvironmentOutlined,
    FieldNumberOutlined,
    FileDoneOutlined,
    FileTextOutlined,
    FormOutlined,
    HistoryOutlined,
    ProfileOutlined,
    QrcodeOutlined,
    SafetyCertificateOutlined,
    ShopOutlined,
    StopOutlined,
    TagOutlined,
    ToolOutlined,
    UndoOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDatos from '@/Components/ListaDatos.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import ModalQr from '@/Components/ModalQr.vue';
import ModalBajaEquipo from '@/Components/ModalBajaEquipo.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    equipo: { type: Object, required: true },
    mantenimientos: { type: Array, default: () => [] },
    solicitudes: { type: Array, default: () => [] },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const tab = ref('resumen');
const confirmar = ref(null);
const modalQr = ref(null);
const modalBaja = ref(null);

const dadoDeBaja = computed(() => !!props.equipo.deleted_at);

const NA = 'No especificado';
const moneda = (v) =>
    v == null ? NA : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v);
const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : NA);
const dato = (v) => v || NA;

const enGarantia = computed(
    () => props.equipo.garantia_hasta && new Date(props.equipo.garantia_hasta) > new Date(),
);

const grupoIdentificacion = computed(() => [
    { icono: TagOutlined, label: 'Tipo', valor: dato(props.equipo.tipo?.nombre), color: '#0d84c9' },
    { icono: ShopOutlined, label: 'Marca', valor: dato(props.equipo.marca?.nombre), color: '#6b4bc9' },
    { icono: FieldNumberOutlined, label: 'Modelo', valor: dato(props.equipo.modelo), color: '#1f9e86' },
    { icono: BarcodeOutlined, label: 'Número de serie', valor: dato(props.equipo.numero_serie), color: '#173a5f' },
]);
const grupoUbicacion = computed(() => [
    { icono: BankOutlined, label: 'Sucursal', valor: dato(props.equipo.sucursal?.nombre), color: '#0d84c9' },
    { icono: ApartmentOutlined, label: 'Ubicación', valor: dato(props.equipo.ubicacion?.nombre), color: '#1f9e86' },
    { icono: UserOutlined, label: 'Responsable', valor: dato(props.equipo.responsable?.nombre), color: '#e08a1e' },
]);
const grupoAdquisicion = computed(() => [
    { icono: ShopOutlined, label: 'Proveedor', valor: dato(props.equipo.proveedor?.razon_social), color: '#6b4bc9' },
    { icono: CalendarOutlined, label: 'Fecha de adquisición', valor: fecha(props.equipo.fecha_adquisicion), color: '#0d84c9' },
    { icono: FileDoneOutlined, label: 'Número de factura', valor: dato(props.equipo.numero_factura), color: '#173a5f' },
    { icono: DollarOutlined, label: 'Valor de adquisición', valor: moneda(props.equipo.valor_adquisicion), color: '#1f9e86' },
    { icono: HistoryOutlined, label: 'Vida útil', valor: dato(props.equipo.vida_util), color: '#e08a1e' },
    { icono: SafetyCertificateOutlined, label: 'Garantía hasta', valor: fecha(props.equipo.garantia_hasta), color: enGarantia.value ? '#1f9e86' : '#d64545' },
]);

const especificaciones = computed(() => Object.entries(props.equipo.especificaciones ?? {}));

const fotoReferencia = computed(() =>
    (props.equipo.documentos ?? []).find((d) => d.pivot?.rol === 'foto_referencia'),
);

const tarjetas = computed(() => [
    { clave: 'mant', etiqueta: 'Mantenimientos', valor: props.mantenimientos.length, icono: ToolOutlined, color: '#0d84c9' },
    { clave: 'sol', etiqueta: 'Solicitudes', valor: props.solicitudes.length, icono: FormOutlined, color: '#6b4bc9' },
    { clave: 'plan', etiqueta: 'Planes preventivos', valor: props.equipo.planes?.length ?? 0, icono: CalendarOutlined, color: '#1f9e86' },
    { clave: 'doc', etiqueta: 'Documentos', valor: props.equipo.documentos?.length ?? 0, icono: FileTextOutlined, color: '#e08a1e' },
]);

const irA = (nombre, params) => router.visit(route(nombre, params));

const reactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: `Reactivar ${props.equipo.codigo_activo}`,
        mensaje: 'El equipo volverá a aparecer en el inventario activo.',
        confirmar: 'Reactivar',
    });
    if (ok) router.put(route('equipos.restore', props.equipo.id));
};

const menuAcciones = [{ key: 'baja', label: 'Dar de baja', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && modalBaja.value.abrir(props.equipo);
</script>

<template>
    <Head :title="`Equipo ${equipo.codigo_activo}`" />

    <AppLayout>
        <FichaEncabezado
            :titulo="equipo.codigo_activo"
            :subtitulo="equipo.descripcion"
            :icono="ToolOutlined"
            :color="equipo.estado?.color || '#1e5eb8'"
            volver="equipos.por_sucursal"
            :volver-params="{ sucursal_id: equipo.sucursal_id }"
            :sello="sello"
        >
            <template #tags>
                <a-tag v-if="dadoDeBaja" color="error"><StopOutlined /> Dado de baja</a-tag>
                <a-tag v-else-if="equipo.estado" :color="equipo.estado.color || 'default'">{{ equipo.estado.nombre }}</a-tag>
                <a-tag v-if="enGarantia" color="green"><SafetyCertificateOutlined /> En garantía</a-tag>
            </template>
            <template #acciones>
                <template v-if="dadoDeBaja">
                    <a-button v-if="puede('equipos.editar')" type="primary" @click="reactivar">
                        <template #icon><UndoOutlined /></template>
                        Reactivar
                    </a-button>
                </template>
                <template v-else>
                    <a-button @click="modalQr.abrir(equipo.id)">
                        <template #icon><QrcodeOutlined /></template>
                        QR
                    </a-button>
                    <a-button
                        v-if="puede('solicitudes.crear')"
                        @click="router.visit(route('solicitudes.create', { equipo_id: equipo.id }))"
                    >
                        <template #icon><FormOutlined /></template>
                        Solicitar mantenimiento
                    </a-button>
                    <a-button v-if="puede('equipos.editar')" type="primary" @click="irA('equipos.edit', equipo.id)">
                        <template #icon><EditOutlined /></template>
                        Editar
                    </a-button>
                    <a-dropdown v-if="puede('equipos.desactivar')">
                        <a-button type="text"><template #icon><EllipsisOutlined /></template></a-button>
                        <template #overlay>
                            <a-menu :items="menuAcciones" @click="onMenuAccion" />
                        </template>
                    </a-dropdown>
                </template>
            </template>
        </FichaEncabezado>

        <a-alert
            v-if="dadoDeBaja"
            type="error"
            show-icon
            class="mb-3"
            :message="`Este equipo fue dado de baja${equipo.baja_por?.nombre ? ' por ' + equipo.baja_por.nombre : ''}${equipo.baja_en ? ' el ' + fecha(equipo.baja_en) : ''}.`"
            :description="equipo.motivo_baja ? `Motivo: ${equipo.motivo_baja}` : 'Sin motivo registrado.'"
        />

        <a-row :gutter="14" class="tarjetas">
            <a-col v-for="t in tarjetas" :key="t.clave" :xs="12" :md="6">
                <div class="tarjeta" :style="{ '--acc': t.color }">
                    <div class="tarjeta__icono"><component :is="t.icono" /></div>
                    <div>
                        <div class="tarjeta__valor">{{ t.valor }}</div>
                        <div class="tarjeta__etq">{{ t.etiqueta }}</div>
                    </div>
                </div>
            </a-col>
        </a-row>

        <a-card :body-style="{ padding: 0 }">
            <a-tabs v-model:activeKey="tab" class="px-4 pt-2">
                <a-tab-pane key="resumen">
                    <template #tab><ProfileOutlined /> Resumen</template>
                    <a-row :gutter="28" class="pb-4">
                        <a-col :xs="24" :md="14">
                            <SeccionFicha titulo="Identificación" :icono="TagOutlined" color="#0d84c9">
                                <a
                                    v-if="fotoReferencia"
                                    :href="route('documentos.ver', fotoReferencia.id)"
                                    target="_blank"
                                    class="foto-referencia"
                                >
                                    <img :src="route('documentos.ver', fotoReferencia.id)" alt="Foto de referencia del equipo" />
                                </a>
                                <ListaDatos :datos="grupoIdentificacion" />
                            </SeccionFicha>

                            <SeccionFicha titulo="Adquisición" :icono="DollarOutlined" color="#e08a1e">
                                <ListaDatos :datos="grupoAdquisicion" />
                            </SeccionFicha>
                        </a-col>

                        <a-col :xs="24" :md="10">
                            <SeccionFicha titulo="Especificaciones técnicas" :icono="ToolOutlined" color="#6b4bc9">
                                <div v-if="especificaciones.length" class="specs">
                                    <div v-for="[k, v] in especificaciones" :key="k" class="specs__it">
                                        <span class="specs__k">{{ k }}</span>
                                        <span class="specs__v">{{ v }}</span>
                                    </div>
                                </div>
                                <span v-else class="vacio">Sin especificaciones registradas.</span>
                            </SeccionFicha>

                            <SeccionFicha titulo="Normas aplicables" :icono="SafetyCertificateOutlined" color="#1f9e86">
                                <a-space v-if="equipo.normas?.length" wrap>
                                    <a-tag v-for="n in equipo.normas" :key="n.id" color="blue">{{ n.codigo }}</a-tag>
                                </a-space>
                                <span v-else class="vacio">Sin normas asociadas.</span>
                            </SeccionFicha>

                            <SeccionFicha titulo="Ubicación y responsable" :icono="EnvironmentOutlined" color="#0d84c9">
                                <ListaDatos :datos="grupoUbicacion" compacto />
                            </SeccionFicha>

                            <SeccionFicha v-if="equipo.notas" titulo="Observaciones" :icono="FormOutlined" color="#173a5f">
                                <p class="notas">{{ equipo.notas }}</p>
                            </SeccionFicha>
                        </a-col>
                    </a-row>
                </a-tab-pane>

                <a-tab-pane key="ubicacion">
                    <template #tab><EnvironmentOutlined /> Historial de ubicación</template>
                    <div class="py-4">
                        <a-timeline v-if="equipo.historialUbicacion?.length">
                            <a-timeline-item v-for="hh in equipo.historialUbicacion" :key="hh.id">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <strong>{{ hh.ubicacion_destino?.nombre ?? 'Sin ubicación' }}</strong>
                                        <span v-if="hh.ubicacion_origen" class="opacity-60"> (desde {{ hh.ubicacion_origen.nombre }})</span>
                                        <div class="text-xs opacity-60">{{ hh.motivo }} · {{ hh.cambiado_por?.nombre ?? 'Sistema' }}</div>
                                    </div>
                                    <div class="text-xs whitespace-nowrap">{{ fecha(hh.cambiado_at) }}</div>
                                </div>
                            </a-timeline-item>
                        </a-timeline>
                        <a-empty v-else description="Sin movimientos de ubicación" />
                    </div>
                </a-tab-pane>

                <a-tab-pane key="mantenimientos">
                    <template #tab><ToolOutlined /> Mantenimientos ({{ mantenimientos.length }})</template>
                    <a-list v-if="mantenimientos.length" :data-source="mantenimientos">
                        <template #renderItem="{ item }">
                            <a-list-item class="cursor-pointer" @click="irA('mantenimientos.show', item.id)">
                                <a-list-item-meta
                                    :title="`${item.folio} · ${item.tipo?.nombre ?? ''}`"
                                    :description="`Programado ${fecha(item.programado_inicio)} — Completado ${fecha(item.completado_at)}`"
                                >
                                    <template #avatar><ToolOutlined /></template>
                                </a-list-item-meta>
                                <a-tag>{{ item.estado?.nombre }}</a-tag>
                            </a-list-item>
                        </template>
                    </a-list>
                    <a-empty v-else description="Sin mantenimientos registrados" class="py-6" />
                </a-tab-pane>

                <a-tab-pane key="preventivo">
                    <template #tab><CalendarOutlined /> Plan preventivo ({{ equipo.planes?.length ?? 0 }})</template>
                    <div class="py-4">
                        <div v-if="!equipo.planes?.length" class="sin-plan">
                            <span class="sin-plan__ic"><CalendarOutlined /></span>
                            <div class="sin-plan__txt">
                                <div class="sin-plan__t">Sin plan preventivo</div>
                                <div class="sin-plan__s">Este equipo no tiene un programa de mantenimiento periódico.</div>
                            </div>
                            <a-button
                                v-if="puede('mantenimientos.crear')"
                                type="primary"
                                @click="router.visit(route('planes.create', { equipo_id: equipo.id }))"
                            >
                                <template #icon><CalendarOutlined /></template>
                                Crear plan preventivo
                            </a-button>
                        </div>
                        <div v-else class="ordenes">
                            <button
                                v-for="item in equipo.planes"
                                :key="item.id"
                                type="button"
                                class="orden"
                                @click="irA('planes.show', item.id)"
                            >
                                <span class="orden__ic" style="background: #e4f4ec; color: #16806c"><CalendarOutlined /></span>
                                <span class="orden__t">
                                    <span class="orden__folio">{{ item.nombre || item.tipo?.nombre }}</span>
                                    <span class="orden__estado">Frecuencia: {{ item.tipo_frecuencia }} ({{ item.valor_frecuencia }}) · Próxima: {{ fecha(item.proxima_fecha) }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </a-tab-pane>

                <a-tab-pane key="documentos">
                    <template #tab><FileTextOutlined /> Documentos ({{ equipo.documentos?.length ?? 0 }})</template>
                    <div class="py-4">
                        <ListaDocumentos
                            :documentos="equipo.documentos ?? []"
                            relacionable-tipo="equipo"
                            :relacionable-id="equipo.id"
                            :roles="['identificacion', 'manual', 'garantia', 'factura', 'certificado']"
                            :puede-subir="puede('documentos.crear')"
                            :puede-eliminar="puede('documentos.desactivar')"
                        />
                    </div>
                </a-tab-pane>

                <a-tab-pane key="solicitudes">
                    <template #tab><FormOutlined /> Solicitudes ({{ solicitudes.length }})</template>
                    <a-list v-if="solicitudes.length" :data-source="solicitudes">
                        <template #renderItem="{ item }">
                            <a-list-item class="cursor-pointer" @click="irA('solicitudes.show', item.id)">
                                <a-list-item-meta :title="item.folio" :description="item.descripcion">
                                    <template #avatar><FormOutlined /></template>
                                </a-list-item-meta>
                                <a-tag>{{ item.estado?.nombre }}</a-tag>
                            </a-list-item>
                        </template>
                    </a-list>
                    <a-empty v-else description="Sin solicitudes para este equipo" class="py-6" />
                </a-tab-pane>
            </a-tabs>
        </a-card>

        <ConfirmarDialog ref="confirmar" />
        <ModalQr ref="modalQr" />
        <ModalBajaEquipo ref="modalBaja" />
    </AppLayout>
</template>

<style scoped>
.sin-plan {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    padding: 18px;
    border: 1px dashed var(--sigam-borde);
    border-radius: 14px;
    background: var(--sigam-navy-050);
}
.sin-plan__ic {
    width: 44px;
    height: 44px;
    flex: none;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--sigam-teal-700);
    background: #e4f4ec;
}
.sin-plan__txt {
    flex: 1;
    min-width: 180px;
}
.sin-plan__t {
    font-weight: 700;
    color: var(--sigam-navy);
}
.sin-plan__s {
    font-size: 12.5px;
    color: var(--sigam-tenue);
}
.ordenes {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.orden {
    display: flex;
    align-items: center;
    gap: 11px;
    width: 100%;
    text-align: left;
    padding: 11px 13px;
    border: 1px solid var(--sigam-borde);
    border-radius: 11px;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.14s ease, box-shadow 0.14s ease, transform 0.14s ease;
}
.orden:hover {
    border-color: var(--sigam-navy-100);
    box-shadow: var(--sigam-sombra-sm);
    transform: translateX(2px);
}
.orden__ic {
    width: 34px;
    height: 34px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
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
    font-size: 12px;
    color: var(--sigam-tenue);
}
.tarjetas {
    margin-bottom: 16px;
}
.tarjeta {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 14px;
    box-shadow: var(--sigam-sombra-sm);
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.18s ease, transform 0.18s ease;
}
.tarjeta::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}
.tarjeta:hover {
    box-shadow: var(--sigam-sombra-md);
    transform: translateY(-2px);
}
.tarjeta__icono {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: var(--acc);
    background: color-mix(in srgb, var(--acc) 12%, #fff);
}
.tarjeta__valor {
    font-size: 20px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.1;
}
.tarjeta__etq {
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 700;
    color: var(--sigam-tenue);
}
.bloque-tit {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 13px;
    color: var(--sigam-navy);
    margin-bottom: 9px;
    padding-left: 10px;
    border-left: 3px solid var(--sigam-teal);
}
.bloque-tit .anticon {
    color: var(--sigam-teal);
}
.datos {
    list-style: none;
    margin: 0 0 4px;
    padding: 0;
    display: flex;
    flex-direction: column;
}
.datos li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 9px 0;
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.datos li:last-child {
    border-bottom: none;
}
.datos__ic {
    width: 32px;
    height: 32px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.datos__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.datos__l {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
}
.datos__v {
    font-size: 13.5px;
    color: var(--sigam-texto);
    font-weight: 500;
}
.specs {
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 11px;
    overflow: hidden;
}
.specs__it {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 12px;
    font-size: 12.5px;
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.specs__it:last-child {
    border-bottom: none;
}
.specs__it:nth-child(even) {
    background: #fafbfd;
}
.specs__k {
    color: var(--sigam-tenue);
    font-weight: 600;
}
.specs__v {
    color: var(--sigam-texto);
    text-align: right;
}
.vacio {
    color: var(--sigam-tenue);
    font-size: 12.5px;
}
.notas {
    font-size: 13px;
    color: var(--sigam-texto);
    background: var(--sigam-navy-050);
    border-radius: 10px;
    padding: 10px 12px;
    margin: 0;
}
.foto-referencia {
    display: block;
    margin-bottom: 12px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--sigam-borde);
    max-width: 220px;
}
.foto-referencia img {
    display: block;
    width: 100%;
    max-height: 160px;
    object-fit: cover;
}
</style>

