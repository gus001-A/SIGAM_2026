-- ============================================================================
-- SIGAM — Script SQL para producción
-- Migraciones pendientes ANTERIORES al módulo de Tareas (nunca se habían
-- desplegado a producción — es la causa del error al editar un usuario:
-- "Table ... usuario_tipo_equipo doesn't exist").
--
-- Corresponde exactamente a estas 3 migraciones de Laravel (ya aplicadas en
-- el entorno local):
--   database/migrations/2026_09_17_090000_create_especialidades_tecnico_tables.php
--   database/migrations/2026_09_18_090000_add_baja_a_equipos.php
--   database/migrations/2026_09_18_100000_create_catalogos_limpieza.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. Corre este script ANTES o DESPUÉS de los de Tareas, el orden entre
--      ellos no importa (no se tocan entre sí). Solo dentro de este script
--      respeta el orden de las secciones.
--   3. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo de nuevo.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) Especialidades de técnicos: en qué tipos de equipo / tipos de
--    mantenimiento es bueno cada usuario técnico (para sugerirlo al delegar
--    una orden). Es lo que falta y causa el error al editar un usuario.
-- ----------------------------------------------------------------------------
CREATE TABLE `usuario_tipo_equipo` (
    `usuario_id` BIGINT UNSIGNED NOT NULL,
    `tipo_equipo_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`usuario_id`, `tipo_equipo_id`),
    KEY `usuario_tipo_equipo_tipo_equipo_id_foreign` (`tipo_equipo_id`),
    CONSTRAINT `usuario_tipo_equipo_usuario_id_foreign`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `usuario_tipo_equipo_tipo_equipo_id_foreign`
        FOREIGN KEY (`tipo_equipo_id`) REFERENCES `tipos_equipo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_tipo_mantenimiento` (
    `usuario_id` BIGINT UNSIGNED NOT NULL,
    `tipo_mantenimiento_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`usuario_id`, `tipo_mantenimiento_id`),
    KEY `usuario_tipo_mantenimiento_tipo_mantenimiento_id_foreign` (`tipo_mantenimiento_id`),
    CONSTRAINT `usuario_tipo_mantenimiento_usuario_id_foreign`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `usuario_tipo_mantenimiento_tipo_mantenimiento_id_foreign`
        FOREIGN KEY (`tipo_mantenimiento_id`) REFERENCES `tipos_mantenimiento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 2) equipos: motivo y responsable de la baja (antes solo se soft-deleteaba
--    sin dejar rastro de por qué).
-- ----------------------------------------------------------------------------
ALTER TABLE `equipos`
    ADD COLUMN `motivo_baja` TEXT NULL AFTER `notas`,
    ADD COLUMN `baja_por` BIGINT UNSIGNED NULL AFTER `motivo_baja`,
    ADD COLUMN `baja_en` TIMESTAMP NULL DEFAULT NULL AFTER `baja_por`;

ALTER TABLE `equipos`
    ADD CONSTRAINT `equipos_baja_por_foreign`
        FOREIGN KEY (`baja_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;


-- ----------------------------------------------------------------------------
-- 3) Catálogos de limpieza hospitalaria: "tipo de área" (cada cuántos días
--    debe limpiarse) y "tipo de limpieza" (cuándo se realiza). Una ubicación
--    puede clasificarse con ambos, opcionalmente.
-- ----------------------------------------------------------------------------
CREATE TABLE `tipos_area` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(255) NOT NULL,
    `clave` VARCHAR(80) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `dias_limpieza` SMALLINT UNSIGNED NOT NULL,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'activo',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tipos_area_nombre_unique` (`nombre`),
    UNIQUE KEY `tipos_area_clave_unique` (`clave`),
    KEY `tipos_area_estado_index` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipos_limpieza` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(255) NOT NULL,
    `clave` VARCHAR(80) NOT NULL,
    `frecuencia` VARCHAR(255) NULL,
    `descripcion` VARCHAR(255) NULL,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'activo',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tipos_limpieza_nombre_unique` (`nombre`),
    UNIQUE KEY `tipos_limpieza_clave_unique` (`clave`),
    KEY `tipos_limpieza_estado_index` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ubicaciones`
    ADD COLUMN `tipo_area_id` BIGINT UNSIGNED NULL AFTER `tipo_id`,
    ADD COLUMN `tipo_limpieza_id` BIGINT UNSIGNED NULL AFTER `tipo_area_id`;

