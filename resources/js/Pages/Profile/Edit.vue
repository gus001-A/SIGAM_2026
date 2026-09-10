<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import {
    IdcardOutlined,
    LockOutlined,
    MailOutlined,
    PhoneOutlined,
    SafetyCertificateOutlined,
    SaveOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeccionFicha from '@/Components/SeccionFicha.vue';
import { reglaCorreo, reglaRequerido, reglaTelefono, soloDigitos } from '@/utils/restricciones';
import { colorRol, etiquetaRol } from '@/utils/roles';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const page = usePage();
const usuario = computed(() => page.props.auth.user);
const roles = computed(() => page.props.auth.roles ?? []);

const iniciales = computed(() => {
    const n = usuario.value?.nombre ?? usuario.value?.name ?? '?';
    return n.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase();
});

const perfil = useForm({
    name: usuario.value?.nombre ?? usuario.value?.name ?? '',
    email: usuario.value?.email ?? '',
    telefono: usuario.value?.telefono ?? '',
});
const reglasPerfil = {
    name: [reglaRequerido('El nombre es obligatorio.')],
    email: [reglaRequerido('El correo es obligatorio.'), reglaCorreo()],
    telefono: [reglaTelefono(10)],
};
const guardarPerfil = () =>
    perfil.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => message.success('Perfil actualizado.'),
    });

const clave = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const reglasClave = {
    current_password: [reglaRequerido('Ingresa tu contraseña actual.')],
    password: [reglaRequerido('Define la nueva contraseña.'), { min: 8, message: 'Mínimo 8 caracteres.' }],
    password_confirmation: [
        reglaRequerido('Confirma la nueva contraseña.'),
        {
            validator: (_r, v) =>
                v === clave.password ? Promise.resolve() : Promise.reject('Las contraseñas no coinciden.'),
        },
    ],
};
const guardarClave = () =>
    clave.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            clave.reset();
            message.success('Contraseña actualizada.');
        },
        onError: () => {
            if (clave.errors.password) clave.reset('password', 'password_confirmation');
            if (clave.errors.current_password) clave.reset('current_password');
        },
    });
</script>

<template>
    <Head title="Mi perfil" />

    <AppLayout titulo="Mi perfil" descripcion="Actualiza tus datos de contacto y tu contraseña.">
        <div class="perfil">
            <a-card :body-style="{ padding: 0 }" class="perfil__hero-card">
                <div class="perfil__hero">
                    <a-avatar :size="72" class="perfil__avatar">{{ iniciales }}</a-avatar>
                    <div class="perfil__hero-txt">
                        <div class="perfil__nombre">{{ usuario?.nombre ?? usuario?.name }}</div>
                        <div class="perfil__correo">{{ usuario?.email }}</div>
                        <div class="perfil__tags">
                            <a-tag v-for="r in roles" :key="r" :color="colorRol(r)">{{ etiquetaRol(r) }}</a-tag>
                            <a-tag :color="usuario?.email_verified_at ? 'green' : 'orange'">
                                <SafetyCertificateOutlined />
                                {{ usuario?.email_verified_at ? 'Correo verificado' : 'Sin verificar' }}
                            </a-tag>
                        </div>
                    </div>
                </div>
            </a-card>

            <a-card size="small" class="perfil__card">
                <SeccionFicha titulo="Datos personales" :icono="IdcardOutlined">
                    <a-form :model="perfil" :rules="reglasPerfil" layout="vertical" @finish="guardarPerfil">
                        <a-form-item label="Nombre completo" name="name" :validate-status="perfil.errors.name ? 'error' : ''" :help="perfil.errors.name">
                            <a-input v-model:value="perfil.name">
                                <template #prefix><UserOutlined class="op-40" /></template>
                            </a-input>
                        </a-form-item>
                        <a-row :gutter="14">
                            <a-col :xs="24" :sm="14">
                                <a-form-item label="Correo electrónico" name="email" :validate-status="perfil.errors.email ? 'error' : ''" :help="perfil.errors.email">
                                    <a-input v-model:value="perfil.email" type="email">
                                        <template #prefix><MailOutlined class="op-40" /></template>
                                    </a-input>
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="10">
                                <a-form-item label="Teléfono" name="telefono" :validate-status="perfil.errors.telefono ? 'error' : ''" :help="perfil.errors.telefono">
                                    <a-input v-model:value="perfil.telefono" :maxlength="10" inputmode="numeric" placeholder="10 dígitos" @keypress="soloDigitos">
                                        <template #prefix><PhoneOutlined class="op-40" /></template>
                                    </a-input>
                                </a-form-item>
                            </a-col>
                        </a-row>

                        <a-alert
                            v-if="mustVerifyEmail && !usuario?.email_verified_at"
                            type="warning"
                            show-icon
                            class="mb-3"
                            message="Tu correo aún no está verificado."
                        />

                        <a-button type="primary" html-type="submit" :loading="perfil.processing">
                            <template #icon><SaveOutlined /></template>
                            Guardar cambios
                        </a-button>
                    </a-form>
                </SeccionFicha>

                <a-divider />

                <SeccionFicha titulo="Contraseña" :icono="LockOutlined" color="var(--sigam-navy)">
                    <a-form :model="clave" :rules="reglasClave" layout="vertical" @finish="guardarClave">
                        <a-form-item label="Contraseña actual" name="current_password" :validate-status="clave.errors.current_password ? 'error' : ''" :help="clave.errors.current_password">
                            <a-input-password v-model:value="clave.current_password" autocomplete="current-password" />
                        </a-form-item>
                        <a-row :gutter="14">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Nueva contraseña" name="password" :validate-status="clave.errors.password ? 'error' : ''" :help="clave.errors.password">
                                    <a-input-password v-model:value="clave.password" autocomplete="new-password" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Confirmar nueva contraseña" name="password_confirmation" :validate-status="clave.errors.password_confirmation ? 'error' : ''" :help="clave.errors.password_confirmation">
                                    <a-input-password v-model:value="clave.password_confirmation" autocomplete="new-password" />
                                </a-form-item>
                            </a-col>
                        </a-row>
                        <a-button type="primary" html-type="submit" :loading="clave.processing">
                            <template #icon><SaveOutlined /></template>
                            Actualizar contraseña
                        </a-button>
                    </a-form>
                </SeccionFicha>
            </a-card>
        </div>
    </AppLayout>
</template>

<style scoped>
.perfil {
    max-width: 640px;
    margin: 0 auto;
}
.op-40 {
    opacity: 0.4;
}
.perfil__hero-card {
    margin-bottom: 16px;
    overflow: hidden;
}
.perfil__hero {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 22px;
    background: var(--sigam-grad);
    background-size: 180% 180%;
    animation: sigam-grad-shift 8s ease infinite;
}
.perfil__avatar {
    background: rgba(255, 255, 255, 0.16) !important;
    border: 2px solid rgba(255, 255, 255, 0.5);
    color: #fff !important;
    font-weight: 800;
    font-size: 24px;
    flex: none;
}
.perfil__hero-txt {
    min-width: 0;
}
.perfil__nombre {
    font-size: 19px;
    font-weight: 800;
    color: #fff;
}
.perfil__correo {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 8px;
}
.perfil__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.perfil__tags :deep(.ant-tag) {
    border: 1px solid rgba(255, 255, 255, 0.35);
}
.perfil__card {
    padding: 6px 6px 2px;
}
</style>
