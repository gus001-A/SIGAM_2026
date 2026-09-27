<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    titulo: { type: String, default: '' },
    subtitulo: { type: String, default: '' },
    icono: { type: [Object, Function], default: null },
    color: { type: String, default: '#0d84c9' },
    maxWidth: { type: String, default: 'md' },
    sinHeader: { type: Boolean, default: false },
    sinPadding: { type: Boolean, default: false },
    /** Fondo del contenedor: 'blanco' | 'oscuro' | 'transparente' */
    fondo: { type: String, default: 'blanco' },
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const dialog = ref(null);
const visible = ref(props.show);

watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
            visible.value = true;
            requestAnimationFrame(() => dialog.value?.showModal());
        } else {
            document.body.style.overflow = '';
            setTimeout(() => {
                dialog.value?.close();
                visible.value = false;
            }, 180);
        }
    },
);

const cerrar = () => {
    if (props.closeable) emit('close');
};

const onEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        cerrar();
    }
};

onMounted(() => document.addEventListener('keydown', onEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', onEscape);
    document.body.style.overflow = '';
});

const widthClass = {
    xs: 'mf-w-xs',
    sm: 'mf-w-sm',
    md: 'mf-w-md',
    lg: 'mf-w-lg',
    xl: 'mf-w-xl',
    '2xl': 'mf-w-2xl',
    '3xl': 'mf-w-3xl',
    '4xl': 'mf-w-4xl',
    '5xl': 'mf-w-5xl',
    full: 'mf-w-full',
};
</script>

<template>
    <dialog ref="dialog" class="mf-dialog" @cancel.prevent="cerrar">
        <div class="mf-backdrop" @click="cerrar"></div>

        <Transition enter-active-class="mf-t-enter" enter-from-class="mf-t-enter-from" enter-to-class="mf-t-enter-to"
            leave-active-class="mf-t-leave" leave-from-class="mf-t-leave-from" leave-to-class="mf-t-leave-to">
            <div v-show="show" class="mf" :class="[
                widthClass[maxWidth] ?? widthClass.md,
                `mf--${fondo}`,
            ]" :style="{ '--c': color }" role="dialog" aria-modal="true">
                <!-- Header -->
                <div v-if="!sinHeader" class="mf-head">
                    <div v-if="icono" class="mf-head__ico">
                        <component :is="icono" />
                    </div>
                    <div class="mf-head__meta">
                        <div class="mf-head__titulo">{{ titulo }}</div>
                        <div v-if="subtitulo" class="mf-head__sub">{{ subtitulo }}</div>
                    </div>
                    <button type="button" class="mf-cerrar" @click="cerrar" aria-label="Cerrar">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                            stroke-width="2.6" stroke-linecap="round">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>

                <!-- Cerrar flotante cuando no hay header -->
                <button v-else type="button" class="mf-cerrar mf-cerrar--flotante" @click="cerrar" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.6"
                        stroke-linecap="round">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>

                <!-- Body -->
                <div class="mf-body" :class="{ 'mf-body--limpio': sinPadding }">
                    <slot />
                </div>

                <!-- Footer -->
                <div v-if="$slots.footer" class="mf-foot">
                    <slot name="footer" />
                </div>
            </div>
        </Transition>
    </dialog>
</template>

<style scoped>
/* =========================================================
   Dialog nativo — reset completo
   ========================================================= */
.mf-dialog {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    max-width: none;
    max-height: none;
    margin: 0;
    padding: 0;
    border: none;
    background: transparent;
    overflow: hidden;
    z-index: 9999;
}

.mf-dialog::backdrop {
    background: transparent;
}

/* Backdrop */
.mf-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
    z-index: 0;
}

/* =========================================================
   Contenedor del modal
   ========================================================= */
.mf {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    z-index: 1;
    overflow: hidden;
}

/* Anchos */
.mf-w-xs {
    max-width: 320px;
}

.mf-w-sm {
    max-width: 400px;
}

.mf-w-md {
    max-width: 480px;
}

.mf-w-lg {
    max-width: 600px;
}

.mf-w-xl {
    max-width: 720px;
}

.mf-w-2xl {
    max-width: 900px;
}

.mf-w-3xl {
    max-width: 1100px;
}

.mf-w-4xl {
    max-width: 1300px;
}

.mf-w-5xl {
    max-width: 1500px;
}

.mf-w-full {
    max-width: calc(100vw - 32px);
}

/* Variantes de fondo */
.mf--blanco {
    background: #fff;
    border-radius: 14px;
    box-shadow:
        0 20px 50px rgba(15, 23, 42, 0.18),
        0 8px 16px rgba(15, 23, 42, 0.08);
}

.mf--oscuro {
    background: #0f1e33;
    border-radius: 14px;
    box-shadow:
        0 20px 50px rgba(0, 0, 0, 0.5),
        0 8px 16px rgba(0, 0, 0, 0.3);
    color: #fff;
}

.mf--transparente {
    background: transparent;
    border-radius: 14px;
    box-shadow: none;
}

/* =========================================================
   Header con color SÓLIDO del sistema
   ========================================================= */
.mf-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    /* Color sólido (sin gradiente) */
    background: var(--sigam-navy);
    color: #fff;
}

.mf-head__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.18);
    flex-shrink: 0;
}

.mf-head__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    flex: 1;
}

