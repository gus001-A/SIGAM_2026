-- ============================================================================
-- SIGAM — Script SQL para producción
-- Mantenimiento a instalaciones (equipo O ubicación) + sucursal_id en planes
--
-- Corresponde exactamente a estas 2 migraciones de Laravel (ya probadas y
-- aplicadas en el entorno local, 169 pruebas verde):
--   database/migrations/2026_09_15_120000_add_ubicacion_a_solicitudes_y_mantenimientos.php
--   database/migrations/2026_09_15_120001_add_ubicacion_y_sucursal_a_planes_mantenimiento.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. En MySQL/MariaDB los ALTER TABLE (DDL) NO son transaccionales: se
--      confirman de inmediato aunque uses START TRANSACTION/COMMIT. Si algo
--      falla a la mitad, los pasos anteriores ya habrán quedado aplicados.
--      Por eso el respaldo del punto 1 es la red de seguridad real, no las
--      transacciones de este script.
--   3. Los nombres de las llaves foráneas (`..._foreign`) asumen que la base
--      de producción se creó con el mismo Laravel (nombres automáticos por
--      convención tabla_columna_foreign). Si algún ALTER TABLE marca error de
--      "foreign key doesn't exist", verifica el nombre real con:
--        SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
--        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'la_tabla' AND COLUMN_NAME = 'equipo_id';
--   4. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo de nuevo.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) solicitudes_mantenimiento: equipo_id pasa a NULL-able + se agrega ubicacion_id
-- ----------------------------------------------------------------------------
ALTER TABLE `solicitudes_mantenimiento`
    DROP FOREIGN KEY `solicitudes_mantenimiento_equipo_id_foreign`;

ALTER TABLE `solicitudes_mantenimiento`
    MODIFY `equipo_id` BIGINT UNSIGNED NULL,
    ADD COLUMN `ubicacion_id` BIGINT UNSIGNED NULL AFTER `equipo_id`;

ALTER TABLE `solicitudes_mantenimiento`
    ADD CONSTRAINT `solicitudes_mantenimiento_equipo_id_foreign`
        FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `solicitudes_mantenimiento_ubicacion_id_foreign`
        FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE;


-- ----------------------------------------------------------------------------
-- 2) mantenimientos: equipo_id pasa a NULL-able + se agrega ubicacion_id
-- ----------------------------------------------------------------------------
ALTER TABLE `mantenimientos`
    DROP FOREIGN KEY `mantenimientos_equipo_id_foreign`;

ALTER TABLE `mantenimientos`
    MODIFY `equipo_id` BIGINT UNSIGNED NULL,
    ADD COLUMN `ubicacion_id` BIGINT UNSIGNED NULL AFTER `equipo_id`;

ALTER TABLE `mantenimientos`
    ADD CONSTRAINT `mantenimientos_equipo_id_foreign`
        FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `mantenimientos_ubicacion_id_foreign`
        FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE;


-- ----------------------------------------------------------------------------
-- 3) planes_mantenimiento: equipo_id NULL-able + ubicacion_id + sucursal_id propio
--    (antes dependía 100% de equipo->sucursal_id; se rellena para los planes
--    que ya existen a partir de su equipo actual).
-- ----------------------------------------------------------------------------
ALTER TABLE `planes_mantenimiento`
    DROP FOREIGN KEY `planes_mantenimiento_equipo_id_foreign`;

ALTER TABLE `planes_mantenimiento`
    MODIFY `equipo_id` BIGINT UNSIGNED NULL,
    ADD COLUMN `ubicacion_id` BIGINT UNSIGNED NULL AFTER `equipo_id`,
    ADD COLUMN `sucursal_id` BIGINT UNSIGNED NULL AFTER `ubicacion_id`;

ALTER TABLE `planes_mantenimiento`
    ADD CONSTRAINT `planes_mantenimiento_equipo_id_foreign`
        FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `planes_mantenimiento_ubicacion_id_foreign`
        FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `planes_mantenimiento_sucursal_id_foreign`
        FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

-- Backfill: llena sucursal_id de los planes ya existentes, tomándolo de su equipo.
UPDATE `planes_mantenimiento` p
JOIN `equipos` e ON e.`id` = p.`equipo_id`
SET p.`sucursal_id` = e.`sucursal_id`
WHERE p.`equipo_id` IS NOT NULL;


-- ----------------------------------------------------------------------------
-- 4) Registrar las migraciones como aplicadas, para que `php artisan migrate`
--    no intente correrlas otra vez la próxima vez que despliegues.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_09_15_120000_add_ubicacion_a_solicitudes_y_mantenimientos', @siguiente_batch),
    ('2026_09_15_120001_add_ubicacion_y_sucursal_a_planes_mantenimiento', @siguiente_batch);


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- ALTER TABLE `planes_mantenimiento`
--     DROP FOREIGN KEY `planes_mantenimiento_ubicacion_id_foreign`,
--     DROP FOREIGN KEY `planes_mantenimiento_sucursal_id_foreign`,
--     DROP FOREIGN KEY `planes_mantenimiento_equipo_id_foreign`,
--     DROP COLUMN `ubicacion_id`,
--     DROP COLUMN `sucursal_id`,
--     MODIFY `equipo_id` BIGINT UNSIGNED NOT NULL;
-- ALTER TABLE `planes_mantenimiento`
--     ADD CONSTRAINT `planes_mantenimiento_equipo_id_foreign`
--         FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE;
--
-- ALTER TABLE `mantenimientos`
--     DROP FOREIGN KEY `mantenimientos_ubicacion_id_foreign`,
--     DROP FOREIGN KEY `mantenimientos_equipo_id_foreign`,
--     DROP COLUMN `ubicacion_id`,
--     MODIFY `equipo_id` BIGINT UNSIGNED NOT NULL;
-- ALTER TABLE `mantenimientos`
--     ADD CONSTRAINT `mantenimientos_equipo_id_foreign`
--         FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE;
--
-- ALTER TABLE `solicitudes_mantenimiento`
--     DROP FOREIGN KEY `solicitudes_mantenimiento_ubicacion_id_foreign`,
--     DROP FOREIGN KEY `solicitudes_mantenimiento_equipo_id_foreign`,
--     DROP COLUMN `ubicacion_id`,
--     MODIFY `equipo_id` BIGINT UNSIGNED NOT NULL;
-- ALTER TABLE `solicitudes_mantenimiento`
--     ADD CONSTRAINT `solicitudes_mantenimiento_equipo_id_foreign`
--         FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE;
--
-- DELETE FROM `migrations` WHERE `migration` IN (
--     '2026_09_15_120000_add_ubicacion_a_solicitudes_y_mantenimientos',
--     '2026_09_15_120001_add_ubicacion_y_sucursal_a_planes_mantenimiento'
-- );
-- ============================================================================
