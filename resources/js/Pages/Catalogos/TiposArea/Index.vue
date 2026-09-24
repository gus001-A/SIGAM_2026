<script setup>
import { Head } from '@inertiajs/vue3';
import CatalogoTabla from '@/Components/CatalogoTabla.vue';

defineProps({
    titulo: String,
    registros: Object,
    filtros: Object,
    orden: Object,
});

const columnas = [
    { key: 'nombre', title: 'Tipo de área', filtro: 'texto', sorter: true, width: 220 },
    { key: 'dias_limpieza', title: 'Limpieza cada (días)', filtro: 'texto', sorter: true, align: 'right', width: 160 },
    { key: 'descripcion', title: 'Descripción', filtro: 'texto', width: 300 },
    { key: 'ubicaciones_count', title: 'Ubicaciones', tipo: 'count', align: 'right', width: 120 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true, ayuda: 'p. ej. Área crítica, semi-crítica, no crítica' },
    {
        name: 'dias_limpieza',
        label: 'Periodicidad de limpieza (días)',
        tipo: 'number',
        required: true,
        min: 1,
        ayuda: 'Cada cuántos días debe limpiarse esta área.',
    },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
];
</script>

<template>
    <Head title="Tipos de área" />
    <CatalogoTabla
        :titulo="titulo"
        descripcion="Criticidad de las áreas hospitalarias y cada cuántos días deben limpiarse."
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.tipos_area"
        singular="tipo de área"
        :columnas="columnas"
        :campos="campos"
    />
</template>
