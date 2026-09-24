<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { antdLocale, antTheme } from '@/theme';

defineProps({
    title: { type: String, default: 'SIGAM' },
});

const currentYear = new Date().getFullYear();

// El logo transparente (logo-sigam.png, 480×108) se ve pixelado al mostrarse
// grande sobre el panel oscuro; logo-web.jpg es la versión de mayor detalle
// (640×190, trae su propia leyenda) pero con fondo claro no transparente —
// por eso va en una tarjeta blanca sólida en vez del vidrio esmerilado que
// usa RIC, para que ese fondo no se note como un recuadro encima del navy.
const logoExists = ref(true);
</script>

<template>
    <a-config-provider :theme="antTheme" :locale="antdLocale">
    <Head :title="title" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#eaf0f6] via-[#eaf0f6] to-[#dae5f0] p-4">
        <div class="w-full max-w-5xl bg-white/80 backdrop-blur-sm rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-white/20 relative">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-[#173a5f] via-[#1f9e86] to-[#173a5f] rounded-3xl blur-xl opacity-20 animate-pulse-slow" />

            <!-- Columna izquierda: identidad de SIGAM -->
            <div class="md:w-1/2 bg-gradient-to-br from-[#0f2c4a] via-[#173a5f] to-[#0f2c4a] p-10 md:p-14 flex flex-col justify-center items-center text-white relative overflow-hidden">
                <div class="absolute -top-20 -right-20 w-72 h-72 bg-gradient-to-br from-[#1f9e86]/30 to-[#173a5f]/30 rounded-full blur-3xl animate-float" />
                <div class="absolute -bottom-20 -left-20 w-72 h-72 bg-gradient-to-tr from-[#173a5f]/30 to-[#1f9e86]/30 rounded-full blur-3xl animate-float" style="animation-delay: 2s" />
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-gradient-to-r from-[#1f9e86]/10 to-[#0d84c9]/10 rounded-full blur-3xl animate-pulse-slow" />

                <span
                    v-for="i in 25" :key="i" class="particle"
                    :style="{
                        left: Math.random() * 100 + '%',
                        top: Math.random() * 100 + '%',
                        width: (Math.random() * 4 + 1) + 'px',
                        height: (Math.random() * 4 + 1) + 'px',
                        animationDelay: (Math.random() * 10) + 's',
                        animationDuration: (Math.random() * 15 + 10) + 's',
                        opacity: Math.random() * 0.3 + 0.1,
                    }"
                />

                <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-white/20 to-transparent" />
                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-white/20 to-transparent" />
                <div class="absolute left-0 top-0 w-0.5 h-full bg-gradient-to-b from-transparent via-white/10 to-transparent" />
                <div class="absolute right-0 top-0 w-0.5 h-full bg-gradient-to-b from-transparent via-white/10 to-transparent" />

                <div class="absolute top-4 left-4 w-8 h-8 border-t-2 border-l-2 border-white/10 rounded-tl-xl" />
                <div class="absolute top-4 right-4 w-8 h-8 border-t-2 border-r-2 border-white/10 rounded-tr-xl" />
                <div class="absolute bottom-4 left-4 w-8 h-8 border-b-2 border-l-2 border-white/10 rounded-bl-xl" />
                <div class="absolute bottom-4 right-4 w-8 h-8 border-b-2 border-r-2 border-white/10 rounded-br-xl" />

                <div class="relative z-10 text-center">
                    <div class="mb-8 flex justify-center group">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-[#1f9e86] via-[#0d84c9] to-[#1f9e86] rounded-3xl blur-2xl group-hover:blur-3xl transition-all duration-700 opacity-60 group-hover:opacity-100" />
                            <div class="relative bg-white rounded-3xl p-5 border border-white/20 shadow-2xl transition-all duration-500 group-hover:scale-105">
                                <img
                                    v-if="logoExists"
                                    src="/images/logo-web.jpg"
                                    alt="SIGAM"
                                    class="h-24 w-auto max-w-[260px] object-contain rounded-lg drop-shadow-2xl"
                                    @error="logoExists = false"
                                />
                                <div v-else class="w-28 h-28 bg-[#173a5f]/10 rounded-2xl flex items-center justify-center">
                                    <span class="text-4xl font-bold text-[#173a5f]">SIGAM</span>
                                </div>
                            </div>
                            <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-16 h-1 bg-gradient-to-r from-transparent via-white/50 to-transparent rounded-full" />
                        </div>
                    </div>

                    <h1 class="text-5xl md:text-6xl font-bold mb-3 tracking-tight">
                        <span class="bg-gradient-to-r from-white via-[#bfe3d9] to-white bg-clip-text text-transparent">SIGAM</span>
                    </h1>

                    <div class="h-1 w-20 bg-gradient-to-r from-[#5eead4]/50 to-[#0d84c9]/50 mx-auto rounded-full mb-6" />

                    <p class="text-[#cfe0ef]/90 text-lg font-light leading-relaxed">
                        Sistema Integral<br />
                        <span class="font-medium text-white">de Gestión de Activos y Mantenimiento</span>
                    </p>

                    <div class="flex items-center justify-center gap-4 mt-10 mb-6">
                        <div class="w-12 h-px bg-gradient-to-r from-transparent via-[#5eead4]/20 to-transparent" />
                        <div class="w-1.5 h-1.5 rounded-full bg-[#5eead4]/30" />
                        <div class="w-12 h-px bg-gradient-to-r from-transparent via-[#5eead4]/20 to-transparent" />
                    </div>

                    <div class="mt-6 text-white/20 text-xs tracking-wider">
                        <p>&copy; SIGAM {{ currentYear }}</p>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: formulario -->
            <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white/90 backdrop-blur-sm relative">
                <div class="absolute -top-20 -right-20 w-48 h-48 bg-gradient-to-br from-[#eaf0f6] to-[#e6f5f1] rounded-full blur-2xl" />
                <div class="absolute -bottom-20 -left-20 w-48 h-48 bg-gradient-to-tr from-[#e6f5f1] to-[#eaf0f6] rounded-full blur-2xl" />

                <div class="absolute top-4 left-4 w-6 h-6 border-t-2 border-l-2 border-[#dae5f0] rounded-tl-lg" />
                <div class="absolute top-4 right-4 w-6 h-6 border-t-2 border-r-2 border-[#dae5f0] rounded-tr-lg" />
                <div class="absolute bottom-4 left-4 w-6 h-6 border-b-2 border-l-2 border-[#dae5f0] rounded-bl-lg" />
                <div class="absolute bottom-4 right-4 w-6 h-6 border-b-2 border-r-2 border-[#dae5f0] rounded-br-lg" />

                <div class="max-w-sm mx-auto w-full relative z-10 au-form-wrap">
                    <div class="mb-10">
                        <div class="flex items-center gap-4 mb-2">
                            <div class="w-1 h-14 bg-gradient-to-b from-[#173a5f] to-[#1f9e86] rounded-full" />
                            <div>
                                <h2 class="text-3xl font-bold text-gray-800 tracking-tight"><slot name="title">Bienvenido</slot></h2>
                                <p class="text-sm text-gray-500 mt-0.5 font-light"><slot name="subtitle" /></p>
                            </div>
                        </div>
                        <div class="w-16 h-0.5 bg-gradient-to-r from-[#173a5f] to-[#1f9e86] rounded-full ml-[22px]" />
                    </div>

                    <slot />

                    <p v-if="$slots.footer" class="text-center text-sm text-gray-500 mt-6"><slot name="footer" /></p>
                </div>
            </div>
        </div>
    </div>
    </a-config-provider>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}
@keyframes pulse-slow {
    0%, 100% { opacity: 0.2; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.1); }
}
@keyframes particleFloat {
    0% { transform: translateY(0px) scale(0); opacity: 0; }
    20% { opacity: 1; }
    80% { opacity: 1; }
    100% { transform: translateY(-200px) scale(1); opacity: 0; }
}
.animate-float {
    animation: float 8s ease-in-out infinite;
}
.animate-pulse-slow {
    animation: pulse-slow 6s ease-in-out infinite;
}
.particle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    animation: particleFloat linear infinite;
    pointer-events: none;
    backdrop-filter: blur(4px);
}
@media (prefers-reduced-motion: reduce) {
    .animate-float,
    .animate-pulse-slow,
    .particle {
        animation: none !important;
    }
}
</style>

