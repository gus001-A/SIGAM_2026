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
    { key: 'nombre', title: 'Tipo de limpieza', filtro: 'texto', sorter: true, width: 200 },
    { key: 'frecuencia', title: 'Frecuencia', filtro: 'texto', width: 220 },
    { key: 'descripcion', title: 'Descripción', filtro: 'texto', width: 280 },
    { key: 'ubicaciones_count', title: 'Ubicaciones', tipo: 'count', align: 'right', width: 120 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true, ayuda: 'p. ej. Rutinaria, Terminal, Exhaustiva' },
    {
        name: 'frecuencia',
        label: 'Frecuencia',
        tipo: 'text',
        ayuda: 'p. ej. Diaria, Después de cada evento, Según tipo de área',
    },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
];
</script>

<template>
    <Head title="Tipos de limpieza" />
    <CatalogoTabla
        :titulo="titulo"
        descripcion="Cuándo se realiza cada tipo de limpieza en las instalaciones."
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.tipos_limpieza"
        singular="tipo de limpieza"
        :columnas="columnas"
        :campos="campos"
    />
</template>
