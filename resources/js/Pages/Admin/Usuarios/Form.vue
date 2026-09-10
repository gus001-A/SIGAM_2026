<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircleFilled, IdcardOutlined, LockOutlined, SafetyCertificateOutlined, SaveOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { colorHexRol, etiquetaRol } from '@/utils/roles';
import { reglaCorreo, reglaRequerido, reglaTelefono, soloDigitos } from '@/utils/restricciones';

const props = defineProps({
    usuario: { type: Object, default: null },
    catalogos: { type: Object, default: () => ({}) },
});

const editando = computed(() => !!props.usuario);

const form = useForm({
    nombre: props.usuario?.nombre ?? '',
    apellidos: props.usuario?.apellidos ?? '',
    email: props.usuario?.email ?? '',
    telefono: props.usuario?.telefono ?? '',
    sucursal_id: props.usuario?.sucursal_id ?? undefined,
    estado: props.usuario?.estado ?? 'activo',
    password: '',
    password_confirmation: '',
    rol: props.usuario?.roles?.[0] ?? null,
});

const reglas = reactive({
    nombre: [reglaRequerido('El nombre es obligatorio.')],
    email: [reglaRequerido('El correo es obligatorio.'), reglaCorreo()],
    telefono: [reglaTelefono(10)],
    password: editando.value ? [] : [reglaRequerido('Define una contraseña.')],
});

const opcionesRoles = computed(() =>
    (props.catalogos.roles ?? []).map((r) => {
        const nombre = r.name ?? r;
        return { valor: nombre, etiqueta: etiquetaRol(nombre), color: colorHexRol(nombre) };
    }),
);

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const enviar = () => {
    const opciones = { preserveScroll: true };
    form.transform((datos) => ({ ...datos, roles: datos.rol ? [datos.rol] : [] }));
    if (editando.value) form.put(route('usuarios.update', props.usuario.id), opciones);
    else form.post(route('usuarios.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('usuarios.show', props.usuario.id) : route('usuarios.index'));
</script>

<template>
    <Head :title="editando ? 'Editar usuario' : 'Nuevo usuario'" />

    <AppLayout
        :titulo="editando ? 'Editar usuario' : 'Nuevo usuario'"
        descripcion="Datos personales, credenciales de acceso y roles del usuario."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar">
            <a-row :gutter="16">
                <a-col :xs="24" :lg="16">
                    <a-card size="small" class="mb-4 sec">
                        <template #title><IdcardOutlined /> Datos personales</template>
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Nombre(s)" name="nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Apellidos" :validate-status="est('apellidos')" :help="form.errors.apellidos">
                                    <a-input v-model:value="form.apellidos" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="14">
                                <a-form-item label="Correo electrónico" name="email" :validate-status="est('email')" :help="form.errors.email">
                                    <a-input v-model:value="form.email" type="email" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="10">
                                <a-form-item label="Teléfono" name="telefono" :validate-status="est('telefono')" :help="form.errors.telefono">
                                    <a-input
                                        v-model:value="form.telefono"
                                        :maxlength="10"
                                        inputmode="numeric"
                                        placeholder="10 dígitos"
                                        @keypress="soloDigitos"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="14">
                                <a-form-item label="Sucursal" :validate-status="est('sucursal_id')" :help="form.errors.sucursal_id">
                                    <a-select
                                        v-model:value="form.sucursal_id"
                                        :options="catalogos.sucursales"
                                        :field-names="{ label: 'nombre', value: 'id' }"
                                        allow-clear
                                        show-search
                                        option-filter-prop="nombre"
                                        placeholder="Sin sucursal asignada"
                                    />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>

                    <a-card size="small" class="sec">
                        <template #title><LockOutlined /> Contraseña</template>
                        <a-alert
                            v-if="editando"
                            type="info"
                            show-icon
                            class="mb-3"
                            message="Déjala en blanco para conservar la contraseña actual."
                        />
                        <a-row :gutter="12">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Contraseña" name="password" :validate-status="est('password')" :help="form.errors.password">
                                    <a-input-password v-model:value="form.password" autocomplete="new-password" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Confirmar contraseña">
                                    <a-input-password v-model:value="form.password_confirmation" autocomplete="new-password" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-card>
                </a-col>

                <a-col :xs="24" :lg="8">
                    <a-card size="small" class="mb-4 sec">
                        <template #title><SafetyCertificateOutlined /> Rol y acceso</template>
                        <a-form-item label="Rol del usuario" :validate-status="est('roles') || est('rol')" :help="form.errors.roles || form.errors.rol">
                            <div class="roles-grid">
                                <button
                                    v-for="op in opcionesRoles"
                                    :key="op.valor"
                                    type="button"
                                    class="rol-op"
                                    :class="{ 'is-sel': form.rol === op.valor }"
                                    :style="{ '--c': op.color }"
                                    @click="form.rol = op.valor"
                                >
                                    <span class="rol-op__punto" />
                                    <span class="rol-op__txt">{{ op.etiqueta }}</span>
                                    <CheckCircleFilled v-if="form.rol === op.valor" class="rol-op__check" />
                                </button>
                            </div>
                        </a-form-item>
                        <a-form-item label="Estado de la cuenta" name="estado">
                            <a-radio-group v-model:value="form.estado" button-style="solid">
                                <a-radio-button value="activo">Activo</a-radio-button>
                                <a-radio-button value="inactivo">Inactivo</a-radio-button>
                            </a-radio-group>
                        </a-form-item>
                    </a-card>

                    <a-card size="small">
                        <a-button type="primary" size="large" block html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Crear usuario' }}
                        </a-button>
                        <a-button type="text" block class="mt-2" @click="cancelar">Cancelar</a-button>
                    </a-card>
                </a-col>
            </a-row>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.roles-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.rol-op {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    text-align: left;
    padding: 10px 12px;
    border: 1px solid var(--sigam-borde);
    border-radius: 11px;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: var(--sigam-texto);
    transition: border-color 0.14s ease, background 0.14s ease, box-shadow 0.14s ease;
}
.rol-op:hover {
    border-color: var(--c);
}
.rol-op.is-sel {
    border-color: var(--c);
    background: color-mix(in srgb, var(--c) 8%, #fff);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--c) 16%, transparent);
}
.rol-op__punto {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--c);
    flex: none;
}
.rol-op__txt {
    flex: 1;
}
.rol-op__check {
    color: var(--c);
    font-size: 16px;
}
.sec :deep(.ant-card-head-title) {
    display: flex;
    align-items: center;
    gap: 8px;
}
.sec :deep(.ant-card-head-title)::before {
    display: none !important;
}
.sec :deep(.ant-card-head-title .anticon) {
    color: var(--sigam-teal);
    font-size: 15px;
}
</style>
