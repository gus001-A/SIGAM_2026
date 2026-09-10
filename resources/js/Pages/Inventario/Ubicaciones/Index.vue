<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    DeleteOutlined,
    EditOutlined,
    EnvironmentOutlined,
    PlusOutlined,
    ToolOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import { usePermisos } from '@/composables/usePermisos';

const props = defineProps({
    sucursales: { type: Array, default: () => [] },
    sucursalSeleccionada: { type: [Number, String], default: null },
    arbol: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
});

const { puede } = usePermisos();
const confirmar = ref(null);

const sucursalActual = computed({
    get: () => (props.sucursalSeleccionada ? Number(props.sucursalSeleccionada) : null),
    set: (v) => router.get(route('ubicaciones.index'), { sucursal_id: v }, { preserveState: false }),
});

const sucursalNombre = computed(
    () => props.sucursales.find((s) => s.id === Number(props.sucursalSeleccionada))?.nombre ?? '',
);

// --- Árbol -> treeData de a-tree -------------------------------------
const aTreeData = (nodos) =>
    nodos.map((n) => ({
        key: n.id,
        nodo: n,
        children: n.hijas?.length ? aTreeData(n.hijas) : undefined,
    }));

const treeData = computed(() => aTreeData(props.arbol));
const totalUbicaciones = computed(() => {
    const contar = (ns) => ns.reduce((acc, n) => acc + 1 + (n.hijas ? contar(n.hijas) : 0), 0);
    return contar(props.arbol);
});

// --- Modal de alta / edición ---------------------------------------
const modal = reactive({ abierto: false, editando: false, tituloPadre: '' });

const form = useForm({
    id: null,
    sucursal_id: null,
    padre_id: null,
    tipo_id: undefined,
    codigo: '',
    nombre: '',
    descripcion: '',
    estado: 'activo',
});

const cargarForm = (datos) => {
    form.clearErrors();
    form.id = datos.id ?? null;
    form.sucursal_id = Number(props.sucursalSeleccionada);
    form.padre_id = datos.padre_id ?? null;
    form.tipo_id = datos.tipo_id ?? undefined;
    form.codigo = datos.codigo ?? '';
    form.nombre = datos.nombre ?? '';
    form.descripcion = datos.descripcion ?? '';
    form.estado = datos.estado ?? 'activo';
};

const abrirAlta = (padre = null) => {
    cargarForm({ padre_id: padre?.id ?? null });
    modal.editando = false;
    modal.tituloPadre = padre?.nombre ?? '';
    modal.abierto = true;
};

const abrirEdicion = (nodo) => {
    cargarForm(nodo);
    modal.editando = true;
    modal.tituloPadre = '';
    modal.abierto = true;
};

const guardar = () => {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => (modal.abierto = false),
    };
    if (modal.editando) form.put(route('ubicaciones.update', form.id), opciones);
    else form.post(route('ubicaciones.store'), opciones);
};

