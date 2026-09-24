<script setup>
import { Head } from '@inertiajs/vue3';
import CatalogoTabla from '@/Components/CatalogoTabla.vue';

defineProps({
    titulo: String,
    registros: Object,
    filtros: Object,
    orden: Object,
});

const OPCIONES_SI_NO = [{ label: 'Sí', value: 1 }, { label: 'No', value: 0 }];

const columnas = [
    { key: 'orden', title: '#', sorter: true, align: 'right', width: 70 },
    { key: 'nombre', title: 'Estado de la orden', filtro: 'texto', sorter: true, width: 240 },
    { key: 'clave', title: 'Clave', tipo: 'tag', width: 180 },
    { key: 'es_abierto', title: 'Orden abierta', tipo: 'bool', filtro: 'select', opciones: OPCIONES_SI_NO, width: 130 },
    { key: 'es_terminal', title: 'Estado final', tipo: 'bool', filtro: 'select', opciones: OPCIONES_SI_NO, width: 130 },
    { key: 'descripcion', title: 'Descripción', filtro: 'texto', width: 280 },
];

const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true, ancho: 16 },
    { name: 'orden', label: 'Orden', tipo: 'number', required: true, min: 0, ancho: 8 },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
    { name: 'es_abierto', label: 'La orden sigue en curso en este estado', tipo: 'switch', ancho: 12 },
    { name: 'es_terminal', label: 'Es un estado final (cierra la orden)', tipo: 'switch', ancho: 12 },
];
</script>

<template>
    <Head title="Estados de mantenimiento" />
    <CatalogoTabla
        :titulo="titulo"
        :registros="registros"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.estados_mantenimiento"
        singular="estado de mantenimiento"
        :columnas="columnas"
        :campos="campos"
    />
</template>
