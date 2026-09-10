<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import { LockOutlined, LoginOutlined, MailOutlined } from '@ant-design/icons-vue';
import AuthShell from '@/Components/AuthShell.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({ email: '', password: '', remember: false });

const enviar = () => {
    form.post(route('login'), {
        onError: (e) => {
            form.reset('password');
            message.error(e.email || e.password || 'No pudimos iniciar sesión. Revisa tus datos.');
        },
    });
};
</script>

<template>
    <AuthShell title="Iniciar sesión">
        <template #title>Bienvenido</template>
        <template #subtitle>Ingresa a tu cuenta para acceder al sistema</template>

        <a-alert
            v-if="status"
            :message="status"
            type="success"
            show-icon
            class="au-status"
        />

        <a-form layout="vertical" class="au-form" @submitcapture.prevent>
            <a-form-item :validate-status="form.errors.email ? 'error' : ''" :help="form.errors.email">
                <a-input
                    v-model:value="form.email"
                    type="email"
                    size="large"
                    placeholder="Correo electrónico"
                    autocomplete="username"
                    @press-enter="enviar"
                >
                    <template #prefix><MailOutlined /></template>
                </a-input>
            </a-form-item>

            <a-form-item :validate-status="form.errors.password ? 'error' : ''" :help="form.errors.password">
                <a-input-password
                    v-model:value="form.password"
                    size="large"
                    placeholder="Contraseña"
                    autocomplete="current-password"
                    @press-enter="enviar"
                >
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
            </a-form-item>

            <div class="au-row">
                <a-checkbox v-model:checked="form.remember">Recordarme</a-checkbox>
                <Link v-if="canResetPassword" :href="route('password.request')" class="au-link">
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <a-button type="primary" size="large" block :loading="form.processing" @click="enviar">
                <template #icon><LoginOutlined /></template>
                Iniciar sesión
            </a-button>
        </a-form>
    </AuthShell>
</template>

<style scoped>
.au-status { margin-bottom: 16px; }
.au-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 2px 0 18px;
    font-size: 13px;
    flex-wrap: wrap;
}
.au-link { color: #1e5eb8; font-weight: 600; white-space: nowrap; }
.au-link:hover { color: #0d3f77; }
</style>
