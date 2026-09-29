import {
    BellOutlined,
    CalendarOutlined,
    CheckCircleOutlined,
    CheckSquareOutlined,
    ClockCircleOutlined,
    CloseCircleOutlined,
    EditOutlined,
    FileAddOutlined,
    SwapOutlined,
    ToolOutlined,
    UserAddOutlined,
} from '@ant-design/icons-vue';

/**
 * Ícono, etiqueta y color por tipo de notificación. Alcance deliberadamente
 * acotado a lo que es relevante para el rol de quien la recibe: lo que le fue
 * asignado a la persona (tarea, orden o plan), o —para supervisores y
 * administradores— las solicitudes nuevas que deben revisar.
 */
export const META_NOTIFICACION = {
    // Tareas
    tarea_asignada: { label: 'Tarea asignada', icon: UserAddOutlined, color: '#16806c' },
    tarea_modificada: { label: 'Tarea modificada', icon: EditOutlined, color: '#0d84c9' },
    tarea_proxima: { label: 'Tarea próxima a vencer', icon: ClockCircleOutlined, color: '#e08a1e' },
    tarea_vencida: { label: 'Tarea vencida', icon: CheckSquareOutlined, color: '#d64545' },
    // Órdenes de mantenimiento
    mantenimiento_asignado: { label: 'Orden asignada', icon: UserAddOutlined, color: '#16806c' },
    mantenimiento_modificado: { label: 'Orden modificada', icon: EditOutlined, color: '#0d84c9' },
    mantenimiento_reprogramado: { label: 'Orden reprogramada', icon: SwapOutlined, color: '#e08a1e' },
    mantenimiento_proximo: { label: 'Preventivo próximo', icon: CalendarOutlined, color: '#0d84c9' },
    mantenimiento_vencido: { label: 'Preventivo vencido', icon: ClockCircleOutlined, color: '#d64545' },
    // Planes de mantenimiento
    plan_asignado: { label: 'Plan asignado', icon: UserAddOutlined, color: '#173a5f' },
    plan_modificado: { label: 'Plan modificado', icon: EditOutlined, color: '#0d84c9' },
    // Solicitudes
    solicitud_creada: { label: 'Solicitud nueva', icon: FileAddOutlined, color: '#6b4bc9' },
    solicitud_autorizada: { label: 'Solicitud autorizada', icon: CheckCircleOutlined, color: '#1f9e86' },
    solicitud_rechazada: { label: 'Solicitud rechazada', icon: CloseCircleOutlined, color: '#d64545' },
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
