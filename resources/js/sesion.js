import { router } from '@inertiajs/vue3';
import { Modal } from 'ant-design-vue';

/**
 * Cuando el token CSRF o la sesión vencen (típico si se deja un formulario
 * abierto un buen rato), Laravel responde 419/401 con una página de error
 * normal — Inertia no sabe qué hacer con eso y el usuario se topa con un
 * error críptico. Aquí lo interceptamos y avisamos con claridad qué pasó,
 * en vez de dejar que truene.
 */
let avisando = false;

router.on('invalid', (evento) => {
    const status = evento.detail?.response?.status;
    if (status !== 419 && status !== 401) return;

    evento.preventDefault();
    if (avisando) return;
    avisando = true;

    Modal.confirm({
        title: 'Tu sesión expiró',
        content:
            'Por seguridad se cerró tu sesión por inactividad. Vuelve a iniciar sesión para continuar — vamos a recargar la página.',
        okText: 'Recargar',
        centered: true,
        closable: false,
        maskClosable: false,
        cancelButtonProps: { style: { display: 'none' } },
        onOk: () => window.location.reload(),
    });
});

/**
 * Respuestas que no vienen de Laravel sino del servidor de hosting (firewall
 * ModSecurity/LiteSpeed: "403 Forbidden — Access to this resource on the
 * server is denied!", o 413 por archivos muy pesados). Inertia las pintaba
 * tal cual en un recuadro blanco encima de la pantalla y el usuario no sabía
 * qué pasó ni si se guardó algo. Aquí se reemplazan por un aviso claro.
 */
router.on('invalid', (evento) => {
    const respuesta = evento.detail?.response;
    const status = respuesta?.status;
    if (status === 419 || status === 401) return;

    const html = typeof respuesta?.data === 'string' ? respuesta.data : '';
    const firewall = /Access to this resource on the server is denied|Mod_?Security|LiteSpeed/i.test(html);

    let content;
    if (status === 413) {
        content = 'Los archivos que intentas subir pesan demasiado para el servidor. Quita algunos o súbelos en varios avances.';
    } else if (status === 403 && firewall) {
        content = 'El servidor bloqueó el envío por seguridad (no se guardó nada). Suele pasar por el nombre o el contenido de algún archivo adjunto: renómbralo o conviértelo (por ejemplo a PDF o JPG) e inténtalo de nuevo. Si persiste, avisa al administrador del sistema.';
    } else if (status === 403) {
        content = 'No tienes permiso para realizar esta acción.';
    } else {
        return;
    }

    evento.preventDefault();
    Modal.error({ title: 'No se pudo completar la acción', content, centered: true, okText: 'Entendido' });
});
