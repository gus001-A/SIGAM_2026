-- ============================================================================
-- SIGAM — Script SQL para producción
-- Rediseño del flujo de Tareas y Órdenes: ambas comparten ahora la misma
-- cadena final de revisión:
--   ... → Completada → [En espera de refacción → Reanudar] → Autorizada
--   → Supervisada → Cerrada
-- con Reprogramada/Cancelada disponibles en el camino. Si al supervisar no
-- se está de acuerdo, se regresa a "Autorizada" sin borrar nada de lo ya
-- registrado (el historial siempre es de solo-lectura, cada vuelta agrega
-- una fila nueva).
--
-- Corresponde exactamente a estas migraciones de Laravel (ya probadas en local):
--   database/migrations/2026_10_07_100000_agregar_estado_autorizada_a_mantenimiento.php
--   database/migrations/2026_10_07_100001_rediseno_estados_tareas.php
-- Y al permiso nuevo `tareas.supervisar` (database/seeders/RolesPermisosSeeder.php).
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. Corre este script UNA sola vez. Al final se registran las dos
--      migraciones en la tabla `migrations` para que `php artisan migrate`
--      no intente aplicarlas otra vez.
--   3. Las sentencias DDL (ALTER TABLE) no se pueden revertir dentro de una
--      transacción en MySQL: si algo falla a medias, revisa antes de volver
--      a correrlo.
--   4. El estado "Autorizado" (sin la "a" final) que YA EXISTÍA no se toca:
--      sigue siendo la autorización de la SOLICITUD, antes de asignar
--      técnico. El nuevo "Autorizada" es un paso DISTINTO, después de
--      "Completada".
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) Órdenes: columnas nuevas para el paso "Autorizada" (distintas de
--    autorizado_at/autorizado_por, que siguen siendo de la solicitud).
-- ----------------------------------------------------------------------------
ALTER TABLE `mantenimientos`
    ADD COLUMN `autorizada_at` TIMESTAMP NULL DEFAULT NULL AFTER `autorizado_at`,
    ADD COLUMN `autorizada_por` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `autorizada_at`,
    ADD CONSTRAINT `mantenimientos_autorizada_por_foreign`
        FOREIGN KEY (`autorizada_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;


-- ----------------------------------------------------------------------------
-- 2) Catálogo de estados de orden: nuevo "Autorizada" + nombres actualizados
--    de los que ya existían (sus `clave` no cambian, nada que dependa de
--    ellas se rompe).
-- ----------------------------------------------------------------------------
INSERT INTO `estados_mantenimiento` (`clave`, `nombre`, `orden`, `es_terminal`, `es_abierto`, `estado`, `created_at`, `updated_at`)
SELECT 'autorizada', 'Autorizada', 55, 0, 1, 'activo', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `estados_mantenimiento` WHERE `clave` = 'autorizada');

UPDATE `estados_mantenimiento` SET `nombre` = 'Asignada'     WHERE `clave` = 'asignado';
UPDATE `estados_mantenimiento` SET `nombre` = 'Completada'   WHERE `clave` = 'realizado';
UPDATE `estados_mantenimiento` SET `nombre` = 'Supervisada'  WHERE `clave` = 'supervisado';
UPDATE `estados_mantenimiento` SET `nombre` = 'Cerrada'      WHERE `clave` = 'cerrado';
UPDATE `estados_mantenimiento` SET `nombre` = 'Reprogramada' WHERE `clave` = 'reprogramado';
UPDATE `estados_mantenimiento` SET `nombre` = 'Cancelada'    WHERE `clave` = 'cancelado';


-- ----------------------------------------------------------------------------
-- 3) Tareas: columnas nuevas para Autorizada/Supervisada/Cerrada/Reprogramada
--    (mismo patrón que ya existe en `mantenimientos`).
-- ----------------------------------------------------------------------------
ALTER TABLE `tareas`
    ADD COLUMN `autorizada_at` TIMESTAMP NULL DEFAULT NULL AFTER `realizada_at`,
    ADD COLUMN `autorizado_por` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `autorizada_at`,
    ADD COLUMN `supervisada_at` TIMESTAMP NULL DEFAULT NULL AFTER `autorizado_por`,
    ADD COLUMN `supervisor_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `supervisada_at`,
    ADD COLUMN `cerrada_at` TIMESTAMP NULL DEFAULT NULL AFTER `supervisor_id`,
    ADD COLUMN `reprogramada_at` TIMESTAMP NULL DEFAULT NULL AFTER `cerrada_at`,
    ADD CONSTRAINT `tareas_autorizado_por_foreign`
        FOREIGN KEY (`autorizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `tareas_supervisor_id_foreign`
        FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;


-- ----------------------------------------------------------------------------
-- 4) Tareas: los nombres guardados en `estado` cambian (pendiente→asignada,
--    realizada→completada). en_proceso y cancelada no cambian.
-- ----------------------------------------------------------------------------
UPDATE `tareas` SET `estado` = 'asignada'  WHERE `estado` = 'pendiente';
UPDATE `tareas` SET `estado` = 'completada' WHERE `estado` = 'realizada';


-- ----------------------------------------------------------------------------
-- 5) Permiso nuevo `tareas.supervisar` (igual que ya existe
--    `mantenimientos.supervisar`), asignado a Supervisor y Superadministrador.
-- ----------------------------------------------------------------------------
INSERT INTO `permisos` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'tareas.supervisar', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permisos` WHERE `name` = 'tareas.supervisar');

INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.id, r.id
FROM `permisos` p
JOIN `roles` r ON r.name IN ('supervisor', 'superadministrador')
WHERE p.name = 'tareas.supervisar'
  AND NOT EXISTS (
      SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.permiso_id = p.id AND rp.rol_id = r.id
  );


-- ----------------------------------------------------------------------------
-- 6) Registrar las migraciones como aplicadas.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_10_07_100000_agregar_estado_autorizada_a_mantenimiento', @siguiente_batch),
    ('2026_10_07_100001_rediseno_estados_tareas', @siguiente_batch);


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- UPDATE `tareas` SET `estado` = 'pendiente' WHERE `estado` = 'asignada';
-- UPDATE `tareas` SET `estado` = 'realizada' WHERE `estado` = 'completada';
-- UPDATE `tareas` SET `estado` = 'realizada' WHERE `estado` IN ('autorizada', 'supervisada', 'cerrada', 'reprogramada');
--
-- ALTER TABLE `tareas`
--     DROP FOREIGN KEY `tareas_autorizado_por_foreign`,
--     DROP FOREIGN KEY `tareas_supervisor_id_foreign`,
--     DROP COLUMN `autorizada_at`, DROP COLUMN `autorizado_por`,
--     DROP COLUMN `supervisada_at`, DROP COLUMN `supervisor_id`,
--     DROP COLUMN `cerrada_at`, DROP COLUMN `reprogramada_at`;
--
-- DELETE rp FROM `rol_tiene_permisos` rp
--     JOIN `permisos` p ON p.id = rp.permiso_id
--     WHERE p.name = 'tareas.supervisar';
-- DELETE FROM `permisos` WHERE `name` = 'tareas.supervisar';
--
-- UPDATE `estados_mantenimiento` SET `nombre` = 'Asignado'     WHERE `clave` = 'asignado';
-- UPDATE `estados_mantenimiento` SET `nombre` = 'Realizado'    WHERE `clave` = 'realizado';
-- UPDATE `estados_mantenimiento` SET `nombre` = 'Supervisado'  WHERE `clave` = 'supervisado';
-- UPDATE `estados_mantenimiento` SET `nombre` = 'Cerrado'      WHERE `clave` = 'cerrado';
-- UPDATE `estados_mantenimiento` SET `nombre` = 'Reprogramado' WHERE `clave` = 'reprogramado';
-- UPDATE `estados_mantenimiento` SET `nombre` = 'Cancelado'    WHERE `clave` = 'cancelado';
-- DELETE FROM `estados_mantenimiento` WHERE `clave` = 'autorizada';
--
-- ALTER TABLE `mantenimientos`
--     DROP FOREIGN KEY `mantenimientos_autorizada_por_foreign`,
--     DROP COLUMN `autorizada_at`, DROP COLUMN `autorizada_por`;
--
-- DELETE FROM `migrations` WHERE `migration` IN (
--     '2026_10_07_100000_agregar_estado_autorizada_a_mantenimiento',
--     '2026_10_07_100001_rediseno_estados_tareas'
-- );
-- ============================================================================
