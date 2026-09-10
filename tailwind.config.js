import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    // Se desactiva el "preflight" de Tailwind para que no interfiera con los
    // estilos de Vuetify 3 (bordes de campos, botones, etc.). Las páginas
    // heredadas de Breeze siguen usando las clases utilitarias de Tailwind.
    corePlugins: {
        preflight: false,
    },

    plugins: [forms],
};
