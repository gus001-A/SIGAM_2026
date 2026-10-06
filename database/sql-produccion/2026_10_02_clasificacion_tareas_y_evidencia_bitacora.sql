-- ============================================================================
-- SIGAM — Script SQL para producción
-- Agrupa TODAS las migraciones pendientes desde el último script
-- (2026_09_25_normalizar_mayusculas.sql). Si esto no se corre, cualquier
-- "Cambiar estado" o "Reprogramar" en Órdenes de mantenimiento falla con un
-- error de SQL ("Unknown column 'documento_evidencia_id'"), porque el código
-- ya desplegado intenta escribir en una columna que la base de datos de
-- producción todavía no tiene. También es la causa de que las fotos de la
-- bitácora no se vean: la consulta ya no usa la heurística anterior y
-- depende de esta columna.
--
-- Corresponde exactamente a estas migraciones de Laravel (ya probadas en el
-- entorno local):
--   database/migrations/2026_09_28_173243_add_descripcion_a_documentos.php
--   database/migrations/2026_09_28_173245_create_materiales_tarea_table.php
--   database/migrations/2026_09_28_173245_quitar_unico_de_email_usuarios.php
--   database/migrations/2026_09_28_175246_add_documento_evidencia_a_historial_estados_tarea.php
--   database/migrations/2026_09_29_185636_create_proyectos_y_categorias_tarea_tables.php
--   database/migrations/2026_09_29_185637_add_clasificacion_a_tareas.php
--   database/migrations/2026_10_01_230256_add_documento_evidencia_a_observaciones_mantenimiento.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. En MySQL los ALTER TABLE / CREATE TABLE (DDL) NO son transaccionales:
--      se confirman de inmediato aunque uses START TRANSACTION/COMMIT. Si algo
--      falla a la mitad, los pasos anteriores ya habrán quedado aplicados. Por
--      eso el respaldo del punto 1 es la red de seguridad real.
--   3. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo otra vez.
--   4. Sobre las fotos de la bitácora de Órdenes que ya existan: este script
--      SOLO agrega la columna; no reconstruye los enlaces de los movimientos
--      que ya pasaron (esa parte de la migración de Laravel corre código PHP,
--      no es practico reproducirla 1:1 en SQL puro sin arriesgar enlazar una
--      foto equivocada a un movimiento). Resultado: los movimientos de
--      bitácora VIEJOS (de antes de correr este script) se van a ver sin foto
--      aunque sí la tuvieran adjunta — eso es solo visual, la foto original
--      sigue en "Evidencias"/el expediente del equipo. Los movimientos
--      NUEVOS (después de este script) sí van a mostrar su foto en la
--      bitácora correctamente desde el primer momento. Si más adelante
--      alguien con acceso SSH/artisan a producción puede correr
--      `php artisan migrate`, ese backfill sí se aplicaría sobre lo viejo.
--   5. El nombre `usuarios_email_unique` (paso 3) es el que Laravel genera
--      por convención para ese índice. Si tu base de datos de producción no
--      tiene ese nombre exacto, corre primero
--      `SHOW INDEX FROM usuarios WHERE Column_name = 'email';`
--      para confirmarlo y ajusta el nombre antes de correr ese paso.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) documentos: descripción libre opcional (ya existía `titulo`, que es más
--    corto / tipo etiqueta).
-- ----------------------------------------------------------------------------
ALTER TABLE `documentos`
    ADD COLUMN `descripcion` VARCHAR(255) NULL AFTER `titulo`;


