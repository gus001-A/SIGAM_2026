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
    { key: 'nombre', title: 'Tipo de ubicación', filtro: 'texto', sorter: true, width: 260 },
    { key: 'clave', title: 'Clave', tipo: 'tag', width: 180 },
    { key: 'descripcion', title: 'Descripción', width: 320 },
    { key: 'ubicaciones_count', title: 'Ubicaciones', tipo: 'count', align: 'right', width: 120 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
];
</script>

<template>
    <Head title="Tipos de ubicación" />
    <CatalogoTabla
        :titulo="titulo"
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.tipos_ubicacion"
        singular="tipo de ubicación"
        :columnas="columnas"
        :campos="campos"
    />
</template>
