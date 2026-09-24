<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CarOutlined,
    ContactsOutlined,
    DeleteOutlined,
    EditOutlined,
    EnvironmentOutlined,
    IdcardOutlined,
    MailOutlined,
    NumberOutlined,
    PhoneOutlined,
    TagOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ListaDatos from '@/Components/ListaDatos.vue';
import ListaDocumentos from '@/Components/ListaDocumentos.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import { FileTextOutlined } from '@ant-design/icons-vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    proveedor: { type: Object, required: true },
    sello: { type: Object, default: null },
});

const { puede } = usePermisos();
const confirmar = ref(null);

const inactivo = computed(() => props.proveedor.estado !== 'activo');

const irA = (nombre, params) => router.visit(route(nombre, params));

const datos = computed(() => [
    { icono: IdcardOutlined, label: 'Nombre comercial', valor: props.proveedor.nombre_comercial, color: '#173a5f' },
    { icono: NumberOutlined, label: 'RFC', valor: props.proveedor.rfc, color: '#6b4bc9' },
    { icono: TagOutlined, label: 'Especialidad', valor: props.proveedor.especialidad, color: '#1f9e86' },
    { icono: UserOutlined, label: 'Persona de contacto', valor: props.proveedor.contacto, color: '#e08a1e' },
    { icono: PhoneOutlined, label: 'Teléfono', valor: props.proveedor.telefono, color: '#1f9e86' },
    { icono: MailOutlined, label: 'Correo', valor: props.proveedor.correo, color: '#0d84c9' },
    { icono: EnvironmentOutlined, label: 'Dirección', valor: props.proveedor.direccion, color: '#173a5f' },
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

const menuAcciones = [{ key: 'baja', label: 'Desactivar proveedor', danger: true, icon: () => h(DeleteOutlined) }];
const onMenuAccion = ({ key }) => key === 'baja' && desactivar();
</script>

<template>
    <Head :title="proveedor.razon_social" />

    <AppLayout>
        <FichaEncabezado
            :titulo="proveedor.razon_social"
            :subtitulo="proveedor.especialidad"
            :icono="CarOutlined"
            volver="proveedores.index"
            :sello="sello"
        >
            <template #tags>
                <a-tag :color="inactivo ? 'default' : 'green'">{{ inactivo ? 'Inactivo' : 'Activo' }}</a-tag>
            </template>
            <template v-if="!inactivo" #acciones>
                <a-button v-if="puede('proveedores.editar')" type="primary" @click="irA('proveedores.edit', proveedor.id)">
                    <template #icon><EditOutlined /></template>
                    Editar
                </a-button>
                <a-dropdown v-if="puede('proveedores.desactivar')">
                    <a-button type="text"><template #icon><DeleteOutlined /></template></a-button>
                    <template #overlay>
                        <a-menu :items="menuAcciones" @click="onMenuAccion" />
                    </template>
                </a-dropdown>
            </template>
        </FichaEncabezado>

        <a-row :gutter="16">
            <a-col :xs="24" :md="13">
                <a-card size="small">
                    <SeccionFicha titulo="Datos del proveedor" :icono="CarOutlined">
                        <ListaDatos :datos="datos" />
                    </SeccionFicha>
                    <SeccionFicha v-if="proveedor.notas" titulo="Notas" :icono="ContactsOutlined" color="#173a5f">
                        <p class="notas">{{ proveedor.notas }}</p>
                    </SeccionFicha>
                </a-card>
            </a-col>

            <a-col :xs="24" :md="11">
                <a-card size="small">
                    <SeccionFicha :titulo="`Equipos suministrados (${proveedor.equipos?.length ?? 0})`" :icono="ToolOutlined" color="#0d84c9">
                        <div v-if="proveedor.equipos?.length" class="ubic-lista">
                            <button v-for="item in proveedor.equipos" :key="item.id" type="button" class="ubic-it" style="cursor:pointer;width:100%;border:0;background:transparent;text-align:left" @click="irA('equipos.show', item.id)">
                                <span class="ubic-it__ic"><ToolOutlined /></span>
                                <span class="ubic-it__t">
                                    <span class="ubic-it__n">{{ item.codigo_activo }}</span>
                                    <span class="ubic-it__c">{{ item.descripcion }}</span>
                                </span>
                            </button>
                        </div>
                        <a-empty v-else description="Sin equipos asociados a este proveedor" class="py-3" />
                    </SeccionFicha>
                </a-card>
            </a-col>
        </a-row>

        <a-card size="small" class="mt-4">
            <SeccionFicha :titulo="`Documentos (${proveedor.documentos?.length ?? 0})`" :icono="FileTextOutlined" color="#6b4bc9">
                <ListaDocumentos
                    :documentos="proveedor.documentos ?? []"
                    relacionable-tipo="proveedor"
                    :relacionable-id="proveedor.id"
                    :roles="['contrato', 'cotización', 'factura', 'certificado']"
                    :puede-subir="puede('documentos.crear')"
                    :puede-eliminar="puede('documentos.desactivar')"
                />
            </SeccionFicha>
        </a-card>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>
