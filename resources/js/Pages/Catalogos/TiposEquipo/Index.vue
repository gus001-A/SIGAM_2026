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
    { key: 'nombre', title: 'Tipo de equipo', filtro: 'texto', sorter: true, width: 260 },
    { key: 'clave', title: 'Clave', tipo: 'tag', width: 180 },
    { key: 'descripcion', title: 'Descripción', width: 300 },
    { key: 'equipos_count', title: 'Equipos', tipo: 'count', align: 'right', width: 110 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
];
</script>

<template>
    <Head title="Tipos de equipo" />
    <CatalogoTabla
        :titulo="titulo"
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.tipos_equipo"
        singular="tipo de equipo"
        :columnas="columnas"
        :campos="campos"
    />
</template>
