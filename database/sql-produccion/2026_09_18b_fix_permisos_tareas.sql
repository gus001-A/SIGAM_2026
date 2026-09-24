-- ============================================================================
-- SIGAM — Corrección: permisos del módulo de Tareas en producción
--
-- El script anterior (2026_09_18_modulo_tareas.sql) creó bien las 3 tablas
-- nuevas (tareas, tarea_responsables, historial_estados_tarea), pero su
-- sección de permisos usaba los nombres de tabla EN INGLÉS por defecto de
-- Spatie (`permissions`, `role_has_permissions`). Este proyecto los tiene
-- renombrados AL ESPAÑOL en config/permission.php:
--   permissions          -> permisos
--   role_has_permissions -> rol_tiene_permisos  (columnas: permiso_id, rol_id)
-- Por eso esa parte del script falló ("tabla no existe") y los permisos
-- tareas.* nunca se crearon — es la razón exacta por la que el menú
-- "Tareas" no aparece: `puede('tareas.ver')` es falso para todos porque el
-- permiso ni siquiera existe.
--
-- Este script SOLO corrige eso. Es seguro correrlo aunque las tablas
-- `tareas`/`tarea_responsables`/`historial_estados_tarea` ya existan (no las
-- toca) y aunque se corra más de una vez (todo con NOT EXISTS).
-- ============================================================================

-- 1) Crear los 6 permisos del módulo si no existen todavía.
INSERT INTO `permisos` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT * FROM (
    SELECT 'tareas.ver' AS name, 'web' AS guard_name, NOW() AS created_at, NOW() AS updated_at
    UNION ALL SELECT 'tareas.crear', 'web', NOW(), NOW()
    UNION ALL SELECT 'tareas.editar', 'web', NOW(), NOW()
    UNION ALL SELECT 'tareas.asignar', 'web', NOW(), NOW()
    UNION ALL SELECT 'tareas.cerrar', 'web', NOW(), NOW()
    UNION ALL SELECT 'tareas.desactivar', 'web', NOW(), NOW()
) AS nuevos
WHERE NOT EXISTS (
    SELECT 1 FROM `permisos` p WHERE p.`name` = nuevos.name AND p.`guard_name` = nuevos.guard_name
);

-- 2) superadministrador: los 6 permisos.
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'superadministrador' AND r.`guard_name` = 'web'
WHERE p.`name` IN ('tareas.ver', 'tareas.crear', 'tareas.editar', 'tareas.asignar', 'tareas.cerrar', 'tareas.desactivar')
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- 3) supervisor: los 6 permisos.
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'supervisor' AND r.`guard_name` = 'web'
WHERE p.`name` IN ('tareas.ver', 'tareas.crear', 'tareas.editar', 'tareas.asignar', 'tareas.cerrar', 'tareas.desactivar')
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- 4) técnico: ver, editar, cerrar (solo sus propias tareas asignadas).
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'tecnico' AND r.`guard_name` = 'web'
WHERE p.`name` IN ('tareas.ver', 'tareas.editar', 'tareas.cerrar')
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- 5) usuario_básico: solo ver.
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'usuario_basico' AND r.`guard_name` = 'web'
WHERE p.`name` = 'tareas.ver'
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- 6) auditor: solo ver.
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'auditor' AND r.`guard_name` = 'web'
WHERE p.`name` = 'tareas.ver'
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- 7) MUY IMPORTANTE: Spatie cachea todos los permisos 24 horas (ver
--    config/permission.php -> 'cache'). Esta app usa el driver de caché de
--    base de datos, así que aunque los permisos ya queden bien en las tablas
--    de arriba, el sistema seguirá usando la lista vieja (sin "tareas.*")
--    hasta que expire el caché o lo borres tú mismo. Este DELETE lo fuerza:
DELETE FROM `cache` WHERE `key` LIKE '%spatie.permission.cache%';


-- ============================================================================
-- REVERSIÓN (por si necesitas deshacer esto):
-- ============================================================================
-- DELETE rp FROM `rol_tiene_permisos` rp
--     JOIN `permisos` p ON p.`id` = rp.`permiso_id`
--     WHERE p.`name` LIKE 'tareas.%';
-- DELETE FROM `permisos` WHERE `name` LIKE 'tareas.%';
-- DELETE FROM `cache` WHERE `key` LIKE '%spatie.permission.cache%';
-- ============================================================================
