-- ============================================================================
-- SIGAM — Script SQL para producción
-- Normaliza a MAYÚSCULAS los campos de texto libre que ya existían antes de
-- tener el trait `ConvierteMayusculas` (o que se sembraron/cargaron con un
-- valor mixto) — el trait solo actúa en altas/ediciones NUEVAS desde código,
-- nunca reprocesa lo que ya estaba guardado. Equivalente exacto del comando
-- `php artisan sigam:normalizar-mayusculas` corrido en local (probado ahí:
-- 48 registros en 12 tablas, incluido el usuario admin que seguía como
-- "Administrador" en vez de "ADMINISTRADOR").
--
-- Es seguro correrlo más de una vez: `UPPER()` sobre un valor que ya está en
-- mayúsculas no cambia nada. NO toca correos, contraseñas, claves de
-- catálogo (`clave`), ni columnas JSON — mismos campos exactos que cada
-- modelo declara en su `camposMayusculas()`.
--
-- ⚠️ Antes de correrlo: respaldo de la base
--    (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
-- ============================================================================

UPDATE `asignaciones_mantenimiento` SET `notas` = UPPER(`notas`) WHERE `notas` IS NOT NULL AND `notas` != '';

UPDATE `campos_formato` SET `etiqueta` = UPPER(`etiqueta`) WHERE `etiqueta` IS NOT NULL AND `etiqueta` != '';
UPDATE `campos_formato` SET `ayuda` = UPPER(`ayuda`) WHERE `ayuda` IS NOT NULL AND `ayuda` != '';

UPDATE `documentos` SET `nombre_original` = UPPER(`nombre_original`) WHERE `nombre_original` IS NOT NULL AND `nombre_original` != '';
UPDATE `documentos` SET `titulo` = UPPER(`titulo`) WHERE `titulo` IS NOT NULL AND `titulo` != '';

UPDATE `equipos` SET `codigo_activo` = UPPER(`codigo_activo`) WHERE `codigo_activo` IS NOT NULL AND `codigo_activo` != '';
UPDATE `equipos` SET `codigo_barras` = UPPER(`codigo_barras`) WHERE `codigo_barras` IS NOT NULL AND `codigo_barras` != '';
UPDATE `equipos` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';
UPDATE `equipos` SET `modelo` = UPPER(`modelo`) WHERE `modelo` IS NOT NULL AND `modelo` != '';
UPDATE `equipos` SET `numero_serie` = UPPER(`numero_serie`) WHERE `numero_serie` IS NOT NULL AND `numero_serie` != '';
UPDATE `equipos` SET `numero_factura` = UPPER(`numero_factura`) WHERE `numero_factura` IS NOT NULL AND `numero_factura` != '';
UPDATE `equipos` SET `vida_util` = UPPER(`vida_util`) WHERE `vida_util` IS NOT NULL AND `vida_util` != '';
UPDATE `equipos` SET `notas` = UPPER(`notas`) WHERE `notas` IS NOT NULL AND `notas` != '';
UPDATE `equipos` SET `motivo_baja` = UPPER(`motivo_baja`) WHERE `motivo_baja` IS NOT NULL AND `motivo_baja` != '';

UPDATE `estados_equipo` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `estados_equipo` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `estados_mantenimiento` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `estados_mantenimiento` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `formatos` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `formatos` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `historial_estados_mantenimiento` SET `nota` = UPPER(`nota`) WHERE `nota` IS NOT NULL AND `nota` != '';
UPDATE `historial_estados_tarea` SET `nota` = UPPER(`nota`) WHERE `nota` IS NOT NULL AND `nota` != '';
UPDATE `historial_ubicacion_equipo` SET `motivo` = UPPER(`motivo`) WHERE `motivo` IS NOT NULL AND `motivo` != '';

UPDATE `mantenimientos` SET `problema_reportado` = UPPER(`problema_reportado`) WHERE `problema_reportado` IS NOT NULL AND `problema_reportado` != '';
UPDATE `mantenimientos` SET `diagnostico` = UPPER(`diagnostico`) WHERE `diagnostico` IS NOT NULL AND `diagnostico` != '';
UPDATE `mantenimientos` SET `descripcion_trabajo` = UPPER(`descripcion_trabajo`) WHERE `descripcion_trabajo` IS NOT NULL AND `descripcion_trabajo` != '';
UPDATE `mantenimientos` SET `observaciones` = UPPER(`observaciones`) WHERE `observaciones` IS NOT NULL AND `observaciones` != '';
UPDATE `mantenimientos` SET `condicion_final` = UPPER(`condicion_final`) WHERE `condicion_final` IS NOT NULL AND `condicion_final` != '';

UPDATE `marcas` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `marcas` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `materiales` SET `codigo` = UPPER(`codigo`) WHERE `codigo` IS NOT NULL AND `codigo` != '';
UPDATE `materiales` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `materiales` SET `unidad` = UPPER(`unidad`) WHERE `unidad` IS NOT NULL AND `unidad` != '';

UPDATE `materiales_mantenimiento` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';
UPDATE `materiales_mantenimiento` SET `unidad` = UPPER(`unidad`) WHERE `unidad` IS NOT NULL AND `unidad` != '';
UPDATE `materiales_mantenimiento` SET `notas` = UPPER(`notas`) WHERE `notas` IS NOT NULL AND `notas` != '';

UPDATE `normas` SET `codigo` = UPPER(`codigo`) WHERE `codigo` IS NOT NULL AND `codigo` != '';
UPDATE `normas` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `normas` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `notificaciones` SET `titulo` = UPPER(`titulo`) WHERE `titulo` IS NOT NULL AND `titulo` != '';
UPDATE `notificaciones` SET `cuerpo` = UPPER(`cuerpo`) WHERE `cuerpo` IS NOT NULL AND `cuerpo` != '';

UPDATE `observaciones_mantenimiento` SET `cuerpo` = UPPER(`cuerpo`) WHERE `cuerpo` IS NOT NULL AND `cuerpo` != '';

UPDATE `planes_mantenimiento` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';

UPDATE `prioridades` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';

UPDATE `proveedores` SET `razon_social` = UPPER(`razon_social`) WHERE `razon_social` IS NOT NULL AND `razon_social` != '';
UPDATE `proveedores` SET `nombre_comercial` = UPPER(`nombre_comercial`) WHERE `nombre_comercial` IS NOT NULL AND `nombre_comercial` != '';
UPDATE `proveedores` SET `rfc` = UPPER(`rfc`) WHERE `rfc` IS NOT NULL AND `rfc` != '';
UPDATE `proveedores` SET `contacto` = UPPER(`contacto`) WHERE `contacto` IS NOT NULL AND `contacto` != '';
UPDATE `proveedores` SET `direccion` = UPPER(`direccion`) WHERE `direccion` IS NOT NULL AND `direccion` != '';
UPDATE `proveedores` SET `especialidad` = UPPER(`especialidad`) WHERE `especialidad` IS NOT NULL AND `especialidad` != '';
UPDATE `proveedores` SET `notas` = UPPER(`notas`) WHERE `notas` IS NOT NULL AND `notas` != '';

UPDATE `reprogramaciones_mantenimiento` SET `motivo` = UPPER(`motivo`) WHERE `motivo` IS NOT NULL AND `motivo` != '';

UPDATE `respuestas_formato` SET `valor_texto` = UPPER(`valor_texto`) WHERE `valor_texto` IS NOT NULL AND `valor_texto` != '';

UPDATE `solicitudes_mantenimiento` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';
UPDATE `solicitudes_mantenimiento` SET `motivo_rechazo` = UPPER(`motivo_rechazo`) WHERE `motivo_rechazo` IS NOT NULL AND `motivo_rechazo` != '';

UPDATE `sucursales` SET `codigo` = UPPER(`codigo`) WHERE `codigo` IS NOT NULL AND `codigo` != '';
UPDATE `sucursales` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `sucursales` SET `direccion` = UPPER(`direccion`) WHERE `direccion` IS NOT NULL AND `direccion` != '';
UPDATE `sucursales` SET `notas` = UPPER(`notas`) WHERE `notas` IS NOT NULL AND `notas` != '';

UPDATE `tareas` SET `titulo` = UPPER(`titulo`) WHERE `titulo` IS NOT NULL AND `titulo` != '';
UPDATE `tareas` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';
UPDATE `tareas` SET `nota_avance` = UPPER(`nota_avance`) WHERE `nota_avance` IS NOT NULL AND `nota_avance` != '';
UPDATE `tareas` SET `nota_cierre` = UPPER(`nota_cierre`) WHERE `nota_cierre` IS NOT NULL AND `nota_cierre` != '';
UPDATE `tareas` SET `nota_cancelacion` = UPPER(`nota_cancelacion`) WHERE `nota_cancelacion` IS NOT NULL AND `nota_cancelacion` != '';

UPDATE `tarea_responsables` SET `notas` = UPPER(`notas`) WHERE `notas` IS NOT NULL AND `notas` != '';

UPDATE `tipos_area` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `tipos_area` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `tipos_equipo` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `tipos_equipo` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `tipos_limpieza` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `tipos_limpieza` SET `frecuencia` = UPPER(`frecuencia`) WHERE `frecuencia` IS NOT NULL AND `frecuencia` != '';
UPDATE `tipos_limpieza` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `tipos_mantenimiento` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `tipos_mantenimiento` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `tipos_ubicacion` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `tipos_ubicacion` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';

UPDATE `ubicaciones` SET `codigo` = UPPER(`codigo`) WHERE `codigo` IS NOT NULL AND `codigo` != '';
UPDATE `ubicaciones` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `ubicaciones` SET `descripcion` = UPPER(`descripcion`) WHERE `descripcion` IS NOT NULL AND `descripcion` != '';
UPDATE `ubicaciones` SET `ruta` = UPPER(`ruta`) WHERE `ruta` IS NOT NULL AND `ruta` != '';

UPDATE `usuarios` SET `nombre` = UPPER(`nombre`) WHERE `nombre` IS NOT NULL AND `nombre` != '';
UPDATE `usuarios` SET `apellidos` = UPPER(`apellidos`) WHERE `apellidos` IS NOT NULL AND `apellidos` != '';
