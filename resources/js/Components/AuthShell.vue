<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { DatabaseOutlined, SafetyCertificateOutlined, ToolOutlined } from '@ant-design/icons-vue';
import { antdLocale, antTheme } from '@/theme';

defineProps({
    title: { type: String, default: 'SIGAMM' },
});

const currentYear = new Date().getFullYear();

// El logo (logo-web-transparente.png) es un JPEG comprimido de origen; sus
// bordes de compresión se notan sobre fondo oscuro/saturado. Por eso va
// dentro de una tarjeta blanca (.au-brand__logo-card) aunque el panel de
// marca detrás ya use el navy/teal del sistema.
const logoExists = ref(true);

const destacados = [
    { icono: ToolOutlined, t: 'Mantenimiento preventivo', d: 'Planes y órdenes con seguimiento total.' },
    { icono: DatabaseOutlined, t: 'Inventario centralizado', d: 'Equipos, ubicaciones y proveedores en un solo lugar.' },
    { icono: SafetyCertificateOutlined, t: 'Trazabilidad completa', d: 'Historial, evidencias y auditoría de cada movimiento.' },
];
</script>

<template>
    <a-config-provider :theme="antTheme" :locale="antdLocale">
    <Head :title="title" />

    <div class="au">
        <!-- Panel de marca -->
        <aside class="au-brand">
            <span class="au-brand__dots" aria-hidden="true"></span>
            <span class="au-brand__blob au-brand__blob--1" aria-hidden="true"></span>
            <span class="au-brand__blob au-brand__blob--2" aria-hidden="true"></span>
            <span class="au-brand__ring" aria-hidden="true"></span>

            <span class="au-brand__corner au-brand__corner--tl" aria-hidden="true"></span>
            <span class="au-brand__corner au-brand__corner--tr" aria-hidden="true"></span>
            <span class="au-brand__corner au-brand__corner--bl" aria-hidden="true"></span>
            <span class="au-brand__corner au-brand__corner--br" aria-hidden="true"></span>

            <div class="au-brand__inner">
                <div class="au-brand__logo-card">
                    <img
                        v-if="logoExists"
                        src="/images/logo-web-transparente.png"
                        alt="SIGAMM"
                        class="au-brand__logo"
                        @error="logoExists = false"
                    />
                    <div v-else class="au-brand__logo-fallback">SM</div>
                </div>

                <div class="au-brand__rule" />

                <p class="au-brand__sub">
                    Sistema Integral de gestión de
                    <span>Activos, Mantenimiento y Minutas</span>
                </p>

                <ul class="au-feats">
                    <li v-for="(f, i) in destacados" :key="f.t" :style="{ '--d': i * 110 + 'ms' }">
                        <span class="au-feats__ic"><component :is="f.icono" /></span>
                        <div>
                            <b>{{ f.t }}</b>
                            <small>{{ f.d }}</small>
                        </div>
                    </li>
                </ul>

                <p class="au-brand__foot">&copy; SIGAMM {{ currentYear }}</p>
            </div>
        </aside>

        <!-- Panel del formulario -->
        <main class="au-side">
            <div class="au-card">
                <div class="au-card__head">
                    <div class="au-card__bar" />
                    <div>
                        <h1 class="au-card__title"><slot name="title">Bienvenido</slot></h1>
                        <p class="au-card__sub"><slot name="subtitle" /></p>
                    </div>
                </div>

                <slot />

                <p v-if="$slots.footer" class="au-card__foot"><slot name="footer" /></p>
            </div>
        </main>
    </div>
    </a-config-provider>
</template>

<style scoped>
/* ==========================================================
   Layout general — split screen a pantalla completa
   ========================================================== */
.au {
    position: relative;
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    min-height: 100vh;
    background: #fff;
}

/* ---------- Panel de marca ---------- */
.au-brand {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 56px 64px;
    background: linear-gradient(155deg, var(--sigam-navy-700) 0%, var(--sigam-navy) 48%, var(--sigam-teal-700) 100%);
    border-right: none;
}

/* curva que empalma con el panel del formulario, como en SAINS */
.au-brand::after {
    content: '';
    position: absolute;
    top: -14%;
    right: -190px;
    width: 320px;
    height: 128%;
    background: #fff;
    border-radius: 50%;
    z-index: 1;
}

