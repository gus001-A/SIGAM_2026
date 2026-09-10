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
    { key: 'codigo', title: 'Código', filtro: 'texto', sorter: true, width: 150 },
    { key: 'nombre', title: 'Material / refacción', filtro: 'texto', enlace: true, sorter: true, width: 300 },
    { key: 'unidad', title: 'Unidad', width: 120 },
    { key: 'costo_referencia', title: 'Costo referencia', tipo: 'money', align: 'right', width: 160 },
];

const campos = [
    { name: 'codigo', label: 'Código', tipo: 'text', ancho: 10 },
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true, ancho: 14 },
    { name: 'unidad', label: 'Unidad de medida', tipo: 'text', required: true, ayuda: 'p. ej. pieza, litro, metro', ancho: 12 },
    { name: 'costo_referencia', label: 'Costo de referencia (MXN)', tipo: 'number', min: 0, ancho: 12 },
];
</script>

<template>
    <Head title="Materiales y refacciones" />
    <CatalogoTabla
        :titulo="titulo"
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.materiales"
        singular="material"
        :columnas="columnas"
        :campos="campos"
    />
</template>
