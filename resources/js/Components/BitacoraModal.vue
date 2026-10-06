<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    CheckCircleOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    FileAddOutlined,
    HistoryOutlined,
    StopOutlined,
    SwapOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';

/**
 * Botón "Ver más movimientos" + modal con la bitácora del registro: ediciones,
 * altas, bajas y, en tareas y órdenes, también los cambios de estado y avances.
 * Los datos vienen en la prop compartida `bitacora` (EsAuditable::bitacoraCambios()).
 */
const modal = ref(false);
const items = computed(() => usePage().props.bitacora ?? []);
const total = computed(() => items.value.length);

/* Paleta propia de la bitácora: violeta / índigo / cian / verde / naranja / rojo */
const ESTILO = {
    crear: { c: '#7c3aed', bg: '#ede9fe', icono: FileAddOutlined },
    actualizar: { c: '#4f46e5', bg: '#e0e7ff', icono: EditOutlined },
    desactivar: { c: '#ea580c', bg: '#ffedd5', icono: StopOutlined },
    eliminar: { c: '#dc2626', bg: '#fee2e2', icono: DeleteOutlined },
    reactivar: { c: '#0891b2', bg: '#cffafe', icono: UndoOutlined },
    estado: { c: '#0d9488', bg: '#ccfbf1', icono: SwapOutlined },
    avance: { c: '#16a34a', bg: '#dcfce7', icono: CheckCircleOutlined },
};
const estilo = (it) => ESTILO[it.accion] ?? { c: '#64748b', bg: '#f1f5f9', icono: HistoryOutlined };

