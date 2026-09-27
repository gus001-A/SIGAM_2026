<script setup>
import { computed, reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { SaveOutlined, ShopOutlined } from '@ant-design/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { reglaCorreo, reglaRequerido, reglaTelefono, soloDigitos } from '@/utils/restricciones';

const props = defineProps({
    sucursal: { type: Object, default: null },
});

const editando = computed(() => !!props.sucursal);

const form = useForm({
    codigo: props.sucursal?.codigo ?? '',
    nombre: props.sucursal?.nombre ?? '',
    direccion: props.sucursal?.direccion ?? '',
    telefono: props.sucursal?.telefono ?? '',
    correo: props.sucursal?.correo ?? '',
    notas: props.sucursal?.notas ?? '',
});

const reglas = reactive({
    nombre: [reglaRequerido('El nombre es obligatorio.')],
    correo: [reglaCorreo()],
    telefono: [reglaTelefono(10)],
});

const est = (campo) => (form.errors[campo] ? 'error' : undefined);

const enviar = () => {
    const opciones = {
        preserveScroll: true,
        onError: () => {
            // Si hay errores, hacer scroll al primer campo con error.
            const primerError = document.querySelector('.ant-form-item-has-error');
            primerError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
    };
    if (editando.value) form.put(route('sucursales.update', props.sucursal.id), opciones);
    else form.post(route('sucursales.store'), opciones);
};

const cancelar = () =>
    router.visit(editando.value ? route('sucursales.show', props.sucursal.id) : route('sucursales.index'));
</script>

<template>

    <Head :title="editando ? `Editar ${sucursal.nombre}` : 'Nueva sucursal'" />

    <AppLayout :titulo="editando ? `Editar sucursal ${sucursal.nombre}` : 'Nueva sucursal'"
        descripcion="Datos de identificación y contacto de la sede.">
        <a-form :model="form" :rules="reglas" layout="vertical" @finish="enviar" @finish-failed="onFinishFailed">
            <a-card size="small" class="form-card">
                <div class="seccion-head">
                    <div class="seccion-head__ico">
                        <ShopOutlined />
                    </div>
                    <div>
                        <div class="seccion-head__titulo">Datos generales</div>
                        <div class="seccion-head__sub">
                            Identificación y contacto de la sede — el código se usa en folios y reportes.
                        </div>
                    </div>
                </div>

                <a-row :gutter="12" class="mt-2">
                    <a-col :xs="24" :sm="8">
                        <a-form-item label="Código" name="codigo"
                            extra="Déjalo en blanco para generarlo solo (4 letras del nombre + consecutivo, p. ej. HOSP-01)."
                            :validate-status="est('codigo')" :help="form.errors.codigo">
                            <a-input v-model:value="form.codigo" placeholder="Automático si se deja vacío" />
                        </a-form-item>
                    </a-col>
                    <a-col :xs="24" :sm="16">
                        <a-form-item label="Nombre" name="nombre" :validate-status="est('nombre')"
                            :help="form.errors.nombre">
                            <a-input v-model:value="form.nombre" />
                        </a-form-item>
                    </a-col>
                    <a-col :span="24">
                        <a-form-item label="Dirección" :validate-status="est('direccion')"
                            :help="form.errors.direccion">
                            <a-input v-model:value="form.direccion" />
                        </a-form-item>
                    </a-col>

                    <!-- Teléfono + Correo -->
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="Teléfono" name="telefono" :validate-status="est('telefono')"
                            :help="form.errors.telefono">
                            <a-input v-model:value="form.telefono" :maxlength="10" inputmode="numeric"
                                placeholder="10 dígitos" @keypress="soloDigitos" />
                        </a-form-item>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="Correo" name="correo" :validate-status="est('correo')"
                            :help="form.errors.correo">
                            <a-input v-model:value="form.correo" />
                        </a-form-item>
                    </a-col>

                    <!-- Notas: debajo de teléfono, ancho completo -->
                    <a-col :span="24">
                        <a-form-item label="Notas"
                            extra="Información adicional libre, visible en la ficha de la sucursal."
                            :validate-status="est('notas')" :help="form.errors.notas">
                            <a-textarea v-model:value="form.notas" :auto-size="{ minRows: 3, maxRows: 8 }"
                                placeholder="Información adicional de la sucursal" />
                        </a-form-item>
                    </a-col>
                </a-row>
            </a-card>

            <a-card size="small" class="form-acciones">
                <a-space>
                    <a-button type="primary" size="large" html-type="submit" :loading="form.processing">
                        <template #icon>
                            <SaveOutlined />
                        </template>
                        {{ editando ? 'Guardar cambios' : 'Registrar sucursal' }}
                    </a-button>
                    <a-button size="large" @click="cancelar">Cancelar</a-button>
                </a-space>
            </a-card>
        </a-form>
    </AppLayout>
</template>

<style scoped>
.form-card {
    margin-bottom: 10px;
}

/* Cabecera de sección en lugar de pestañas */
.seccion-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    margin-bottom: 16px;
}

.seccion-head__ico {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    color: #fff;
    background: var(--sigam-grad);
    box-shadow: 0 4px 10px rgba(15, 37, 71, 0.15);
    flex-shrink: 0;
}

.seccion-head__titulo {
    font-weight: 800;
    font-size: 14.5px;
    color: var(--sigam-navy);
    letter-spacing: -0.2px;
    line-height: 1.2;
}

.seccion-head__sub {
    font-size: 12px;
    color: var(--sigam-tenue);
    margin-top: 2px;
}

.form-acciones {
    position: sticky;
    bottom: 0;
    z-index: 5;
}
</style>