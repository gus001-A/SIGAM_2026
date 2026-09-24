<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircleFilled, IdcardOutlined, LockOutlined, SafetyCertificateOutlined, SaveOutlined, StarOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';
import { colorHexRol, descripcionRol, etiquetaRol } from '@/utils/roles';
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
    especialidades_equipo: props.usuario?.especialidades_equipo ?? [],
    especialidades_mantenimiento: props.usuario?.especialidades_mantenimiento ?? [],
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
const esTecnico = computed(() => form.rol === 'tecnico');
const opcionesTiposEquipo = computed(() => (props.catalogos.tipos_equipo ?? []).map((t) => ({ label: t.nombre, value: t.id })));
const opcionesTiposMantenimiento = computed(() => (props.catalogos.tipos_mantenimiento ?? []).map((t) => ({ label: t.nombre, value: t.id })));

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas({
    per: ['nombre', 'apellidos', 'email', 'telefono', 'sucursal_id'],
    clave: ['password', 'password_confirmation'],
    rol: ['roles', 'rol', 'estado', 'especialidades_equipo', 'especialidades_mantenimiento'],
}, 'per');

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor };
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
        descripcion="Datos personales, credenciales de acceso, rol y especialidades del usuario."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <a-tabs v-model:activeKey="pestanaActiva">
                    <a-tab-pane key="per">
                        <template #tab><span><IdcardOutlined /> Datos personales</span></template>
                        <p class="tab-ayuda">Identificación y forma de contacto del usuario.</p>
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
                                <a-form-item label="Correo electrónico" name="email" extra="Se usa para iniciar sesión y recuperar la contraseña." :validate-status="est('email')" :help="form.errors.email">
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
                                <a-form-item label="Sucursal" extra="A qué sede pertenece este usuario — opcional." :validate-status="est('sucursal_id')" :help="form.errors.sucursal_id">
                                    <a-select
                                        v-model:value="form.sucursal_id"
                                        :options="catalogos.sucursales"
                                        :field-names="{ label: 'nombre', value: 'id' }"
                                        allow-clear
                                        placeholder="Sin sucursal asignada"
                                    />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <a-tab-pane key="clave">
                        <template #tab><span><LockOutlined /> Contraseña</span></template>
                        <p class="tab-ayuda">Credencial de acceso al sistema.</p>
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
                    </a-tab-pane>

                    <a-tab-pane key="rol">
                        <template #tab><span><SafetyCertificateOutlined /> Rol y acceso</span></template>
                        <p class="tab-ayuda">Qué puede hacer este usuario dentro del sistema — cada usuario tiene exactamente un rol.</p>
                        <a-row :gutter="24">
                            <a-col :xs="24" :md="12">
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
                                    <p v-if="form.rol" class="rol-desc">{{ descripcionRol(form.rol) }}</p>
                                </a-form-item>
                                <a-form-item label="Estado de la cuenta" name="estado">
                                    <a-radio-group v-model:value="form.estado" button-style="solid">
                                        <a-radio-button value="activo">Activo</a-radio-button>
                                        <a-radio-button value="inactivo">Inactivo</a-radio-button>
                                    </a-radio-group>
                                </a-form-item>
                            </a-col>

                            <a-col :xs="24" :md="12" class="especialidades-col">
                                <div class="especialidades-tit">
                                    <StarOutlined /> Especialidades del técnico
                                    <span class="especialidades-tit__sub">— se usa para sugerirlo al delegar una orden</span>
                                </div>
                                <template v-if="esTecnico">
                                    <a-form-item label="Tipos de equipo en los que es especialista">
                                        <a-select
                                            v-model:value="form.especialidades_equipo"
                                            :options="opcionesTiposEquipo"
                                            mode="multiple"
                                            size="small"
                                            :show-search="false"
                                            allow-clear
                                            placeholder="Sin especialidades de equipo registradas"
                                        />
                                    </a-form-item>
                                    <a-form-item label="Tipos de mantenimiento en los que es especialista">
                                        <a-select
                                            v-model:value="form.especialidades_mantenimiento"
                                            :options="opcionesTiposMantenimiento"
                                            mode="multiple"
                                            size="small"
                                            :show-search="false"
                                            allow-clear
                                            placeholder="Sin especialidades de mantenimiento registradas"
                                        />
                                    </a-form-item>
                                </template>
                                <a-alert
                                    v-else
                                    type="info"
                                    show-icon
                                    message="Solo aplica al rol Técnico"
                                    description="Elige el rol Técnico para registrar sus especialidades."
                                />
                            </a-col>
                        </a-row>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <a-card size="small" class="form-acciones">
                <a-space>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon><SaveOutlined /></template>
                        {{ editando ? 'Guardar cambios' : 'Crear usuario' }}
                    </a-button>
                    <a-button size="large" @click="cancelar">Cancelar</a-button>
                </a-space>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.roles-grid {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-width: 420px;
}
.rol-op {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    text-align: left;
    padding: 7px 12px;
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
.rol-desc {
    margin: 8px 0 0;
    max-width: 420px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
    background: var(--sigam-navy-050);
    border-radius: 9px;
    padding: 8px 10px;
}
.especialidades-col {
    border-left: 1px solid var(--sigam-borde-suave);
    padding-left: 24px;
}
@media (max-width: 767px) {
    .especialidades-col {
        border-left: none;
        border-top: 1px solid var(--sigam-borde-suave);
        padding-left: 0;
        padding-top: 16px;
        margin-top: 8px;
    }
}
.especialidades-tit {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 6px;
    font-weight: 700;
    font-size: 13px;
    color: var(--sigam-navy);
    margin-bottom: 8px;
}
.especialidades-tit .anticon {
    color: var(--sigam-teal);
    align-self: center;
}
.especialidades-tit__sub {
    font-weight: 500;
    font-size: 11.5px;
    color: var(--sigam-tenue);
}
.especialidades-col :deep(.ant-form-item) {
    margin-bottom: 8px;
}
.form-card {
    margin-bottom: 10px;
}
.form-card :deep(.ant-tabs-nav) {
    margin-bottom: 10px;
}
.tab-ayuda {
    margin: -4px 0 10px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}
.form-acciones {
    position: sticky;
    bottom: 0;
    z-index: 5;
}
</style>
