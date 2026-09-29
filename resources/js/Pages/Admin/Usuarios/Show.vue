<script setup>
import { computed, h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ApartmentOutlined,
    CalendarOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    EllipsisOutlined,
    KeyOutlined,
    MailOutlined,
    PhoneOutlined,
    SafetyCertificateOutlined,
    StarOutlined,
    TeamOutlined,
    UndoOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FichaEncabezado from '@/Components/FichaEncabezado.vue';
import ConfirmarDialog from '@/Components/ConfirmarDialog.vue';
import { usePermisos } from '@/composables/usePermisos';
import { colorHexRol, descripcionRol, etiquetaRol } from '@/utils/roles';

const props = defineProps({
    usuario: { type: Object, required: true },
    actividad: { type: Array, default: () => [] },
    sello: { type: Object, default: null },
});

const { puede, usuario: yo } = usePermisos();
const confirmar = ref(null);

const inactivo = computed(() => props.usuario.estado !== 'activo');
const esYo = computed(() => props.usuario.id === yo.value?.id);

const iniciales = computed(() =>
    props.usuario.nombre_completo
        .split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const dato = (v) => v || 'No especificado';
const fechaHora = (v) =>
    v ? new Date(v).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : 'No especificado';

// --- Mini-cards: cuenta -------------------------------------------------
const filasCuenta = computed(() => [
    { icono: MailOutlined, label: 'Correo', valor: props.usuario.email, color: '#0d84c9' },
    { icono: PhoneOutlined, label: 'Teléfono', valor: dato(props.usuario.telefono), color: '#1f9e86' },
    { icono: ClockCircleOutlined, label: 'Último acceso', valor: fechaHora(props.usuario.ultimo_acceso_at), color: '#e08a1e' },
    { icono: CalendarOutlined, label: 'Fecha de alta', valor: fechaHora(props.usuario.created_at), color: '#173a5f' },
]);

const sucursalesCount = computed(() => props.usuario.sucursales?.length ?? 0);
const rolesCount = computed(() => props.usuario.roles?.length ?? 0);
const especialidadesCount = computed(
    () =>
        (props.usuario.especialidades_equipo?.length ?? 0) +
        (props.usuario.especialidades_mantenimiento?.length ?? 0),
);
const actividadCount = computed(() => props.actividad?.length ?? 0);

const modalActividad = ref(false);

const irA = (n, p) => router.visit(route(n, p));

const restablecer = async () => {
    const ok = await confirmar.value.abrir({
        titulo: 'Restablecer contraseña',
        mensaje: `Se enviará un enlace de restablecimiento a ${props.usuario.email}.`,
        confirmar: 'Enviar enlace',
    });
    if (ok) router.post(route('usuarios.restablecer_contrasena', props.usuario.id), {}, { preserveScroll: true });
};

const desactivar = async () => {
    const ok = await confirmar.value.abrir({
        titulo: `Desactivar a ${props.usuario.nombre_completo}`,
        mensaje: 'El usuario no podrá iniciar sesión. Su historial se conserva.',
        confirmar: 'Desactivar',
        peligro: true,
    });
    if (ok) router.delete(route('usuarios.destroy', props.usuario.id));
};

const reactivar = () => router.put(route('usuarios.restore', props.usuario.id));

const menuAcciones = computed(() =>
    esYo.value ? [] : [{ key: 'baja', label: 'Desactivar usuario', danger: true, icon: () => h(DeleteOutlined) }],
);
const onMenuAccion = ({ key }) => {
    if (key === 'baja') desactivar();
};
</script>

<template>

    <Head :title="usuario.nombre_completo" />

    <AppLayout>
        <div class="ficha-compacta">
            <FichaEncabezado :titulo="usuario.nombre_completo" :subtitulo="usuario.email" :iniciales="iniciales"
                :icono="TeamOutlined" volver="usuarios.index" :sello="sello">
                <template #tags>
                    <a-tag :color="inactivo ? 'default' : 'green'" class="estado-tag">
                        <component :is="inactivo ? ClockCircleOutlined : SafetyCertificateOutlined" />
                        {{ inactivo ? 'Inactivo' : 'Activo' }}
                    </a-tag>
                    <a-tag :color="usuario.email_verified_at ? 'green' : 'orange'" class="email-tag">
                        <SafetyCertificateOutlined />
                        {{ usuario.email_verified_at ? 'Correo verificado' : 'Sin verificar' }}
                    </a-tag>
                    <a-tag v-if="esYo" color="blue" class="yo-tag">
                        <UserOutlined /> Tú
                    </a-tag>
                </template>
                <template #acciones>
                    <!-- Botón Reactivar (usuario inactivo) -->
                    <button
                        v-if="inactivo && puede('usuarios.editar')"
                        type="button"
                        class="btn-hero btn-hero--primary"
                        style="--hc: #1f9e86; --hc2: #16806c"
                        @click="reactivar"
                    >
                        <span class="btn-hero__ic">
                            <UndoOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Reactivar</span>
                            <span class="btn-hero__s">Usuario</span>
                        </span>
                    </button>

                    <!-- Botón Editar (usuario activo) -->
                    <button
                        v-if="!inactivo && puede('usuarios.editar')"
                        type="button"
                        class="btn-hero btn-hero--primary"
                        style="--hc: #0d84c9; --hc2: #0a6ba6"
                        @click="irA('usuarios.edit', usuario.id)"
                    >
                        <span class="btn-hero__ic">
                            <EditOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Editar</span>
                            <span class="btn-hero__s">Usuario</span>
                        </span>
                    </button>

                    <!-- Botón Restablecer contraseña -->
                    <button
                        v-if="!inactivo && puede('usuarios.editar')"
                        type="button"
                        class="btn-hero btn-hero--default"
                        style="--hc: #e08a1e; --hc2: #a86717"
                        @click="restablecer"
                    >
                        <span class="btn-hero__ic">
                            <KeyOutlined />
                        </span>
                        <span class="btn-hero__txt">
                            <span class="btn-hero__l">Restablecer</span>
                            <span class="btn-hero__s">Contraseña</span>
                        </span>
                    </button>

                    <!-- Dropdown de acciones -->
                    <a-dropdown v-if="!inactivo && puede('usuarios.editar') && menuAcciones.length">
                        <button type="button" class="btn-more">
                            <EllipsisOutlined />
                        </button>
                        <template #overlay>
                            <a-menu :items="menuAcciones" @click="onMenuAccion" />
                        </template>
                    </a-dropdown>
                </template>
            </FichaEncabezado>

            <!-- Grid principal -->
            <div class="grid-ficha">
                <!-- COLUMNA IZQUIERDA -->
                <div class="col-izq">
                    <!-- Cuenta -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #0d84c9">
                                <MailOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">Información de la cuenta</div>
                                <div class="card__sub">Datos de contacto y accesos</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="mini-grid mini-grid--2">
                                <div v-for="d in filasCuenta" :key="d.label" class="mini" :style="{ '--c': d.color }">
                                    <span class="mini__ic">
                                        <component :is="d.icono" />
                                    </span>
                                    <span class="mini__t">
                                        <span class="mini__l">{{ d.label }}</span>
                                        <span class="mini__v">{{ d.valor }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sucursales -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #1f9e86">
                                <ApartmentOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Sucursales asignadas
                                    <span v-if="sucursalesCount" class="badge badge--green">{{ sucursalesCount }}</span>
                                </div>
                                <div class="card__sub">Sedes a las que pertenece el usuario</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div v-if="usuario.sucursales?.length" class="mini-grid mini-grid--2">
                                <div v-for="s in usuario.sucursales" :key="s.id" class="mini" style="--c: #1f9e86">
                                    <span class="mini__ic">
                                        <ApartmentOutlined />
                                    </span>
                                    <span class="mini__t">
                                        <span class="mini__l">Sucursal</span>
                                        <span class="mini__v">{{ s.nombre }}</span>
                                    </span>
                                </div>
                            </div>
                            <div v-else class="vacio-box">
                                <ApartmentOutlined />
                                <span>Sin sucursales asignadas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Roles -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #6b4bc9">
                                <SafetyCertificateOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Roles asignados
                                    <span v-if="rolesCount" class="badge badge--purple">{{ rolesCount }}</span>
                                </div>
                                <div class="card__sub">Permisos y responsabilidades</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div v-if="usuario.roles.length" class="roles-usuario">
                                <div v-for="r in usuario.roles" :key="r" class="roles-usuario__card"
                                    :style="{ '--rol-color': colorHexRol(r) }">
                                    <span class="roles-usuario__titulo">{{ etiquetaRol(r) }}</span>
                                    <span class="roles-usuario__desc">{{ descripcionRol(r) }}</span>
                                </div>
                            </div>
                            <div v-else class="vacio-box">
                                <SafetyCertificateOutlined />
                                <span>Sin roles asignados</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="col-der">
                    <!-- Especialidades (solo técnico) -->
                    <div v-if="usuario.roles.includes('tecnico')" class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #e08a1e">
                                <StarOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Especialidades
                                    <span v-if="especialidadesCount" class="badge badge--orange">{{ especialidadesCount
                                        }}</span>
                                </div>
                                <div class="card__sub">Se usa para sugerirlo al delegar órdenes</div>
                            </div>
                        </div>
                        <div class="card__body">
                            <div class="esp-grupo">
                                <div class="esp-grupo__l">
                                    <span class="esp-dot" style="background: #0d84c9"></span>
                                    Tipos de equipo
                                </div>
                                <a-space v-if="usuario.especialidades_equipo?.length" wrap>
                                    <a-tag v-for="e in usuario.especialidades_equipo" :key="e" color="blue">{{ e
                                        }}</a-tag>
                                </a-space>
                                <span v-else class="vacio">Ninguna registrada</span>
                            </div>
                            <div class="esp-grupo">
                                <div class="esp-grupo__l">
                                    <span class="esp-dot" style="background: #6b4bc9"></span>
                                    Tipos de mantenimiento
                                </div>
                                <a-space v-if="usuario.especialidades_mantenimiento?.length" wrap>
                                    <a-tag v-for="e in usuario.especialidades_mantenimiento" :key="e" color="purple">{{
                                        e }}</a-tag>
                                </a-space>
                                <span v-else class="vacio">Ninguna registrada</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad reciente (últimos 5) -->
                    <div class="card">
                        <div class="card__head">
                            <div class="card__ico" style="--c: #173a5f">
                                <ClockCircleOutlined />
                            </div>
                            <div class="card__meta">
                                <div class="card__titulo">
                                    Actividad reciente
                                    <span v-if="actividadCount" class="badge badge--navy">{{ actividadCount }}</span>
                                </div>
                                <div class="card__sub">Últimos movimientos registrados</div>
                            </div>
                            <a-button v-if="actividad.length > 5" class="card__extra" size="small" type="text"
                                @click="modalActividad = true">
                                Ver todo
                            </a-button>
                        </div>
                        <div class="card__body">
                            <div v-if="actividad.length" class="actividad">
                                <div v-for="a in actividad.slice(0, 5)" :key="a.id" class="actividad__it">
                                    <span class="actividad__dot"></span>
                                    <div class="actividad__txt">
                                        <div class="actividad__accion">
                                            <strong>{{ a.accion }}</strong>
                                            <span class="actividad__modulo">{{ a.modulo }}</span>
                                        </div>
                                        <div class="actividad__fecha">{{ fechaHora(a.created_at) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="vacio-box">
                                <ClockCircleOutlined />
                                <span>Sin actividad registrada</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Actividad completa -->
        <a-modal v-model:open="modalActividad" :title="`Actividad reciente (${actividadCount})`" :footer="null"
            :width="640">
            <a-timeline v-if="actividad.length" class="mt-2">
                <a-timeline-item v-for="a in actividad" :key="a.id" color="#1f9e86">
                    <div class="act-modal">
                        <div>
                            <strong>{{ a.accion }}</strong>
                            <span class="actividad__modulo"> · {{ a.modulo }}</span>
                        </div>
                        <div class="actividad__fecha">{{ fechaHora(a.created_at) }}</div>
                    </div>
                </a-timeline-item>
            </a-timeline>
            <a-empty v-else description="Sin actividad registrada" />
        </a-modal>

        <ConfirmarDialog ref="confirmar" />
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   BOTONES HERO (acciones del header)
   ========================================================== */
.btn-hero {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    height: 42px;
    padding: 0 14px 0 8px;
    border-radius: 11px;
    border: 1px solid transparent;
    font-family: inherit;
    font-weight: 800;
    cursor: pointer;
    overflow: hidden;
    transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease, background 0.16s ease;
    flex-shrink: 0;
}

.btn-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.24) 50%, transparent 100%);
    transform: translateX(-100%) skewX(-20deg);
    transition: transform 0.6s ease;
    pointer-events: none;
}

.btn-hero:hover:not(:disabled)::after {
    transform: translateX(200%) skewX(-20deg);
}

.btn-hero:active:not(:disabled) {
    transform: translateY(0) scale(0.98);
}

.btn-hero:disabled {
    cursor: not-allowed;
    opacity: 0.55;
    filter: grayscale(0.4);
}

.btn-hero__ic {
    width: 28px;
    height: 28px;
    flex: none;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    background: rgba(255, 255, 255, 0.24);
    color: #fff;
    transition: transform 0.2s ease;
    position: relative;
    z-index: 1;
}

.btn-hero:hover:not(:disabled) .btn-hero__ic {
    transform: scale(1.1) rotate(-6deg);
}

.btn-hero__txt {
    display: flex;
    flex-direction: column;
    gap: 1px;
    line-height: 1.1;
    text-align: left;
    color: #fff;
    position: relative;
    z-index: 1;
}

.btn-hero__l {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    white-space: nowrap;
}

.btn-hero__s {
    font-size: 9.5px;
    font-weight: 600;
    opacity: 0.82;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.btn-hero--primary {
    background: linear-gradient(135deg, var(--hc, #1f9e86) 0%, var(--hc2, #16806c) 100%);
    box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--hc, #1f9e86) 65%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--primary:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px color-mix(in srgb, var(--hc, #1f9e86) 75%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-hero--default {
    background: linear-gradient(135deg, var(--hc, #0d84c9) 0%, var(--hc2, #0f6fb0) 100%);
    box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--hc, #0d84c9) 55%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--default:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px color-mix(in srgb, var(--hc, #0d84c9) 70%, transparent), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-hero--danger {
    background: linear-gradient(135deg, #d64545 0%, #b91c1c 100%);
    box-shadow: 0 6px 16px -6px rgba(214, 69, 69, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

.btn-hero--danger:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 8px 20px -6px rgba(214, 69, 69, 0.75), inset 0 1px 0 rgba(255, 255, 255, 0.26);
}

.btn-more {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    border: 1px solid var(--sigam-borde);
    background: #fff;
    color: var(--sigam-tenue);
    cursor: pointer;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.14s ease, color 0.14s ease, border-color 0.14s ease, transform 0.14s ease;
    flex-shrink: 0;
}

.btn-more:hover {
    background: #eef4fb;
    border-color: #cfe4f5;
    color: #0d84c9;
    transform: translateY(-1px);
}

/* ==========================================================
   Layout base
   ========================================================== */
.ficha-compacta {
    display: flex;
    flex-direction: column;
    gap: 13px;
    min-height: 0;
}

/* ---------- Tags del header ---------- */
.estado-tag,
.email-tag,
.yo-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin: 0;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 12px;
    line-height: 20px;
    border: none;
    box-shadow: 0 1px 3px rgba(15, 37, 71, 0.06);
}

.estado-tag .anticon,
.email-tag .anticon,
.yo-tag .anticon {
    font-size: 12px;
}

/* ---------- Grid 2 columnas ---------- */
.grid-ficha {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 14px;
    align-items: start;
}

.col-izq,
.col-der {
    display: flex;
    flex-direction: column;
    gap: 13px;
    min-width: 0;
}

/* ---------- Cards ---------- */
.card {
    background: #fff;
    border: 1px solid var(--sigam-borde-suave);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--sigam-sombra-sm);
    transition: box-shadow 0.18s ease;
}

.card:hover {
    box-shadow: var(--sigam-sombra-md);
}

.card__head {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 70%);
}

.card__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 14%, #fff);
    flex-shrink: 0;
}

.card__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    flex: 1;
}

.card__titulo {
    font-weight: 800;
    font-size: 13.5px;
    color: var(--sigam-navy);
    letter-spacing: -0.1px;
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.card__sub {
    font-size: 11px;
    color: var(--sigam-tenue);
}

.card__extra {
    flex-shrink: 0;
}

.card__body {
    padding: 13px 16px;
}

/* ---------- Badges ---------- */
.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    background: #efe9fb;
    color: #6b4bc9;
    font-size: 10.5px;
    font-weight: 800;
}

.badge--green {
    background: #e4f4ec;
    color: #16806c;
}

.badge--orange {
    background: #fdf3e6;
    color: #a86717;
}

.badge--navy {
    background: var(--sigam-navy-050);
    color: var(--sigam-navy);
}

.badge--purple {
    background: #efe9fb;
    color: #6b4bc9;
}

/* ---------- Mini-cards ---------- */
.mini-grid {
    display: grid;
    gap: 9px;
}

.mini-grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.mini {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--c) 6%, #fff);
    border: 1px solid color-mix(in srgb, var(--c) 18%, transparent);
    transition: transform 0.14s ease, box-shadow 0.14s ease;
}

.mini:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px color-mix(in srgb, var(--c) 20%, transparent);
}

.mini__ic {
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
    background: var(--c);
}

.mini__t {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 0;
}

.mini__l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
    color: var(--sigam-tenue);
    line-height: 1.1;
}

.mini__v {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--sigam-texto);
    word-break: break-word;
    line-height: 1.25;
}