.au-brand__dots {
    position: absolute;
    top: 44px;
    left: 64px;
    width: 140px;
    height: 110px;
    background-image: radial-gradient(rgba(255, 255, 255, .22) 1.6px, transparent 1.8px);
    background-size: 18px 18px;
}

.au-brand__blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(50px);
}
.au-brand__blob--1 {
    top: -120px;
    right: -60px;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle at 38% 38%, rgba(255, 255, 255, .22), rgba(31, 158, 134, .18) 60%, transparent 70%);
    animation: au-float 9s ease-in-out infinite;
}
.au-brand__blob--2 {
    bottom: -140px;
    left: -80px;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle at 60% 60%, rgba(31, 158, 134, .28), rgba(255, 255, 255, .1) 60%, transparent 70%);
    animation: au-float 12s ease-in-out infinite reverse;
}
.au-brand__ring {
    position: absolute;
    top: 50%;
    left: 8%;
    width: 260px;
    height: 260px;
    margin-top: -130px;
    border-radius: 50%;
    border: 2px dashed rgba(255, 255, 255, .16);
    animation: au-spin 40s linear infinite;
}
@keyframes au-float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-16px, 18px); }
}
@keyframes au-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.au-brand__corner {
    position: absolute;
    width: 28px;
    height: 28px;
    border-color: rgba(255, 255, 255, .3);
    z-index: 1;
}
.au-brand__corner--tl { top: 24px; left: 24px; border-top: 2px solid; border-left: 2px solid; border-radius: 12px 0 0 0; }
.au-brand__corner--tr { top: 24px; right: 24px; border-top: 2px solid; border-right: 2px solid; border-radius: 0 12px 0 0; }
.au-brand__corner--bl { bottom: 24px; left: 24px; border-bottom: 2px solid; border-left: 2px solid; border-radius: 0 0 0 12px; }
.au-brand__corner--br { bottom: 24px; right: 24px; border-bottom: 2px solid; border-right: 2px solid; border-radius: 0 0 12px 0; }

.au-brand__inner {
    position: relative;
    z-index: 2;
    max-width: 520px;
    width: 100%;
    text-align: center;
    animation: au-slide-in .65s cubic-bezier(.16, 1, .3, 1);
}
@keyframes au-slide-in { from { opacity: 0; transform: translateX(-26px); } to { opacity: 1; transform: none; } }

.au-brand__logo-card {
    position: relative;
    display: inline-block;
    background: rgba(255, 255, 255, .97);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 26px;
    border: 1px solid rgba(255, 255, 255, .6);
    padding: 26px 34px;
    box-shadow:
        0 28px 50px -16px rgba(6, 16, 28, .55),
        0 0 0 1px rgba(255, 255, 255, .06) inset,
        0 60px 90px -40px rgba(31, 158, 134, .35);
    animation: au-logo-in .7s cubic-bezier(.16, 1, .3, 1) .08s both;
}
.au-brand__logo-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 1px;
    background: linear-gradient(155deg, rgba(255, 255, 255, .9), rgba(255, 255, 255, 0) 40%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
}
@keyframes au-logo-in { from { opacity: 0; transform: scale(.92) translateY(-8px); } to { opacity: 1; transform: none; } }

.au-brand__logo {
    display: block;
    width: 100%;
    max-width: 420px;
    height: auto;
    object-fit: contain;
}

.au-brand__logo-fallback {
    width: 96px;
    height: 96px;
    margin: 0 auto;
    border-radius: 20px;
    background: var(--sigam-navy-050);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 800;
    color: var(--sigam-navy);
}

