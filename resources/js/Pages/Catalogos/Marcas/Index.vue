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
    { key: 'nombre', title: 'Marca', filtro: 'texto', sorter: true, width: 260 },
    { key: 'descripcion', title: 'Descripción', filtro: 'texto', width: 320 },
    { key: 'equipos_count', title: 'Equipos', tipo: 'count', align: 'right', width: 110 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
];
</script>

<template>
    <Head title="Marcas" />
    <CatalogoTabla
        :titulo="titulo"
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.marcas"
        singular="marca"
        :columnas="columnas"
        :campos="campos"
    />
</template>