ALTER TABLE `ubicaciones`
    ADD CONSTRAINT `ubicaciones_tipo_area_id_foreign`
        FOREIGN KEY (`tipo_area_id`) REFERENCES `tipos_area` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `ubicaciones_tipo_limpieza_id_foreign`
        FOREIGN KEY (`tipo_limpieza_id`) REFERENCES `tipos_limpieza` (`id`) ON DELETE SET NULL;

-- Catálogo inicial (mismos valores que sembró CatalogosSeeder en local; se
-- guardan en MAYÚSCULAS porque el modelo los normaliza así al guardarse).
INSERT INTO `tipos_area` (`nombre`, `clave`, `dias_limpieza`, `estado`, `created_at`, `updated_at`) VALUES
    ('ÁREA CRÍTICA', 'critica', 7, 'activo', NOW(), NOW()),
    ('ÁREA SEMI-CRÍTICA', 'semi_critica', 15, 'activo', NOW(), NOW()),
    ('ÁREA NO CRÍTICA', 'no_critica', 30, 'activo', NOW(), NOW());

INSERT INTO `tipos_limpieza` (`nombre`, `clave`, `frecuencia`, `estado`, `created_at`, `updated_at`) VALUES
    ('RUTINARIA', 'rutinaria', 'DIARIA', 'activo', NOW(), NOW()),
    ('TERMINAL', 'terminal', 'DESPUÉS DE CADA EVENTO', 'activo', NOW(), NOW()),
    ('EXHAUSTIVA', 'exhaustiva', 'SEGÚN TIPO DE ÁREA', 'activo', NOW(), NOW());


-- ----------------------------------------------------------------------------
-- 4) Registrar las migraciones como aplicadas.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_09_17_090000_create_especialidades_tecnico_tables', @siguiente_batch),
    ('2026_09_18_090000_add_baja_a_equipos', @siguiente_batch),
    ('2026_09_18_100000_create_catalogos_limpieza', @siguiente_batch);

-- Spatie no interviene aquí, pero por si el caché de configuración de
-- catálogos (si alguna vez se agrega) quedó guardado, no está de más.
DELETE FROM `cache` WHERE `key` LIKE '%spatie.permission.cache%';


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- ALTER TABLE `ubicaciones`
--     DROP FOREIGN KEY `ubicaciones_tipo_area_id_foreign`,
--     DROP FOREIGN KEY `ubicaciones_tipo_limpieza_id_foreign`,
--     DROP COLUMN `tipo_area_id`,
--     DROP COLUMN `tipo_limpieza_id`;
-- DROP TABLE IF EXISTS `tipos_limpieza`;
-- DROP TABLE IF EXISTS `tipos_area`;
--
-- ALTER TABLE `equipos`
--     DROP FOREIGN KEY `equipos_baja_por_foreign`,
--     DROP COLUMN `motivo_baja`,
--     DROP COLUMN `baja_por`,
--     DROP COLUMN `baja_en`;
--
-- DROP TABLE IF EXISTS `usuario_tipo_mantenimiento`;
-- DROP TABLE IF EXISTS `usuario_tipo_equipo`;
--
-- DELETE FROM `migrations` WHERE `migration` IN (
--     '2026_09_17_090000_create_especialidades_tecnico_tables',
--     '2026_09_18_090000_add_baja_a_equipos',
--     '2026_09_18_100000_create_catalogos_limpieza'
-- );
-- ============================================================================
