<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeftOutlined, PrinterOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    equipo: { type: Object, required: true },
    url: { type: String, required: true },
    svg: { type: String, required: true },
});

const imprimir = () => window.print();
</script>

<template>
    <Head :title="`QR ${equipo.codigo_activo}`" />

    <AppLayout titulo="Código QR del activo">
        <template #acciones>
            <a-button type="text" @click="router.visit(route('equipos.show', equipo.id))">
                <template #icon><ArrowLeftOutlined /></template>
                Volver al equipo
            </a-button>
            <a-button type="primary" @click="imprimir">
                <template #icon><PrinterOutlined /></template>
                Imprimir
            </a-button>
        </template>

        <a-row justify="center">
            <a-col :xs="24" :sm="16" :md="10" :lg="7">
                <a-card class="etiqueta text-center">
                    <div class="text-lg font-bold">SIGAM</div>
                    <div class="text-xs opacity-60 mb-4">Identificación de activo</div>

                    <div class="qr mx-auto mb-4" v-html="svg" />

                    <div class="text-lg font-bold">{{ equipo.codigo_activo }}</div>
                    <div class="text-sm opacity-60 mb-2">{{ equipo.descripcion }}</div>
                    <div class="text-xs opacity-40 break-all">{{ url }}</div>
                </a-card>

                <p class="text-xs opacity-60 text-center mt-3">
                    Al escanear, un usuario autenticado accede directamente al expediente del equipo.
                </p>
            </a-col>
        </a-row>
    </AppLayout>
</template>

<style scoped>
.qr :deep(svg) {
    width: 220px;
    height: 220px;
}
@media print {
    :deep(.app-sider),
    :deep(.app-header),
    :deep(.app-page-head) {
        display: none !important;
    }
    :deep(.app-content) {
        padding: 0 !important;
    }
    .etiqueta {
        box-shadow: none !important;
        border: 1px solid #000;
    }
}
</style>
