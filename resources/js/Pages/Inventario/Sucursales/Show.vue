<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    DeleteOutlined,
    EditOutlined,
    EnvironmentOutlined,
    MailOutlined,
    PhoneOutlined,
    ToolOutlined,
    UndoOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDatos from '@/Components/ListaDatos.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import { FileTextOutlined } from '@ant-design/icons-vue';
import { DollarOutlined, IdcardOutlined, ShopOutlined } from '@ant-design/icons-vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    sucursal: { type: Object, required: true },
    valorActivos: { type: Number, default: 0 },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const confirmar = ref(null);

const inactiva = computed(() => props.sucursal.estado !== 'activo');

const moneda = (v) =>
    v == null ? 'No especificado' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(v);

const irA = (nombre, params) => router.visit(route(nombre, params));

const datos = computed(() => [
    { icono: IdcardOutlined, label: 'Código', valor: props.sucursal.codigo, color: '#173a5f' },
    { icono: EnvironmentOutlined, label: 'Dirección', valor: props.sucursal.direccion, color: '#0d84c9' },
    { icono: PhoneOutlined, label: 'Teléfono', valor: props.sucursal.telefono, color: '#1f9e86' },
    { icono: MailOutlined, label: 'Correo', valor: props.sucursal.correo, color: '#6b4bc9' },
    { icono: UserOutlined, label: 'Responsable', valor: props.sucursal.responsable?.nombre, color: '#e08a1e' },
]);

const stats = computed(() => [
    { label: 'Equipos', valor: props.sucursal.equipos_count, icono: ToolOutlined, color: '#0d84c9' },
    { label: 'Ubicaciones', valor: props.sucursal.ubicaciones_count, icono: EnvironmentOutlined, color: '#1f9e86' },
    { label: 'Usuarios', valor: props.sucursal.usuarios_count, icono: UserOutlined, color: '#6b4bc9' },
    { label: 'Valor de activos', valor: moneda(props.valorActivos), icono: DollarOutlined, color: '#e08a1e' },
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
</script>

<template>
    <Head :title="sucursal.nombre" />

    <AppLayout>
        <FichaEncabezado :titulo="sucursal.nombre" :subtitulo="sucursal.codigo" :icono="ShopOutlined" volver="sucursales.index" :sello="sello">
            <template #tags>
                <a-tag :color="inactiva ? 'default' : 'green'">{{ inactiva ? 'Inactiva' : 'Activa' }}</a-tag>
            </template>
            <template #acciones>
                <a-button v-if="inactiva && puede('sucursales.editar')" @click="reactivar">
                    <template #icon><UndoOutlined /></template>
                    Reactivar
                </a-button>
                <a-button v-if="!inactiva && puede('sucursales.editar')" type="primary" @click="irA('sucursales.edit', sucursal.id)">
                    <template #icon><EditOutlined /></template>
                    Editar
                </a-button>
                <a-dropdown v-if="!inactiva && puede('sucursales.desactivar')">
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
            <a-col :xs="24" :md="12">
                <a-card size="small" class="mb-4">
                    <SeccionFicha titulo="Datos de la sucursal" :icono="ShopOutlined">
                        <ListaDatos :datos="datos" />
                    </SeccionFicha>
                    <SeccionFicha v-if="sucursal.notas" titulo="Notas" :icono="IdcardOutlined" color="#173a5f">
                        <p class="notas">{{ sucursal.notas }}</p>
                    </SeccionFicha>
                    <a-space class="mt-2" wrap>
                        <a-button size="small" @click="router.visit(route('equipos.por_sucursal', { sucursal_id: sucursal.id }))">
                            <template #icon><ToolOutlined /></template>
                            Ver equipos
                        </a-button>
                        <a-button size="small" @click="router.visit(route('ubicaciones.index', { sucursal_id: sucursal.id }))">
                            <template #icon><ApartmentOutlined /></template>
                            Ver ubicaciones
                        </a-button>
                    </a-space>
                </a-card>
            </a-col>

            <a-col :xs="24" :md="12">
                <a-card size="small">
                    <SeccionFicha :titulo="`Ubicaciones principales (${sucursal.ubicaciones_count})`" :icono="EnvironmentOutlined" color="#1f9e86">
                        <template #extra>
                            <a-button v-if="puede('ubicaciones.ver')" type="link" size="small" @click="router.visit(route('ubicaciones.index', { sucursal_id: sucursal.id }))">
                                Gestionar
                            </a-button>
                        </template>
                        <div v-if="sucursal.ubicaciones.length" class="ubic-lista">
                            <div v-for="item in sucursal.ubicaciones" :key="item.id" class="ubic-it">
                                <span class="ubic-it__ic"><EnvironmentOutlined /></span>
                                <span class="ubic-it__t">
                                    <span class="ubic-it__n">{{ item.nombre }}</span>
                                    <span class="ubic-it__c">{{ item.codigo || 'Sin código' }}</span>
                                </span>
                                <a-tag v-if="item.hijas_count">{{ item.hijas_count }} sub</a-tag>
                            </div>
                        </div>
                        <a-empty v-else description="Sin ubicaciones registradas" class="py-3" />
                    </SeccionFicha>
                </a-card>
            </a-col>
        </a-row>

        <a-card size="small" class="mt-4">
            <SeccionFicha :titulo="`Documentos (${sucursal.documentos?.length ?? 0})`" :icono="FileTextOutlined" color="#6b4bc9">
                <ListaDocumentos
                    :documentos="sucursal.documentos ?? []"
                    relacionable-tipo="sucursal"
                    :relacionable-id="sucursal.id"
                    :roles="['contrato', 'plano', 'permiso', 'certificado']"
                    :puede-subir="puede('documentos.crear')"
                    :puede-eliminar="puede('documentos.desactivar')"
                />
            </SeccionFicha>
        </a-card>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>
