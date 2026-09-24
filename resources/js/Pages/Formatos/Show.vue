<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { DeleteOutlined, EditOutlined, SnippetsOutlined } from '@ant-design/icons-vue';
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

        <a-row :gutter="16">
            <a-col :xs="24" :md="14">
                <a-card title="Vista previa" size="small">
                    <a-empty v-if="!campos.length" description="Este formato no tiene campos" />

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
                </a-card>
            </a-col>

            <a-col :xs="24" :md="10">
                <a-card size="small" class="mb-4">
                    <a-descriptions bordered :column="1" size="small">
                        <a-descriptions-item label="Campos">{{ campos.length }}</a-descriptions-item>
                        <a-descriptions-item label="Obligatorios">{{ campos.filter((c) => c.obligatorio).length }}</a-descriptions-item>
                        <a-descriptions-item label="Respuestas capturadas">{{ formato.respuestas_count ?? 'No especificado' }}</a-descriptions-item>
                        <a-descriptions-item label="Versión">{{ formato.version }}</a-descriptions-item>
                    </a-descriptions>
                </a-card>

                <a-card title="Estructura" size="small">
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
                </a-card>
            </a-col>
        </a-row>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
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
</style>