-- ----------------------------------------------------------------------------
-- 2) materiales_tarea: insumos usados en una tarea (espejo de
--    materiales_mantenimiento, que ya existe). `material_id` es opcional:
--    permite capturar insumos fuera de catálogo con solo una descripción.
-- ----------------------------------------------------------------------------
CREATE TABLE `materiales_tarea` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tarea_id` BIGINT UNSIGNED NOT NULL,
    `material_id` BIGINT UNSIGNED NULL,
    `descripcion` VARCHAR(255) NULL,
    `cantidad` DECIMAL(12,2) NOT NULL DEFAULT 1,
    `unidad` VARCHAR(30) NOT NULL DEFAULT 'pza',
    `costo_unitario` DECIMAL(12,2) NULL,
    `notas` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `materiales_tarea_tarea_id_foreign` (`tarea_id`),
    KEY `materiales_tarea_material_id_foreign` (`material_id`),
    CONSTRAINT `materiales_tarea_tarea_id_foreign`
        FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `materiales_tarea_material_id_foreign`
        FOREIGN KEY (`material_id`) REFERENCES `materiales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 3) usuarios: el correo deja de ser único (decisión de negocio: dos usuarios
--    pueden compartir correo y/o teléfono). El login ya se ajustó en el
--    código para probar la contraseña contra cada fila que coincida.
-- ----------------------------------------------------------------------------
ALTER TABLE `usuarios`
    DROP INDEX `usuarios_email_unique`;


-- ----------------------------------------------------------------------------
-- 4) historial_estados_tarea: liga cada movimiento del historial de una
--    tarea con su foto de evidencia (si se adjuntó una al hacer la
--    transición), para mostrarla directo ahí.
-- ----------------------------------------------------------------------------
ALTER TABLE `historial_estados_tarea`
    ADD COLUMN `documento_evidencia_id` BIGINT UNSIGNED NULL AFTER `nota`;

ALTER TABLE `historial_estados_tarea`
    ADD CONSTRAINT `historial_estados_tarea_documento_evidencia_id_foreign`
        FOREIGN KEY (`documento_evidencia_id`) REFERENCES `documentos` (`id`) ON DELETE SET NULL;


-- ----------------------------------------------------------------------------
-- 5) proyectos / categorias_tarea: catálogos para clasificar una tarea
--    (general / por proyecto / por categoría).
-- ----------------------------------------------------------------------------
CREATE TABLE `proyectos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(255) NOT NULL,
    `clave` VARCHAR(80) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'activo',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `proyectos_nombre_unique` (`nombre`),
    UNIQUE KEY `proyectos_clave_unique` (`clave`),
    KEY `proyectos_estado_index` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categorias_tarea` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(255) NOT NULL,
    `clave` VARCHAR(80) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `estado` VARCHAR(20) NOT NULL DEFAULT 'activo',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `categorias_tarea_nombre_unique` (`nombre`),
    UNIQUE KEY `categorias_tarea_clave_unique` (`clave`),
    KEY `categorias_tarea_estado_index` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorías sugeridas de arranque (las mismas que sembró el seeder local;
-- el superadministrador puede agregar más desde Catálogos > Categorías de
-- tarea). Si ya las agregaste a mano en producción, omite este INSERT.
INSERT INTO `categorias_tarea` (`nombre`, `clave`, `estado`, `created_at`, `updated_at`) VALUES
    ('ADMINISTRATIVA', 'administrativa', 'activo', NOW(), NOW()),
    ('LIMPIEZA', 'limpieza', 'activo', NOW(), NOW()),
    ('CAPACITACIÓN', 'capacitacion', 'activo', NOW(), NOW()),
    ('DOCUMENTACIÓN', 'documentacion', 'activo', NOW(), NOW()),
    ('SEGURIDAD', 'seguridad', 'activo', NOW(), NOW());


-- ----------------------------------------------------------------------------
-- 6) tareas: clasificación (general / proyecto / categoría). Solo una de las
--    dos referencias aplica según `clasificacion`.
-- ----------------------------------------------------------------------------
ALTER TABLE `tareas`
    ADD COLUMN `clasificacion` VARCHAR(20) NOT NULL DEFAULT 'general' AFTER `prioridad_id`,
    ADD COLUMN `proyecto_id` BIGINT UNSIGNED NULL AFTER `clasificacion`,
    ADD COLUMN `categoria_tarea_id` BIGINT UNSIGNED NULL AFTER `proyecto_id`;