const fechaHora = (v) =>
    v
        ? new Date(v).toLocaleString('es-MX', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
        : '—';
</script>

<template>
    <div class="bm-trigger">
        <button type="button" class="bm-btn" @click="modal = true">
            <HistoryOutlined />
            <span>Ver más movimientos</span>
            <span v-if="total" class="bm-btn__badge">{{ total }}</span>
        </button>
    </div>

    <a-modal v-model:open="modal" :footer="null" :closable="false" :width="720" centered
        class="modal-bitacora-movimientos" :mask-closable="true">
        <div class="bm">
            <header class="bm-head">
                <div class="bm-head__ico">
                    <HistoryOutlined />
                </div>
                <div class="bm-head__meta">
                    <div class="bm-head__row">
                        <h2 class="bm-head__titulo">Movimientos</h2>
                        <span class="bm-head__badge">{{ total }} registro{{ total === 1 ? '' : 's' }}</span>
                    </div>
                    <p class="bm-head__sub">Quién hizo cada cambio, cuándo y qué cambió.</p>
                </div>
                <button type="button" class="bm-head__close" title="Cerrar" @click="modal = false">✕</button>
            </header>

            <div class="bm-body">
                <div v-if="items.length" class="bm-scroll">
                    <article v-for="(it, i) in items" :key="it.id" class="bm-item"
                        :style="{ '--ac': estilo(it).c, '--abg': estilo(it).bg }">
                        <div class="bm-item__rail">
                            <span class="bm-item__ic">
                                <component :is="estilo(it).icono" />
                            </span>
                            <span v-if="i < items.length - 1" class="bm-item__linea"></span>
                        </div>
                        <div class="bm-item__card">
                            <div class="bm-item__cab">
                                <span class="bm-item__acc">{{ it.etiqueta }}</span>
                                <span class="bm-item__usuario">
                                    <span class="bm-item__avatar">{{ (it.usuario ?? 'S').charAt(0).toUpperCase() }}</span>
                                    {{ it.usuario }}
                                </span>
                                <span class="bm-item__fecha">
                                    <ClockCircleOutlined /> {{ fechaHora(it.fecha) }}
                                </span>
                            </div>
                            <p v-if="it.nota" class="bm-item__nota">{{ it.nota }}</p>
                            <ul v-if="it.cambios?.length" class="bm-campos">
                                <li v-for="c in it.cambios" :key="c.campo">
                                    <span class="bm-campo">{{ c.campo }}:</span>
                                    <template v-if="c.antes === null">
                                        <span class="bm-valor">{{ c.despues ?? '—' }}</span>
                                    </template>
                                    <template v-else>
                                        <span class="bm-antes">{{ c.antes }}</span>
                                        <span class="bm-flecha">→</span>
                                        <span class="bm-despues">{{ c.despues ?? '—' }}</span>
                                    </template>
                                </li>
                            </ul>
                        </div>
                    </article>
                </div>
                <div v-else class="bm-vacio">
                    <HistoryOutlined />
                    <div class="bm-vacio__t">Sin movimientos registrados</div>
                </div>
            </div>

            <footer class="bm-foot">
                <a-button size="large" @click="modal = false">Cerrar</a-button>
            </footer>
        </div>
    </a-modal>
</template>

<style>
/* Global (no scoped): el modal de Ant Design se monta fuera de este componente */
.bm-trigger {
    display: inline-flex;
}
.bm-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    padding: 4px 12px;
    border: 1px solid #c4b5fd;
    border-radius: 999px;
    background: linear-gradient(135deg, #f5f3ff, #eef2ff);
    color: #5b21b6;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.bm-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(91, 33, 182, 0.15);
}
.bm-btn__badge {
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    border-radius: 999px;
    background: #7c3aed;
    color: #fff;
    font-size: 10.5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.bm-head {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 18px 22px;
    background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 55%, #4f46e5 100%);
    color: #fff;
    border-radius: 16px 16px 0 0;
}
.bm-head__ico {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.18);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.bm-head__meta {
    flex: 1;
    min-width: 0;
}
.bm-head__row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.bm-head__titulo {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    color: #fff;
}
.bm-head__badge {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 999px;
    padding: 2px 10px;
    font-size: 11.5px;
    font-weight: 700;
}
.bm-head__sub {
    margin: 4px 0 0;
    font-size: 12.5px;
    opacity: 0.9;
}
.bm-head__close {
    border: none;
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
}

.bm-body {
    padding: 18px 22px;
    background: #faf9ff;
}
.bm-scroll {
    max-height: 460px;
    overflow-y: auto;
    padding-right: 6px;
}
.bm-item {
    display: flex;
    gap: 12px;
}
.bm-item__rail {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 34px;
    flex-shrink: 0;
}
.bm-item__ic {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--abg);
    color: var(--ac);
    border: 2px solid var(--ac);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
.bm-item__linea {
    flex: 1;
    width: 2px;
    margin: 4px 0;
    background: linear-gradient(#c4b5fd, #e0e7ff);
}
.bm-item__card {
    flex: 1;
    min-width: 0;
    margin-bottom: 14px;
    background: #fff;
    border: 1px solid #e9e5ff;
    border-left: 4px solid var(--ac);
    border-radius: 12px;
    padding: 10px 14px;
    box-shadow: 0 1px 3px rgba(76, 29, 149, 0.06);
}
.bm-item__cab {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}
.bm-item__acc {
    background: var(--ac);
    color: #fff;
    border-radius: 999px;
    padding: 1px 9px;
    font-size: 11px;
    font-weight: 700;
}
.bm-item__usuario {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    font-size: 13px;
    color: #312e81;
}
.bm-item__avatar {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff;
    font-size: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.bm-item__fecha {
    margin-left: auto;
    font-size: 11.5px;
    color: #6b7280;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.bm-item__nota {
    margin: 8px 0 0;
    font-size: 13px;
    color: #1e1b4b;
    white-space: pre-line;
}
.bm-campos {
    margin: 8px 0 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 12px;
}
.bm-campos li {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    word-break: break-word;
}
.bm-campo {
    background: #e0e7ff;
    color: #3730a3;
    border-radius: 6px;
    padding: 0 7px;
    font-weight: 700;
    text-transform: capitalize;
    font-size: 11px;
}
.bm-antes {
    color: #b91c1c;
    background: #fee2e2;
    border-radius: 4px;
    padding: 0 6px;
    text-decoration: line-through;
}
.bm-valor {
    color: #312e81;
    font-weight: 700;
}
.bm-flecha {
    color: #a78bfa;
    font-weight: 700;
}
.bm-despues {
    color: #15803d;
    background: #dcfce7;
    border-radius: 4px;
    padding: 0 6px;
    font-weight: 600;
}
.bm-vacio {
    text-align: center;
    padding: 28px 12px;
    color: #8b5cf6;
    font-size: 26px;
}
.bm-vacio__t {
    margin-top: 6px;
    font-size: 13px;
    color: #6b7280;
}
.bm-foot {
    display: flex;
    justify-content: flex-end;
    padding: 14px 22px;
    border-top: 1px solid #ede9fe;
    background: #fff;
    border-radius: 0 0 16px 16px;
}
</style>
