<script setup>
import { computed, h, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import {
    AppstoreOutlined,
    BarChartOutlined,
    CalendarOutlined,
    CarOutlined,
    DashboardOutlined,
    DownOutlined,
    EnvironmentOutlined,
    FileSearchOutlined,
    FileTextOutlined,
    FormOutlined,
    LogoutOutlined,
    MenuOutlined,
    SafetyCertificateOutlined,
    SettingOutlined,
    ShopOutlined,
    TeamOutlined,
    ToolOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import { antdLocale, antTheme } from '@/theme';
import { usePermisos } from '@/composables/usePermisos';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import PanelNotificaciones from '@/Components/PanelNotificaciones.vue';

const props = defineProps({
    titulo: { type: String, default: '' },
    descripcion: { type: String, default: '' },
});

const page = usePage();
const { puede, usuario } = usePermisos();

// --- Menú de navegación (barra superior) --------------------------
const grupos = [
    {
        clave: 'g-panel',
        label: 'Panel',
        icono: DashboardOutlined,
        items: [{ label: 'Panel', ruta: 'dashboard', patron: 'dashboard', permiso: 'dashboard.ver', icon: DashboardOutlined }],
        directo: true,
    },
    {
        clave: 'g-inventario',
        label: 'Inventario',
        icono: AppstoreOutlined,
        items: [
            { label: 'Equipos', ruta: 'equipos.index', patron: 'equipos.*', permiso: 'equipos.ver', icon: ToolOutlined },
            { label: 'Sucursales', ruta: 'sucursales.index', patron: 'sucursales.*', permiso: 'sucursales.ver', icon: ShopOutlined },
            { label: 'Ubicaciones', ruta: 'ubicaciones.index', patron: 'ubicaciones.*', permiso: 'ubicaciones.ver', icon: EnvironmentOutlined },
            { label: 'Proveedores', ruta: 'proveedores.index', patron: 'proveedores.*', permiso: 'proveedores.ver', icon: CarOutlined },
        ],
    },
    {
        clave: 'g-mantenimiento',
        label: 'Mantenimiento',
        icono: ToolOutlined,
        items: [
            { label: 'Solicitudes', ruta: 'solicitudes.index', patron: 'solicitudes.*', permiso: 'solicitudes.ver', icon: FormOutlined },
            { label: 'Órdenes', ruta: 'mantenimientos.index', patron: 'mantenimientos.*', permiso: 'mantenimientos.ver', icon: ToolOutlined },
            { label: 'Planes preventivos', ruta: 'planes.index', patron: 'planes.*', permiso: 'mantenimientos.ver', icon: CalendarOutlined },
            { label: 'Calendario', ruta: 'calendario.index', patron: 'calendario.*', permiso: 'mantenimientos.ver', icon: CalendarOutlined },
        ],
    },
    {
        clave: 'g-normas',
        label: 'Normas y formatos',
        icono: SafetyCertificateOutlined,
        items: [
            { label: 'Normas', ruta: 'normas.index', patron: 'normas.*', permiso: 'normas.ver', icon: FileTextOutlined },
            { label: 'Formatos', ruta: 'formatos.index', patron: 'formatos.*', permiso: 'formatos.ver', icon: FormOutlined },
        ],
    },
    {
        clave: 'g-reportes',
        label: 'Reportes',
        icono: BarChartOutlined,
        items: [{ label: 'Reportes', ruta: 'reportes.index', patron: 'reportes.*', permiso: 'reportes.ver', icon: BarChartOutlined }],
        directo: true,
    },
    {
        clave: 'g-admin',
        label: 'Administración',
        icono: SettingOutlined,
        items: [
            { label: 'Catálogos', ruta: 'catalogos.marcas.index', patron: 'catalogos.*', permiso: 'catalogos.ver', icon: AppstoreOutlined },
            { label: 'Usuarios', ruta: 'usuarios.index', patron: 'usuarios.*', permiso: 'usuarios.ver', icon: TeamOutlined },
            { label: 'Bitácora', ruta: 'auditoria.index', patron: 'auditoria.*', permiso: 'auditoria.ver', icon: FileSearchOutlined },
        ],
    },
];

const menuItems = computed(() =>
    grupos
        .map((g) => {
            const visibles = g.items.filter((i) => puede(i.permiso));
            if (visibles.length === 0) return null;

            if (g.directo) {
                return { key: visibles[0].ruta, label: visibles[0].label, icon: () => h(g.icono) };
            }
            return {
                key: g.clave,
                label: g.label,
                icon: () => h(g.icono),
                children: visibles.map((i) => ({ key: i.ruta, icon: () => h(i.icon), label: i.label })),
            };
        })
        .filter(Boolean),
);

const grupoActivo = computed(() =>
    grupos.find((g) =>
        g.items.some((i) => {
            try {
                return route().current(i.patron);
            } catch (e) {
                return false;
            }
        }),
    ),
);

const selectedKeys = computed(() => {
    const g = grupoActivo.value;
    if (!g) return [];
    if (g.directo) return [g.items[0].ruta];
    const hijo = g.items.find((i) => {
        try {
            return route().current(i.patron);
        } catch (e) {
            return false;
        }
    });
    return hijo ? [g.clave, hijo.ruta] : [g.clave];
});

const onMenu = ({ key }) => {
    if (key.startsWith('g-')) return;
    router.visit(route(key));
};

// --- Menú móvil (panel deslizante con hamburguesa) -----------------
const menuMovilAbierto = ref(false);
const onMenuMovil = (info) => {
    menuMovilAbierto.value = false;
    onMenu(info);
};

// --- Encabezado ------------------------------------------------
const tituloPagina = computed(() => props.titulo || page.props.titulo || 'SIGAM');
const iniciales = computed(() => {
    const n = usuario.value?.nombre ?? usuario.value?.name ?? '?';
    return n.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase();
});

const noLeidas = ref(0);
const notis = ref([]);
const cargarNoLeidas = async () => {
    try {
        const { data } = await window.axios.get(route('notificaciones.no_leidas'));
        noLeidas.value = data.total ?? 0;
        notis.value = data.items ?? [];
    } catch (e) {
        /* silencioso */
    }
};
onMounted(() => {
    cargarNoLeidas();
    router.on('navigate', cargarNoLeidas);
});

const confirmar = ref(null);
const cerrarSesion = async () => {
    const ok = await confirmar.value.abrir({
        titulo: 'Cerrar sesión',
        mensaje: '¿Seguro que quieres salir de SIGAM? Tendrás que iniciar sesión de nuevo para continuar.',
        confirmar: 'Cerrar sesión',
        peligro: true,
    });
    if (ok) router.post(route('logout'));
};
const onUserMenu = ({ key }) => {
    if (key === 'perfil') router.visit(route('profile.edit'));
    if (key === 'auditoria') router.visit(route('auditoria.index'));
    if (key === 'salir') cerrarSesion();
};

const menuUsuario = computed(() => [
    { key: 'info', label: usuario.value?.email, disabled: true },
    { type: 'divider' },
    { key: 'perfil', icon: () => h(UserOutlined), label: 'Mi perfil' },
    ...(puede('auditoria.ver')
        ? [{ key: 'auditoria', icon: () => h(FileSearchOutlined), label: 'Bitácora de auditoría' }]
        : []),
    { type: 'divider' },
    { key: 'salir', icon: () => h(LogoutOutlined), label: 'Cerrar sesión', danger: true },
]);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.exito) message.success(flash.exito);
        else if (flash?.error) message.error(flash.error);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <Head :title="titulo" />

    <a-config-provider :theme="antTheme" :locale="antdLocale">
        <a-app>
            <a-layout class="app-shell">
                <a-layout-header class="app-nav">
                    <div class="app-nav__izq">
                        <a-button
                            type="text"
                            class="app-nav__burger"
                            aria-label="Abrir menú"
                            @click="menuMovilAbierto = true"
                        >
                            <template #icon><MenuOutlined /></template>
                        </a-button>
                        <div class="app-nav__brand" @click="router.visit(route('dashboard'))">
                            <img src="/images/logo-sigam.png" alt="SIGAM" class="app-nav__logo" />
                        </div>
                    </div>

                    <a-menu
                        mode="horizontal"
                        theme="light"
                        :items="menuItems"
                        :selected-keys="selectedKeys"
                        class="app-nav__menu"
                        @click="onMenu"
                    />

                    <div class="app-nav__acciones">
                        <PanelNotificaciones :items="notis" :total="noLeidas" @recargar="cargarNoLeidas" />

                        <a-dropdown placement="bottomRight">
                            <a class="app-user" @click.prevent>
                                <a-avatar :size="30" class="app-user__avatar">{{ iniciales }}</a-avatar>
                                <span class="app-user__nombre">{{ usuario?.nombre ?? usuario?.name }}</span>
                                <DownOutlined class="app-user__caret" />
                            </a>
                            <template #overlay>
                                <a-menu class="app-user__menu" :items="menuUsuario" @click="onUserMenu" />
                            </template>
                        </a-dropdown>
                    </div>
                </a-layout-header>

                <a-layout-content class="app-content">
                    <div v-if="titulo || $slots.acciones" class="app-page-head">
                        <div class="app-page-head__main">
                            <h1 class="app-page-title">{{ tituloPagina }}</h1>
                            <p v-if="descripcion" class="app-page-desc">{{ descripcion }}</p>
                            <slot name="subtitulo" />
                        </div>
                        <div class="app-page-actions">
                            <slot name="acciones" />
                        </div>
                    </div>

                    <div class="app-content__body">
                        <slot />
                    </div>
                </a-layout-content>
            </a-layout>

            <ConfirmarDialog ref="confirmar" />

            <a-drawer
                v-model:open="menuMovilAbierto"
                placement="left"
                :width="280"
                :closable="false"
                root-class-name="app-nav-drawer"
            >
                <template #title>
                    <img src="/images/logo-sigam.png" alt="SIGAM" class="app-nav-drawer__logo" />
                </template>
                <a-menu
                    mode="inline"
                    :items="menuItems"
                    :selected-keys="selectedKeys"
                    :default-open-keys="grupoActivo ? [grupoActivo.clave] : []"
                    class="app-nav-drawer__menu"
                    @click="onMenuMovil"
                />
            </a-drawer>
        </a-app>
    </a-config-provider>
</template>

<style scoped>
.app-shell {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    overflow: hidden;
}

/* ---------- Barra de navegación (blanca, acentos del sistema) ---------- */
.app-nav {
    flex: none;
    background: #ffffff;
    border-bottom: 1px solid var(--sigam-borde);
    box-shadow: 0 2px 14px -8px rgba(17, 34, 51, 0.18);
    height: 64px;
    line-height: 64px;
    padding: 0 18px;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 10px;
    z-index: 20;
    position: relative;
}
.app-nav::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    height: 3px;
    background: linear-gradient(90deg, var(--sigam-navy), var(--sigam-teal), var(--sigam-navy));
    background-size: 220% 100%;
    animation: sigam-slide 7s linear infinite;
    opacity: 0.9;
}
.app-nav__izq {
    justify-self: start;
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 0;
}
.app-nav__burger {
    display: none;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    color: var(--sigam-navy);
}
.app-nav__brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 12px;
    min-width: 0;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.16s ease;
}
.app-nav__brand:hover {
    background: var(--sigam-navy-050);
}
.app-nav__logo {
    height: 30px;
    width: auto;
    display: block;
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.app-nav__brand:hover .app-nav__logo {
    transform: scale(1.05);
}
.app-nav__menu {
    justify-self: center;
    border-bottom: none;
    background: transparent;
    line-height: 64px;
    min-width: 0;
}
.app-nav__acciones {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 6px;
}
.app-user {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 4px 12px 4px 4px;
    border-radius: 999px;
    color: var(--sigam-texto);
    line-height: normal;
    border: 1px solid transparent;
    transition: background 0.15s, border-color 0.15s;
}
.app-user:hover {
    background: var(--sigam-navy-050);
    border-color: var(--sigam-navy-100);
}
.app-user__avatar {
    background: var(--sigam-grad) !important;
    color: #fff !important;
    font-weight: 800;
}
.app-user__nombre {
    font-size: 13px;
    font-weight: 600;
    color: var(--sigam-texto);
}
.app-user__caret {
    font-size: 10px;
    color: #94a3b8;
}

/* Título de página grande + descripción */
.app-page-desc {
    margin: 4px 0 0 14px;
    font-size: 13px;
    color: var(--sigam-tenue);
    max-width: 70ch;
    line-height: 1.5;
}

/* ---------- Contenido ---------- */
.app-content {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    padding: 20px 26px 0;
    max-width: 1760px;
    width: 100%;
    margin: 0 auto;
}
.app-page-head {
    flex: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 16px;
    margin-bottom: 16px;
}
.app-page-head__main {
    min-width: 0;
}
.app-page-title {
    font-size: 25px;
    font-weight: 800;
    margin: 0;
    color: #0f172a;
    letter-spacing: -0.015em;
    line-height: 1.15;
    position: relative;
    padding-left: 14px;
}
.app-page-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 4px;
    bottom: 4px;
    width: 5px;
    border-radius: 3px;
    background: var(--sigam-grad);
}
.app-page-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

