import '../css/app.css';
import 'ant-design-vue/dist/reset.css';
import './bootstrap';
import './sesion';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import Antd from 'ant-design-vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import iconos from './plugins/iconos';

const appName = import.meta.env.VITE_APP_NAME || 'SIGAM';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Antd)
            .use(iconos)
            .mount(el);
    },
    progress: {
        color: '#1e5eb8',
    },
});
