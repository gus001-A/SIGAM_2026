<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    DeleteOutlined,
    EditOutlined,
    EllipsisOutlined,
    EnvironmentOutlined,
    FileImageOutlined,
    FilePdfOutlined,
    FileTextOutlined,
    IdcardOutlined,
    MailOutlined,
    PhoneOutlined,
    ShopOutlined,
    ToolOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDatos from '@/Components/ListaDatos.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import SubirDocumento from '@/Components/SubirDocumento.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    sucursal: { type: Object, required: true },
    valorActivos: { type: Number, default: 0 },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const confirmar = ref(null);
const subirDoc = ref(null);

const inactiva = computed(() => props.sucursal.estado !== 'activo');

const irA = (nombre, params) => router.visit(route(nombre, params));

const datos = computed(() => [
    { icono: IdcardOutlined, label: 'Código', valor: props.sucursal.codigo, color: '#173a5f' },
    { icono: EnvironmentOutlined, label: 'Dirección', valor: props.sucursal.direccion, color: '#0d84c9' },
    { icono: PhoneOutlined, label: 'Teléfono', valor: props.sucursal.telefono, color: '#1f9e86' },
    { icono: MailOutlined, label: 'Correo', valor: props.sucursal.correo, color: '#6b4bc9' },
]);

const desactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${props.sucursal.nombre}`,
        mensaje: 'La sucursal se conservará en el historial pero dejará de aparecer en los listados activos.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('sucursales.destroy', props.sucursal.id));
};

const reactivar = () => router.put(route('sucursales.restore', props.sucursal.id));

const menuAcciones = [{ key: 'baja', label: 'Desactivar sucursal', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && desactivar();

const abrirPdf = () => subirDoc.value?.abrirPdf();
const abrirImagen = () => subirDoc.value?.abrirImagen();
</script>

<template>

    <Head :title="sucursal.nombre" />

    <AppLayout>
        <div class="ficha-sucursal">
            <FichaEncabezado :titulo="sucursal.nombre" :subtitulo="sucursal.codigo" :icono="ShopOutlined"
                color="#0d84c9" volver="sucursales.index" :sello="sello">
                <template #tags>
                    <a-tag v-if="inactiva" color="default">
                        <span class="dot dot--gray"></span> Inactiva
                    </a-tag>
                    <a-tag v-else color="green">
                        <span class="dot dot--green"></span> Activa
                    </a-tag>
                    <a-tag v-if="sucursal.codigo" color="purple">
                        <IdcardOutlined /> {{ sucursal.codigo }}
                    </a-tag>
                </template>

                <template #acciones>
                    <!-- Botón Ver equipos -->
                    <button
                        type="button"
                        class="btn-hero btn-hero--default"
                        style="--hc: #1f9e86; --hc2: #16806c"
                        @click="router.visit(route('equipos.por_sucursal', { sucursal_id: sucursal.id }))"
                    >
                        <span class="btn-hero__ic">
                            <ToolOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Ver equipos</span>
                            <span class="btn-hero__s">Inventario</span>
                        </span>
                    </button>

                    <!-- Botón Ver ubicaciones -->
                    <button
                        type="button"
                        class="btn-hero btn-hero--default"
                        style="--hc: #6b4bc9; --hc2: #563a9e"
                        @click="router.visit(route('ubicaciones.index', { sucursal_id: sucursal.id }))"
                    >
                        <span class="btn-hero__ic">
                            <ApartmentOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Ver ubicaciones</span>
                            <span class="btn-hero__s">Espacios</span>
                        </span>
                    </button>

                    <!-- Botón Reactivar (sucursal inactiva) -->
                    <button
                        v-if="inactiva && puede('sucursales.editar')"
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
                            <span class="btn-hero__s">Sucursal</span>
                        </span>
                    </button>

                    <!-- Botón Editar (sucursal activa) -->
                    <button
                        v-if="!inactiva && puede('sucursales.editar')"
                        type="button"
                        class="btn-hero btn-hero--primary"
                        style="--hc: #0d84c9; --hc2: #0a6ba6"
                        @click="irA('sucursales.edit', sucursal.id)"
                    >
                        <span class="btn-hero__ic">
                            <EditOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Editar</span>
                            <span class="btn-hero__s">Sucursal</span>
                        </span>
                    </button>

                    <!-- Dropdown de acciones -->
                    <a-dropdown v-if="!inactiva && puede('sucursales.desactivar')">
                        <button type="button" class="btn-more">
                            <EllipsisOutlined />
                        </button>
                        <template #overlay>
                            <a-menu :items="menuAcciones" @click="onMenuAccion" />
                        </template>
                    </a-dropdown>
                </template>
            </FichaEncabezado>

            <div class="grid-ficha">
                <!-- COLUMNA IZQUIERDA -->
                <div class="col-izq">
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <ShopOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Datos de la sucursal</div>
                                <div class="card__sub">Información general y de contacto</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <ListaDatos :datos="datos" />
                        </div>
                    </div>

                    <div v-if="sucursal.notas" class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #173a5f">
                                <IdcardOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Notas</div>
                                <div class="card__sub">Observaciones internas</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <p class="notas">{{ sucursal.notas }}</p>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="col-der">
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <EnvironmentOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Ubicaciones principales
                                    <span v-if="sucursal.ubicaciones_count" class="badge badge--green">{{
                                        sucursal.ubicaciones_count
                                    }}</span>
                                </div>
                                <div class="card__sub">Espacios registrados en la sucursal</div>
                            </div>
                            <a-button v-if="puede('ubicaciones.ver')" type="link" size="small"
                                class="card__extra btn-gestionar"
                                @click="router.visit(route('ubicaciones.index', { sucursal_id: sucursal.id }))">
                                <template #icon>
                                    <ApartmentOutlined />
                                </template>
                                Gestionar
                            </a-button>
                        </div>
                        <div class="card__body card__body--scroll">
                            <div v-if="sucursal.ubicaciones.length" class="ubic-lista">
                                <div v-for="item in sucursal.ubicaciones" :key="item.id" class="ubic-it">
                                    <span class="ubic-it__ic">
                                        <EnvironmentOutlined />
                                    </span>
                                    <span class="ubic-it__t">
                                        <span class="ubic-it__n">{{ item.nombre }}</span>
                                        <span class="ubic-it__c">{{ item.codigo || 'Sin código' }}</span>
                                    </span>
                                    <a-tag v-if="item.hijas_count" class="tag-sub">{{ item.hijas_count }} sub</a-tag>
                                </div>
                            </div>
                            <div v-else class="vacio-box">
                                <EnvironmentOutlined />
                                <span>Sin ubicaciones registradas</span>
                            </div>
                        </div>
                    </div>

                    <div class="card card--docs">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <FileTextOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Documentos
                                    <span v-if="sucursal.documentos?.length" class="badge">{{ sucursal.documentos.length
                                    }}</span>
                                </div>
                                <div class="card__sub">Contratos, planos, permisos y certificados</div>
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
                            <ListaDocumentos :documentos="sucursal.documentos ?? []" relacionable-tipo="sucursal"
                                :relacionable-id="sucursal.id" :roles="['contrato', 'plano', 'permiso', 'certificado']"
                                :puede-subir="false" :puede-eliminar="puede('documentos.desactivar')" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <SubirDocumento ref="subirDoc" relacionable-tipo="sucursal" :relacionable-id="sucursal.id"
            :roles="['contrato', 'plano', 'permiso', 'certificado']" />
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
   Layout
   ========================================================== */
.ficha-sucursal {
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-height: 0;
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
   Botón "Gestionar" con color
   ========================================================== */
.btn-gestionar {
    color: #0d84c9 !important;
    font-weight: 700;
    border-radius: 8px;
    padding: 0 10px;
    transition: background 0.14s ease, color 0.14s ease, transform 0.14s ease;
}

.btn-gestionar:hover {
    color: #0f6fb0 !important;
    background: #e6f2fb !important;
    transform: translateY(-1px);
}

/* ==========================================================
   Botones para subir PDF e Imagen
   ========================================================== */
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
   Ubicaciones
   ========================================================== */
.ubic-lista {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.ubic-it {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 12px;
    border: 1px solid var(--sigam-borde);
    border-radius: 10px;
    background: #fff;
    transition: border-color 0.14s ease, box-shadow 0.14s ease, transform 0.14s ease;
}

.ubic-it:hover {
    border-color: var(--sigam-navy-100);
    box-shadow: var(--sigam-sombra-sm);
    transform: translateX(2px);
}

.ubic-it__ic {
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

.ubic-it__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.ubic-it__n {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13px;
}

.ubic-it__c {
    font-size: 11.5px;
    color: var(--sigam-tenue);
}

.tag-sub {
    background: #e6f2fb;
    color: #0d6fae;
    border: 1px solid #cfe3f2;
    font-weight: 800;
    border-radius: 999px;
    padding: 0 10px;
    line-height: 20px;
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
    .ficha-sucursal {
        max-height: calc(100vh - 130px);
    }

    .card__body--scroll {
        max-height: 300px;
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

@media (max-width: 767px) {
    .btn-hero {
        width: 100%;
        justify-content: flex-start;
    }

    .btn-more {
        width: 100%;
    }
}
</style>