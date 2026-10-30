-- ============================================================================
-- SIGAM — Script SQL para producción
-- El código de activo de un equipo pasa a ser único POR SUCURSAL (antes era
-- único en todo el sistema). Así el equipo "101" puede existir a la vez en
-- Clínica San Antonio y en Fundación, pero no dos veces en la misma sucursal.
--
-- Corresponde exactamente a esta migración de Laravel (ya probada en local):
--   database/migrations/2026_10_17_100000_codigo_equipo_unico_por_sucursal.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo otra vez.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) Quita la unicidad global del código y la cambia por (sucursal, código).
--    Se deja un índice simple en el código para que las búsquedas sigan rápidas.
-- ----------------------------------------------------------------------------
ALTER TABLE `equipos`
    DROP INDEX `equipos_codigo_activo_unique`,
    ADD UNIQUE INDEX `equipos_sucursal_id_codigo_activo_unique` (`sucursal_id`, `codigo_activo`),
    ADD INDEX `equipos_codigo_activo_index` (`codigo_activo`);


-- ----------------------------------------------------------------------------
-- 2) Registra la migración como ya aplicada.
-- ----------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_17_100000_codigo_equipo_unico_por_sucursal', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;
