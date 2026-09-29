<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    DeleteOutlined,
    DownloadOutlined,
    EditOutlined,
    EllipsisOutlined,
    EyeOutlined,
    FileImageOutlined,
    FilePdfOutlined,
    FileProtectOutlined,
    FileTextOutlined,
    NumberOutlined,
    PaperClipOutlined,
    ToolOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import ModalFicha from '@/Components/ModalFicha.vue';
import SubirDocumento from '@/Components/SubirDocumento.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    norma: { type: Object, required: true },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const confirmar = ref(null);

const inactiva = computed(() => props.norma.estado !== 'activo');
const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const revisionVencida = computed(
    () => props.norma.fecha_revision && new Date(props.norma.fecha_revision) < new Date(),
);

const irA = (n, p) => router.visit(route(n, p));

/* ==========================================================
   Documento oficial
   ========================================================== */
const esImagenOficial = computed(() => /^image\//.test(props.norma.documento?.tipo_mime || ''));
const previsualizandoOficial = ref(false);
const urlVerOficial = computed(() =>
    props.norma.documento ? route('documentos.ver', props.norma.documento.id) : null,
);

/* ==========================================================
   Datos clave
   ========================================================== */
const datosNorma = computed(() => [
    {
        icono: FileProtectOutlined,
        label: 'Nombre',
        valor: props.norma.nombre,
        color: '#0d84c9',
    },
    {
        icono: NumberOutlined,
        label: 'Versión',
        valor: props.norma.version || 'No especificado',
        color: '#6b4bc9',
    },
    {
        icono: CalendarOutlined,
        label: 'Entrada en vigor',
        valor: fecha(props.norma.fecha_vigencia),
        color: '#1f9e86',
    },
    {
        icono: CalendarOutlined,
        label: 'Próxima revisión',
        valor: fecha(props.norma.fecha_revision),
        color: revisionVencida.value ? '#d64545' : '#e08a1e',
    },
]);

/* ==========================================================
   KPIs (uso de la norma)
   ========================================================== */
const stats = computed(() => [
    {
        label: 'Equipos',
        valor: props.norma.equipos_count ?? 0,
        icono: ToolOutlined,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
    },
    {
        label: 'Planes preventivos',
        valor: props.norma.planes_count ?? 0,
        icono: CalendarOutlined,
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e4f4ec',
    },
    {
        label: 'Órdenes',
        valor: props.norma.mantenimientos_count ?? 0,
        icono: FileTextOutlined,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
    },
]);

/* ==========================================================
   Acciones
   ========================================================== */
const desactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${props.norma.codigo}`,
        mensaje: 'La norma se conservará en el historial pero dejará de estar disponible para asociar.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('normas.destroy', props.norma.id));
};

const reactivar = () => router.put(route('normas.restore', props.norma.id));

const menuAcciones = [{ key: 'baja', label: 'Desactivar norma', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && desactivar();

/* ==========================================================
   Documentos anexos (igual que Tareas)
   ========================================================== */
const documentosNorma = computed(() => props.norma.documentos ?? []);
const modalDocumentos = ref(false);
const subirDoc = ref(null);

const iconoDocumento = (doc) => {
    const mime = doc?.mime ?? doc?.tipo ?? '';
    if (mime.includes('pdf')) return FilePdfOutlined;
    if (mime.includes('image')) return FileImageOutlined;
    return FileTextOutlined;
};

const claseDocumento = (doc) => {
    const mime = doc?.mime ?? doc?.tipo ?? '';
    if (mime.includes('image')) return 'img';
    if (mime.includes('pdf')) return 'pdf';
    return 'doc';
};

const tamañoLegible = (bytes) => {
    if (!bytes) return '—';
    const u = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let n = Number(bytes);
    while (n >= 1024 && i < u.length - 1) {
        n /= 1024;
        i++;
    }
    return `${n.toFixed(n >= 10 || i === 0 ? 0 : 1)} ${u[i]}`;
};

const abrirPdf = () => subirDoc.value?.abrirPdf();
const abrirImagen = () => subirDoc.value?.abrirImagen();
</script>

<template>
    <Head :title="norma.codigo" />

    <AppLayout>
        <div class="ficha-compacta">
            <!-- ==========================================================
                 Encabezado
                 ========================================================== -->
            <FichaEncabezado
                :titulo="norma.codigo"
                :subtitulo="norma.nombre"
                :icono="FileProtectOutlined"
                volver="normas.index"
                :sello="sello"
            >
                <template #tags>
                    <a-tag :color="inactiva ? 'default' : 'green'" class="estado-tag">
                        {{ inactiva ? 'Inactiva' : 'Vigente' }}
                    </a-tag>
                    <a-tag v-if="revisionVencida" color="error" class="estado-tag">
                        Revisión vencida
                    </a-tag>
                </template>
                <template #acciones>
                    <!-- Botón Reactivar (norma inactiva) -->
                    <button
                        v-if="inactiva && puede('normas.editar')"
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
                            <span class="btn-hero__s">Norma</span>
                        </span>
                    </button>

                    <!-- Botón Editar (norma activa) -->
                    <button
                        v-if="!inactiva && puede('normas.editar')"
                        type="button"
                        class="btn-hero btn-hero--primary"
                        style="--hc: #0d84c9; --hc2: #0a6ba6"
                        @click="irA('normas.edit', norma.id)"
                    >
                        <span class="btn-hero__ic">
                            <EditOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Editar</span>
                            <span class="btn-hero__s">Norma</span>
                        </span>
                    </button>

                    <!-- Dropdown de acciones -->
                    <a-dropdown v-if="!inactiva && puede('normas.desactivar')">
                        <button type="button" class="btn-more">
                            <EllipsisOutlined />
                        </button>
                        <template #overlay>
                            <a-menu :items="menuAcciones" @click="onMenuAccion" />
                        </template>
                    </a-dropdown>
                </template>
            </FichaEncabezado>

            <!-- ==========================================================
                 KPIs (uso de la norma)
                 ========================================================== -->
            <div class="kpis">
                <div
                    v-for="k in stats"
                    :key="k.label"
                    class="kpi"
                    :style="{
                        '--acc': k.color,
                        '--acc2': k.color2,
                        '--soft': k.soft,
                    }"
                >
                    <div class="kpi__glow"></div>
                    <div class="kpi__icono">
                        <component :is="k.icono" />
                    </div>
                    <div class="kpi__txt">
                        <div class="kpi__valor">{{ k.valor }}</div>
                        <div class="kpi__etq">{{ k.label }}</div>
                    </div>
                </div>
            </div>

            <!-- ==========================================================
                 Grid 2 columnas
                 ========================================================== -->
            <div class="grid-ficha">
                <!-- COLUMNA IZQUIERDA -->
                <div class="col-izq">
                    <!-- Datos de la norma -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <FileProtectOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Datos de la norma</div>
                                <div class="card__sub">Información general</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--2">
                                <div
                                    v-for="d in datosNorma"
                                    :key="d.label"
                                    class="mini"
                                    :style="{ '--c': d.color }"
                                >
                                    <span class="mini__ic">
                                        <component :is="d.icono" />
                                    </span>
                                    <span class="mini__t">
                                        <span class="mini__l">{{ d.label }}</span>
                                        <span class="mini__v">{{ d.valor }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Descripción (dentro de la card) -->
                            <div v-if="norma.descripcion" class="descripcion">
                                <span class="descripcion__label">Descripción / alcance</span>
                                <p class="descripcion__texto">{{ norma.descripcion }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Documento oficial -->
                    <div v-if="norma.documento" class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <FileTextOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Documento oficial</div>
                                <div class="card__sub">Archivo principal de esta norma</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <button
                                type="button"
                                class="doc-of"
                                @click="esImagenOficial ? (previsualizandoOficial = true) : null"
                            >
                                <span class="doc-of__ic">
                                    <img v-if="esImagenOficial" :src="urlVerOficial" alt="" />
                                    <FileTextOutlined v-else />
                                </span>
                                <span class="doc-of__n">{{ norma.documento.nombre_original }}</span>
                                <a-button
                                    v-if="esImagenOficial"
                                    size="small"
                                    @click.stop="previsualizandoOficial = true"
                                >
                                    <template #icon><EyeOutlined /></template>
                                    Ver
                                </a-button>
                                <a v-else :href="urlVerOficial" target="_blank" @click.stop>
                                    <a-button size="small">
                                        <template #icon><EyeOutlined /></template>
                                        Ver
                                    </a-button>
                                </a>
                                <a
                                    :href="route('documentos.download', norma.documento.id)"
                                    target="_blank"
                                    @click.stop
                                >
                                    <a-button size="small">
                                        <template #icon><DownloadOutlined /></template>
                                        Descargar
                                    </a-button>
                                </a>
                            </button>
                        </div>
                    </div>

                    <!-- ==========================================================
                         Anexos (igual que Tareas: botones PDF/Imagen + grid)
                         ========================================================== -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <FileTextOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Anexos adicionales
                                    <span v-if="documentosNorma.length" class="badge badge--purple">
                                        {{ documentosNorma.length }}
                                    </span>
                                </div>
                                <div class="card__sub">
                                    Evidencias, certificados y documentos de referencia
                                </div>
                            </div>

                            <a-space v-if="puede('documentos.crear')" class="card__extra">
                                <a-button class="btn-doc btn-doc--pdf" size="small" @click="abrirPdf">
                                    <template #icon><FilePdfOutlined /></template>
                                    PDF
                                </a-button>
                                <a-button class="btn-doc btn-doc--img" size="small" @click="abrirImagen">
                                    <template #icon><FileImageOutlined /></template>
                                    Imagen
                                </a-button>
                            </a-space>

                            <a-button
                                v-else-if="documentosNorma.length"
                                class="card__extra"
                                size="small"
                                type="text"
                                @click="modalDocumentos = true"
                            >
                                <template #icon><EyeOutlined /></template>
                                Ver todos
                            </a-button>
                        </div>
                        <div class="card__body">
                            <div v-if="documentosNorma.length" class="docs-grid">
                                <article
                                    v-for="doc in documentosNorma.slice(0, 4)"
                                    :key="doc.id"
                                    class="doc-card"
                                    @click="modalDocumentos = true"
                                >
                                    <div class="doc-card__ico" :class="`doc-card__ico--${claseDocumento(doc)}`">
                                        <component :is="iconoDocumento(doc)" />
                                    </div>
                                    <div class="doc-card__body">
                                        <div class="doc-card__nombre" :title="doc.nombre_original">
                                            {{ doc.titulo || doc.nombre_original }}
                                        </div>
                                        <div class="doc-card__meta">
                                            <span v-if="doc.tipo" class="doc-card__tipo">
                                                {{ doc.tipo }}
                                            </span>
                                            <span class="doc-card__size">
                                                {{ tamañoLegible(doc.tamaño) }}
                                            </span>
                                        </div>
                                    </div>
                                    <a
                                        class="doc-card__accion"
                                        :href="doc.url"
                                        target="_blank"
                                        title="Abrir"
                                        @click.stop
                                    >
                                        <EyeOutlined />
                                    </a>
                                </article>
                                <button
                                    v-if="documentosNorma.length > 4"
                                    type="button"
                                    class="docs-mas"
                                    @click="modalDocumentos = true"
                                >
                                    +{{ documentosNorma.length - 4 }} más
                                </button>
                            </div>
                            <div v-else class="vacio-box">
                                <PaperClipOutlined />
                                <span>Sin documentos adjuntos</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="col-der">
                    <!-- Equipos que aplican esta norma -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <ToolOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Equipos que aplican
                                    <span v-if="norma.equipos?.length" class="badge badge--blue">
                                        {{ norma.equipos.length }}
                                    </span>
                                </div>
                                <div class="card__sub">Equipos con esta norma asociada</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div v-if="norma.equipos?.length" class="equipos">
                                <button
                                    v-for="item in norma.equipos"
                                    :key="item.id"
                                    type="button"
                                    class="equipo"
                                    @click="irA('equipos.show', item.id)"
                                >
                                    <span class="equipo__ic">
                                        <ToolOutlined />
                                    </span>
                                    <span class="equipo__t">
                                        <span class="equipo__n">{{ item.codigo_activo }}</span>
                                        <span class="equipo__c">{{ item.descripcion }}</span>
                                    </span>
                                </button>
                            </div>
                            <div v-else class="vacio-box">
                                <ToolOutlined />
                                <span>Ningún equipo tiene asociada esta norma</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================================
             Modal: documentos (igual que Tareas)
             ========================================================== -->
        <ModalFicha
            :show="modalDocumentos"
            titulo="Documentos"
            :subtitulo="`${documentosNorma.length} archivo(s)`"
            :icono="PaperClipOutlined"
            color="#6b4bc9"
            max-width="lg"
            @close="modalDocumentos = false"
        >
            <ListaDocumentos
                :documentos="documentosNorma"
                relacionable-tipo="norma"
                :relacionable-id="norma.id"
                :roles="['evidencia', 'certificado', 'referencia']"
                :puede-subir="puede('documentos.crear')"
                :puede-eliminar="puede('documentos.desactivar')"
            />
        </ModalFicha>

        <!-- Subir documento (ref expone abrirPdf() y abrirImagen()) -->
        <SubirDocumento
            ref="subirDoc"
            relacionable-tipo="norma"
            :relacionable-id="norma.id"
            :roles="['evidencia', 'certificado', 'referencia']"
        />

        <!-- Modal: previsualizar documento oficial (imagen) -->
        <a-modal
            :open="previsualizandoOficial"
            :footer="null"
            :width="720"
            centered
            :title="norma.documento?.nombre_original"
            @cancel="previsualizandoOficial = false"
        >
            <img
                v-if="norma.documento"
                :src="urlVerOficial"
                :alt="norma.documento.nombre_original"
                class="doc-of__prev"
            />
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
   Layout base
   ========================================================== */
.ficha-compacta {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ==========================================================
   KPIs (uso de la norma)
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 11px;
}

.kpi {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 14px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 2px 6px -3px rgba(15, 37, 71, 0.1);
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.kpi:hover {
    transform: translateY(-2px);
    border-color: var(--acc);
    box-shadow: 0 8px 20px -10px rgba(15, 37, 71, 0.35);
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, var(--acc) 0%, var(--acc2) 100%);
}

.kpi__glow {
    position: absolute;
    right: -30px;
    top: -30px;
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: radial-gradient(circle, var(--soft) 0%, transparent 70%);
    opacity: 0.9;
    pointer-events: none;
}

.kpi__icono {
    position: relative;
    z-index: 1;
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    background: linear-gradient(135deg, var(--acc) 0%, var(--acc2) 100%);
    flex-shrink: 0;
    box-shadow: 0 4px 10px -3px rgba(15, 37, 71, 0.35);
}

.kpi__txt {
    position: relative;
    z-index: 1;
    min-width: 0;
}

.kpi__valor {
    font-size: 20px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.05;
    letter-spacing: -0.5px;
}

.kpi__etq {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
    color: #7b8a9c;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}

@media (max-width: 767px) {
    .kpis {
        grid-template-columns: 1fr;
    }
}

/* ==========================================================
   Grid de 2 columnas
   ========================================================== */
.grid-ficha {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 12px;
    align-items: start;
}

.col-izq,
.col-der {
    display: flex;
    flex-direction: column;
    gap: 12px;
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
    padding: 11px 15px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 70%);
}

.card__ico {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 14%, #fff);
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
    font-size: 13px;
    color: var(--sigam-navy);
    letter-spacing: -0.1px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.card__sub {
    font-size: 10.5px;
    color: var(--sigam-tenue);
}

.card__extra {
    flex-shrink: 0;
}

.card__body {
    padding: 12px 15px;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    background: #eef4fb;
    color: #0f6fb0;
    font-size: 10.5px;
    font-weight: 800;
}

.badge--blue {
    background: #eef4fb;
    color: #0f6fb0;
}

.badge--purple {
    background: #efe9fb;
    color: #6b4bc9;
}

/* ==========================================================
   Mini-cards de datos
   ========================================================== */
.mini-grid {
    display: grid;
    gap: 8px;
}

.mini-grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.mini {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 10px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--c) 6%, #fff);
    border: 1px solid color-mix(in srgb, var(--c) 18%, transparent);
    transition: transform 0.14s ease, box-shadow 0.14s ease;
    min-width: 0;
}

.mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px color-mix(in srgb, var(--c) 20%, transparent);
}

.mini__ic {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
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
    font-size: 9.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    line-height: 1.1;
}

.mini__v {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--sigam-texto);
    word-break: break-word;
    line-height: 1.22;
}

/* ==========================================================
   Descripción
   ========================================================== */
.descripcion {
    margin-top: 12px;
    padding: 11px 13px;
    border-radius: 11px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid var(--sigam-borde-suave);
}

.descripcion__label {
    display: inline-block;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--sigam-tenue);
    margin-bottom: 4px;
}

.descripcion__texto {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: var(--sigam-texto);
    white-space: pre-line;
}

/* ==========================================================
   Documento oficial
   ========================================================== */
.doc-of {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--sigam-borde);
    border-radius: 11px;
    background: var(--sigam-navy-050);
    cursor: default;
    font-family: inherit;
}

.doc-of__ic {
    width: 32px;
    height: 32px;
    flex: none;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    color: #0d6ca6;
    background: #e8f3fb;
}

.doc-of__ic img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.doc-of__n {
    flex: 1;
    min-width: 0;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: var(--sigam-texto);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.doc-of__prev {
    display: block;
    width: 100%;
    max-height: 70vh;
    object-fit: contain;
    background: #0f2c4a;
}

/* ==========================================================
   Botones PDF / Imagen (igual que Tareas)
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
   Documentos: grid de cards (igual que Tareas)
   ========================================================== */
.docs-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.doc-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 11px;
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    cursor: pointer;
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
    min-width: 0;
}

.doc-card:hover {
    transform: translateY(-1px);
    border-color: #cfe3f2;
    box-shadow: 0 5px 14px -8px rgba(13, 132, 201, 0.4);
}

.doc-card__ico {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
}

.doc-card__ico--img {
    background: linear-gradient(135deg, #6b4bc9 0%, #563a9e 100%);
}

.doc-card__ico--pdf {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

.doc-card__ico--doc {
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
}

.doc-card__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.doc-card__nombre {
    font-size: 12px;
    font-weight: 700;
    color: #173a5f;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.doc-card__meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 10.5px;
    color: #7b8a9c;
}

.doc-card__tipo {
    display: inline-flex;
    align-items: center;
    padding: 1px 6px;
    border-radius: 999px;
    background: #eef4fb;
    color: #0d6ca6;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-size: 9.5px;
}

.doc-card__size {
    font-variant-numeric: tabular-nums;
}

.doc-card__accion {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #0d6ca6;
    background: #eef4fb;
    font-size: 12px;
    transition: background 0.14s ease, color 0.14s ease;
}

.doc-card__accion:hover {
    background: #0d84c9;
    color: #fff;
}

.docs-mas {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 10px;
    border: 1px dashed #cdd8e3;
    border-radius: 9px;
    background: #f7fafd;
    color: #0d6ca6;
    font-family: inherit;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
}

.docs-mas:hover {
    background: #e8f3fb;
    border-color: #0d84c9;
    transform: translateY(-1px);
}

/* ==========================================================
   Lista de equipos
   ========================================================== */
.equipos {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.equipo {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 10px;
    border: 1px solid var(--sigam-borde-suave);
    background: #fff;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    width: 100%;
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.equipo:hover {
    transform: translateY(-1px);
    border-color: #cfe4f5;
    box-shadow: 0 4px 10px -6px rgba(13, 132, 201, 0.28);
}

.equipo__ic {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    background: linear-gradient(135deg, #0d84c9 0%, #0a6ba6 100%);
    box-shadow: 0 3px 8px -3px rgba(13, 132, 201, 0.55);
}

.equipo__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 1px;
    flex: 1;
}

.equipo__n {
    font-size: 12.5px;
    font-weight: 800;
    color: #173a5f;
    line-height: 1.2;
}

.equipo__c {
    font-size: 11px;
    color: var(--sigam-tenue);
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ==========================================================
   Vacio
   ========================================================== */
.vacio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 18px 12px;
    border-radius: 11px;
    border: 1px dashed var(--sigam-borde);
    background: var(--sigam-navy-050);
    color: var(--sigam-tenue);
    font-size: 12px;
}

.vacio-box .anticon {
    font-size: 18px;
    opacity: 0.5;
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
    .mini-grid--2 {
        grid-template-columns: 1fr;
    }

    .docs-grid {
        grid-template-columns: 1fr;
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