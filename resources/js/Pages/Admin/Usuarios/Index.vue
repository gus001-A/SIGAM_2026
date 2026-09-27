<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    FilterOutlined,
    InfoCircleOutlined,
    PlusOutlined,
    StopOutlined,
    TeamOutlined,
    ToolOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTableInertia from '@/Components/DataTableInertia.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import CeldaRegistro from '@/Components/CeldaRegistro.vue';
import { usePermisos } from '@/composables/usePermisos';
import { useTablaInertia } from '@/composables/useTablaInertia';
import { etiquetaRol, colorRol, colorHexRol, listaRoles } from '@/utils/roles';

const props = defineProps({
    usuarios: { type: Object, required: true },
    kpis: { type: Object, default: () => ({}) },
    filtros: { type: Object, default: () => ({}) },
    orden: { type: Object, default: () => ({}) },
    catalogos: { type: Object, default: () => ({}) },
});

const tarjetas = computed(() => [
    { label: 'Usuarios totales', valor: props.kpis.total ?? 0, icono: TeamOutlined, color: '#0d84c9' },
    { label: 'Activos', valor: props.kpis.activos ?? 0, icono: CheckCircleOutlined, color: '#1f9e86' },
    { label: 'Inactivos', valor: props.kpis.inactivos ?? 0, icono: StopOutlined, color: '#d64545' },
    { label: 'Técnicos', valor: props.kpis.tecnicos ?? 0, icono: ToolOutlined, color: '#6b4bc9' },
]);

const { puede, usuario: yo } = usePermisos();

const modalRoles = ref(false);
const roles = listaRoles();

const { filtros, orden, cargando, filtrar, aplicar, limpiar, hayFiltros, onCambioTabla } = useTablaInertia('usuarios.index', {
    filtros: {
        nombre: props.filtros.nombre ?? '',
        email: props.filtros.email ?? '',
        sucursal_id: props.filtros.sucursal_id ?? undefined,
        rol: props.filtros.rol ?? undefined,
        estado: props.filtros.estado ?? undefined,
        registrado_por: props.filtros.registrado_por ?? '',
    },
    orden: { campo: props.orden.campo ?? 'nombre', dir: props.orden.dir ?? 'asc' },
});

const opcionesFiltro = {
    sucursal_id: computed(() => (props.catalogos.sucursales ?? []).map((s) => ({ label: s.nombre, value: s.id }))),
    rol: computed(() => (props.catalogos.roles ?? []).map((r) => ({ label: etiquetaRol(r), value: r }))),
    estado: computed(() => [
        { label: 'Activo', value: 'activo' },
        { label: 'Inactivo', value: 'inactivo' },
    ]),
};

const columns = [
    { title: 'Usuario', key: 'nombre_completo', filtro: 'texto', filtroClave: 'nombre', sorter: true, width: 240 },
    { title: 'Correo', key: 'email', dataIndex: 'email', filtro: 'texto', filtroClave: 'email', sorter: true, width: 250 },
    { title: 'Sucursal', key: 'sucursal', filtro: 'select', filtroClave: 'sucursal_id', width: 180 },
    { title: 'Roles', key: 'roles', filtro: 'select', filtroClave: 'rol', width: 220 },
    { title: 'Último acceso', key: 'ultimo_acceso_at', dataIndex: 'ultimo_acceso_at', sorter: true, width: 150 },
    { title: 'Estado', key: 'estado', filtro: 'select', filtroClave: 'estado', width: 120 },
    { title: 'Registrado por', key: 'registrado', filtro: 'texto', filtroClave: 'registrado_por', width: 150 },
    { title: '', key: 'acciones', align: 'right', width: 130, fixed: 'right' },
];

const fechaHora = (v) => (v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'Nunca');

const confirmar = ref(null);
const irA = (nombre, params) => router.visit(route(nombre, params));

