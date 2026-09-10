<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { BellOutlined, CheckOutlined, InboxOutlined } from '@ant-design/icons-vue';
import { fechaRelativa, metaNotificacion } from '@/utils/notificaciones';

const props = defineProps({
    items: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
});
const emit = defineEmits(['recargar']);

const abierto = ref(false);

const abrir = (n) => {
    abierto.value = false;
    if (!n.leida) {
        router.put(route('notificaciones.leida', n.id), {}, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                emit('recargar');
                if (n.url) router.visit(n.url);
            },
        });
    } else if (n.url) {
        router.visit(n.url);
    }
};

const marcarTodas = () =>
    router.put(route('notificaciones.leer_todas'), {}, {
        preserveScroll: true,
        onSuccess: () => emit('recargar'),
    });

const verTodas = () => {
    abierto.value = false;
    router.visit(route('notificaciones.index'));
};
</script>

<template>
    <a-popover
        v-model:open="abierto"
        trigger="click"
        placement="bottomRight"
        :arrow="false"
        overlay-class-name="pnoti-pop"
    >
        <a-badge :count="total" :offset="[-3, 5]" size="small">
            <a-button
                type="text"
                shape="circle"
                class="app-nav__btn"
                :class="{ 'app-nav__btn--activo': total > 0 }"
            >
                <template #icon><BellOutlined /></template>
            </a-button>
        </a-badge>

        <template #content>
            <div class="pnoti">
                <div class="pnoti__head">
                    <span class="pnoti__titulo">Notificaciones</span>
                    <a v-if="total" class="pnoti__marcar" @click="marcarTodas">
                        <CheckOutlined /> Marcar todas
                    </a>
                </div>

                <div v-if="items.length" class="pnoti__lista">
                    <button
                        v-for="n in items"
                        :key="n.id"
                        type="button"
                        class="pnoti__item"
                        :class="{ 'is-leida': n.leida }"
                        @click="abrir(n)"
                    >
                        <span
                            class="pnoti__ic"
                            :style="{ background: metaNotificacion(n.tipo).color + '1a', color: metaNotificacion(n.tipo).color }"
                        >
                            <component :is="metaNotificacion(n.tipo).icon" />
                        </span>
                        <span class="pnoti__cuerpo">
                            <span class="pnoti__fila">
                                <span class="pnoti__nt">{{ n.titulo }}</span>
                                <span class="pnoti__fecha">{{ fechaRelativa(n.created_at) }}</span>
                            </span>
                            <span v-if="n.cuerpo" class="pnoti__texto">{{ n.cuerpo }}</span>
                        </span>
                        <span v-if="!n.leida" class="pnoti__punto" />
                    </button>
                </div>

                <div v-else class="pnoti__vacio">
                    <InboxOutlined />
                    <span>Sin notificaciones</span>
                </div>

                <button type="button" class="pnoti__pie" @click="verTodas">
                    Ver todo el historial
                </button>
            </div>
        </template>
    </a-popover>
</template>

<style>
.pnoti-pop .ant-popover-inner {
    padding: 0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--sigam-sombra-lg);
}
.pnoti-pop .ant-popover-inner-content {
    padding: 0;
}
</style>

<style scoped>
.pnoti {
    width: 360px;
    max-width: 92vw;
}
.pnoti__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: var(--sigam-navy-050);
}
.pnoti__titulo {
    font-weight: 700;
    color: var(--sigam-navy);
    font-size: 13.5px;
}
.pnoti__marcar {
    font-size: 12px;
    color: var(--sigam-teal-700);
    cursor: pointer;
    font-weight: 600;
}
.pnoti__lista {
    max-height: 380px;
    overflow-y: auto;
}
.pnoti__item {
    display: flex;
    gap: 11px;
    width: 100%;
    text-align: left;
    padding: 11px 14px;
    border: 0;
    border-bottom: 1px solid var(--sigam-borde-suave);
    background: #fff;
    cursor: pointer;
    transition: background 0.12s ease;
}
.pnoti__item:hover {
    background: var(--sigam-navy-050);
}
.pnoti__item.is-leida {
    opacity: 0.6;
}
.pnoti__ic {
    flex: none;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
.pnoti__cuerpo {
    flex: 1;
    min-width: 0;
}
.pnoti__fila {
    display: flex;
    align-items: baseline;
    gap: 8px;
}
.pnoti__nt {
    font-weight: 600;
    font-size: 13px;
    color: var(--sigam-texto);
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pnoti__fecha {
    font-size: 11px;
    color: #94a3b8;
    flex: none;
}
.pnoti__texto {
    display: block;
    font-size: 12px;
    color: var(--sigam-tenue);
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.pnoti__punto {
    flex: none;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--sigam-teal);
    margin-top: 6px;
}
.pnoti__vacio {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 34px 0;
    color: #94a3b8;
    font-size: 12.5px;
}
.pnoti__vacio .anticon {
    font-size: 24px;
}
.pnoti__pie {
    width: 100%;
    padding: 11px;
    border: 0;
    border-top: 1px solid var(--sigam-borde-suave);
    background: #fbfcfe;
    color: var(--sigam-navy);
    font-weight: 600;
    font-size: 12.5px;
    cursor: pointer;
    transition: background 0.12s ease;
}
.pnoti__pie:hover {
    background: var(--sigam-navy-050);
}
</style>
