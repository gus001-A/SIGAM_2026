<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircleFilled,
    CopyOutlined,
    ExclamationCircleFilled,
    IdcardOutlined,
    LockOutlined,
    ReloadOutlined,
    SafetyCertificateOutlined,
    SaveOutlined,
    StarOutlined,
    TeamOutlined,
    ThunderboltFilled,
    UserOutlined,
} from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useFormularioPestanas } from '@/composables/useFormularioPestanas';
import { colorHexRol, descripcionRol, etiquetaRol } from '@/utils/roles';
import { reglaCorreo, reglaRequerido, reglaTelefono, soloDigitos } from '@/utils/restricciones';

const props = defineProps({
    usuario: { type: Object, default: null },
    catalogos: { type: Object, default: () => ({}) },
});

const editando = computed(() => !!props.usuario);

// Sucursales: normaliza a array de ids.
const sucursalesIniciales = (() => {
    const s = props.usuario?.sucursales;
    if (Array.isArray(s)) return s.map((x) => (typeof x === 'object' ? x.id : x));
    if (props.usuario?.sucursal_id) return [props.usuario.sucursal_id];
    return [];
})();

const form = useForm({
    nombre: props.usuario?.nombre ?? '',
    apellidos: props.usuario?.apellidos ?? '',
    email: props.usuario?.email ?? '',
    telefono: props.usuario?.telefono ?? '',
    sucursales: sucursalesIniciales,
    password: '',
    password_confirmation: '',
    rol: props.usuario?.roles?.[0] ?? null,
    especialidades_equipo: props.usuario?.especialidades_equipo ?? [],
    especialidades_mantenimiento: props.usuario?.especialidades_mantenimiento ?? [],
});

const reglas = reactive({
    nombre: [reglaRequerido('El nombre es obligatorio.')],
    email: [reglaRequerido('El correo es obligatorio.'), reglaCorreo()],
    telefono: [reglaRequerido('El teléfono es obligatorio.'), reglaTelefono(10)],
    password: editando.value ? [] : [reglaRequerido('Define una contraseña.')],
});

const opcionesRoles = computed(() =>
    (props.catalogos.roles ?? []).map((r) => {
        const nombre = r.name ?? r;
        return { valor: nombre, etiqueta: etiquetaRol(nombre), color: colorHexRol(nombre) };
    }),
);
const esTecnico = computed(() => form.rol === 'tecnico');
const opcionesTiposEquipo = computed(() =>
    (props.catalogos.tipos_equipo ?? []).map((t) => ({ label: t.nombre, value: t.id })),
);
const opcionesTiposMantenimiento = computed(() =>
    (props.catalogos.tipos_mantenimiento ?? []).map((t) => ({ label: t.nombre, value: t.id })),
);

