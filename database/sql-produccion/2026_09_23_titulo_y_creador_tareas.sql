-- ============================================================================
-- SIGAM — Script SQL para producción
-- Título corto y creador de la tarea (Tareas: título visible en listados +
-- "solo el superadministrador ve todas las tareas, el resto solo las suyas")
--
-- Corresponde exactamente a esta migración de Laravel (ya probada y aplicada
-- en el entorno local, 197 pruebas verde):
--   database/migrations/2026_09_23_150311_add_titulo_y_creador_a_tareas.php
--
-- ⚠️ IMPORTANTE ANTES DE CORRERLO EN PRODUCCIÓN:
--   1. Haz un respaldo completo de la base de datos primero
--      (mysqldump -u usuario -p nombre_bd > respaldo_antes_de_este_script.sql).
--   2. En MySQL los ALTER TABLE (DDL) NO son transaccionales: se confirman de
--      inmediato aunque uses START TRANSACTION/COMMIT. Si algo falla a la
--      mitad, los pasos anteriores ya habrán quedado aplicados. Por eso el
--      respaldo del punto 1 es la red de seguridad real.
--   3. Requiere que la tabla `tareas` ya exista (script previo
--      2026_09_18_modulo_tareas.sql) y que `registros_auditoria` también
--      exista (bitácora del núcleo del sistema).
--   4. Si la tabla `tareas` está vacía en producción (por ejemplo, si ya
--      corriste la limpieza de datos de prueba), los pasos 2 y 3 de abajo
--      simplemente no afectan ninguna fila — no hay problema en dejarlos.
--   5. Corre este script UNA sola vez. Al final se registra en la tabla
--      `migrations` para que `php artisan migrate` no intente aplicarlo otra vez.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1) tareas: se agregan las columnas `titulo` (nullable por ahora) y
--    `creado_por`
-- ----------------------------------------------------------------------------
ALTER TABLE `tareas`
    ADD COLUMN `titulo` VARCHAR(150) NULL AFTER `id`;

ALTER TABLE `tareas`
    ADD COLUMN `creado_por` BIGINT UNSIGNED NULL AFTER `cancelada_at`;

ALTER TABLE `tareas`
    ADD CONSTRAINT `tareas_creado_por_foreign`
        FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;


-- ----------------------------------------------------------------------------
-- 2) Rellena el título de las tareas que ya existan, a partir de su
--    descripción (los primeros 100 caracteres, igual que hace la migración
--    de Laravel).
-- ----------------------------------------------------------------------------
UPDATE `tareas`
SET `titulo` = LEFT(TRIM(`descripcion`), 100)
WHERE `titulo` IS NULL;


-- ----------------------------------------------------------------------------
-- 3) Rellena `creado_por` a partir del primer registro de auditoría "crear"
--    de cada tarea (EsAuditable ya lo guarda ahí desde que se creó el
--    módulo). Si una tarea no tiene ese registro (por ejemplo, se sembró por
--    seeder), `creado_por` se queda en NULL — es válido, la columna es
--    nullable.
-- ----------------------------------------------------------------------------
UPDATE `tareas` t
JOIN (
    SELECT `auditable_id`, MIN(`usuario_id`) AS `usuario_id`
    FROM `registros_auditoria`
    WHERE `auditable_type` = 'App\\Models\\Tarea' AND `accion` = 'crear'
    GROUP BY `auditable_id`
) ra ON ra.`auditable_id` = t.`id`
SET t.`creado_por` = ra.`usuario_id`
WHERE t.`creado_por` IS NULL;


-- ----------------------------------------------------------------------------
-- 4) Ahora que todas las filas existentes ya tienen título, se vuelve
--    obligatoria (igual que en el modelo `Tarea` y en el formulario).
-- ----------------------------------------------------------------------------
ALTER TABLE `tareas`
    MODIFY COLUMN `titulo` VARCHAR(150) NOT NULL;


-- ----------------------------------------------------------------------------
-- 5) Registrar la migración como aplicada, para que `php artisan migrate` no
--    intente correrla otra vez la próxima vez que despliegues.
-- ----------------------------------------------------------------------------
SET @siguiente_batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_09_23_150311_add_titulo_y_creador_a_tareas', @siguiente_batch);


-- ============================================================================
-- REVERSIÓN (por si algo sale mal y necesitas deshacer este script):
-- ============================================================================
-- ALTER TABLE `tareas` DROP FOREIGN KEY `tareas_creado_por_foreign`;
-- ALTER TABLE `tareas` DROP COLUMN `creado_por`;
-- ALTER TABLE `tareas` DROP COLUMN `titulo`;
--
-- DELETE FROM `migrations` WHERE `migration` = '2026_09_23_150311_add_titulo_y_creador_a_tareas';
-- ============================================================================
