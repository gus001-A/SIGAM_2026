import {
    AlertOutlined,
    BellOutlined,
    CalendarOutlined,
    CheckOutlined,
    ClockCircleOutlined,
    SafetyCertificateOutlined,
    ToolOutlined,
    UserAddOutlined,
} from '@ant-design/icons-vue';

/** Ícono, etiqueta y color por tipo de notificación. */
export const META_NOTIFICACION = {
    mantenimiento_proximo: { label: 'Preventivo próximo', icon: CalendarOutlined, color: '#0d84c9' },
    mantenimiento_vencido: { label: 'Preventivo vencido', icon: ClockCircleOutlined, color: '#d64545' },
    urgencia: { label: 'Urgencia', icon: AlertOutlined, color: '#d64545' },
    asignacion: { label: 'Asignación', icon: UserAddOutlined, color: '#16806c' },
    trabajo_terminado: { label: 'Trabajo terminado', icon: CheckOutlined, color: '#1f9e86' },
    pendiente_supervision: { label: 'Pendiente de supervisión', icon: ToolOutlined, color: '#e08a1e' },
    garantia_por_vencer: { label: 'Garantía por vencer', icon: SafetyCertificateOutlined, color: '#e08a1e' },
};

export const metaNotificacion = (tipo) =>
    META_NOTIFICACION[tipo] ?? { label: tipo, icon: BellOutlined, color: '#64748b' };

export const fechaRelativa = (v) => {
    if (!v) return '';
    const d = new Date(v);
    const min = Math.round((Date.now() - d) / 60000);
    if (min < 1) return 'ahora';
    if (min < 60) return `hace ${min} min`;
    if (min < 1440) return `hace ${Math.round(min / 60)} h`;
    return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' });
};
