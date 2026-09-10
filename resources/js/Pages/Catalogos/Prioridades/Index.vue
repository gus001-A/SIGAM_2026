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
    { key: 'nivel', title: 'Nivel', sorter: true, align: 'right', width: 80 },
    { key: 'nombre', title: 'Prioridad', filtro: 'texto', enlace: true, sorter: true, width: 220 },
    { key: 'clave', title: 'Clave', tipo: 'tag', width: 160 },
    { key: 'minutos_respuesta', title: 'Respuesta (min)', sorter: true, align: 'right', width: 150 },
    { key: 'color', title: 'Color', tipo: 'color', width: 140 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true, ancho: 16 },
    { name: 'nivel', label: 'Nivel', tipo: 'number', required: true, min: 1, max: 255, ancho: 8 },
    { name: 'minutos_respuesta', label: 'Tiempo objetivo de respuesta (minutos)', tipo: 'number', min: 0 },
    { name: 'color', label: 'Color', tipo: 'color', ancho: 12 },
];
</script>

<template>
    <Head title="Prioridades" />
    <CatalogoTabla
        :titulo="titulo"
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.prioridades"
        singular="prioridad"
        :columnas="columnas"
        :campos="campos"
    />
</template>
