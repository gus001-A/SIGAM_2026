<script setup>
import { Head } from '@inertiajs/vue3';
import CatalogoTabla from '@/Components/CatalogoTabla.vue';

defineProps({
    titulo: String,
    registros: Object,
    filtros: Object,
    orden: Object,
});

const OPCIONES_CATEGORIA = [
    { label: 'Preventivo', value: 'preventivo' },
    { label: 'Correctivo', value: 'correctivo' },
    { label: 'Urgente', value: 'urgente' },
    { label: 'Inspección', value: 'inspeccion' },
];

const columnas = [
    { key: 'nombre', title: 'Tipo de mantenimiento', filtro: 'texto', sorter: true, width: 240 },
    { key: 'categoria', title: 'Categoría', tipo: 'tag', filtro: 'select', opciones: OPCIONES_CATEGORIA, width: 150 },
    { key: 'descripcion', title: 'Descripción', filtro: 'texto', width: 280 },
    { key: 'planes_count', title: 'Planes', tipo: 'count', align: 'right', width: 100 },
    { key: 'mantenimientos_count', title: 'Órdenes', tipo: 'count', align: 'right', width: 100 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true },
    { name: 'categoria', label: 'Categoría', tipo: 'select', required: true, opciones: OPCIONES_CATEGORIA },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
];
</script>

<template>
    <Head title="Tipos de mantenimiento" />
    <CatalogoTabla
        :titulo="titulo"
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.tipos_mantenimiento"
        singular="tipo de mantenimiento"
        :columnas="columnas"
        :campos="campos"
    />
</template>