/* ---------- Roles ---------- */
.roles-usuario {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.roles-usuario__card {
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding: 10px 13px;
    border-radius: 11px;
    background: color-mix(in srgb, var(--rol-color) 6%, #fff);
    border: 1px solid color-mix(in srgb, var(--rol-color) 20%, #fff);
    border-left: 4px solid var(--rol-color);
}

.roles-usuario__titulo {
    font-size: 13px;
    font-weight: 800;
    color: var(--rol-color);
    letter-spacing: -0.1px;
}

.roles-usuario__desc {
    font-size: 12px;
    color: var(--sigam-texto);
    line-height: 1.45;
}

/* ---------- Especialidades ---------- */
.esp-grupo {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 14px;
}

.esp-grupo:last-child {
    margin-bottom: 0;
}

.esp-grupo__l {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sigam-tenue);
}

.esp-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

/* ---------- Actividad (card) ---------- */
.actividad {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.actividad__it {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    padding: 9px 0;
    border-bottom: 1px dashed var(--sigam-borde-suave);
}

.actividad__it:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.actividad__it:first-child {
    padding-top: 0;
}

.actividad__dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1f9e86 0%, #16806c 100%);
    box-shadow: 0 0 0 3px rgba(31, 158, 134, 0.15);
    margin-top: 5px;
    flex-shrink: 0;
}

.actividad__txt {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    flex: 1;
}

.actividad__accion {
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
}

.actividad__accion strong {
    font-size: 12.5px;
    color: var(--sigam-navy);
    font-weight: 700;
}

.actividad__modulo {
    font-size: 11px;
    color: var(--sigam-tenue);
    font-weight: 500;
}

.actividad__fecha {
    font-size: 11px;
    color: var(--sigam-tenue);
}

/* ---------- Modal actividad ---------- */
.act-modal {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

/* ---------- Vacio ---------- */
.vacio-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 22px 12px;
    border-radius: 11px;
    border: 1px dashed var(--sigam-borde);
    background: var(--sigam-navy-050);
    color: var(--sigam-tenue);
    font-size: 12px;
}

.vacio-box .anticon {
    font-size: 20px;
    opacity: 0.5;
}

.vacio {
    color: var(--sigam-tenue);
    font-size: 12px;
    font-style: italic;
}

/* ---------- Responsive ---------- */
@media (max-width: 1199px) {
    .grid-ficha {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767px) {
    .btn-hero {
        width: 100%;
        justify-content: flex-start;
    }

    .btn-more {
        width: 100%;
    }
}

@media (max-width: 575px) {
    .mini-grid--2 {
        grid-template-columns: 1fr;
    }
}
</style>