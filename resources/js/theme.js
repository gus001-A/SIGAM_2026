import { theme } from 'ant-design-vue';
import esES from 'ant-design-vue/es/locale/es_ES';

/**
 * Paleta de marca SIGAM (tomada del logotipo):
 *  - Azul marino  #173a5f  (primario)
 *  - Verde azulado #1f9e86  (acento)
 * Sistema claro, fondo blanco (entorno hospitalario).
 */
export const SIGAM_NAVY = '#173a5f';
export const SIGAM_TEAL = '#1f9e86';

export const antTheme = {
    cssVar: true,
    algorithm: theme.defaultAlgorithm,
    token: {
        colorPrimary: SIGAM_NAVY,
        colorInfo: SIGAM_NAVY,
        colorLink: SIGAM_NAVY,
        colorSuccess: '#1f9e86',
        colorWarning: '#e08a1e',
        colorError: '#d64545',
        colorTextBase: '#1c2b3a',
        colorBgLayout: '#ffffff',
        colorBorder: '#dbe2ea',
        colorBorderSecondary: '#e8edf2',
        borderRadius: 10,
        controlHeight: 36,
        fontFamily:
            "'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
        fontSize: 14,
    },
    components: {
        Menu: {
            itemBorderRadius: 9,
            itemHoverColor: SIGAM_NAVY,
            itemSelectedColor: SIGAM_NAVY,
            itemSelectedBg: '#e4edf6',
        },
        Table: {
            headerBg: '#f4f7fb',
            headerColor: '#42566b',
            rowHoverBg: '#eff5fb',
            borderColor: '#e8edf3',
        },
        Tabs: { inkBarColor: SIGAM_TEAL, itemSelectedColor: SIGAM_NAVY },
        Button: { primaryShadow: '0 8px 18px -8px rgba(23, 58, 95, 0.5)' },
        Segmented: { itemSelectedBg: '#ffffff', trackBg: '#eef2f7' },
    },
};

export const antdLocale = esES;
