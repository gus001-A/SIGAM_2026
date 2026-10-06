<script setup>
import { Head } from '@inertiajs/vue3';
import CatalogoTabla from '@/Components/CatalogoTabla.vue';

defineProps({
    kpis: Object,
    titulo: String,
    registros: Object,
    filtros: Object,
    orden: Object,
});

const columnas = [
    { key: 'nombre', title: 'Proyecto', filtro: 'texto', sorter: true, width: 240 },
    { key: 'descripcion', title: 'Descripción', filtro: 'texto', width: 320 },
    { key: 'tareas_count', title: 'Tareas', tipo: 'count', align: 'right', width: 120 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true, ayuda: 'p. ej. Remodelación de urgencias 2026' },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
];
</script>

<template>
    <Head title="Proyectos" />
    <CatalogoTabla
        :titulo="titulo"
        descripcion="Proyectos a los que se les puede asignar una tarea de seguimiento."
        :registros="registros"
        :kpis="kpis"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.proyectos"
        singular="proyecto"
        :columnas="columnas"
        :campos="campos"
    />
</template>
