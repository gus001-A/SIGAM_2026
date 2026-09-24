-- ============================================================================
-- SIGAM — Script SQL para producción
-- Módulo de Tareas completo (Propuesta técnica — anexo "TAREAS")
--
-- Corresponde exactamente a estas 5 migraciones de Laravel (ya probadas y
-- aplicadas en el entorno local, 195 pruebas verde):
--   database/migrations/2026_09_18_133920_create_tareas_table.php
--   database/migrations/2026_09_18_133924_create_tarea_responsables_table.php
--   database/migrations/2026_09_18_133928_create_historial_estados_tarea_table.php
--   database/migrations/2026_09_18_143454_add_prioridad_a_tareas.php
--   database/migrations/2026_09_18_145155_add_soft_deletes_a_tareas.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. En MySQL/MariaDB los CREATE/ALTER TABLE (DDL) NO son transaccionales:
--      se confirman de inmediato aunque uses START TRANSACTION/COMMIT. Si algo
--      falla a la mitad, los pasos anteriores ya habrán quedado aplicados.
--      Por eso el respaldo del punto 1 es la red de seguridad real.
--   3. Requiere que ya existan las tablas `usuarios` y `prioridades` (son del
--      núcleo del sistema, deberían estar desde la instalación inicial).
--   4. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo de nuevo.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) tareas — tabla principal del módulo
-- ----------------------------------------------------------------------------
CREATE TABLE `tareas` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `descripcion` TEXT NOT NULL,
    `fecha_limite` DATE NOT NULL,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    `nota_avance` TEXT NULL,
    `nota_cierre` TEXT NULL,
    `nota_cancelacion` TEXT NULL,
    `costo` DECIMAL(10,2) NULL,
    `iniciada_at` TIMESTAMP NULL DEFAULT NULL,
    `realizada_at` TIMESTAMP NULL DEFAULT NULL,
    `cancelada_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tareas_estado_fecha_limite_idx` (`estado`, `fecha_limite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 2) tarea_responsables — responsables asignados a cada tarea (uno o más)
-- ----------------------------------------------------------------------------
CREATE TABLE `tarea_responsables` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tarea_id` BIGINT UNSIGNED NOT NULL,
    `usuario_id` BIGINT UNSIGNED NOT NULL,
    `asignado_por` BIGINT UNSIGNED NULL,
    `es_principal` TINYINT(1) NOT NULL DEFAULT 0,
    `asignado_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `desasignado_at` TIMESTAMP NULL DEFAULT NULL,
    `notas` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    KEY `tarea_responsables_tarea_id_usuario_id_index` (`tarea_id`, `usuario_id`),
    CONSTRAINT `tarea_responsables_tarea_id_foreign`
        FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tarea_responsables_usuario_id_foreign`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tarea_responsables_asignado_por_foreign`
        FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 3) historial_estados_tarea — trazabilidad de cambios de estado (para el
--    auditor: quién cambió a qué estado, cuándo y con qué nota)
-- ----------------------------------------------------------------------------
CREATE TABLE `historial_estados_tarea` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tarea_id` BIGINT UNSIGNED NOT NULL,
    `estado_origen` VARCHAR(20) NULL,
    `estado_destino` VARCHAR(20) NOT NULL,
    `cambiado_por` BIGINT UNSIGNED NULL,
    `nota` VARCHAR(255) NULL,
    `cambiado_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `hist_estados_tarea_tarea_cambiado_idx` (`tarea_id`, `cambiado_at`),
    CONSTRAINT `historial_estados_tarea_tarea_id_foreign`
        FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `historial_estados_tarea_cambiado_por_foreign`
        FOREIGN KEY (`cambiado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 4) tareas: se agrega prioridad_id (catálogo de Prioridades ya existente)
-- ----------------------------------------------------------------------------
ALTER TABLE `tareas`
    ADD COLUMN `prioridad_id` BIGINT UNSIGNED NULL AFTER `fecha_limite`;

ALTER TABLE `tareas`
    ADD CONSTRAINT `tareas_prioridad_id_foreign`
        FOREIGN KEY (`prioridad_id`) REFERENCES `prioridades` (`id`) ON DELETE SET NULL;


-- ----------------------------------------------------------------------------
-- 5) tareas: se agrega deleted_at (baja lógica / eliminar tarea)
-- ----------------------------------------------------------------------------
ALTER TABLE `tareas`
    ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL;


-- ----------------------------------------------------------------------------
-- 6) Registrar las migraciones como aplicadas, para que `php artisan migrate`
--    no intente correrlas otra vez la próxima vez que despliegues.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_09_18_133920_create_tareas_table', @siguiente_batch),
    ('2026_09_18_133924_create_tarea_responsables_table', @siguiente_batch),
    ('2026_09_18_133928_create_historial_estados_tarea_table', @siguiente_batch),
    ('2026_09_18_143454_add_prioridad_a_tareas', @siguiente_batch),
    ('2026_09_18_145155_add_soft_deletes_a_tareas', @siguiente_batch);


