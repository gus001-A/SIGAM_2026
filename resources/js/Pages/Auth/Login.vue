<script setup>
import { onMounted, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import { LockOutlined, LoginOutlined, MailOutlined } from '@ant-design/icons-vue';
import AuthShell from '@/Components/AuthShell.vue';

const props = defineProps({
    canResetPassword: Boolean,
    status: String,
});

onMounted(() => {
    if (props.status) message.success(props.status);
});

const form = useForm({ email: '', password: '', remember: false });
const huboError = ref(false);

const limpiarError = () => (huboError.value = false);

const enviar = () => {
    const credencialesCompletas = form.email && form.password;
    huboError.value = false;
    form.post(route('login'), {
        onError: () => {
            form.reset('password');
            if (credencialesCompletas) {
                form.clearErrors();
                huboError.value = true;
                message.error('Correo y/o contraseña incorrectas.');
            }
        },
    });
};
</script>

<template>
    <AuthShell title="Iniciar sesión">
        <template #title>Bienvenido</template>
        <template #subtitle>Ingresa a tu cuenta para acceder al sistema</template>

        <a-form layout="vertical" class="au-form" @submitcapture.prevent>
            <a-form-item :validate-status="(form.errors.email || huboError) ? 'error' : ''" :help="form.errors.email">
                <a-input
                    v-model:value="form.email"
                    type="email"
                    size="large"
                    placeholder="Correo electrónico"
                    autocomplete="username"
                    @update:value="limpiarError"
                    @press-enter="enviar"
                >
                    <template #prefix><MailOutlined /></template>
                </a-input>
            </a-form-item>

            <a-form-item :validate-status="(form.errors.password || huboError) ? 'error' : ''" :help="form.errors.password">
                <a-input-password
                    v-model:value="form.password"
                    size="large"
                    placeholder="Contraseña"
                    autocomplete="current-password"
                    @update:value="limpiarError"
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
.au-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 2px 0 18px;
    font-size: 13px;
    flex-wrap: wrap;
}
.au-link { color: var(--sigam-navy); font-weight: 600; white-space: nowrap; }
.au-link:hover { color: var(--sigam-teal-700); }
</style>
