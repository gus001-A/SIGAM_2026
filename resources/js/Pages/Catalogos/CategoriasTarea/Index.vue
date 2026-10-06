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
    { key: 'nombre', title: 'Categoría', filtro: 'texto', sorter: true, width: 240 },
    { key: 'descripcion', title: 'Descripción', filtro: 'texto', width: 320 },
    { key: 'tareas_count', title: 'Tareas', tipo: 'count', align: 'right', width: 120 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true, ayuda: 'p. ej. Administrativa, Limpieza, Capacitación' },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
];
</script>

<template>
    <Head title="Categorías de tarea" />
    <CatalogoTabla
        :titulo="titulo"
        descripcion="Categorías para agrupar tareas generales que no pertenecen a un proyecto."
        :registros="registros"
        :kpis="kpis"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.categorias_tarea"
        singular="categoría"
        :columnas="columnas"
        :campos="campos"
    />
</template>
