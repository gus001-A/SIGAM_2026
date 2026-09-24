import { ref } from 'vue';

/**
 * Formularios organizados en pestañas (`<a-tabs>`): si hay un error en un
 * campo que vive en una pestaña que no está activa, el usuario no lo ve.
 * Este helper salta automáticamente a la primera pestaña con un error,
 * tanto si el error lo detecta el propio AntD antes de enviar (`@finish-failed`,
 * validación del navegador) como si lo regresa el servidor (422 de Inertia).
 *
 * @param {Record<string, string[]>} mapaCampos  { claveDePestana: [nombresDeCampo] }
 * @param {string} inicial  clave de la pestaña que se muestra al abrir el formulario
 */
export function useFormularioPestanas(mapaCampos, inicial) {
    const pestanaActiva = ref(inicial);

    const irAPestanaConError = (nombresCampos) => {
        if (!nombresCampos?.length) return;
        const entrada = Object.entries(mapaCampos).find(([, campos]) => campos.some((c) => nombresCampos.includes(c)));
        if (entrada) pestanaActiva.value = entrada[0];
    };

    /** Handler para `@finish-failed` de `<a-form>` (validación del navegador). */
    const onFinishFailed = ({ errorFields }) => {
        irAPestanaConError((errorFields ?? []).map((f) => f.name[0]));
    };

    /** Handler para `onError` de `form.post()`/`form.put()` de Inertia (validación del servidor). */
    const onErrorServidor = (errores) => irAPestanaConError(Object.keys(errores ?? {}));

    return { pestanaActiva, onFinishFailed, onErrorServidor };
}
