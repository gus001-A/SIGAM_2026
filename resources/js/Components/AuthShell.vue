<script setup>
import { Head } from '@inertiajs/vue3';
import {
    CalendarOutlined,
    HistoryOutlined,
    ProfileOutlined,
} from '@ant-design/icons-vue';
import { antdLocale, antTheme } from '@/theme';

defineProps({
    title: { type: String, default: 'SIGAM' },
});

const caracteristicas = [
    { icon: ProfileOutlined, t: 'Expediente digital', d: 'Historial técnico completo de cada equipo médico.' },
    { icon: CalendarOutlined, t: 'Mantenimiento preventivo', d: 'Programación automática y alertas de vencimiento.' },
    { icon: HistoryOutlined, t: 'Trazabilidad total', d: 'Cada acción queda registrada para auditoría.' },
];
</script>

<template>
    <a-config-provider :theme="antTheme" :locale="antdLocale">
        <Head :title="title" />

        <div class="au">
            <!-- Panel de marca -->
            <aside class="au-brand">
                <span class="au-brand__dots" aria-hidden="true" />
                <span class="au-brand__arc" aria-hidden="true" />
                <span class="au-brand__ring" aria-hidden="true" />

                <div class="au-brand__inner">
                    <img src="/images/logo-sigam-blanco.png" alt="SIGAM" class="au-brand__logo" />
                    <h2 class="au-brand__title">
                        Gestión integral de <span>activos y mantenimiento</span> hospitalario
                    </h2>
                    <p class="au-brand__sub">
                        Inventario centralizado, mantenimiento preventivo y correctivo, expediente
                        digital y trazabilidad — en una sola plataforma.
                    </p>

                    <ul class="au-feats">
                        <li v-for="(f, i) in caracteristicas" :key="f.t" :style="{ '--d': i * 90 + 'ms' }">
                            <span class="au-feats__ic"><component :is="f.icon" /></span>
                            <b>{{ f.t }}</b>
                            <small>{{ f.d }}</small>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Panel del formulario -->
            <main class="au-side">
                <div class="au-card">
                    <img src="/images/logo-sigam.png" alt="SIGAM" class="au-card__logo" />

                    <h1 class="au-card__title"><slot name="title">Bienvenido</slot></h1>
                    <p class="au-card__sub"><slot name="subtitle" /></p>

                    <slot />

                    <p v-if="$slots.footer" class="au-card__foot"><slot name="footer" /></p>
                </div>
                <p class="au-legal">© {{ new Date().getFullYear() }} SIGAM · Sistema de Inventario y Gestión de Mantenimientos</p>
            </main>
        </div>
    </a-config-provider>
</template>

<style scoped>
.au {
    position: relative;
    display: grid;
    grid-template-columns: 1.05fr 1fr;
    min-height: 100vh;
    background: #ffffff;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
}

/* ---------- Panel de marca ---------- */
.au-brand {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    padding: 56px 132px 56px 7%;
    color: #fff;
    background: linear-gradient(158deg, #0d3f77 0%, #1e5eb8 52%, #0b6b60 100%);
}
.au-brand::after {
    content: '';
    position: absolute;
    top: -14%;
    right: -190px;
    width: 300px;
    height: 128%;
    background: #ffffff;
    border-radius: 50%;
}
.au-brand__dots {
    position: absolute;
    top: 46px;
    right: 130px;
    width: 150px;
    height: 118px;
    background-image: radial-gradient(rgba(255, 255, 255, 0.38) 1.7px, transparent 1.8px);
    background-size: 19px 19px;
    opacity: 0.7;
}
.au-brand__arc {
    position: absolute;
    bottom: -140px;
    right: -60px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle at 38% 38%, #5eead4, #2dd4bf 60%, transparent 68%);
    opacity: 0.9;
    animation: au-float 9s ease-in-out infinite;
}
.au-brand__ring {
    position: absolute;
    top: -120px;
    left: -120px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.12);
    animation: au-float 12s ease-in-out infinite reverse;
}
@keyframes au-float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-14px, 16px); }
}
.au-brand__inner {
    position: relative;
    z-index: 2;
    max-width: 520px;
    width: 100%;
    animation: au-slide-in 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes au-slide-in { from { opacity: 0; transform: translateX(-22px); } to { opacity: 1; transform: none; } }

.au-brand__logo {
    height: 34px;
    width: auto;
    display: block;
    margin-bottom: 30px;
    opacity: 0.96;
}
.au-brand__title {
    font-size: clamp(1.7rem, 2.6vw, 2.3rem);
    font-weight: 800;
    line-height: 1.24;
    margin: 0 0 14px;
    letter-spacing: -0.01em;
}
.au-brand__title span {
    color: #5eead4;
    position: relative;
    white-space: nowrap;
}
.au-brand__title span::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 2px;
    height: 8px;
    background: rgba(94, 234, 212, 0.26);
    border-radius: 4px;
    z-index: -1;
}
.au-brand__sub {
    font-size: 1rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.8);
    margin: 0 0 40px;
    max-width: 460px;
}
.au-feats {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}
.au-feats li {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 5px;
    opacity: 0;
    animation: au-rise 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    animation-delay: calc(300ms + var(--d));
}
@keyframes au-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.au-feats li:not(:last-child)::after {
    content: '';
    position: absolute;
    right: -11px;
    top: 6px;
    bottom: 6px;
    width: 1px;
    background: rgba(255, 255, 255, 0.2);
}
.au-feats__ic {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1.5px solid rgba(255, 255, 255, 0.35);
    background: rgba(255, 255, 255, 0.06);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #5eead4;
    margin-bottom: 6px;
}
.au-feats b { font-size: 13.5px; font-weight: 700; }
.au-feats small { font-size: 11.5px; color: rgba(255, 255, 255, 0.62); line-height: 1.4; }