-- ----------------------------------------------------------------------------
-- 7) Permisos nuevos del módulo (tareas.ver/crear/editar/asignar/cerrar/
--    desactivar) — inserta los que falten y los asigna a superadministrador
--    y supervisor; técnico y usuario_básico ya se resuelven en el código de
--    RolesPermisosSeeder si vuelves a sembrar, pero esto cubre producción sin
--    tener que correr el seeder completo (que podría tocar otros módulos).
--
--    ⚠️ IMPORTANTE: este proyecto renombró las tablas de spatie/permission
--    al español (ver config/permission.php): `permissions` -> `permisos` y
--    `role_has_permissions` -> `rol_tiene_permisos` (columnas `permiso_id`,
--    `rol_id`). Si tu copia de SIGAM NO tiene ese renombrado, cambia los
--    nombres de tabla/columna de vuelta a los de spatie por defecto.
-- ----------------------------------------------------------------------------
INSERT INTO `permisos` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT * FROM (SELECT 'tareas.ver' AS name, 'web' AS guard_name, NOW() AS created_at, NOW() AS updated_at
    UNION ALL SELECT 'tareas.crear', 'web', NOW(), NOW()
    UNION ALL SELECT 'tareas.editar', 'web', NOW(), NOW()
    UNION ALL SELECT 'tareas.asignar', 'web', NOW(), NOW()
    UNION ALL SELECT 'tareas.cerrar', 'web', NOW(), NOW()
    UNION ALL SELECT 'tareas.desactivar', 'web', NOW(), NOW()
) AS nuevos
WHERE NOT EXISTS (
    SELECT 1 FROM `permisos` p WHERE p.`name` = nuevos.name AND p.`guard_name` = nuevos.guard_name
);

-- superadministrador: todos los permisos nuevos.
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'superadministrador' AND r.`guard_name` = 'web'
WHERE p.`name` IN ('tareas.ver', 'tareas.crear', 'tareas.editar', 'tareas.asignar', 'tareas.cerrar', 'tareas.desactivar')
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- supervisor: todos los permisos nuevos.
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'supervisor' AND r.`guard_name` = 'web'
WHERE p.`name` IN ('tareas.ver', 'tareas.crear', 'tareas.editar', 'tareas.asignar', 'tareas.cerrar', 'tareas.desactivar')
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- técnico: ver, editar, cerrar (sus propias tareas asignadas).
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'tecnico' AND r.`guard_name` = 'web'
WHERE p.`name` IN ('tareas.ver', 'tareas.editar', 'tareas.cerrar')
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- usuario_básico: solo ver.
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'usuario_basico' AND r.`guard_name` = 'web'
WHERE p.`name` = 'tareas.ver'
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- auditor: solo ver.
INSERT INTO `rol_tiene_permisos` (`permiso_id`, `rol_id`)
SELECT p.`id`, r.`id`
FROM `permisos` p
JOIN `roles` r ON r.`name` = 'auditor' AND r.`guard_name` = 'web'
WHERE p.`name` = 'tareas.ver'
    AND NOT EXISTS (
        SELECT 1 FROM `rol_tiene_permisos` rp WHERE rp.`permiso_id` = p.`id` AND rp.`rol_id` = r.`id`
    );

-- Spatie cachea los permisos 24h (config/permission.php); sin esto, los
-- permisos nuevos no se verían hasta que el caché expirara solo.
DELETE FROM `cache` WHERE `key` LIKE '%spatie.permission.cache%';


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- DELETE rp FROM `rol_tiene_permisos` rp
--     JOIN `permisos` p ON p.`id` = rp.`permiso_id`
--     WHERE p.`name` LIKE 'tareas.%';
-- DELETE FROM `permisos` WHERE `name` LIKE 'tareas.%';
--
-- ALTER TABLE `tareas` DROP COLUMN `deleted_at`;
--
-- ALTER TABLE `tareas` DROP FOREIGN KEY `tareas_prioridad_id_foreign`;
-- ALTER TABLE `tareas` DROP COLUMN `prioridad_id`;
--
-- DROP TABLE IF EXISTS `historial_estados_tarea`;
-- DROP TABLE IF EXISTS `tarea_responsables`;
-- DROP TABLE IF EXISTS `tareas`;
--
-- DELETE FROM `migrations` WHERE `migration` IN (
--     '2026_09_18_133920_create_tareas_table',
--     '2026_09_18_133924_create_tarea_responsables_table',
--     '2026_09_18_133928_create_historial_estados_tarea_table',
--     '2026_09_18_143454_add_prioridad_a_tareas',
--     '2026_09_18_145155_add_soft_deletes_a_tareas'
-- );
-- ============================================================================
