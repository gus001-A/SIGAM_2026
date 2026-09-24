-- ============================================================================
-- SIGAM — Limpieza de datos de PRUEBA en el entorno LOCAL de desarrollo
--
-- Este script es SOLO para la base de datos local (`sigam`, wamp mysql8.4.7),
-- donde absolutamente todo lo que hay hoy es demo/prueba (equipos "Equipo de
-- prueba 1..8", solicitudes SOL-DEMO-*, la sucursal "Sucursal Matriz" de
-- ejemplo, ubicaciones de prueba, etc.). NO USAR ESTE SCRIPT EN PRODUCCIÓN —
-- para producción existe un script aparte, basado en patrones de nombre, que
-- SÍ es seguro de revisar y correr ahí (ver el otro archivo en esta carpeta).
--
-- Qué SE BORRA: todo lo transaccional (equipos, solicitudes, órdenes, planes,
-- ubicaciones, sucursales, documentos, notificaciones, bitácora de auditoría)
-- y se reinician los consecutivos de folios/códigos (SOL-/MTO-/EQ-) para que
-- el próximo registro real empiece limpio desde 1.
--
-- Qué NO se toca: catálogos (marcas, tipos, estados, prioridades, materiales),
-- roles y permisos, y la cuenta de usuario "Administrador SIGAM" — son
-- configuración del sistema, no "datos de prueba".
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `respuestas_formato`;
TRUNCATE TABLE `materiales_mantenimiento`;
TRUNCATE TABLE `observaciones_mantenimiento`;
TRUNCATE TABLE `reprogramaciones_mantenimiento`;
TRUNCATE TABLE `historial_estados_mantenimiento`;
TRUNCATE TABLE `asignaciones_mantenimiento`;
TRUNCATE TABLE `mantenimiento_norma`;
TRUNCATE TABLE `ocurrencias_plan_mantenimiento`;
TRUNCATE TABLE `documento_relacionado`;
TRUNCATE TABLE `documentos`;
TRUNCATE TABLE `mantenimientos`;
TRUNCATE TABLE `solicitudes_mantenimiento`;
TRUNCATE TABLE `planes_mantenimiento`;
TRUNCATE TABLE `historial_ubicacion_equipo`;
TRUNCATE TABLE `equipo_norma`;
TRUNCATE TABLE `equipos`;
TRUNCATE TABLE `ubicaciones`;
TRUNCATE TABLE `sucursales`;
TRUNCATE TABLE `usuario_tipo_equipo`;
TRUNCATE TABLE `usuario_tipo_mantenimiento`;
TRUNCATE TABLE `registros_auditoria`;
TRUNCATE TABLE `notificaciones`;
TRUNCATE TABLE `secuencias_folio`;

SET FOREIGN_KEY_CHECKS = 1;
