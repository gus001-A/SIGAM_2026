<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import { LockOutlined, MailOutlined, SaveOutlined } from '@ant-design/icons-vue';
import AuthShell from '@/Components/AuthShell.vue';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const enviar = () => {
    form.post(route('password.store'), {
        onError: (e) => {
            form.reset('password', 'password_confirmation');
            message.error(e.email || e.password || 'No pudimos restablecer la contraseña.');
        },
    });
};
</script>

<template>
    <AuthShell title="Restablecer contraseña">
        <template #title>Restablecer contraseña</template>
        <template #subtitle>Define una nueva contraseña para tu cuenta</template>

        <a-form layout="vertical" class="au-form" @submitcapture.prevent>
            <a-form-item :validate-status="form.errors.email ? 'error' : ''" :help="form.errors.email">
                <a-input v-model:value="form.email" type="email" size="large" placeholder="Correo electrónico" autocomplete="username">
                    <template #prefix><MailOutlined /></template>
                </a-input>
            </a-form-item>

            <a-form-item :validate-status="form.errors.password ? 'error' : ''" :help="form.errors.password">
                <a-input-password v-model:value="form.password" size="large" placeholder="Nueva contraseña" autocomplete="new-password">
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
            </a-form-item>

            <a-form-item :validate-status="form.errors.password_confirmation ? 'error' : ''" :help="form.errors.password_confirmation">
                <a-input-password
                    v-model:value="form.password_confirmation"
                    size="large"
                    placeholder="Confirmar contraseña"
                    autocomplete="new-password"
                    @press-enter="enviar"
                >
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
            </a-form-item>

            <a-button type="primary" size="large" block :loading="form.processing" @click="enviar">
                <template #icon><SaveOutlined /></template>
                Guardar contraseña
            </a-button>
        </a-form>

        <template #footer>
            <Link :href="route('login')" class="au-link au-link--strong">Volver a iniciar sesión</Link>
        </template>
    </AuthShell>
</template>

<style scoped>
.au-link { color: var(--sigam-navy); font-weight: 600; }
.au-link:hover { color: var(--sigam-teal-700); }
.au-link--strong { display: inline-flex; align-items: center; gap: 5px; }
</style>