.mf-head__titulo {
    font-weight: 700;
    font-size: 14px;
    color: #fff;
    letter-spacing: -0.2px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mf-head__sub {
    font-size: 11.5px;
    color: rgba(255, 255, 255, 0.72);
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* =========================================================
   Botón cerrar — grande, con animación
   ========================================================= */
.mf-cerrar {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    border: 1px solid rgba(255, 255, 255, 0.22);
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    outline: none;
    -webkit-tap-highlight-color: transparent;
    transition:
        background 0.2s ease,
        border-color 0.2s ease,
        transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1),
        box-shadow 0.2s ease;
}

.mf-cerrar:focus,
.mf-cerrar:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
}

.mf-cerrar:hover {
    background: rgba(220, 38, 38, 0.9);
    border-color: rgba(220, 38, 38, 1);
    transform: rotate(90deg) scale(1.08);
    box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
}

.mf-cerrar:active {
    transform: rotate(90deg) scale(0.96);
    transition-duration: 0.1s;
}

.mf-cerrar svg {
    transition: transform 0.25s ease;
}

.mf-cerrar:hover svg {
    transform: rotate(-90deg);
}

.mf--oscuro .mf-cerrar {
    border-color: rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.08);
}

/* Flotante (sin header) */
.mf-cerrar--flotante {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
    width: 38px;
    height: 38px;
    border-radius: 11px;
    background: rgba(255, 255, 255, 0.95);
    color: #334155;
    border: 1px solid rgba(15, 37, 71, 0.08);
    box-shadow: 0 4px 12px rgba(15, 37, 71, 0.18);
}

.mf-cerrar--flotante:hover {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
    transform: rotate(90deg) scale(1.08);
    box-shadow: 0 6px 18px rgba(220, 38, 38, 0.35);
}

/* =========================================================
   Body
   ========================================================= */
.mf-body {
    padding: 16px 18px 18px;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    scrollbar-width: thin;
}

.mf-body--limpio {
    padding: 0;
    overflow: hidden;
}

.mf-body::-webkit-scrollbar {
    width: 6px;
}

.mf-body::-webkit-scrollbar-thumb {
    background: var(--sigam-borde);
    border-radius: 3px;
}

/* =========================================================
   Footer
   ========================================================= */
.mf-foot {
    padding: 12px 18px 16px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
}

.mf-foot :deep(.ant-btn),
.mf-foot :deep(a.ant-btn) {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 34px;
    padding: 0 14px;
    border-radius: 9px;
    font-weight: 600;
    font-size: 12.5px;
    border: 1px solid transparent;
    box-shadow: none !important;
    outline: none !important;
    transition: background 0.14s ease, color 0.14s ease, border-color 0.14s ease;
    -webkit-tap-highlight-color: transparent;
    text-decoration: none !important;
    cursor: pointer;
}

.mf-foot :deep(.ant-btn:focus),
.mf-foot :deep(.ant-btn:focus-visible) {
    outline: none !important;
    box-shadow: none !important;
}

.mf-foot :deep(.ant-btn:active) {
    transform: scale(0.97);
}

.mf-foot :deep(.ant-btn-primary) {
    background: var(--c);
    color: #fff;
    border-color: var(--c);
}

.mf-foot :deep(.ant-btn-primary:hover) {
    background: color-mix(in srgb, var(--c) 88%, #000);
    border-color: color-mix(in srgb, var(--c) 88%, #000);
    color: #fff;
}

.mf-foot :deep(.ant-btn-default),
.mf-foot :deep(.ant-btn:not(.ant-btn-primary):not(.ant-btn-dangerous)) {
    background: transparent;
    color: var(--sigam-texto);
    border-color: var(--sigam-borde);
}

.mf-foot :deep(.ant-btn-default:hover),
.mf-foot :deep(.ant-btn:not(.ant-btn-primary):not(.ant-btn-dangerous):hover) {
    background: var(--sigam-navy-050);
    color: var(--sigam-navy);
    border-color: color-mix(in srgb, var(--c) 40%, var(--sigam-borde));
}

.mf-foot :deep(.ant-btn-dangerous) {
    background: transparent;
    color: #dc2626;
    border-color: #fecaca;
}

.mf-foot :deep(.ant-btn-dangerous:hover) {
    background: #fef2f2;
    color: #b91c1c;
}

.mf--oscuro .mf-foot :deep(.ant-btn-default),
.mf--oscuro .mf-foot :deep(.ant-btn:not(.ant-btn-primary):not(.ant-btn-dangerous)) {
    color: #fff;
    border-color: rgba(255, 255, 255, 0.2);
}

.mf--oscuro .mf-foot :deep(.ant-btn-default:hover),
.mf--oscuro .mf-foot :deep(.ant-btn:not(.ant-btn-primary):not(.ant-btn-dangerous):hover) {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.35);
}

/* =========================================================
   Transiciones
   ========================================================= */
.mf-t-enter {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.mf-t-enter-from {
    opacity: 0;
    transform: translate(-50%, -45%) scale(0.96);
}

.mf-t-enter-to {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.mf-t-leave {
    transition: opacity 0.16s ease, transform 0.16s ease;
}

.mf-t-leave-from {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.mf-t-leave-to {
    opacity: 0;
    transform: translate(-50%, -46%) scale(0.97);
}

/* Accesibilidad */
.mf :deep(*) {
    -webkit-tap-highlight-color: transparent;
}

.mf :deep(button) {
    outline: none;
}

.mf :deep(button:focus-visible) {
    outline: 2px solid rgba(255, 255, 255, 0.55);
    outline-offset: 2px;
}
</style>