.au-brand__rule {
    width: 84px;
    height: 4px;
    margin: 26px auto 22px;
    border-radius: 999px;
    background: linear-gradient(90deg, #fff, var(--sigam-teal-050));
}

.au-brand__sub {
    font-size: 15px;
    font-weight: 300;
    line-height: 1.6;
    color: rgba(255, 255, 255, .78);
    max-width: 320px;
    margin: 0 auto;
}
.au-brand__sub span {
    font-weight: 700;
    color: #fff;
}

.au-feats {
    list-style: none;
    margin: 34px 0 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    text-align: left;
}
.au-feats li {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    opacity: 0;
    animation: au-rise .5s cubic-bezier(.16, 1, .3, 1) forwards;
    animation-delay: calc(380ms + var(--d));
}
@keyframes au-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.au-feats__ic {
    width: 36px;
    height: 36px;
    flex: none;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: rgba(255, 255, 255, .16);
    border: 1px solid rgba(255, 255, 255, .3);
    box-shadow: 0 6px 14px -6px rgba(9, 22, 37, .5);
    transition: transform .22s ease;
}
.au-feats li:hover .au-feats__ic { transform: translateY(-3px) scale(1.05); }
.au-feats b {
    display: block;
    font-size: 11.5px;
    font-weight: 800;
    color: #fff;
    line-height: 1.25;
}
.au-feats small {
    font-size: 10.5px;
    color: rgba(255, 255, 255, .6);
    line-height: 1.35;
}

.au-brand__foot {
    margin: 30px 0 0;
    font-size: 11px;
    letter-spacing: .06em;
    color: rgba(255, 255, 255, .5);
}

/* ---------- Panel del formulario ---------- */
.au-side {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 28px;
    background: #fff;
}

.au-card {
    width: 100%;
    max-width: 400px;
    animation: au-card-in .55s cubic-bezier(.16, 1, .3, 1);
}
@keyframes au-card-in { from { opacity: 0; transform: translateY(18px) scale(.99); } to { opacity: 1; transform: none; } }

.au-card__head {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 30px;
}
.au-card__bar {
    width: 4px;
    height: 52px;
    flex: none;
    border-radius: 999px;
    background: linear-gradient(180deg, var(--sigam-navy), var(--sigam-teal));
}
.au-card__title {
    font-size: 27px;
    font-weight: 800;
    color: #1f2937;
    letter-spacing: -0.01em;
    margin: 0;
}
.au-card__sub {
    font-size: 13.5px;
    color: #6b7280;
    font-weight: 400;
    margin: 3px 0 0;
}

.au-card__foot {
    margin: 22px 0 0;
    text-align: center;
    font-size: 13px;
    color: #6b7280;
}

/* ---------- Responsive ---------- */
@media (max-width: 1024px) {
    .au { grid-template-columns: 1fr; }
    .au-brand { display: none; }
    .au-side { min-height: 100vh; }
}

@media (prefers-reduced-motion: reduce) {
    .au *, .au *::before, .au *::after {
        animation: none !important;
    }
    .au-feats li,
    .au-brand__inner {
        opacity: 1 !important;
    }
}
</style>

<style>
/* Campos del formulario dentro del shell */
.au-form .ant-input-affix-wrapper,
.au-form .ant-input {
    border-radius: 1rem;
    border: 2px solid #e5e7eb;
    background: rgba(249, 250, 251, 0.5);
    padding-block: 9px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
.au-form .ant-input-affix-wrapper:hover {
    border-color: var(--sigam-navy);
    background: #fff;
}
.au-form .ant-input-affix-wrapper-focused {
    border-color: var(--sigam-navy) !important;
    background: #fff;
    box-shadow: 0 0 0 3px var(--sigam-navy-100) !important;
}
.au-form .ant-input-affix-wrapper > .ant-input-prefix {
    color: var(--sigam-navy);
    margin-inline-end: 10px;
    font-size: 15px;
}
.au-form .ant-form-item {
    margin-bottom: 16px;
}
.au-form .ant-btn {
    border-radius: 1rem;
    font-weight: 700;
}
.au-form .ant-btn-primary {
    height: 46px;
    border: 0;
    background: linear-gradient(to right, var(--sigam-navy), var(--sigam-teal-700));
    box-shadow: 0 10px 15px -3px rgba(23, 58, 95, 0.3);
}
.au-form .ant-btn-primary:not(:disabled):hover {
    background: linear-gradient(to right, var(--sigam-navy-700), var(--sigam-teal-700));
    box-shadow: 0 20px 25px -5px rgba(23, 58, 95, 0.4);
}

/* Entrada escalonada de los campos del formulario, como en SAINS */
.au-form > * {
    opacity: 0;
    animation: au-field-in .45s cubic-bezier(.16, 1, .3, 1) forwards;
}
.au-form > *:nth-child(1) { animation-delay: .18s; }
.au-form > *:nth-child(2) { animation-delay: .25s; }
.au-form > *:nth-child(3) { animation-delay: .32s; }
.au-form > *:nth-child(4) { animation-delay: .39s; }
.au-form > *:nth-child(5) { animation-delay: .46s; }
.au-form > *:nth-child(6) { animation-delay: .53s; }
@keyframes au-field-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

@media (prefers-reduced-motion: reduce) {
    .au-form > * { animation: none !important; opacity: 1 !important; }
}
</style>
