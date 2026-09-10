<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import {
    LockOutlined,
    LoginOutlined,
    MailOutlined,
    UserAddOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AuthShell from '@/Components/AuthShell.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const acepta = ref(false);

const fuerza = computed(() => {
    const p = form.password || '';
    let s = 0;
    if (p.length >= 8) s++;
    if (p.length >= 12) s++;
    if (/[0-9]/.test(p) && /[a-zA-Z]/.test(p)) s++;
    if (/[^a-zA-Z0-9]/.test(p)) s++;
    return Math.min(s, 4);
});
const fuerzaTxt = computed(() => ['', 'Débil', 'Aceptable', 'Buena', 'Excelente'][fuerza.value]);
const fuerzaColor = computed(() => ['#e2e8f0', '#ef4444', '#f59e0b', '#3b82f6', '#16a34a'][fuerza.value]);

const enviar = () => {
    if (!acepta.value) {
        message.warning('Debes aceptar las políticas de uso del sistema.');
        return;
    }
    form.post(route('register'), {
        onError: (e) => {
            form.reset('password', 'password_confirmation');
            message.error(e.name || e.email || e.password || 'No pudimos crear la cuenta. Revisa los datos.');
        },
    });
};
</script>

<template>
    <AuthShell title="Crear cuenta">
        <template #title>Crear cuenta</template>
        <template #subtitle>Registra tus datos para acceder al sistema</template>

        <a-form layout="vertical" class="au-form" @submitcapture.prevent>
            <a-form-item :validate-status="form.errors.name ? 'error' : ''" :help="form.errors.name">
                <a-input v-model:value="form.name" size="large" placeholder="Nombre completo" autocomplete="name">
                    <template #prefix><UserOutlined /></template>
                </a-input>
            </a-form-item>

            <a-form-item :validate-status="form.errors.email ? 'error' : ''" :help="form.errors.email">
                <a-input v-model:value="form.email" type="email" size="large" placeholder="Correo electrónico" autocomplete="username">
                    <template #prefix><MailOutlined /></template>
                </a-input>
            </a-form-item>

            <a-form-item :validate-status="form.errors.password ? 'error' : ''" :help="form.errors.password">
                <a-input-password v-model:value="form.password" size="large" placeholder="Contraseña (mín. 8)" autocomplete="new-password">
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
                <div v-if="form.password" class="au-strength">
                    <span v-for="n in 4" :key="n" :style="{ background: n <= fuerza ? fuerzaColor : '#e2e8f0' }" />
                    <small :style="{ color: fuerzaColor }">{{ fuerzaTxt }}</small>
                </div>
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

            <div class="au-terms" @click="acepta = !acepta">
                <a-checkbox :checked="acepta" @click.stop="acepta = !acepta" />
                <span>He leído y acepto las políticas de uso y confidencialidad del sistema.</span>
            </div>

            <a-button type="primary" size="large" block :loading="form.processing" @click="enviar">
                <template #icon><UserAddOutlined /></template>
                Crear cuenta
            </a-button>
        </a-form>

        <template #footer>
            ¿Ya tienes una cuenta?
            <Link :href="route('login')" class="au-link au-link--strong">
                <LoginOutlined /> Iniciar sesión
            </Link>
        </template>
    </AuthShell>
</template>

<style scoped>
.au-link { color: #1e5eb8; font-weight: 600; }
.au-link:hover { color: #0d3f77; }
.au-link--strong { display: inline-flex; align-items: center; gap: 5px; }
.au-terms {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin: 2px 0 16px;
    font-size: 12.5px;
    color: #475569;
    cursor: pointer;
    user-select: none;
    line-height: 1.4;
}
.au-terms :deep(.ant-checkbox) { top: 2px; }
.au-strength { display: flex; align-items: center; gap: 4px; margin-top: 8px; }
.au-strength span { height: 4px; flex: 1; border-radius: 999px; transition: background 0.2s ease; }
.au-strength small { font-size: 11px; font-weight: 600; flex: none; margin-left: 4px; }
</style>