/* Zona desplazable: el scroll vive aquí (o dentro de la tabla), no en la página */
.app-content__body {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overflow-x: hidden;
    padding-bottom: 22px;
    scrollbar-width: thin;
}
.app-content__body::-webkit-scrollbar {
    width: 10px;
}
.app-content__body::-webkit-scrollbar-thumb {
    background: #d5dce5;
    border: 3px solid transparent;
    border-radius: 999px;
    background-clip: content-box;
}

/* Por debajo de este ancho el menú horizontal ya no cabe (se traslapa con
   el logo y el usuario) — se oculta y se usa la hamburguesa + panel. */
@media (max-width: 1180px) {
    .app-nav {
        grid-template-columns: auto 1fr auto;
    }
    .app-nav__menu.ant-menu-horizontal {
        display: none !important;
    }
    .app-nav__burger {
        display: inline-flex !important;
    }
}

@media (max-width: 720px) {
    .app-user__nombre {
        display: none;
    }
    .app-nav {
        padding: 0 10px;
        gap: 6px;
    }
    .app-nav__brand {
        padding: 6px 8px;
    }
    .app-content {
        padding: 14px 12px 0;
    }
}

@media (max-width: 420px) {
    .app-nav__logo {
        height: 24px;
    }
}
</style>

<style>
/* ---------- Menú superior: pastillas sobre barra blanca ---------- */
.app-nav__menu.ant-menu-horizontal {
    border-bottom: none;
    display: flex;
    justify-content: center;
    background: transparent;
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-item,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu {
    position: relative;
    top: 0;
    margin: 0 3px;
    padding: 0 14px;
    height: 40px;
    line-height: 40px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    color: #4b5b6d;
    transition: background 0.16s ease, color 0.16s ease, transform 0.14s ease,
        box-shadow 0.16s ease;
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-item .anticon,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu .anticon {
    font-size: 15px;
    color: #8a97a6;
    transition: color 0.16s ease;
    margin-inline-end: 7px;
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-item::after,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu::after {
    display: none !important;
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-item:hover,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu:hover,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu-open {
    color: var(--sigam-navy);
    background: var(--sigam-navy-050);
    transform: translateY(-1px);
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-item:hover .anticon,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu:hover .anticon {
    color: var(--sigam-teal);
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-item-selected,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu-selected,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu-selected > .ant-menu-submenu-title {
    color: #fff !important;
    background: var(--sigam-grad) !important;
    background-size: 180% 180% !important;
    font-weight: 700;
    box-shadow: 0 9px 20px -9px rgba(23, 58, 95, 0.55);
    animation: sigam-grad-shift 6s ease infinite;
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-item-selected .ant-menu-title-content,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu-selected .ant-menu-title-content {
    color: #fff !important;
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-item-selected .anticon,
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu-selected .anticon {
    color: rgba(255, 255, 255, 0.92) !important;
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu-selected > .ant-menu-submenu-title:hover {
    color: #fff !important;
}
.app-nav__menu.ant-menu-horizontal > .ant-menu-submenu .ant-menu-submenu-arrow {
    display: none;
}

/* Submenú desplegable (se mantiene claro aunque la barra sea oscura) */
.ant-menu-submenu-popup .ant-menu,
.ant-menu-submenu-popup.ant-menu-dark .ant-menu,
.ant-menu-submenu-popup .ant-menu-sub {
    border-radius: 12px;
    padding: 6px;
    background: #fff !important;
    box-shadow: var(--sigam-sombra-lg);
}
.ant-menu-submenu-popup .ant-menu-item {
    font-size: 13.5px;
    font-weight: 500;
    border-radius: 8px;
    margin-inline: 0;
    color: var(--sigam-texto) !important;
}
.ant-menu-submenu-popup .ant-menu-item .anticon {
    color: var(--sigam-teal) !important;
}
.ant-menu-submenu-popup .ant-menu-item:hover {
    background: var(--sigam-navy-050) !important;
    color: var(--sigam-navy) !important;
}
.ant-menu-submenu-popup .ant-menu-item-selected {
    color: var(--sigam-navy) !important;
    background: var(--sigam-navy-100) !important;
    font-weight: 600;
}

/* Botón campana */
.app-nav__btn.ant-btn {
    width: 37px;
    height: 37px;
    color: #4b5b6d;
    border: 1px solid transparent;
    background: transparent;
    transition: background 0.14s, color 0.14s, border-color 0.14s;
}
.app-nav__btn.ant-btn:hover {
    background: var(--sigam-navy-050);
    border-color: var(--sigam-navy-100);
    color: var(--sigam-navy);
}
.app-nav__btn--activo {
    color: var(--sigam-teal);
}
.app-nav__btn--activo .anticon {
    transform-origin: 50% 12%;
    animation: sigam-ring 2.6s ease-in-out 1s infinite;
}

/* Menú del usuario */
.app-user__menu.ant-dropdown-menu {
    min-width: 230px;
    padding: 6px;
    border-radius: 13px;
    box-shadow: var(--sigam-sombra-lg);
}
.app-user__menu .ant-dropdown-menu-item {
    border-radius: 9px;
    padding: 8px 11px;
}
.app-user__menu .ant-dropdown-menu-item .anticon {
    color: var(--sigam-teal);
}

/* ---------- Panel deslizante del menú (móvil / tablet) ---------- */
.app-nav-drawer .ant-drawer-header {
    padding: 14px 16px;
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.app-nav-drawer .ant-drawer-body {
    padding: 10px;
}
.app-nav-drawer__logo {
    height: 26px;
    width: auto;
    display: block;
}
.app-nav-drawer__menu.ant-menu {
    border-inline-end: none !important;
}
.app-nav-drawer__menu.ant-menu-inline .ant-menu-item,
.app-nav-drawer__menu.ant-menu-inline .ant-menu-submenu-title {
    border-radius: 10px;
    margin-block: 3px;
    font-weight: 600;
    color: #4b5b6d;
}
.app-nav-drawer__menu .ant-menu-item .anticon,
.app-nav-drawer__menu .ant-menu-submenu-title .anticon {
    color: #8a97a6;
}
.app-nav-drawer__menu .ant-menu-item-selected {
    background: var(--sigam-grad) !important;
    color: #fff !important;
    font-weight: 700;
}
.app-nav-drawer__menu .ant-menu-item-selected .anticon,
.app-nav-drawer__menu .ant-menu-item-selected .ant-menu-title-content {
    color: #fff !important;
}
.app-nav-drawer__menu .ant-menu-submenu-open > .ant-menu-submenu-title {
    color: var(--sigam-navy);
    background: var(--sigam-navy-050);
}
.app-nav-drawer__menu .ant-menu-submenu-open > .ant-menu-submenu-title .anticon {
    color: var(--sigam-teal);
}
</style>