<style>
/* Campos del formulario dentro del shell */
.au-form-wrap .ant-input-affix-wrapper,
.au-form-wrap .ant-input {
    border-radius: 1rem;
    border: 2px solid #e5e7eb;
    background: rgba(249, 250, 251, 0.5);
    padding-block: 9px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
.au-form-wrap .ant-input-affix-wrapper:hover {
    border-color: var(--sigam-navy);
    background: #fff;
}
.au-form-wrap .ant-input-affix-wrapper-focused {
    border-color: var(--sigam-navy) !important;
    background: #fff;
    box-shadow: 0 0 0 3px var(--sigam-navy-100) !important;
}
.au-form-wrap .ant-input-affix-wrapper > .ant-input-prefix {
    color: var(--sigam-navy);
    margin-inline-end: 10px;
    font-size: 15px;
}
.au-form-wrap .ant-form-item {
    margin-bottom: 16px;
}
.au-form-wrap .ant-btn {
    border-radius: 1rem;
    font-weight: 700;
}
.au-form-wrap .ant-btn-primary {
    height: 46px;
    border: 0;
    background: linear-gradient(to right, var(--sigam-navy), var(--sigam-teal-700));
    box-shadow: 0 10px 15px -3px rgba(23, 58, 95, 0.3);
}
.au-form-wrap .ant-btn-primary:not(:disabled):hover {
    background: linear-gradient(to right, var(--sigam-navy-700), var(--sigam-teal-700));
    box-shadow: 0 20px 25px -5px rgba(23, 58, 95, 0.4);
}
</style>
