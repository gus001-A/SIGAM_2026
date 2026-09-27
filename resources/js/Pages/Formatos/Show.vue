<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckSquareOutlined,
    DatabaseOutlined,
    DeleteOutlined,
    EditOutlined,
    OrderedListOutlined,
    SnippetsOutlined,
    TagOutlined,
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

const statsFormato = computed(() => [
    { label: 'Campos', valor: campos.value.length, icono: OrderedListOutlined, color: '#0d84c9' },
    { label: 'Obligatorios', valor: campos.value.filter((c) => c.obligatorio).length, icono: CheckSquareOutlined, color: '#e08a1e' },
    { label: 'Respuestas capturadas', valor: props.formato.respuestas_count ?? 'No especificado', icono: DatabaseOutlined, color: '#1f9e86' },
    { label: 'Versión', valor: props.formato.version, icono: TagOutlined, color: '#6b4bc9' },
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
        <FichaEncabezado :titulo="formato.nombre" :subtitulo="formato.descripcion" :icono="SnippetsOutlined" volver="formatos.index" :sello="sello">
            <template #tags>
                <a-tag :color="inactivo ? 'default' : 'green'">{{ inactivo ? 'Inactivo' : 'Activo' }}</a-tag>
                <a-tag>v{{ formato.version }}</a-tag>
            </template>
            <template #acciones>
                <a-button v-if="inactivo && puede('formatos.editar')" @click="reactivar">Reactivar</a-button>
                <a-button v-if="!inactivo && puede('formatos.editar')" type="primary" @click="irA('formatos.edit', formato.id)">
                    <template #icon><EditOutlined /></template>
                    Editar
                </a-button>
                <a-dropdown v-if="!inactivo && puede('formatos.desactivar')">
                    <a-button type="text"><template #icon><DeleteOutlined /></template></a-button>
                    <template #overlay>
                        <a-menu :items="menuAcciones" @click="onMenuAccion" />
                    </template>
                </a-dropdown>
            </template>
        </FichaEncabezado>

        <div class="grid-ficha">
            <div class="col-izq">
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
                                <a-textarea v-else-if="campo.tipo === 'area_texto'" disabled :rows="2" placeholder="Respuesta larga" />
                                <a-input-number v-else-if="campo.tipo === 'numero'" disabled style="width: 100%" placeholder="0" />
                                <a-input v-else-if="campo.tipo === 'fecha'" disabled type="date" />
                                <a-select v-else-if="campo.tipo === 'seleccion'" disabled style="width: 100%" placeholder="Selecciona…"
                                    :options="(campo.opciones ?? []).map((o) => ({ label: o, value: o }))" />
                                <a-checkbox v-else-if="campo.tipo === 'checkbox'" disabled>Sí</a-checkbox>
                                <div v-else-if="campo.tipo === 'foto'" class="pv-placeholder">📷 Captura de fotografía</div>
                                <div v-else-if="campo.tipo === 'firma'" class="pv-placeholder">✍️ Firma</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-der">
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #6b4bc9">
                            <DatabaseOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Resumen</div>
                            <div class="card__sub">Campos y uso del formato</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <div class="mini-grid mini-grid--2">
                            <div v-for="d in statsFormato" :key="d.label" class="mini" :style="{ '--c': d.color }">
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
                            <OrderedListOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Estructura</div>
                            <div class="card__sub">Orden de los campos del formato</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <a-list :data-source="campos" size="small">
                            <template #renderItem="{ item, index }">
                                <a-list-item>
                                    <a-list-item-meta :title="`${index + 1}. ${item.etiqueta}`">
                                        <template #description>
                                            <a-space :size="4">
                                                <a-tag>{{ etiquetaTipo(item.tipo) }}</a-tag>
                                                <a-tag v-if="item.obligatorio" color="orange">Obligatorio</a-tag>
                                            </a-space>
                                        </template>
                                    </a-list-item-meta>
                                </a-list-item>
                            </template>
                        </a-list>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
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

.card__body {
    padding: 13px 16px;
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
   Caja vacía
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
   Vista previa del formato
   ========================================================== */
.preview { display: flex; flex-direction: column; gap: 18px; }
.pv-campo__label { font-weight: 600; font-size: 14px; display: block; margin-bottom: 4px; }
.pv-campo__req { color: #d32f2f; }
.pv-campo__ayuda { font-size: 12px; color: #64748b; margin-bottom: 6px; }
.pv-placeholder {
    border: 1px dashed #d0d9e2;
    border-radius: 8px;
    padding: 14px;
    text-align: center;
    color: #64748b;
    font-size: 13px;
}

/* ==========================================================
   Responsive
   ========================================================== */
@media (max-width: 1199px) {
    .grid-ficha {
        grid-template-columns: 1fr;
    }
}
</style>