// Preselección de sucursales: mapear ids a nombres para mostrar en tags
const sucursalesMap = computed(() => {
    const m = {};
    (props.catalogos.sucursales ?? []).forEach((s) => (m[s.id] = s.nombre));
    return m;
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const TABS = {
    per: {
        titulo: 'Datos personales',
        subtitulo: 'Identificación y contacto',
        color: '#0d84c9',
        campos: ['nombre', 'apellidos', 'email', 'telefono', 'sucursales'],
    },
    clave: {
        titulo: 'Contraseña',
        subtitulo: 'Credencial de acceso',
        color: '#a86717',
        campos: ['password', 'password_confirmation'],
    },
    rol: {
        titulo: 'Rol y acceso',
        subtitulo: 'Permisos y especialidades',
        color: '#6b4bc9',
        campos: ['roles', 'rol', 'especialidades_equipo', 'especialidades_mantenimiento'],
    },
};

const { pestanaActiva, onFinishFailed, onErrorServidor } = useFormularioPestanas(
    Object.fromEntries(Object.entries(TABS).map(([k, t]) => [k, t.campos])),
    'per',
);

const estadoTab = (key) => {
    if (TABS[key].campos.some((c) => form.errors[c])) return 'error';
    if (key === 'per' && form.nombre && form.email) return 'ok';
    if (key === 'rol' && form.rol) return 'ok';
    return null;
};

// --- Generador de contraseña aleatoria --------------------------------
const pwdGenerada = ref(false);

const generarPassword = () => {
    const mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const minus = 'abcdefghijkmnopqrstuvwxyz';
    const nums = '23456789';
    const simb = '!@#$%&*?';
    const todos = mayus + minus + nums + simb;

    const obligatorios = [
        mayus[Math.floor(Math.random() * mayus.length)],
        minus[Math.floor(Math.random() * minus.length)],
        nums[Math.floor(Math.random() * nums.length)],
        simb[Math.floor(Math.random() * simb.length)],
    ];
    const relleno = Array.from({ length: 8 }, () => todos[Math.floor(Math.random() * todos.length)]);
    const mezcla = [...obligatorios, ...relleno].sort(() => Math.random() - 0.5);
    const pwd = mezcla.join('');

    form.password = pwd;
    form.password_confirmation = pwd;
    pwdGenerada.value = true;

    // Ocultar feedback después de 2s
    setTimeout(() => (pwdGenerada.value = false), 2000);
};

// --- Copiar contraseña generada ---------------------------------------
const copiada = ref(false);
const copiarPassword = async () => {
    if (!form.password) return;
    try {
        await navigator.clipboard.writeText(form.password);
        copiada.value = true;
        setTimeout(() => (copiada.value = false), 1800);
    } catch (_) {
        // Fallback silencioso
    }
};

const enviar = () => {
    const opciones = { preserveScroll: true, onError: onErrorServidor };
    form.transform((datos) => ({ ...datos, roles: datos.rol ? [datos.rol] : [] }));
    if (editando.value) form.put(route('usuarios.update', props.usuario.id), opciones);
    else form.post(route('usuarios.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('usuarios.show', props.usuario.id) : route('usuarios.index'));
</script>

<template>
    <Head :title="editando ? 'Editar usuario' : 'Nuevo usuario'" />

    <AppLayout
        :titulo="editando ? 'Editar usuario' : 'Nuevo usuario'"
        descripcion="Datos personales, credenciales de acceso, rol y especialidades del usuario."
    >
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card" :bordered="false">
                <a-tabs v-model:activeKey="pestanaActiva" class="form-tabs">
                    <!-- ============================
                         TAB: Datos personales
                         ============================ -->
                    <a-tab-pane key="per">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.per.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><IdcardOutlined /></span>
                                    <span v-if="estadoTab('per') === 'ok'" class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                    <span v-else-if="estadoTab('per') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.per.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.per.subtitulo }}</span>
                                </span>
                            </span>
                        </template>

                        <div class="sec-head">
                            <div class="sec-head__ico" style="--c: #0d84c9"><IdcardOutlined /></div>
                            <div class="sec-head__meta">
                                <div class="sec-head__t">Identificación y contacto</div>
                                <div class="sec-head__s">Datos básicos del usuario y sucursales a las que pertenece.</div>
                            </div>
                        </div>

                        <a-row :gutter="14">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Nombre(s)" name="nombre" :validate-status="est('nombre')" :help="form.errors.nombre">
                                    <a-input v-model:value="form.nombre" size="large" placeholder="Ej. Juan Carlos">
                                        <template #prefix><UserOutlined class="inp-icon" /></template>
                                    </a-input>
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Apellidos" :validate-status="est('apellidos')" :help="form.errors.apellidos">
                                    <a-input v-model:value="form.apellidos" size="large" placeholder="Ej. Pérez López">
                                        <template #prefix><UserOutlined class="inp-icon" /></template>
                                    </a-input>
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="14">
                                <a-form-item
                                    label="Correo electrónico"
                                    name="email"
                                    extra="Se usa para iniciar sesión y recuperar la contraseña."
                                    :validate-status="est('email')"
                                    :help="form.errors.email"
                                >
                                    <a-input v-model:value="form.email" type="email" size="large" placeholder="usuario@dominio.com" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="10">
                                <a-form-item label="Teléfono" name="telefono" :validate-status="est('telefono')" :help="form.errors.telefono">
                                    <a-input
                                        v-model:value="form.telefono"
                                        :maxlength="10"
                                        inputmode="numeric"
                                        size="large"
                                        placeholder="10 dígitos"
                                        @keypress="soloDigitos"
                                    />
                                </a-form-item>
                            </a-col>
                            <a-col :span="24">
                                <a-form-item
                                    label="Sucursales asignadas"
                                    extra="Puede pertenecer a varias sedes — opcional."
                                    :validate-status="est('sucursales')"
                                    :help="form.errors.sucursales"
                                >
                                    <a-select
                                        v-model:value="form.sucursales"
                                        :options="catalogos.sucursales"
                                        :field-names="{ label: 'nombre', value: 'id' }"
                                        mode="multiple"
                                        allow-clear
                                        size="large"
                                        placeholder="Selecciona una o varias sucursales"
                                        class="select-sucursales"
                                    />
                                </a-form-item>
                            </a-col>
                        </a-row>
                    </a-tab-pane>

                    <!-- ============================
                         TAB: Contraseña
                         ============================ -->
                    <a-tab-pane key="clave">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.clave.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><LockOutlined /></span>
                                    <span v-if="estadoTab('clave') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.clave.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.clave.subtitulo }}</span>
                                </span>
                            </span>
                        </template>

                        <div class="sec-head">
                            <div class="sec-head__ico" style="--c: #a86717"><LockOutlined /></div>
                            <div class="sec-head__meta">
                                <div class="sec-head__t">Credencial de acceso</div>
                                <div class="sec-head__s">Contraseña con la que el usuario inicia sesión en el sistema.</div>
                            </div>
                        </div>

                        <a-alert
                            v-if="editando"
                            type="info"
                            show-icon
                            class="mb-3"
                            message="Déjala en blanco para conservar la contraseña actual."
                        />

                        <a-row :gutter="14">
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Contraseña" name="password" :validate-status="est('password')" :help="form.errors.password">
                                    <a-input-password v-model:value="form.password" autocomplete="new-password" size="large" placeholder="Mínimo 8 caracteres" />
                                </a-form-item>
                            </a-col>
                            <a-col :xs="24" :sm="12">
                                <a-form-item label="Confirmar contraseña">
                                    <a-input-password v-model:value="form.password_confirmation" autocomplete="new-password" size="large" placeholder="Repite la contraseña" />
                                </a-form-item>
                            </a-col>
                        </a-row>

                        <div class="pwd-tools">
                            <button type="button" class="pwd-btn" @click="generarPassword">
                                <ReloadOutlined />
                                <span>Generar contraseña aleatoria</span>
                            </button>
                            <button
                                v-if="form.password"
                                type="button"
                                class="pwd-btn pwd-btn--ghost"
                                @click="copiarPassword"
                            >
                                <CopyOutlined />
                                <span>{{ copiada ? '¡Copiada!' : 'Copiar' }}</span>
                            </button>
                            <Transition name="pwd-fade">
                                <span v-if="pwdGenerada" class="pwd-ok">
                                    <CheckCircleFilled />
                                    Contraseña generada
                                </span>
                            </Transition>
                        </div>
                        <p class="pwd-hint">
                            Se generará una contraseña segura con mayúsculas, minúsculas, números y símbolos.
                        </p>
                    </a-tab-pane>

                    <!-- ============================
                         TAB: Rol y acceso
                         ============================ -->
                    <a-tab-pane key="rol">
                        <template #tab>
                            <span class="tab-label" :style="{ '--tab-color': TABS.rol.color }">
                                <span class="tab-label__ico-wrap">
                                    <span class="tab-label__ico"><SafetyCertificateOutlined /></span>
                                    <span v-if="estadoTab('rol') === 'ok'" class="tab-label__badge tab-label__badge--ok">
                                        <CheckCircleFilled />
                                    </span>
                                    <span v-else-if="estadoTab('rol') === 'error'" class="tab-label__badge tab-label__badge--error">
                                        <ExclamationCircleFilled />
                                    </span>
                                </span>
                                <span class="tab-label__col">
                                    <span class="tab-label__txt">{{ TABS.rol.titulo }}</span>
                                    <span class="tab-label__sub">{{ TABS.rol.subtitulo }}</span>
                                </span>
                            </span>
                        </template>

                        <div class="sec-head">
                            <div class="sec-head__ico" style="--c: #6b4bc9"><SafetyCertificateOutlined /></div>
                            <div class="sec-head__meta">
                                <div class="sec-head__t">Rol y especialidades</div>
                                <div class="sec-head__s">Define qué puede hacer el usuario y sus especialidades si es técnico.</div>
                            </div>
                        </div>

                        <div class="rol-layout" :class="{ 'rol-layout--tecnico': esTecnico }">
                            <!-- Selector de rol -->
                            <div class="rol-col">
                                <div class="col-tit">Rol del usuario</div>
                                <div class="roles-grid">
                                    <button
                                        v-for="op in opcionesRoles"
                                        :key="op.valor"
                                        type="button"
                                        class="rol-op"
                                        :class="{ 'is-sel': form.rol === op.valor }"
                                        :style="{ '--c': op.color }"
                                        @click="form.rol = op.valor"
                                    >
                                        <span class="rol-op__punto" />
                                        <span class="rol-op__txt">{{ op.etiqueta }}</span>
                                        <CheckCircleFilled v-if="form.rol === op.valor" class="rol-op__check" />
                                    </button>
                                </div>
                                <Transition name="rol-fade">
                                    <div v-if="form.rol" class="rol-desc">
                                        <span class="rol-desc__t">{{ etiquetaRol(form.rol) }}</span>
                                        <span class="rol-desc__s">{{ descripcionRol(form.rol) }}</span>
                                    </div>
                                </Transition>
                                <p
                                    v-if="form.errors.roles || form.errors.rol"
                                    class="rol-error"
                                >
                                    {{ form.errors.roles || form.errors.rol }}
                                </p>
                            </div>

                            <!-- Especialidades: solo técnico -->
                            <Transition name="esp-fade">
                                <div v-if="esTecnico" class="esp-col">
                                    <div class="col-tit col-tit--star">
                                        <StarOutlined />
                                        Especialidades del técnico
                                    </div>
                                    <p class="esp-hint">Se usan para sugerirlo al delegar una orden.</p>

                                    <a-form-item label="Tipos de equipo" class="esp-item">
                                        <a-select
                                            v-model:value="form.especialidades_equipo"
                                            :options="opcionesTiposEquipo"
                                            mode="multiple"
                                            :show-search="false"
                                            allow-clear
                                            placeholder="Sin especialidades de equipo"
                                        />
                                    </a-form-item>
                                    <a-form-item label="Tipos de mantenimiento" class="esp-item">
                                        <a-select
                                            v-model:value="form.especialidades_mantenimiento"
                                            :options="opcionesTiposMantenimiento"
                                            mode="multiple"
                                            :show-search="false"
                                            allow-clear
                                            placeholder="Sin especialidades de mantenimiento"
                                        />
                                    </a-form-item>
                                </div>
                            </Transition>
                        </div>
                    </a-tab-pane>
                </a-tabs>
            </a-card>

            <!-- ============================
                 Acciones
                 ============================ -->
            <a-card size="small" class="form-acciones" :bordered="false">
                <div class="acciones-wrap">
                    <div class="acciones-info">
                        <ThunderboltFilled class="acciones-info__ic" />
                        <span>{{ editando ? 'Los cambios se reflejan de inmediato.' : 'El usuario podrá acceder al sistema al guardar.' }}</span>
                    </div>
                    <a-space>
                        <a-button size="large" @click="cancelar">Cancelar</a-button>
                        <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                            <template #icon><SaveOutlined /></template>
                            {{ editando ? 'Guardar cambios' : 'Crear usuario' }}
                        </a-button>
                    </a-space>
                </div>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
/* ==========================================================
   Card y tabs
   ========================================================== */
.form-card {
    margin-bottom: 12px;
    border-radius: 16px;
    box-shadow: var(--sigam-sombra-sm);
}
.form-card :deep(.ant-card-body) {
    padding: 0;
}

.form-tabs :deep(.ant-tabs-nav) {
    padding: 8px 12px 0;
    margin-bottom: 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid var(--sigam-borde-suave);
}
.form-tabs :deep(.ant-tabs-nav::before) {
    border-bottom: none;
}
.form-tabs :deep(.ant-tabs-nav-wrap) {
    align-items: stretch;
}
.form-tabs :deep(.ant-tabs-nav-list) {
    gap: 4px;
    align-items: stretch;
}
.form-tabs :deep(.ant-tabs-tab) {
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 11px 11px 0 0;
    transition: background 0.2s ease;
    height: 56px;
    display: inline-flex !important;
    align-items: center !important;
}
.form-tabs :deep(.ant-tabs-tab:hover) {
    background: #f3f6fa;
}
.form-tabs :deep(.ant-tabs-tab-active) {
    background: #fff;
}
.form-tabs :deep(.ant-tabs-tab-btn) {
    color: inherit !important;
    height: 100%;
    display: inline-flex !important;
    align-items: center !important;
    padding: 0 14px !important;
    transition: none !important;
}
.form-tabs :deep(.ant-tabs-ink-bar) {
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: #0d84c9;
}
.form-tabs :deep(.ant-tabs-content-holder) {
    padding: 22px 24px 24px;
}

/* Label completo */
.tab-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    height: 100%;
    line-height: 1;
}
.tab-label__ico-wrap {
    position: relative;
    flex: none;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.tab-label__ico {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #94a3b8;
    background: #eef2f7;
    transition: all 0.25s cubic-bezier(0.34, 1.4, 0.4, 1);
    line-height: 1;
}
.tab-label__ico .anticon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}
.form-tabs :deep(.ant-tabs-tab-active) .tab-label__ico {
    color: #fff;
    background: var(--tab-color);
    box-shadow: 0 4px 10px -4px var(--tab-color);
    transform: scale(1.05);
}
.tab-label__badge {
    position: absolute;
    bottom: -3px;
    right: -3px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    line-height: 1;
    border: 2px solid #fff;
    background: #fff;
    pointer-events: none;
    z-index: 2;
}
.tab-label__badge .anticon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    margin: 0;
}
.tab-label__badge--ok {
    color: #1f9e86;
}
.tab-label__badge--error {
    color: #d64545;
    animation: pulseError 1.6s ease infinite;
}
@keyframes pulseError {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}
.tab-label__col {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 2px;
    min-width: 0;
    text-align: left;
    line-height: 1.15;
}
.tab-label__txt {
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
    transition: color 0.2s ease;
    white-space: nowrap;
    line-height: 1.2;
    display: block;
}
.form-tabs :deep(.ant-tabs-tab-active) .tab-label__txt {
    color: var(--sigam-navy);
}
.tab-label__sub {
    font-size: 10.5px;
    color: var(--sigam-tenue);
    white-space: nowrap;
    line-height: 1.2;
    font-weight: 500;
    display: block;
}
@media (max-width: 991px) {
    .tab-label__sub {
        display: none;
    }
}
@media (max-width: 640px) {
    .tab-label__col {
        display: none;
    }
    .form-tabs :deep(.ant-tabs-tab) {
        height: 50px;
    }
}

/* ==========================================================
   Cabecera de sección
   ========================================================== */
.sec-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 14px;
    margin-bottom: 18px;
    border-bottom: 1px dashed var(--sigam-borde-suave);
}
.sec-head__ico {
    width: 40px;
    height: 40px;
    flex: none;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 12%, #fff);
    box-shadow: 0 4px 12px color-mix(in srgb, var(--c) 22%, transparent);
}
.sec-head__meta {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}
.sec-head__t {
    font-weight: 800;
    font-size: 14px;
    color: var(--sigam-navy);
    letter-spacing: -0.2px;
}
.sec-head__s {
    font-size: 12px;
    color: var(--sigam-tenue);
}

.inp-icon {
    color: var(--sigam-tenue);
}

/* ==========================================================
   Contraseña — botón generar + copiar
   ========================================================== */
.pwd-tools {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 14px 16px;
    border-radius: 12px;
    background: linear-gradient(120deg, #fff9ec 0%, #fff 70%);
    border: 1px dashed #f5d59a;
}
.pwd-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 38px;
    padding: 0 18px;
    border: none;
    border-radius: 10px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    cursor: pointer;
    background: linear-gradient(135deg, #e08a1e 0%, #b96b0e 100%);
    box-shadow:
        0 4px 12px rgba(224, 138, 30, 0.32),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
    transition: transform 0.14s ease, box-shadow 0.14s ease, filter 0.14s ease;
    -webkit-tap-highlight-color: transparent;
}
.pwd-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.06);
    box-shadow: 0 6px 16px rgba(224, 138, 30, 0.42),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
}
.pwd-btn:active {
    transform: translateY(0) scale(0.97);
}
.pwd-btn--ghost {
    color: var(--sigam-navy);
    background: #fff;
    border: 1px solid var(--sigam-borde);
    box-shadow: none;
}
.pwd-btn--ghost:hover {
    background: var(--sigam-navy-050);
    border-color: #a86717;
    color: #a86717;
    box-shadow: 0 4px 10px rgba(168, 103, 23, 0.12);
}
.pwd-ok {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 6px;
    padding: 5px 11px;
    border-radius: 999px;
    background: #e6f5ee;
    color: #16806c;
    font-size: 12px;
    font-weight: 700;
    animation: pulseOk 0.4s ease;
}
@keyframes pulseOk {
    from { transform: scale(0.9); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
.pwd-hint {
    margin: 10px 0 0;
    font-size: 11.5px;
    color: var(--sigam-tenue);
    text-align: left;
}

.pwd-fade-enter-active,
.pwd-fade-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.pwd-fade-enter-from,
.pwd-fade-leave-to {
    opacity: 0;
    transform: translateX(-6px);
}

/* ==========================================================
   Rol y especialidades
   ========================================================== */
.rol-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    align-items: start;
}
.rol-layout--tecnico {
    grid-template-columns: 1fr 1fr;
}
@media (max-width: 767px) {
    .rol-layout--tecnico {
        grid-template-columns: 1fr;
    }
}

.rol-col,
.esp-col {
    min-width: 0;
}

.col-tit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 800;
    font-size: 12.5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--sigam-navy);
    margin-bottom: 10px;
}
.col-tit--star .anticon {
    color: var(--sigam-teal);
}

