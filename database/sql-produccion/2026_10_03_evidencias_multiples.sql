-- ============================================================================
-- SIGAM — Script SQL para producción
-- Varias fotos de evidencia por avance (tareas y órdenes de mantenimiento).
--
-- Corresponde exactamente a esta migración de Laravel (ya probada en local):
--   database/migrations/2026_10_03_100000_create_evidencias_movimiento_table.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. Corre PRIMERO el script 2026_10_02_clasificacion_tareas_y_evidencia_bitacora.sql
--      si todavía no lo corriste (este depende de las columnas
--      `documento_evidencia_id` que ese script agrega).
--   3. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo otra vez.
--   4. Copia los enlaces de una sola foto que ya existían (los avances
--      anteriores) a la nueva tabla. No se usa ninguna heurística: solo se
--      copian los enlaces que ya estaban guardados.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) Tabla pivote polimórfica: un movimiento (historial de tarea o bitácora
--    de orden) puede tener N documentos de evidencia.
-- ----------------------------------------------------------------------------
CREATE TABLE `evidencias_movimiento` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `movimiento_type` VARCHAR(120) NOT NULL,
    `movimiento_id` BIGINT UNSIGNED NOT NULL,
    `documento_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `evidencias_movimiento_movimiento_type_movimiento_id_index` (`movimiento_type`, `movimiento_id`),
    UNIQUE KEY `evidencias_movimiento_unico` (`movimiento_type`, `movimiento_id`, `documento_id`),
    KEY `evidencias_movimiento_documento_id_foreign` (`documento_id`),
    CONSTRAINT `evidencias_movimiento_documento_id_foreign`
        FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 2) Copia los enlaces únicos que ya existían (un documento por movimiento).
-- ----------------------------------------------------------------------------
INSERT INTO `evidencias_movimiento` (`movimiento_type`, `movimiento_id`, `documento_id`, `created_at`, `updated_at`)
SELECT 'App\\Models\\HistorialEstadoTarea', `id`, `documento_evidencia_id`, NOW(), NOW()
FROM `historial_estados_tarea`
WHERE `documento_evidencia_id` IS NOT NULL;

INSERT INTO `evidencias_movimiento` (`movimiento_type`, `movimiento_id`, `documento_id`, `created_at`, `updated_at`)
SELECT 'App\\Models\\ObservacionMantenimiento', `id`, `documento_evidencia_id`, NOW(), NOW()
FROM `observaciones_mantenimiento`
WHERE `documento_evidencia_id` IS NOT NULL;


-- ----------------------------------------------------------------------------
-- 3) Registrar la migración como aplicada.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_10_03_100000_create_evidencias_movimiento_table', @siguiente_batch);


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- DROP TABLE IF EXISTS `evidencias_movimiento`;
--
-- DELETE FROM `migrations` WHERE `migration` = '2026_10_03_100000_create_evidencias_movimiento_table';
-- ============================================================================
