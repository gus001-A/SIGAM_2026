import { Select } from 'ant-design-vue';

// Hace que todas las listas desplegables (<a-select>) permitan escribir para
// buscar, comparando sin importar mayúsculas ni acentos. Un select puede
// seguir desactivándolo con :show-search="false" o usar su propio :filter-option.

const normalizar = (texto) =>
    String(texto ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();

// Texto visible de los hijos de un <a-select-option> (VNodes o strings).
const textoDeNodos = (nodos) => {
    if (nodos == null || typeof nodos === 'boolean') return '';
    if (typeof nodos === 'string' || typeof nodos === 'number') return String(nodos);
    if (Array.isArray(nodos)) return nodos.map(textoDeNodos).join(' ');
    if (typeof nodos === 'function') return textoDeNodos(nodos());
    if (typeof nodos === 'object') {
        if (nodos.children) return textoDeNodos(nodos.children.default ?? nodos.children);
    }
    return '';
};

const textoOpcion = (opcion) => {
    if (typeof opcion.label === 'string' || typeof opcion.label === 'number') return String(opcion.label);
    if (opcion.children) return textoDeNodos(opcion.children);
    if (opcion.label) return textoDeNodos(opcion.label);
    // Opciones con :field-names personalizados ({ nombre, id }, { razon_social, id }...)
    return Object.values(opcion)
        .filter((v) => typeof v === 'string')
        .join(' ');
};

export const filtrarOpcion = (entrada, opcion) =>
    normalizar(textoOpcion(opcion)).includes(normalizar(entrada));

export default {
    install() {
        Select.props.showSearch = { type: Boolean, default: true };
        Select.props.filterOption = { type: [Boolean, Function], default: () => filtrarOpcion };
    },
};