.roles-grid {
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.rol-op {
    display: flex;
    align-items: center;
    gap: 11px;
    width: 100%;
    text-align: left;
    padding: 11px 14px;
    border: 1px solid var(--sigam-borde);
    border-radius: 11px;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: var(--sigam-texto);
    font-family: inherit;
    transition: border-color 0.16s ease, background 0.16s ease,
        box-shadow 0.16s ease, transform 0.14s ease;
    outline: none;
}
.rol-op:hover {
    border-color: var(--c);
    transform: translateX(2px);
    box-shadow: 0 3px 10px color-mix(in srgb, var(--c) 15%, transparent);
}
.rol-op.is-sel {
    border-color: var(--c);
    background: color-mix(in srgb, var(--c) 8%, #fff);
    box-shadow:
        0 0 0 3px color-mix(in srgb, var(--c) 18%, transparent),
        0 4px 12px color-mix(in srgb, var(--c) 20%, transparent);
}
.rol-op__punto {
    width: 11px;
    height: 11px;
    border-radius: 50%;
    background: var(--c);
    flex: none;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--c) 22%, transparent);
    transition: box-shadow 0.16s ease;
}
.rol-op.is-sel .rol-op__punto {
    box-shadow: 0 0 0 5px color-mix(in srgb, var(--c) 25%, transparent);
}
.rol-op__txt {
    flex: 1;
}
.rol-op__check {
    color: var(--c);
    font-size: 17px;
    animation: checkPop 0.24s ease;
}
@keyframes checkPop {
    from { transform: scale(0.7); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

.rol-desc {
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 11px;
    background: linear-gradient(120deg, var(--sigam-navy-050), #fff 75%);
    border-left: 3px solid var(--sigam-teal);
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.rol-desc__t {
    font-size: 12.5px;
    font-weight: 800;
    color: var(--sigam-navy);
    letter-spacing: -0.1px;
}
.rol-desc__s {
    font-size: 12px;
    color: var(--sigam-tenue);
    line-height: 1.4;
}

.rol-error {
    margin: 8px 0 0;
    font-size: 12px;
    color: #dc2626;
    font-weight: 600;
}

.rol-fade-enter-active,
.rol-fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.rol-fade-enter-from,
.rol-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

/* Especialidades */
.esp-col {
    padding: 16px 18px;
    border-radius: 12px;
    background: linear-gradient(160deg, #f0faf7 0%, #fff 70%);
    border: 1px dashed color-mix(in srgb, var(--sigam-teal) 30%, transparent);
}
.esp-hint {
    margin: -6px 0 12px;
    font-size: 11.5px;
    color: var(--sigam-tenue);
}
.esp-item :deep(.ant-form-item-label > label) {
    font-weight: 600;
    font-size: 12.5px;
}

.esp-fade-enter-active,
.esp-fade-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
}
.esp-fade-enter-from,
.esp-fade-leave-to {
    opacity: 0;
    transform: translateX(10px);
}

/* ==========================================================
   Acciones
   ========================================================== */
.form-acciones {
    position: sticky;
    bottom: 0;
    z-index: 5;
    border-radius: 14px;
    box-shadow: 0 -6px 20px rgba(15, 37, 71, 0.06);
    background: #fff;
}
.form-acciones :deep(.ant-card-body) {
    padding: 12px 20px;
}
.acciones-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}
.acciones-info {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: var(--sigam-tenue);
}
.acciones-info__ic {
    color: #e08a1e;
    font-size: 14px;
}
@media (max-width: 575px) {
    .acciones-wrap {
        flex-direction: column-reverse;
        align-items: stretch;
    }
    .acciones-info {
        justify-content: center;
    }
}
</style>