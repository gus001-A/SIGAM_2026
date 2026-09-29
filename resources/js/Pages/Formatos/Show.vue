<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckSquareOutlined,
    DatabaseOutlined,
    DeleteOutlined,
    EditOutlined,
    EllipsisOutlined,
    OrderedListOutlined,
    SnippetsOutlined,
    TagOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    formato: { type: Object, required: true },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const confirmar = ref(null);

const inactivo = computed(() => props.formato.estado !== 'activo');
const campos = computed(() => props.formato.campos ?? []);

const ETIQUETAS_TIPO = {
    texto: 'Texto corto', area_texto: 'Texto largo', numero: 'Número', fecha: 'Fecha',
    seleccion: 'Selección', checkbox: 'Casilla', foto: 'Fotografía', firma: 'Firma',
};
const etiquetaTipo = (t) => ETIQUETAS_TIPO[t] ?? t;

/* ==========================================================
   KPIs (uso del formato)
   ========================================================== */
const statsFormato = computed(() => [
    {
        label: 'Campos',
        valor: campos.value.length,
        icono: OrderedListOutlined,
        color: '#0d84c9',
        color2: '#0f6fb0',
        soft: '#e6f2fb',
    },
    {
        label: 'Obligatorios',
        valor: campos.value.filter((c) => c.obligatorio).length,
        icono: CheckSquareOutlined,
        color: '#e08a1e',
        color2: '#a86717',
        soft: '#fdf3e6',
    },
    {
        label: 'Respuestas capturadas',
        valor: props.formato.respuestas_count ?? 0,
        icono: DatabaseOutlined,
        color: '#1f9e86',
        color2: '#16806c',
        soft: '#e4f4ec',
    },
    {
        label: 'Versión',
        valor: props.formato.version,
        icono: TagOutlined,
        color: '#6b4bc9',
        color2: '#563a9e',
        soft: '#efe9fb',
    },
]);

const irA = (n, p) => router.visit(route(n, p));

const desactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${props.formato.nombre}`,
        mensaje: 'El formato dejará de estar disponible para nuevas órdenes. Las respuestas ya capturadas se conservan.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('formatos.destroy', props.formato.id));
};

const reactivar = () => router.put(route('formatos.restore', props.formato.id));

const menuAcciones = [{ key: 'baja', label: 'Desactivar formato', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && desactivar();
</script>

<template>

    <Head :title="formato.nombre" />

    <AppLayout>
        <div class="ficha-compacta">
            <!-- ==========================================================
                 Encabezado
                 ========================================================== -->
            <FichaEncabezado :titulo="formato.nombre" :subtitulo="formato.descripcion" :icono="SnippetsOutlined"
                volver="formatos.index" :sello="sello">
                <template #tags>
                    <a-tag :color="inactivo ? 'default' : 'green'" class="estado-tag">
                        {{ inactivo ? 'Inactivo' : 'Activo' }}
                    </a-tag>
                    <a-tag color="blue" class="estado-tag">v{{ formato.version }}</a-tag>
                </template>
                <template #acciones>
                    <!-- Botón Reactivar (formato inactivo) -->
                    <button
                        v-if="inactivo && puede('formatos.editar')"
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
                            <span class="btn-hero__s">Formato</span>
                        </span>
                    </button>

                    <!-- Botón Editar (formato activo) -->
                    <button
                        v-if="!inactivo && puede('formatos.editar')"
                        type="button"
                        class="btn-hero btn-hero--primary"
                        style="--hc: #0d84c9; --hc2: #0a6ba6"
                        @click="irA('formatos.edit', formato.id)"
                    >
                        <span class="btn-hero__ic">
                            <EditOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Editar</span>
                            <span class="btn-hero__s">Formato</span>
                        </span>
                    </button>

                    <!-- Dropdown de acciones -->
                    <a-dropdown v-if="!inactivo && puede('formatos.desactivar')">
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
                 KPIs
                 ========================================================== -->
            <div class="kpis">
                <div v-for="k in statsFormato" :key="k.label" class="kpi" :style="{
                    '--acc': k.color,
                    '--acc2': k.color2,
                    '--soft': k.soft,
                }">
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
                 Grid: Estructura (izq) + Vista previa (der)
                 ========================================================== -->
            <div class="grid-ficha">
                <!-- COLUMNA IZQUIERDA: ESTRUCTURA -->
                <div class="col-izq">
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #e08a1e">
                                <OrderedListOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Estructura
                                    <span v-if="campos.length" class="badge badge--orange">
                                        {{ campos.length }}
                                    </span>
                                </div>
                                <div class="card__sub">Orden de los campos del formato</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div v-if="!campos.length" class="vacio-box">
                                <OrderedListOutlined />
                                <span>Este formato no tiene campos</span>
                            </div>

                            <ul v-else class="estructura">
                                <li v-for="(item, i) in campos" :key="i" class="estructura__it">
                                    <span class="estructura__num">{{ i + 1 }}</span>
                                    <div class="estructura__body">
                                        <span class="estructura__label">{{ item.etiqueta }}</span>
                                        <span class="estructura__meta">
                                            <a-tag class="estructura__tag">
                                                {{ etiquetaTipo(item.tipo) }}
                                            </a-tag>
                                            <a-tag v-if="item.obligatorio" color="orange" class="estructura__tag">
                                                Obligatorio
                                            </a-tag>
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: VISTA PREVIA -->
                <div class="col-der">
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <SnippetsOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Vista previa</div>
                                <div class="card__sub">Cómo se ve el formato al llenarlo</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div v-if="!campos.length" class="vacio-box">
                                <SnippetsOutlined />
                                <span>Este formato no tiene campos</span>
                            </div>

                            <div v-else class="preview">
                                <div v-for="(campo, i) in campos" :key="i" class="pv-campo">
                                    <label class="pv-campo__label">
                                        {{ campo.etiqueta }}
                                        <span v-if="campo.obligatorio" class="pv-campo__req">*</span>
                                    </label>
                                    <div v-if="campo.ayuda" class="pv-campo__ayuda">{{ campo.ayuda }}</div>

                                    <a-input v-if="campo.tipo === 'texto'" disabled placeholder="Respuesta de texto" />
                                    <a-textarea v-else-if="campo.tipo === 'area_texto'" disabled :rows="2"
                                        placeholder="Respuesta larga" />
                                    <a-input-number v-else-if="campo.tipo === 'numero'" disabled style="width: 100%"
                                        placeholder="0" />
                                    <a-input v-else-if="campo.tipo === 'fecha'" disabled type="date" />
                                    <a-select v-else-if="campo.tipo === 'seleccion'" disabled style="width: 100%"
                                        placeholder="Selecciona…"
                                        :options="(campo.opciones ?? []).map((o) => ({ label: o, value: o }))" />
                                    <a-checkbox v-else-if="campo.tipo === 'checkbox'" disabled>Sí</a-checkbox>
                                    <div v-else-if="campo.tipo === 'foto'" class="pv-placeholder">📷 Captura de
                                        fotografía</div>
                                    <div v-else-if="campo.tipo === 'firma'" class="pv-placeholder">✍️ Firma</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

.estado-tag {
    display: inline-flex;
    align-items: center;
    margin: 0;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 12px;
    line-height: 20px;
    border: none;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
}

/* ==========================================================
   KPIs (uso del formato)
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
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
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* ==========================================================
   Grid de 2 columnas
   ========================================================== */
.grid-ficha {
    display: grid;
    grid-template-columns: 1fr 1.15fr;
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
    font-size: 10.5px;
    font-weight: 800;
}

.badge--orange {
    background: #fdf3e6;
    color: #a86717;
}

/* ==========================================================
   Estructura
   ========================================================== */
.estructura {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.estructura__it {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    background: linear-gradient(120deg, #fff 0%, #fafbff 100%);
    border: 1px solid var(--sigam-borde-suave);
    transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
}

.estructura__it:hover {
    transform: translateY(-1px);
    border-color: #f7e4c4;
    box-shadow: 0 4px 10px -6px rgba(224, 138, 30, 0.35);
}

.estructura__num {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, #e08a1e 0%, #a86717 100%);
    box-shadow: 0 3px 8px -3px rgba(224, 138, 30, 0.55);
}

.estructura__body {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
    flex: 1;
}

.estructura__label {
    font-size: 13px;
    font-weight: 700;
    color: var(--sigam-navy);
    line-height: 1.25;
    word-break: break-word;
}

.estructura__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.estructura__tag {
    display: inline-flex !important;
    align-items: center;
    margin: 0;
    font-size: 10px;
    line-height: 18px;
    padding: 0 8px;
    border-radius: 999px;
    font-weight: 700;
    border: none !important;
}

/* ==========================================================
   Vista previa
   ========================================================== */
.preview {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.pv-campo__label {
    font-weight: 600;
    font-size: 14px;
    display: block;
    margin-bottom: 4px;
    color: var(--sigam-navy);
}

.pv-campo__req {
    color: #d32f2f;
}

.pv-campo__ayuda {
    font-size: 12px;
    color: var(--sigam-tenue);
    margin-bottom: 6px;
}

.pv-placeholder {
    border: 1px dashed #d0d9e2;
    border-radius: 8px;
    padding: 14px;
    text-align: center;
    color: var(--sigam-tenue);
    font-size: 13px;
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