/* ---------- Panel del formulario ---------- */
.au-side {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 28px;
    gap: 18px;
}
.au-card {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border: 1px solid #eef1f6;
    border-radius: 24px;
    padding: 40px 40px 32px;
    box-shadow: 0 44px 100px -38px rgba(15, 23, 42, 0.32);
    text-align: center;
    animation: au-card-in 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes au-card-in { from { opacity: 0; transform: translateY(16px) scale(0.99); } to { opacity: 1; transform: none; } }
.au-card__logo { height: 34px; width: auto; margin: 0 auto 18px; }
.au-card__title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0d3f77;
    margin: 0 0 5px;
    letter-spacing: -0.01em;
}
.au-card__sub { font-size: 0.88rem; color: #64748b; margin: 0 0 24px; }
.au-card > :deep(form),
.au-card > :deep(.au-form) { text-align: left; }

.au-card :deep(.au-form) > * {
    opacity: 0;
    animation: au-field-in 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.au-card :deep(.au-form) > *:nth-child(1) { animation-delay: 0.12s; }
.au-card :deep(.au-form) > *:nth-child(2) { animation-delay: 0.19s; }
.au-card :deep(.au-form) > *:nth-child(3) { animation-delay: 0.26s; }
.au-card :deep(.au-form) > *:nth-child(4) { animation-delay: 0.33s; }
.au-card :deep(.au-form) > *:nth-child(5) { animation-delay: 0.40s; }
.au-card :deep(.au-form) > *:nth-child(6) { animation-delay: 0.47s; }
@keyframes au-field-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

.au-card__foot { margin: 18px 0 0; font-size: 13px; color: #64748b; }
.au-legal { font-size: 11px; color: #94a3b8; margin: 0; text-align: center; }

/* ---------- Responsive ---------- */
@media (max-width: 1100px) {
    .au-brand { padding-right: 96px; }
    .au-brand::after { right: -220px; }
    .au-feats { gap: 16px; }
}
@media (max-width: 1024px) {
    .au { grid-template-columns: 1fr; }
    .au-brand { display: none; }
    .au-side {
        padding: 36px 18px;
        min-height: 100vh;
        background: linear-gradient(158deg, #0d3f77 0%, #1e5eb8 60%, #0b6b60 100%);
    }
    .au-card { box-shadow: 0 30px 70px -20px rgba(15, 23, 42, 0.45); }
    .au-legal { color: rgba(255, 255, 255, 0.75); }
}
@media (prefers-reduced-motion: reduce) {
    .au *, .au *::before, .au *::after { animation: none !important; }
    .au-card :deep(.au-form) > *,
    .au-feats li,
    .au-brand__inner { opacity: 1 !important; }
}
</style>

<style>
/* Campos */
.au .ant-input-affix-wrapper {
    border-radius: 12px;
    padding-block: 9px;
    border-color: #dbe3ec;
    background: #f8fafc;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}
.au .ant-input-affix-wrapper:hover {
    border-color: #1e5eb8;
    background: #fff;
}
.au .ant-input-affix-wrapper-focused {
    background: #fff;
    border-color: #1e5eb8;
    box-shadow: 0 0 0 3px rgba(30, 94, 184, 0.14);
}
.au .ant-input-affix-wrapper > .ant-input-prefix {
    color: #1e5eb8;
    margin-inline-end: 10px;
    font-size: 15px;
}
.au .ant-input-affix-wrapper > input.ant-input { background: transparent; font-size: 14.5px; }
.au .ant-form-item { margin-bottom: 16px; }

/* Botón principal */
.au .ant-btn {
    border-radius: 12px;
    font-weight: 700;
    letter-spacing: 0.01em;
}
.au .ant-btn-primary {
    height: 46px;
    box-shadow: 0 10px 22px -8px rgba(30, 94, 184, 0.55);
    background: linear-gradient(180deg, #2a6cc9, #1e5eb8);
    border: none;
}
.au .ant-btn-primary:not(:disabled):hover {
    background: linear-gradient(180deg, #2f74d3, #1c559f);
    box-shadow: 0 14px 26px -8px rgba(30, 94, 184, 0.6);
}
</style>
