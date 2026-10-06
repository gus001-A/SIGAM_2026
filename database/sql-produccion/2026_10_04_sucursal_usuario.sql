-- ============================================================================
-- SIGAM — Script SQL para producción
-- Tabla pivote usuario ↔ sucursal (sucursal_usuario).
--
-- Corresponde exactamente a esta migración de Laravel (ya probada en local):
--   database/migrations/2026_10_04_100000_create_sucursal_usuario_table.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo otra vez.
--   3. Copia la sucursal actual de cada usuario (usuarios.sucursal_id) a la
--      nueva tabla, para que nadie pierda su acceso.
--   4. Las sentencias DDL (CREATE TABLE) no se pueden revertir dentro de una
--      transacción en MySQL: si algo falla a medias, revisa antes de volver a correrlo.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) Tabla pivote.
-- ----------------------------------------------------------------------------
CREATE TABLE `sucursal_usuario` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` BIGINT UNSIGNED NOT NULL,
    `sucursal_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `sucursal_usuario_usuario_id_sucursal_id_unique` (`usuario_id`, `sucursal_id`),
    KEY `sucursal_usuario_sucursal_id_foreign` (`sucursal_id`),
    CONSTRAINT `sucursal_usuario_usuario_id_foreign`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `sucursal_usuario_sucursal_id_foreign`
        FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 2) Copia la sucursal actual de cada usuario a la tabla pivote.
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `sucursal_usuario` (`usuario_id`, `sucursal_id`, `created_at`, `updated_at`)
SELECT `id`, `sucursal_id`, NOW(), NOW()
FROM `usuarios`
WHERE `sucursal_id` IS NOT NULL;


-- ----------------------------------------------------------------------------
-- 3) Registrar la migración como aplicada.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_10_04_100000_create_sucursal_usuario_table', @siguiente_batch);


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- DROP TABLE IF EXISTS `sucursal_usuario`;
--
-- DELETE FROM `migrations` WHERE `migration` = '2026_10_04_100000_create_sucursal_usuario_table';
-- ============================================================================
