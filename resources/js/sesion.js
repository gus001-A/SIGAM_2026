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
