<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import {
    CalendarOutlined,
    IdcardOutlined,
    LockOutlined,
    MailOutlined,
    PhoneOutlined,
    SaveOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { reglaCorreo, reglaRequerido, reglaTelefono, soloDigitos } from '@/utils/restricciones';
import { colorRol, etiquetaRol } from '@/utils/roles';

defineProps({
    status: String,
});

const page = usePage();
const usuario = computed(() => page.props.auth.user);
const roles = computed(() => page.props.auth.roles ?? []);

const iniciales = computed(() => {
    const n = usuario.value?.nombre ?? usuario.value?.name ?? '?';
    return n.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase();
});

const miembroDesde = computed(() =>
    usuario.value?.created_at
        ? new Date(usuario.value.created_at).toLocaleDateString('es-MX', { month: 'long', year: 'numeric' })
        : null,
);

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
            <!-- Hero -->
            <div class="perfil__hero">
                <span class="perfil__hero-dots" aria-hidden="true"></span>
                <a-avatar :size="76" class="perfil__avatar">{{ iniciales }}</a-avatar>
                <div class="perfil__hero-txt">
                    <div class="perfil__nombre">{{ usuario?.nombre ?? usuario?.name }}</div>
                    <div class="perfil__correo"><MailOutlined /> {{ usuario?.email }}</div>
                    <div class="perfil__tags">
                        <a-tag v-for="r in roles" :key="r" :color="colorRol(r)">{{ etiquetaRol(r) }}</a-tag>
                    </div>
                    <div class="perfil__chips">
                        <span v-if="usuario?.telefono" class="perfil__chip">
                            <PhoneOutlined /> {{ usuario.telefono }}
                        </span>
                        <span v-if="miembroDesde" class="perfil__chip">
                            <CalendarOutlined /> Miembro desde {{ miembroDesde }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tarjetas -->
            <div class="perfil__grid">
                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #0d84c9">
                            <IdcardOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Datos personales</div>
                            <div class="card__sub">Tu nombre, correo y teléfono de contacto</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <a-form :model="perfil" :rules="reglasPerfil" layout="vertical" @finish="guardarPerfil">
                            <a-form-item label="Nombre completo" name="name" :validate-status="perfil.errors.name ? 'error' : ''" :help="perfil.errors.name">
                                <a-input v-model:value="perfil.name">
                                    <template #prefix><UserOutlined class="op-40" /></template>
                                </a-input>
                            </a-form-item>
                            <a-form-item label="Correo electrónico" name="email" :validate-status="perfil.errors.email ? 'error' : ''" :help="perfil.errors.email">
                                <a-input v-model:value="perfil.email" type="email">
                                    <template #prefix><MailOutlined class="op-40" /></template>
                                </a-input>
                            </a-form-item>
                            <a-form-item label="Teléfono" name="telefono" :validate-status="perfil.errors.telefono ? 'error' : ''" :help="perfil.errors.telefono">
                                <a-input v-model:value="perfil.telefono" :maxlength="10" inputmode="numeric" placeholder="10 dígitos" @keypress="soloDigitos">
                                    <template #prefix><PhoneOutlined class="op-40" /></template>
                                </a-input>
                            </a-form-item>

                            <a-button type="primary" html-type="submit" :loading="perfil.processing">
                                <template #icon><SaveOutlined /></template>
                                Guardar cambios
                            </a-button>
                        </a-form>
                    </div>
                </div>

                <div class="card">
                    <div class="card__head">
                        <div class="card__ico" style="--c: #6b4bc9">
                            <LockOutlined />
                        </div>
                        <div class="card__meta">
                            <div class="card__titulo">Seguridad</div>
                            <div class="card__sub">Actualiza tu contraseña de acceso</div>
                        </div>
                    </div>
                    <div class="card__body">
                        <a-form :model="clave" :rules="reglasClave" layout="vertical" @finish="guardarClave">
                            <a-form-item label="Contraseña actual" name="current_password" :validate-status="clave.errors.current_password ? 'error' : ''" :help="clave.errors.current_password">
                                <a-input-password v-model:value="clave.current_password" autocomplete="current-password" />
                            </a-form-item>
                            <a-form-item label="Nueva contraseña" name="password" :validate-status="clave.errors.password ? 'error' : ''" :help="clave.errors.password">
                                <a-input-password v-model:value="clave.password" autocomplete="new-password" />
                            </a-form-item>
                            <a-form-item label="Confirmar nueva contraseña" name="password_confirmation" :validate-status="clave.errors.password_confirmation ? 'error' : ''" :help="clave.errors.password_confirmation">
                                <a-input-password v-model:value="clave.password_confirmation" autocomplete="new-password" />
                            </a-form-item>

                            <a-button type="primary" html-type="submit" :loading="clave.processing">
                                <template #icon><SaveOutlined /></template>
                                Actualizar contraseña
                            </a-button>
                        </a-form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.perfil {
    max-width: 980px;
    margin: 0 auto;
}
.op-40 {
    opacity: 0.4;
}

/* ---------- Hero ---------- */
.perfil__hero {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 26px 28px;
    border-radius: 16px;
    margin-bottom: 14px;
    background: var(--sigam-grad);
    background-size: 180% 180%;
    animation: sigam-grad-shift 8s ease infinite;
    box-shadow: var(--sigam-sombra-md);
}
.perfil__hero-dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, .16) 1.6px, transparent 1.8px);
    background-size: 20px 20px;
    -webkit-mask-image: linear-gradient(120deg, #000 0%, transparent 60%);
    mask-image: linear-gradient(120deg, #000 0%, transparent 60%);
}
.perfil__avatar {
    position: relative;
    z-index: 1;
    background: rgba(255, 255, 255, 0.16) !important;
    border: 2px solid rgba(255, 255, 255, 0.5);
    color: #fff !important;
    font-weight: 800;
    font-size: 26px;
    flex: none;
    box-shadow: 0 8px 20px -6px rgba(0, 0, 0, 0.35);
}
.perfil__hero-txt {
    position: relative;
    z-index: 1;
    min-width: 0;
}
.perfil__nombre {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.01em;
}
.perfil__correo {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.85);
    margin: 2px 0 10px;
}
.perfil__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 10px;
}
.perfil__tags :deep(.ant-tag) {
    border: 1px solid rgba(255, 255, 255, 0.35);
}
.perfil__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.perfil__chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 11px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 600;
    color: #fff;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.24);
}

/* ---------- Tarjetas ---------- */
.perfil__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    align-items: start;
}
@media (max-width: 860px) {
    .perfil__grid { grid-template-columns: 1fr; }
}

.card {
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--sigam-sombra-sm);
    transition: box-shadow 0.18s ease;
}
.card:hover { box-shadow: var(--sigam-sombra-md); }
.card__head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 70%);
}
.card__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 14%, #fff);
    flex-shrink: 0;
}
.card__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    flex: 1;
}
.card__titulo {
    font-weight: 800;
    font-size: 14px;
    color: var(--sigam-navy);
    letter-spacing: -0.1px;
    line-height: 1.2;
}
.card__sub { font-size: 11px; color: var(--sigam-tenue); }
.card__body { padding: 18px 16px 16px; }
</style>
