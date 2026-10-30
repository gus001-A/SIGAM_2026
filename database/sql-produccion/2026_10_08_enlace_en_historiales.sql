-- ============================================================================
-- SIGAM — Script SQL para producción
-- Enlace opcional en los avances de tareas y en los movimientos de órdenes.
--
-- Corresponde exactamente a esta migración de Laravel (ya probada en local):
--   database/migrations/2026_10_08_100000_agregar_enlace_a_historiales.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo otra vez.
--   3. Este cambio no necesita ningún backfill de datos: la columna nace vacía.
-- ============================================================================


ALTER TABLE `historial_estados_tarea`
    ADD COLUMN `enlace` VARCHAR(2048) NULL DEFAULT NULL AFTER `nota`;

ALTER TABLE `observaciones_mantenimiento`
    ADD COLUMN `enlace` VARCHAR(2048) NULL DEFAULT NULL AFTER `cuerpo`;


-- ----------------------------------------------------------------------------
-- Registrar la migración como aplicada.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_10_08_100000_agregar_enlace_a_historiales', @siguiente_batch);


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- ALTER TABLE `historial_estados_tarea` DROP COLUMN `enlace`;
-- ALTER TABLE `observaciones_mantenimiento` DROP COLUMN `enlace`;
--
-- DELETE FROM `migrations` WHERE `migration` = '2026_10_08_100000_agregar_enlace_a_historiales';
-- ============================================================================
