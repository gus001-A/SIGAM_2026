<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CarOutlined,
    ContactsOutlined,
    DeleteOutlined,
    EditOutlined,
    EnvironmentOutlined,
    FileImageOutlined,
    FilePdfOutlined,
    FileTextOutlined,
    IdcardOutlined,
    MailOutlined,
    NumberOutlined,
    PhoneOutlined,
    ShopOutlined,
    TagOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SubirDocumento from '@/Components/SubirDocumento.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    proveedor: { type: Object, required: true },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const confirmar = ref(null);
const subirDoc = ref(null);

const inactivo = computed(() => props.proveedor.estado !== 'activo');

const NA = 'No especificado';
const dato = (v) => v || NA;

const irA = (nombre, params) => router.visit(route(nombre, params));

const datosGenerales = computed(() => [
    { icono: IdcardOutlined, label: 'Nombre comercial', valor: dato(props.proveedor.nombre_comercial), color: '#0d84c9' },
    { icono: NumberOutlined, label: 'RFC', valor: dato(props.proveedor.rfc), color: '#6b4bc9' },
    { icono: TagOutlined, label: 'Especialidad', valor: dato(props.proveedor.especialidad), color: '#1f9e86' },
    { icono: ShopOutlined, label: 'Razón social', valor: dato(props.proveedor.razon_social), color: '#173a5f' },
]);

const datosContacto = computed(() => [
    { icono: UserOutlined, label: 'Persona de contacto', valor: dato(props.proveedor.contacto), color: '#e08a1e' },
    { icono: PhoneOutlined, label: 'Teléfono', valor: dato(props.proveedor.telefono), color: '#1f9e86' },
    { icono: MailOutlined, label: 'Correo', valor: dato(props.proveedor.correo), color: '#0d84c9' },
]);

const datosUbicacion = computed(() => [
    { icono: EnvironmentOutlined, label: 'Dirección', valor: dato(props.proveedor.direccion), color: '#173a5f' },
]);

const desactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${props.proveedor.razon_social}`,
        mensaje: 'El proveedor se conservará en el historial pero dejará de aparecer en los listados activos.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('proveedores.destroy', props.proveedor.id));
};

const reactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: `Reactivar ${props.proveedor.razon_social}`,
        mensaje: 'El proveedor volverá a aparecer como opción en los listados y al registrar equipos.',
        confirmar: 'Reactivar',
    });
    if (ok) router.put(route('proveedores.restore', props.proveedor.id));
};

const menuAcciones = [{ key: 'baja', label: 'Desactivar proveedor', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && desactivar();

const abrirPdf = () => subirDoc.value?.abrirPdf();
const abrirImagen = () => subirDoc.value?.abrirImagen();
</script>

<template>

    <Head :title="proveedor.razon_social" />

    <AppLayout>
        <div class="ficha-proveedor">
            <FichaEncabezado :titulo="proveedor.razon_social" :subtitulo="proveedor.especialidad" :icono="CarOutlined"
                color="#0d84c9" volver="proveedores.index" :sello="sello">
                <template #tags>
                    <a-tag v-if="inactivo" color="default">
                        <span class="dot dot--gray"></span> Inactivo
                    </a-tag>
                    <a-tag v-else color="green">
                        <span class="dot dot--green"></span> Activo
                    </a-tag>
                    <a-tag v-if="proveedor.rfc" color="purple">
                        <NumberOutlined /> {{ proveedor.rfc }}
                    </a-tag>
                    <a-tag v-if="proveedor.especialidad" color="cyan">
                        <TagOutlined /> {{ proveedor.especialidad }}
                    </a-tag>
                </template>

                <template #acciones>
                    <template v-if="!inactivo">
                        <a-button v-if="puede('proveedores.editar')" type="primary" class="btn-editar"
                            @click="irA('proveedores.edit', proveedor.id)">
                            <template #icon>
                                <EditOutlined />
                            </template>
                            Editar
                        </a-button>
                        <a-dropdown v-if="puede('proveedores.desactivar')">
                            <a-button type="text" class="btn-mas">
                                <template #icon>
                                    <DeleteOutlined />
                                </template>
                            </a-button>
                            <template #overlay>
                                <a-menu :items="menuAcciones" @click="onMenuAccion" />
                            </template>
                        </a-dropdown>
                    </template>
                    <template v-else>
                        <a-button v-if="puede('proveedores.editar')" type="primary" class="btn-editar" @click="reactivar">
                            <template #icon>
                                <CarOutlined />
                            </template>
                            Reactivar
                        </a-button>
                    </template>
                </template>
            </FichaEncabezado>

            <a-alert v-if="inactivo" type="warning" show-icon class="alerta-inactivo" message="Proveedor inactivo"
                description="Este proveedor está desactivado. Puedes reactivarlo para volver a usarlo en altas de equipos." />

            <div class="grid-ficha">
                <!-- COLUMNA IZQUIERDA -->
                <div class="col-izq">
                    <!-- Datos generales -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <IdcardOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Datos generales</div>
                                <div class="card__sub">Identificación fiscal y comercial</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--2">
                                <div v-for="d in datosGenerales" :key="d.label" class="mini" :style="{ '--c': d.color }">
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

                    <!-- Contacto -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <ContactsOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Contacto</div>
                                <div class="card__sub">Cómo comunicarse con el proveedor</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--3">
                                <div v-for="d in datosContacto" :key="d.label" class="mini" :style="{ '--c': d.color }">
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

                    <!-- Ubicación -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #e08a1e">
                                <EnvironmentOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Ubicación</div>
                                <div class="card__sub">Domicilio registrado</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--full">
                                <div v-for="d in datosUbicacion" :key="d.label" class="mini" :style="{ '--c': d.color }">
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
                    <!-- Equipos suministrados -->
                    <div class="card card--flex">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <ToolOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Equipos suministrados
                                    <span v-if="proveedor.equipos?.length" class="badge badge--green">{{
                                        proveedor.equipos.length }}</span>
                                </div>
                                <div class="card__sub">Inventario provisto por este proveedor</div>
                            </div>
                        </div>
                        <div class="card__body card__body--scroll">
                            <div v-if="proveedor.equipos?.length" class="ordenes">
                                <button v-for="item in proveedor.equipos" :key="item.id" type="button" class="orden"
                                    @click="irA('equipos.show', item.id)">
                                    <span class="orden__ic">
                                        <ToolOutlined />
                                    </span>
                                    <span class="orden__t">
                                        <span class="orden__folio">{{ item.codigo_activo }}</span>
                                        <span class="orden__estado">{{ item.descripcion }}</span>
                                    </span>
                                </button>
                            </div>
                            <div v-else class="vacio-box">
                                <ToolOutlined />
                                <span>Sin equipos asociados</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notas -->
                    <div v-if="proveedor.notas" class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #173a5f">
                                <ContactsOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Notas</div>
                                <div class="card__sub">Observaciones internas</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <p class="notas">{{ proveedor.notas }}</p>
                        </div>
                    </div>

                    <!-- Documentos -->
                    <div class="card card--docs">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <FileTextOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Documentos
                                    <span v-if="proveedor.documentos?.length" class="badge">{{ proveedor.documentos.length
                                        }}</span>
                                </div>
                                <div class="card__sub">Contratos, cotizaciones, facturas</div>
                            </div>

                            <!-- Botones para subir PDF / Imagen -->
                            <a-space v-if="puede('documentos.crear')" class="card__extra">
                                <a-button class="btn-doc btn-doc--pdf" size="small" @click="abrirPdf">
                                    <template #icon>
                                        <FilePdfOutlined />
                                    </template>
                                    PDF
                                </a-button>
                                <a-button class="btn-doc btn-doc--img" size="small" @click="abrirImagen">
                                    <template #icon>
                                        <FileImageOutlined />
                                    </template>
                                    Imagen
                                </a-button>
                            </a-space>
                        </div>
                        <div class="card__body">
                            <ListaDocumentos :documentos="proveedor.documentos ?? []" relacionable-tipo="proveedor"
                                :relacionable-id="proveedor.id"
                                :roles="['contrato', 'cotización', 'factura', 'certificado']" :puede-subir="false"
                                :puede-eliminar="puede('documentos.desactivar')" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <SubirDocumento ref="subirDoc" relacionable-tipo="proveedor" :relacionable-id="proveedor.id"
            :roles="['contrato', 'cotización', 'factura', 'certificado']" />
        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Layout
   ========================================================== */
.ficha-proveedor {
    display: flex;
    flex-direction: column;
    gap: 13px;
    min-height: 0;
}

.alerta-inactivo {
    border-radius: 12px;
}

/* ==========================================================
   Tags del encabezado
   ========================================================== */
.dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
    vertical-align: middle;
}

.dot--green {
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
}

.dot--gray {
    background: #94a3b8;
    box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.18);
}

/* ==========================================================
   Botones
   ========================================================== */
.btn-editar {
    background: #0d84c9 !important;
    border-color: #0d84c9 !important;
    color: #fff !important;
}

.btn-editar:hover {
    background: #0f6fb0 !important;
    border-color: #0f6fb0 !important;
}

.btn-mas {
    color: #6b4bc9;
}

.btn-mas:hover {
    background: #efe9fb !important;
    color: #563a9e !important;
}

/* Botones para subir PDF e Imagen */
.btn-doc {
    border-radius: 10px;
    font-weight: 700;
    transition: transform 0.14s ease, box-shadow 0.14s ease, background 0.14s ease, border-color 0.14s ease;
}

.btn-doc--pdf {
    background: #fff !important;
    border: 1px solid #fecaca !important;
    color: #d64545 !important;
}

.btn-doc--pdf:hover {
    background: #fdecec !important;
    border-color: #d64545 !important;
    color: #b91c1c !important;
    transform: translateY(-1px);
}

.btn-doc--img {
    background: #fff !important;
    border: 1px solid #c9b8f0 !important;
    color: #6b4bc9 !important;
}

.btn-doc--img:hover {
    background: #faf7ff !important;
    border-color: #6b4bc9 !important;
    color: #563a9e !important;
    transform: translateY(-1px);
}

/* ==========================================================
   Grid principal
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
    gap: 13px;
    min-width: 0;
}

/* ==========================================================
   Cards
   ========================================================== */
.card {
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--sigam-sombra-sm);
    transition: box-shadow 0.18s ease;
}

.card:hover {
    box-shadow: var(--sigam-sombra-md);
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

.card__extra {
    flex-shrink: 0;
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
}

.badge--green {
    background: #e4f4ec;
    color: #16806c;
}

.card__body {
    padding: 13px 16px;
}

/* ==========================================================
   Mini-cards
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

.mini-grid--full {
    grid-template-columns: 1fr;
}

.mini {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    transition: transform 0.14s ease, box-shadow 0.14s ease;
}

.mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(15, 37, 71, 0.12);
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
   Lista de equipos
   ========================================================== */
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
    border: 1px solid var(--sigam-borde);
    border-radius: 10px;
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
    width: 32px;
    height: 32px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    background: #e4f4ec;
    color: #16806c;
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
    background: var(--sigam-navy-050);
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
    background: var(--sigam-navy-050);
    border-radius: 10px;
    padding: 11px 13px;
    margin: 0;
    line-height: 1.5;
    white-space: pre-line;
}

/* ==========================================================
   Scroll interno
   ========================================================== */
@media (min-width: 1200px) {
    .ficha-proveedor {
        max-height: calc(100vh - 130px);
    }

    .card__body--scroll {
        max-height: 260px;
        overflow-y: auto;
        scrollbar-width: thin;
    }

    .card__body--scroll::-webkit-scrollbar {
        width: 6px;
    }

    .card__body--scroll::-webkit-scrollbar-thumb {
        background: var(--sigam-borde);
        border-radius: 3px;
    }
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 1199px) {
    .grid-ficha {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 575px) {
    .mini-grid--2,
    .mini-grid--3 {
        grid-template-columns: 1fr;
    }
}
</style>