const desactivar = async (usuario) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar a ${usuario.nombre_completo}`,
        mensaje: 'El usuario no podrá iniciar sesión. Su historial y registros se conservan.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('usuarios.destroy', usuario.id), { preserveScroll: true });
};

const reactivar = (usuario) => router.put(route('usuarios.restore', usuario.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout
        titulo="Usuarios"
        descripcion="Cuentas del personal con sus roles, permisos y sucursal asignada."
    >
        <template #acciones>
            <a-button @click="modalRoles = true">
                <template #icon><InfoCircleOutlined /></template>
                ¿Qué puede hacer cada rol?
            </a-button>
            <a-button v-if="hayFiltros()" @click="limpiar">
                <template #icon><FilterOutlined /></template>
                Limpiar filtros
            </a-button>
            <a-button v-if="puede('usuarios.crear')" type="primary" @click="irA('usuarios.create')">
                <template #icon><PlusOutlined /></template>
                Nuevo usuario
            </a-button>
        </template>

        <!-- KPIs con colores sólidos -->
        <div class="kpis">
            <div v-for="k in tarjetas" :key="k.label" class="kpi" :style="{ '--acc': k.color }">
                <div class="kpi__icono">
                    <component :is="k.icono" />
                </div>
                <div class="kpi__txt">
                    <div class="kpi__valor">{{ k.valor }}</div>
                    <div class="kpi__etq">{{ k.label }}</div>
                </div>
            </div>
        </div>

        <DataTableInertia :paginador="usuarios" :columns="columns" :orden="orden" :cargando="cargando" @cambio="onCambioTabla">
            <template #filtro="{ column }">
                        <a-input
                            v-if="column.filtro === 'texto'"
                            v-model:value="filtros[column.filtroClave]"
                            size="small"
                            allow-clear
                            placeholder="Filtrar"
                            @update:value="filtrar()"
                        />
                        <a-select
                            v-else-if="column.filtro === 'select'"
                            v-model:value="filtros[column.filtroClave]"
                            :options="opcionesFiltro[column.filtroClave].value"
                            size="small"
                            allow-clear
                            placeholder="Todos"
                            style="width: 100%"
                            @change="aplicar()"
                        />
            </template>

            <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'nombre_completo'">
                    <a class="font-medium" @click="irA('usuarios.show', record.id)">{{ record.nombre_completo }}</a>
                    <div v-if="record.telefono" class="text-xs opacity-60">{{ record.telefono }}</div>
                </template>

                <template v-else-if="column.key === 'sucursal'">{{ record.sucursal || '—' }}</template>

                <template v-else-if="column.key === 'roles'">
                    <a-space :size="4" wrap>
                        <a-tag v-for="r in record.roles" :key="r" :color="colorRol(r)">{{ etiquetaRol(r) }}</a-tag>
                        <span v-if="!record.roles.length" class="opacity-50">Sin rol</span>
                    </a-space>
                </template>

                <template v-else-if="column.key === 'ultimo_acceso_at'">
                    <span class="text-sm">{{ fechaHora(record.ultimo_acceso_at) }}</span>
                </template>

                <template v-else-if="column.key === 'estado'">
                    <a-tag :color="record.estado === 'activo' ? 'green' : 'default'">
                        {{ record.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                    </a-tag>
                </template>

                <template v-else-if="column.key === 'registrado'">
                    <CeldaRegistro :usuario="record.creado_por" :fecha="record.creado_en" />
                </template>

                <template v-else-if="column.key === 'acciones'">
                    <a-space :size="2">
                        <a-tooltip title="Ver detalle">
                            <a-button type="text" size="small" @click="irA('usuarios.show', record.id)">
                                <template #icon><EyeOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-if="record.estado === 'activo' && puede('usuarios.editar')" title="Editar">
                            <a-button type="text" size="small" @click="irA('usuarios.edit', record.id)">
                                <template #icon><EditOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip
                            v-if="record.estado === 'activo' && puede('usuarios.desactivar') && record.id !== yo?.id"
                            title="Desactivar"
                        >
                            <a-button type="text" size="small" danger @click="desactivar(record)">
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-tooltip>
                        <a-tooltip v-else-if="record.estado === 'inactivo' && puede('usuarios.editar')" title="Reactivar">
                            <a-button type="text" size="small" @click="reactivar(record)">
                                <template #icon><UndoOutlined /></template>
                            </a-button>
                        </a-tooltip>
                    </a-space>
                </template>
            </template>
        </DataTableInertia>

        <ConfirmarDialog ref="confirmar" />

        <a-modal v-model:open="modalRoles" title="Permisos por rol" :footer="null" width="620px" wrap-class-name="modal-roles-wrap">
            <p class="leyenda-roles__intro">Principales facultades de cada rol dentro del sistema.</p>
            <div class="leyenda-roles">
                <div
                    v-for="r in roles"
                    :key="r.slug"
                    class="leyenda-roles__card"
                    :style="{ '--rol-color': colorHexRol(r.slug) }"
                >
                    <span class="leyenda-roles__titulo">{{ r.etiqueta }}</span>
                    <span class="leyenda-roles__desc">{{ r.descripcion }}</span>
                </div>
            </div>
        </a-modal>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   KPIs con colores sólidos
   ========================================================== */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
    margin-bottom: 14px;
}

.kpi {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 14px;
    background: #fff;
    border: 1px solid var(--sigam-borde);
    border-radius: 13px;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
    position: relative;
    overflow: hidden;
}

.kpi::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--acc);
}

.kpi__icono {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: var(--acc);
    flex-shrink: 0;
}

.kpi__valor {
    font-size: 18px;
    font-weight: 800;
    color: var(--sigam-navy);
    line-height: 1.1;
}

.kpi__etq {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 767px) {
    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.leyenda-roles__intro {
    margin: -4px 0 14px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}
.leyenda-roles {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.leyenda-roles__card {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px 14px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--rol-color) 6%, #fff);
    border: 1px solid color-mix(in srgb, var(--rol-color) 20%, #fff);
    border-left: 4px solid var(--rol-color);
}
.leyenda-roles__titulo {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--rol-color);
}
.leyenda-roles__desc {
    font-size: 13px;
    color: var(--sigam-texto);
    line-height: 1.5;
}
</style>
