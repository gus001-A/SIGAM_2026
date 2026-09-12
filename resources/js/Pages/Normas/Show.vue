<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    DeleteOutlined,
    DownloadOutlined,
    EditOutlined,
    EyeOutlined,
    FileProtectOutlined,
    FileTextOutlined,
    NumberOutlined,
    TagOutlined,
    ToolOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDatos from '@/Components/ListaDatos.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    norma: { type: Object, required: true },
});

const { puede } = usePermisos();
const confirmar = ref(null);

const inactiva = computed(() => props.norma.estado !== 'activo');
const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX') : 'No especificado');
const revisionVencida = computed(
    () => props.norma.fecha_revision && new Date(props.norma.fecha_revision) < new Date(),
);

const irA = (n, p) => router.visit(route(n, p));

const esImagenOficial = computed(() => /^image\//.test(props.norma.documento?.tipo_mime || ''));
const previsualizandoOficial = ref(false);
const urlVerOficial = computed(() => (props.norma.documento ? route('documentos.ver', props.norma.documento.id) : null));

const datos = computed(() => [
    { icono: FileProtectOutlined, label: 'Nombre', valor: props.norma.nombre, color: '#173a5f' },
    { icono: NumberOutlined, label: 'Versión', valor: props.norma.version, color: '#6b4bc9' },
    { icono: CalendarOutlined, label: 'Entrada en vigor', valor: fecha(props.norma.fecha_vigencia), color: '#1f9e86' },
    { icono: CalendarOutlined, label: 'Próxima revisión', valor: fecha(props.norma.fecha_revision), color: revisionVencida.value ? '#d64545' : '#e08a1e' },
]);

const stats = computed(() => [
    { label: 'Equipos', valor: props.norma.equipos_count, icono: ToolOutlined, color: '#0d84c9' },
    { label: 'Planes preventivos', valor: props.norma.planes_count, icono: CalendarOutlined, color: '#1f9e86' },
    { label: 'Órdenes', valor: props.norma.mantenimientos_count, icono: FileTextOutlined, color: '#6b4bc9' },
]);

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
</script>

<template>
    <Head :title="norma.codigo" />

    <AppLayout>
        <FichaEncabezado :titulo="norma.codigo" :subtitulo="norma.nombre" :icono="FileProtectOutlined" volver="normas.index">
            <template #tags>
                <a-tag :color="inactiva ? 'default' : 'green'">{{ inactiva ? 'Inactiva' : 'Vigente' }}</a-tag>
                <a-tag v-if="revisionVencida" color="error">Revisión vencida</a-tag>
            </template>
            <template #acciones>
                <a-button v-if="inactiva && puede('normas.editar')" @click="reactivar">
                    <template #icon><UndoOutlined /></template>
                    Reactivar
                </a-button>
                <a-button v-if="!inactiva && puede('normas.editar')" type="primary" @click="irA('normas.edit', norma.id)">
                    <template #icon><EditOutlined /></template>
                    Editar
                </a-button>
                <a-dropdown v-if="!inactiva && puede('normas.desactivar')">
                    <a-button type="text"><template #icon><DeleteOutlined /></template></a-button>
                    <template #overlay>
                        <a-menu :items="menuAcciones" @click="onMenuAccion" />
                    </template>
                </a-dropdown>
            </template>
        </FichaEncabezado>

        <div class="stat-row">
            <div v-for="s in stats" :key="s.label" class="stat" :style="{ '--acc': s.color }">
                <span class="stat__ic"><component :is="s.icono" /></span>
                <div>
                    <div class="stat__v">{{ s.valor }}</div>
                    <div class="stat__l">{{ s.label }}</div>
                </div>
            </div>
        </div>

        <a-row :gutter="16">
            <a-col :xs="24" :md="13">
                <a-card size="small">
                    <SeccionFicha titulo="Datos de la norma" :icono="FileProtectOutlined">
                        <ListaDatos :datos="datos" />
                    </SeccionFicha>
                    <SeccionFicha v-if="norma.descripcion" titulo="Descripción / alcance" :icono="TagOutlined" color="#6b4bc9">
                        <p class="notas">{{ norma.descripcion }}</p>
                    </SeccionFicha>
                    <SeccionFicha v-if="norma.documento" titulo="Documento oficial" :icono="FileTextOutlined" color="#0d84c9">
                        <button type="button" class="doc-of" @click="esImagenOficial ? (previsualizandoOficial = true) : null">
                            <span class="doc-of__ic">
                                <img v-if="esImagenOficial" :src="urlVerOficial" alt="" />
                                <FileTextOutlined v-else />
                            </span>
                            <span class="doc-of__n">{{ norma.documento.nombre_original }}</span>
                            <a-button v-if="esImagenOficial" size="small" @click.stop="previsualizandoOficial = true">
                                <template #icon><EyeOutlined /></template>Ver
                            </a-button>
                            <a v-else :href="urlVerOficial" target="_blank" @click.stop>
                                <a-button size="small"><template #icon><EyeOutlined /></template>Ver</a-button>
                            </a>
                            <a :href="route('documentos.download', norma.documento.id)" target="_blank" @click.stop>
                                <a-button size="small"><template #icon><DownloadOutlined /></template>Descargar</a-button>
                            </a>
                        </button>
                    </SeccionFicha>
                </a-card>
            </a-col>

            <a-col :xs="24" :md="11">
                <a-card size="small">
                    <SeccionFicha :titulo="`Equipos que aplican esta norma (${norma.equipos?.length ?? 0})`" :icono="ToolOutlined" color="#1f9e86">
                        <div v-if="norma.equipos?.length" class="ubic-lista">
                            <button v-for="item in norma.equipos" :key="item.id" type="button" class="ubic-it" style="cursor:pointer;width:100%;border:0;background:transparent;text-align:left" @click="irA('equipos.show', item.id)">
                                <span class="ubic-it__ic"><ToolOutlined /></span>
                                <span class="ubic-it__t">
                                    <span class="ubic-it__n">{{ item.codigo_activo }}</span>
                                    <span class="ubic-it__c">{{ item.descripcion }}</span>
                                </span>
                            </button>
                        </div>
                        <a-empty v-else description="Ningún equipo tiene asociada esta norma" class="py-3" />
                    </SeccionFicha>
                </a-card>
            </a-col>
        </a-row>

        <a-card size="small" class="mt-4">
            <SeccionFicha :titulo="`Anexos adicionales (${norma.documentos?.length ?? 0})`" :icono="FileTextOutlined" color="#6b4bc9">
                <ListaDocumentos
                    :documentos="norma.documentos ?? []"
                    relacionable-tipo="norma"
                    :relacionable-id="norma.id"
                    :roles="['evidencia', 'certificado', 'referencia']"
                    :puede-subir="puede('documentos.crear')"
                    :puede-eliminar="puede('documentos.desactivar')"
                />
            </SeccionFicha>
        </a-card>

        <a-modal
            :open="previsualizandoOficial"
            :footer="null"
            :width="620"
            centered
            :title="norma.documento?.nombre_original"
            @cancel="previsualizandoOficial = false"
        >
            <img v-if="norma.documento" :src="urlVerOficial" :alt="norma.documento.nombre_original" class="doc-of__prev" />
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
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
</style>
