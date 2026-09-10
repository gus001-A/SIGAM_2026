<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CalendarOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    KeyOutlined,
    MailOutlined,
    PhoneOutlined,
    SafetyCertificateOutlined,
    TeamOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import { usePermisos } from '@/composables/usePermisos';
import { colorRol, etiquetaRol } from '@/utils/roles';

const props = defineProps({
    usuario: { type: Object, required: true },
    permisosEfectivos: { type: Array, default: () => [] },
    actividad: { type: Array, default: () => [] },
});

const { puede, usuario: yo } = usePermisos();
const confirmar = ref(null);

const inactivo = computed(() => props.usuario.estado !== 'activo');
const esYo = computed(() => props.usuario.id === yo.value?.id);

const iniciales = computed(() =>
    props.usuario.nombre_completo.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase(),
);

const dato = (v) => v || 'No especificado';
const fechaHora = (v) =>
    v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado';

const filas = computed(() => [
    { icono: MailOutlined, label: 'Correo', valor: props.usuario.email, color: '#0d84c9' },
    { icono: PhoneOutlined, label: 'Teléfono', valor: dato(props.usuario.telefono), color: '#1f9e86' },
    { icono: ApartmentOutlined, label: 'Sucursal', valor: dato(props.usuario.sucursal?.nombre), color: '#6b4bc9' },
    { icono: ClockCircleOutlined, label: 'Último acceso', valor: fechaHora(props.usuario.ultimo_acceso_at), color: '#e08a1e' },
    { icono: CalendarOutlined, label: 'Fecha de alta', valor: fechaHora(props.usuario.created_at), color: '#173a5f' },
]);

const irA = (n, p) => router.visit(route(n, p));

const restablecer = async () => {
    const ok = await confirmar.value.abrir({
        titulo: 'Restablecer contraseña',
        mensaje: `Se enviará un enlace de restablecimiento a ${props.usuario.email}.`,
        confirmar: 'Enviar enlace',
    });
    if (ok) router.post(route('usuarios.restablecer_contrasena', props.usuario.id), {}, { preserveScroll: true });
};

const desactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar a ${props.usuario.nombre_completo}`,
        mensaje: 'El usuario no podrá iniciar sesión. Su historial se conserva.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('usuarios.destroy', props.usuario.id));
};

const reactivar = () => router.put(route('usuarios.restore', props.usuario.id));

const menuAcciones = computed(() => [
    { key: 'clave', label: 'Restablecer contraseña', icon: () => h(KeyOutlined) },
    ...(esYo.value ? [] : [{ key: 'baja', label: 'Desactivar usuario', danger: true, icon: () => h(DeleteOutlined) }]),
]);
const onMenuAccion = ({ key }) => {
    if (key === 'clave') restablecer();
    if (key === 'baja') desactivar();
};
</script>

<template>
    <Head :title="usuario.nombre_completo" />

    <AppLayout>
        <FichaEncabezado
            :titulo="usuario.nombre_completo"
            :subtitulo="usuario.email"
            :iniciales="iniciales"
            :icono="TeamOutlined"
            volver="usuarios.index"
        >
            <template #tags>
                <a-tag :color="inactivo ? 'default' : 'green'">{{ inactivo ? 'Inactivo' : 'Activo' }}</a-tag>
                <a-tag :color="usuario.email_verified_at ? 'green' : 'orange'">
                    <SafetyCertificateOutlined /> {{ usuario.email_verified_at ? 'Correo verificado' : 'Sin verificar' }}
                </a-tag>
            </template>
            <template #acciones>
                <a-button v-if="inactivo && puede('usuarios.editar')" @click="reactivar">
                    <template #icon><UndoOutlined /></template>
                    Reactivar
                </a-button>
                <a-button v-if="!inactivo && puede('usuarios.editar')" type="primary" @click="irA('usuarios.edit', usuario.id)">
                    <template #icon><EditOutlined /></template>
                    Editar
                </a-button>
                <a-dropdown v-if="!inactivo && puede('usuarios.editar')">
                    <a-button type="text"><template #icon><KeyOutlined /></template></a-button>
                    <template #overlay>
                        <a-menu :items="menuAcciones" @click="onMenuAccion" />
                    </template>
                </a-dropdown>
            </template>
        </FichaEncabezado>

        <a-row :gutter="16">
            <a-col :xs="24" :md="10">
                <a-card size="small" class="mb-4 tarjeta">
                    <template #title><span class="tt"><MailOutlined /> Información de la cuenta</span></template>
                    <ul class="datos">
                        <li v-for="f in filas" :key="f.label">
                            <span class="datos__ic" :style="{ color: f.color, background: f.color + '18' }">
                                <component :is="f.icono" />
                            </span>
                            <span class="datos__t">
                                <span class="datos__l">{{ f.label }}</span>
                                <span class="datos__v">{{ f.valor }}</span>
                            </span>
                        </li>
                    </ul>
                </a-card>

                <a-card size="small" class="tarjeta">
                    <template #title><span class="tt"><SafetyCertificateOutlined /> Roles asignados</span></template>
                    <a-space wrap>
                        <a-tag v-for="r in usuario.roles" :key="r" :color="colorRol(r)" class="rol-tag">
                            {{ etiquetaRol(r) }}
                        </a-tag>
                        <span v-if="!usuario.roles.length" class="vacio">Sin roles asignados</span>
                    </a-space>
                </a-card>
            </a-col>

            <a-col :xs="24" :md="14">
                <a-card size="small" class="tarjeta">
                    <template #title><span class="tt"><ClockCircleOutlined /> Actividad reciente</span></template>
                    <a-timeline v-if="actividad.length" class="mt-2">
                        <a-timeline-item v-for="a in actividad" :key="a.id" color="#1f9e86">
                            <div class="act">
                                <span><b>{{ a.accion }}</b> <span class="vacio">· {{ a.modulo }}</span></span>
                                <span class="act__f">{{ fechaHora(a.created_at) }}</span>
                            </div>
                        </a-timeline-item>
                    </a-timeline>
                    <a-empty v-else description="Sin actividad registrada" class="py-4" />
                </a-card>
            </a-col>
        </a-row>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
.tt {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.tt .anticon {
    color: var(--sigam-teal);
}
.datos {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
}
.datos li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.datos li:last-child {
    border-bottom: none;
}
.datos__ic {
    width: 34px;
    height: 34px;
    flex: none;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
.datos__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.datos__l {
    font-size: 11.5px;
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
.rol-tag {
    font-size: 12px;
    padding: 2px 10px;
}
.vacio {
    color: var(--sigam-tenue);
    font-size: 12.5px;
}
.act {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}
.act__f {
    font-size: 11.5px;
    white-space: nowrap;
    color: var(--sigam-tenue);
}
</style>