const eliminar = async (nodo) => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar ${nodo.nombre}`,
        mensaje: 'Solo se puede desactivar si no tiene sububicaciones ni equipos asignados.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('ubicaciones.destroy', nodo.id), { preserveScroll: true });
};

const est = (campo) => (form.errors[campo] ? 'error' : undefined);
</script>

<template>
    <Head title="Ubicaciones" />

    <AppLayout
        titulo="Ubicaciones"
        descripcion="Estructura jerárquica de áreas y espacios de cada sucursal donde se ubican los equipos."
    >
        <template #acciones>
            <a-select
                v-model:value="sucursalActual"
                :options="sucursales"
                :field-names="{ label: 'nombre', value: 'id' }"
                style="min-width: 220px"
                placeholder="Selecciona una sucursal"
            />
            <a-button
                v-if="puede('ubicaciones.crear') && sucursalSeleccionada"
                type="primary"
                @click="abrirAlta()"
            >
                <template #icon><PlusOutlined /></template>
                Nueva ubicación
            </a-button>
        </template>

        <a-card v-if="!sucursalSeleccionada" size="small">
            <a-empty description="Selecciona una sucursal para ver y gestionar sus ubicaciones" />
        </a-card>

        <a-card v-else :body-style="{ padding: 0 }" class="ubic-card">
            <div class="ubic-head">
                <div class="ubic-head__ico"><ApartmentOutlined /></div>
                <div>
                    <div class="ubic-head__titulo">{{ sucursalNombre }}</div>
                    <div class="ubic-head__sub">{{ totalUbicaciones }} ubicación(es) registradas</div>
                </div>
            </div>

            <div class="ubic-cuerpo">
                <a-tree
                    v-if="treeData.length"
                    :tree-data="treeData"
                    default-expand-all
                    :selectable="false"
                    block-node
                    class="ubic-tree"
                >
                    <template #title="{ nodo }">
                        <div class="nodo" :class="{ 'nodo--inactiva': nodo.estado !== 'activo' }">
                            <span class="nodo__pin"><EnvironmentOutlined /></span>
                            <div class="nodo__info">
                                <span class="nodo__nombre">{{ nodo.nombre }}</span>
                                <a-tag v-if="nodo.codigo" class="nodo__cod">{{ nodo.codigo }}</a-tag>
                                <span v-if="nodo.tipo" class="nodo__tipo">{{ nodo.tipo }}</span>
                                <a-tag v-if="nodo.equipos_count" color="blue">
                                    <ToolOutlined /> {{ nodo.equipos_count }}
                                </a-tag>
                                <a-tag v-if="nodo.estado !== 'activo'">Inactiva</a-tag>
                            </div>
                            <div class="nodo__acciones">
                                <a-tooltip title="Añadir sububicación">
                                    <a-button v-if="puede('ubicaciones.crear')" class="nodo__btn nodo__btn--add" @click.stop="abrirAlta(nodo)">
                                        <template #icon><PlusOutlined /></template>
                                    </a-button>
                                </a-tooltip>
                                <a-tooltip title="Editar">
                                    <a-button v-if="puede('ubicaciones.editar')" class="nodo__btn nodo__btn--edit" @click.stop="abrirEdicion(nodo)">
                                        <template #icon><EditOutlined /></template>
                                    </a-button>
                                </a-tooltip>
                                <a-tooltip title="Desactivar">
                                    <a-button v-if="puede('ubicaciones.desactivar')" class="nodo__btn nodo__btn--del" @click.stop="eliminar(nodo)">
                                        <template #icon><DeleteOutlined /></template>
                                    </a-button>
                                </a-tooltip>
                            </div>
                        </div>
                    </template>
                </a-tree>

                <a-empty v-else description="Esta sucursal aún no tiene ubicaciones registradas">
                    <a-button v-if="puede('ubicaciones.crear')" type="primary" @click="abrirAlta()">
                        Crear la primera ubicación
                    </a-button>
                </a-empty>
            </div>
        </a-card>

        <a-modal
            v-model:open="modal.abierto"
            :title="modal.editando ? 'Editar ubicación' : (modal.tituloPadre ? `Nueva sububicación en «${modal.tituloPadre}»` : 'Nueva ubicación')"
            :confirm-loading="form.processing"
            ok-text="Guardar"
            cancel-text="Cancelar"
            @ok="guardar"
        >
            <a-form layout="vertical" class="mt-2" @submit.prevent="guardar">
                <a-row :gutter="12">
                    <a-col :span="10">
                        <a-form-item label="Código" :validate-status="est('codigo')" :help="form.errors.codigo">
                            <a-input v-model:value="form.codigo" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="14">
                        <a-form-item label="Nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                            <a-input v-model:value="form.nombre" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Tipo de ubicación" :validate-status="est('tipo_id')" :help="form.errors.tipo_id">
                            <a-select
                                v-model:value="form.tipo_id"
                                :options="tipos"
                                :field-names="{ label: 'nombre', value: 'id' }"
                                allow-clear
                                placeholder="Sin especificar"
                            />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Descripción" :validate-status="est('descripcion')" :help="form.errors.descripcion">
                            <a-textarea v-model:value="form.descripcion" :auto-size="{ minRows: 2, maxRows: 4 }" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activa</a-radio-button>
                                <a-radio-button value="inactivo">Inactiva</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
.ubic-head {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 15px 18px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 70%);
}
.ubic-head__ico {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    color: #fff;
    background: var(--sigam-grad);
}
.ubic-head__titulo {
    font-weight: 800;
    font-size: 15px;
    color: var(--sigam-navy);
}
.ubic-head__sub {
    font-size: 12px;
    color: var(--sigam-tenue);
}
.ubic-cuerpo {
    padding: 12px 14px 16px;
}

.ubic-tree :deep(.ant-tree-treenode) {
    padding: 3px 0;
    width: 100%;
    align-items: center;
}
.ubic-tree :deep(.ant-tree-node-content-wrapper) {
    flex: 1;
    padding: 0;
    border-radius: 10px;
    transition: background 0.13s ease;
}
.ubic-tree :deep(.ant-tree-node-content-wrapper:hover) {
    background: var(--sigam-navy-050);
}
.ubic-tree :deep(.ant-tree-switcher) {
    color: var(--sigam-tenue);
}

.nodo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 10px;
}
.nodo--inactiva {
    opacity: 0.55;
}
.nodo__pin {
    color: var(--sigam-teal);
    font-size: 14px;
    display: inline-flex;
}
.nodo__info {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
    flex: 1;
    min-width: 0;
}
.nodo__nombre {
    font-weight: 600;
    color: var(--sigam-texto);
}
.nodo__cod {
    font-family: ui-monospace, monospace;
    background: var(--sigam-navy-050);
    color: var(--sigam-navy);
}
.nodo__tipo {
    font-size: 12px;
    color: var(--sigam-tenue);
}
.nodo__acciones {
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.15s;
}
.nodo:hover .nodo__acciones {
    opacity: 1;
}
.nodo__btn.ant-btn {
    width: 28px;
    height: 28px;
    min-width: 28px;
    padding: 0;
    border: 1px solid transparent;
    border-radius: 8px;
    box-shadow: none;
    transition: background 0.13s ease, color 0.13s ease, transform 0.13s ease;
}
.nodo__btn.ant-btn:hover {
    transform: translateY(-1px);
    color: #fff;
}
.nodo__btn--add {
    background: #e4f4ee;
    color: var(--sigam-teal-700);
}
.nodo__btn--add:hover {
    background: var(--sigam-teal);
}
.nodo__btn--edit {
    background: #e7eefb;
    color: #23508c;
}
.nodo__btn--edit:hover {
    background: var(--sigam-navy);
}
.nodo__btn--del {
    background: #fbeaea;
    color: #c23b3b;
}
.nodo__btn--del:hover {
    background: #dc2626;
}
</style>
