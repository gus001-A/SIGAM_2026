-- ============================================================================
-- SIGAM — Script SQL para producción
-- Comentario de supervisión sobre un MOVIMIENTO del historial de una tarea
-- (no sobre la tarea en general): va "dentro" de ese movimiento, igual que
-- la nota que escribió el responsable al hacerlo. Solo lo escriben
-- supervisores y auditores; solo lo ven ellos mismos y los responsables
-- activos de esa tarea — nadie más. No se edita ni se borra.
--
-- Corresponde exactamente a esta migración de Laravel (ya probada en local):
--   database/migrations/2026_10_09_100000_create_comentarios_tarea_table.php
-- Y al permiso nuevo `tareas.comentar` (database/seeders/RolesPermisosSeeder.php).
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo otra vez.
--   3. Este script asume que ya corriste antes:
--      database/sql-produccion/2026_10_07_rediseno_estados_tareas_ordenes.sql
--      (necesitas el rol "auditor" ya creado).
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) Tabla de comentarios de supervisión, ligada al movimiento del historial.
-- ----------------------------------------------------------------------------
CREATE TABLE `comentarios_tarea` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tarea_id` BIGINT UNSIGNED NOT NULL,
    `historial_estado_tarea_id` BIGINT UNSIGNED NOT NULL,
    `usuario_id` BIGINT UNSIGNED NOT NULL,
    `cuerpo` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `comentarios_tarea_historial_estado_tarea_id_created_at_idx` (`historial_estado_tarea_id`, `created_at`),
    KEY `comentarios_tarea_tarea_id_foreign` (`tarea_id`),
    KEY `comentarios_tarea_usuario_id_foreign` (`usuario_id`),
    CONSTRAINT `comentarios_tarea_tarea_id_foreign`
        FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comentarios_tarea_historial_estado_tarea_id_foreign`
        FOREIGN KEY (`historial_estado_tarea_id`) REFERENCES `historial_estados_tarea` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comentarios_tarea_usuario_id_foreign`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 2) Permiso nuevo `tareas.comentar`, asignado a Supervisor, Auditor y
--    Superadministrador (quien ya tiene todos los permisos).
-- ----------------------------------------------------------------------------
INSERT INTO `permisos` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'tareas.comentar', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permisos` WHERE `name` = 'tareas.comentar');

INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.id, r.id
FROM `permisos` p
JOIN `roles` r ON r.name IN ('supervisor', 'auditor', 'superadministrador')
WHERE p.name = 'tareas.comentar'
  AND NOT EXISTS (
      SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.permiso_id = p.id AND rp.rol_id = r.id
  );


-- ----------------------------------------------------------------------------
-- 3) Registrar la migración como aplicada.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_10_09_100000_create_comentarios_tarea_table', @siguiente_batch);


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- DELETE rp FROM `rol_tiene_permisos` rp
--     JOIN `permisos` p ON p.id = rp.permiso_id
--     WHERE p.name = 'tareas.comentar';
-- DELETE FROM `permisos` WHERE `name` = 'tareas.comentar';
--
-- DROP TABLE IF EXISTS `comentarios_tarea`;
--
-- DELETE FROM `migrations` WHERE `migration` = '2026_10_09_100000_create_comentarios_tarea_table';
-- ============================================================================
