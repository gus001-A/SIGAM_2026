<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import { ArrowLeftOutlined, MailOutlined, SendOutlined } from '@ant-design/icons-vue';
import AuthShell from '@/Components/AuthShell.vue';

defineProps({ status: String });

const form = useForm({ email: '' });

const enviar = () => {
    form.post(route('password.email'), {
        onError: (e) => message.error(e.email || 'No pudimos enviar el enlace.'),
    });
};
</script>

<template>
    <AuthShell title="Recuperar contraseña">
        <template #title>Recuperar contraseña</template>
        <template #subtitle>Te enviaremos un enlace para restablecerla</template>

        <a-alert v-if="status" :message="status" type="success" show-icon class="au-status" />

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

            <a-button type="primary" size="large" block :loading="form.processing" @click="enviar">
                <template #icon><SendOutlined /></template>
                Enviar enlace
            </a-button>
        </a-form>

        <template #footer>
            <Link :href="route('login')" class="au-link au-link--strong">
                <ArrowLeftOutlined /> Volver a iniciar sesión
            </Link>
        </template>
    </AuthShell>
</template>

<style scoped>
.au-status { margin-bottom: 16px; }
.au-link { color: var(--sigam-navy); font-weight: 600; }
.au-link:hover { color: var(--sigam-teal-700); }
.au-link--strong { display: inline-flex; align-items: center; gap: 5px; }
</style>
