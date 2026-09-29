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

/* ==========================================================
   Columnas de la tabla
   ========================================================== */
const columnas = [
    {
        key: 'nombre',
        title: 'Estado de equipo',
        filtro: 'texto',
        sorter: true,
        width: 220,
    },
    {
        key: 'es_operativo',
        title: 'Cuenta como operativo',
        tipo: 'bool',
        filtro: 'select',
        opciones: [
            { label: 'Sí', value: 1 },
            { label: 'No', value: 0 },
        ],
        width: 170,
    },
    {
        key: 'equipos_count',
        title: 'Equipos',
        tipo: 'count',
        align: 'right',
        width: 110,
    },
];

/* ==========================================================
   Campos del modal (crear / editar)
   - Se quitó el campo "es_operativo" (era el que se podía
     considerar como "estado"). Si lo necesitas de vuelta,
     descomenta la línea.
   ========================================================== */
const campos = [
    { name: 'nombre', label: 'Nombre', tipo: 'text', required: true },
    { name: 'descripcion', label: 'Descripción', tipo: 'textarea' },
    { name: 'color', label: 'Color', tipo: 'color', ancho: 12 },
    // { name: 'es_operativo', label: '¿El equipo se considera operativo en este estado?', tipo: 'switch', ancho: 12 },
];
</script>

<template>
    <Head title="Estados de equipo" />

    <CatalogoTabla
        :titulo="titulo"
        :registros="registros"
        :kpis="kpis"
        :filtros="filtros"
        :orden="orden"
        ruta-base="catalogos.estados_equipo"
        singular="estado de equipo"
        :columnas="columnas"
        :campos="campos"
    />
</template>