ALTER TABLE `tareas`
    ADD CONSTRAINT `tareas_proyecto_id_foreign`
        FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `tareas_categoria_tarea_id_foreign`
        FOREIGN KEY (`categoria_tarea_id`) REFERENCES `categorias_tarea` (`id`) ON DELETE SET NULL;


-- ----------------------------------------------------------------------------
-- 7) observaciones_mantenimiento (la "Bitácora" de una orden): liga cada
--    movimiento con su foto de evidencia. Esta es la columna que faltaba y
--    rompía "Cambiar estado" / "Reprogramar" en producción.
--    ⚠️ Ver punto 4 de las notas de arriba: este script NO reconstruye los
--    enlaces de movimientos viejos, solo agrega la columna.
-- ----------------------------------------------------------------------------
ALTER TABLE `observaciones_mantenimiento`
    ADD COLUMN `documento_evidencia_id` BIGINT UNSIGNED NULL AFTER `cuerpo`;

ALTER TABLE `observaciones_mantenimiento`
    ADD CONSTRAINT `observaciones_mantenimiento_documento_evidencia_id_foreign`
        FOREIGN KEY (`documento_evidencia_id`) REFERENCES `documentos` (`id`) ON DELETE SET NULL;


-- ----------------------------------------------------------------------------
-- 8) Registrar las migraciones como aplicadas.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_09_28_173243_add_descripcion_a_documentos', @siguiente_batch),
    ('2026_09_28_173245_create_materiales_tarea_table', @siguiente_batch),
    ('2026_09_28_173245_quitar_unico_de_email_usuarios', @siguiente_batch),
    ('2026_09_28_175246_add_documento_evidencia_a_historial_estados_tarea', @siguiente_batch),
    ('2026_09_29_185636_create_proyectos_y_categorias_tarea_tables', @siguiente_batch),
    ('2026_09_29_185637_add_clasificacion_a_tareas', @siguiente_batch),
    ('2026_10_01_230256_add_documento_evidencia_a_observaciones_mantenimiento', @siguiente_batch);


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- ALTER TABLE `observaciones_mantenimiento`
--     DROP FOREIGN KEY `observaciones_mantenimiento_documento_evidencia_id_foreign`,
--     DROP COLUMN `documento_evidencia_id`;
--
-- ALTER TABLE `tareas`
--     DROP FOREIGN KEY `tareas_proyecto_id_foreign`,
--     DROP FOREIGN KEY `tareas_categoria_tarea_id_foreign`,
--     DROP COLUMN `clasificacion`,
--     DROP COLUMN `proyecto_id`,
--     DROP COLUMN `categoria_tarea_id`;
--
-- DROP TABLE IF EXISTS `categorias_tarea`;
-- DROP TABLE IF EXISTS `proyectos`;
--
-- ALTER TABLE `historial_estados_tarea`
--     DROP FOREIGN KEY `historial_estados_tarea_documento_evidencia_id_foreign`,
--     DROP COLUMN `documento_evidencia_id`;
--
-- ALTER TABLE `usuarios`
--     ADD UNIQUE `usuarios_email_unique` (`email`);
--
-- DROP TABLE IF EXISTS `materiales_tarea`;
--
-- ALTER TABLE `documentos`
--     DROP COLUMN `descripcion`;
--
-- DELETE FROM `migrations` WHERE `migration` IN (
--     '2026_09_28_173243_add_descripcion_a_documentos',
--     '2026_09_28_173245_create_materiales_tarea_table',
--     '2026_09_28_173245_quitar_unico_de_email_usuarios',
--     '2026_09_28_175246_add_documento_evidencia_a_historial_estados_tarea',
--     '2026_09_29_185636_create_proyectos_y_categorias_tarea_tables',
--     '2026_09_29_185637_add_clasificacion_a_tareas',
--     '2026_10_01_230256_add_documento_evidencia_a_observaciones_mantenimiento'
-- );
-- ============================================================================
