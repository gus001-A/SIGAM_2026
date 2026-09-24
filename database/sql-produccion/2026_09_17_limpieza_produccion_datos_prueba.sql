-- ============================================================================
-- SIGAM — Limpieza de datos de PRUEBA en PRODUCCIÓN (Hostinger)
--
-- ⚠️ A DIFERENCIA del script de limpieza local, este NO borra todo — no
-- tengo visibilidad de qué hay realmente en tu base de producción, así que
-- este script busca por PATRONES típicos de datos de prueba (nombres que
-- contienen "prueba", "demo", "test", "audit", folios SOL-DEMO-/EQ-AUDIT-,
-- etc.) en vez de vaciar tablas completas.
--
-- CÓMO USARLO (en este orden, sin saltarte pasos):
--   1. RESPALDO COMPLETO primero, sin excepción:
--        mysqldump -u usuario -p nombre_bd > respaldo_antes_de_limpiar.sql
--   2. Corre SOLO la PARTE 1 (las consultas SELECT de abajo) y revisa la
--      lista completa de lo que va a borrar. Si ves ahí algo que SÍ es un
--      registro real que tú diste de alta, AJUSTA los patrones LIKE de la
--      PARTE 1 (agrega una exclusión `AND nombre NOT LIKE '...'`, o quita
--      esa tabla de la limpieza) y repite la revisión hasta que la lista
--      contenga solo basura de prueba.
--   3. Hasta que la PARTE 1 te muestre exactamente lo que esperas, corre la
--      PARTE 2 (los DELETE, con los MISMOS patrones que ya revisaste).
--   4. Los DELETE van en orden pensado para no chocar con llaves foráneas —
--      no cambies el orden de las secciones.
--   5. Ya que confirmes que quedó bien, considera correr también
--      `2026_09_15_mantenimiento_instalaciones.sql` si tu producción aún no
--      lo tiene (mantenimiento a instalaciones) — son cosas independientes.
--
-- Los `ALTER`/`DELETE` de MySQL NO son transaccionales: si algo sale mal a
-- medio script, lo ya borrado no se deshace solo — por eso el respaldo del
-- paso 1 es tu red de seguridad real, no un intento de "deshacer" después.
-- ============================================================================


-- ============================================================================
-- PARTE 1 — SOLO LECTURA: revisa esto ANTES de borrar nada
-- ============================================================================

-- Equipos que parecen de prueba
SELECT id, codigo_activo, descripcion, created_at FROM equipos
WHERE descripcion LIKE '%prueba%' OR descripcion LIKE '%Prueba%' OR descripcion LIKE '%PRUEBA%'
   OR descripcion LIKE '%demo%' OR descripcion LIKE '%Demo%'
   OR descripcion LIKE '%test%' OR descripcion LIKE '%Test%'
   OR codigo_activo LIKE '%PRUEBA%' OR codigo_activo LIKE '%AUDIT%' OR codigo_activo LIKE '%TEST%'
   OR codigo_activo = 'OLA';

-- Solicitudes de mantenimiento de prueba
SELECT id, folio, descripcion, created_at FROM solicitudes_mantenimiento
WHERE folio LIKE '%DEMO%' OR folio LIKE '%PRUEBA%' OR folio LIKE '%TEST%'
   OR descripcion LIKE '%prueba%' OR descripcion LIKE '%Prueba%' OR descripcion LIKE '%demo%';

-- Órdenes de mantenimiento de prueba
SELECT id, folio, problema_reportado, created_at FROM mantenimientos
WHERE folio LIKE '%DEMO%' OR folio LIKE '%PRUEBA%' OR folio LIKE '%TEST%'
   OR problema_reportado LIKE '%prueba%' OR problema_reportado LIKE '%Prueba%';

-- Planes preventivos de prueba
SELECT id, nombre, created_at FROM planes_mantenimiento
WHERE nombre LIKE '%prueba%' OR nombre LIKE '%Prueba%' OR nombre LIKE '%demo%' OR nombre LIKE '%test%';

-- Ubicaciones de prueba
SELECT id, nombre, created_at FROM ubicaciones
WHERE nombre LIKE '%prueba%' OR nombre LIKE '%Prueba%' OR nombre LIKE '%demo%' OR nombre LIKE '%test%';

-- Sucursales de prueba (revisa con cuidado — normalmente NO quieres borrar
-- tu(s) sucursal(es) real(es); solo debería aparecer aquí algo con nombre
-- literal de prueba, no tu sucursal real aunque se llame parecido)
SELECT id, codigo, nombre, created_at FROM sucursales
WHERE nombre LIKE '%prueba%' OR nombre LIKE '%Prueba%' OR nombre LIKE '%demo%' OR nombre LIKE '%test%';

-- Usuarios de prueba (revisa con MUCHO cuidado — nunca debe aparecer aquí
-- una cuenta real de alguien del hospital)
SELECT id, nombre, apellidos, email, created_at FROM usuarios
WHERE email LIKE '%test%' OR email LIKE '%prueba%' OR email LIKE '%demo%' OR email LIKE '%example.com%';


-- ============================================================================
-- PARTE 2 — DELETE (solo después de revisar y ajustar la PARTE 1 de arriba)
-- Cada DELETE usa EXACTAMENTE los mismos patrones que ya revisaste.
-- ============================================================================

-- 2.1 Hijos de "mantenimientos" que vayan a borrarse
DELETE rf FROM respuestas_formato rf
  JOIN mantenimientos m ON m.id = rf.mantenimiento_id
  WHERE m.folio LIKE '%DEMO%' OR m.folio LIKE '%PRUEBA%' OR m.folio LIKE '%TEST%'
     OR m.problema_reportado LIKE '%prueba%' OR m.problema_reportado LIKE '%Prueba%';

DELETE mm FROM materiales_mantenimiento mm
  JOIN mantenimientos m ON m.id = mm.mantenimiento_id
  WHERE m.folio LIKE '%DEMO%' OR m.folio LIKE '%PRUEBA%' OR m.folio LIKE '%TEST%'
     OR m.problema_reportado LIKE '%prueba%' OR m.problema_reportado LIKE '%Prueba%';

DELETE om FROM observaciones_mantenimiento om
  JOIN mantenimientos m ON m.id = om.mantenimiento_id
  WHERE m.folio LIKE '%DEMO%' OR m.folio LIKE '%PRUEBA%' OR m.folio LIKE '%TEST%'
     OR m.problema_reportado LIKE '%prueba%' OR m.problema_reportado LIKE '%Prueba%';

DELETE rm FROM reprogramaciones_mantenimiento rm
  JOIN mantenimientos m ON m.id = rm.mantenimiento_id
  WHERE m.folio LIKE '%DEMO%' OR m.folio LIKE '%PRUEBA%' OR m.folio LIKE '%TEST%'
     OR m.problema_reportado LIKE '%prueba%' OR m.problema_reportado LIKE '%Prueba%';

DELETE he FROM historial_estados_mantenimiento he
  JOIN mantenimientos m ON m.id = he.mantenimiento_id
  WHERE m.folio LIKE '%DEMO%' OR m.folio LIKE '%PRUEBA%' OR m.folio LIKE '%TEST%'
     OR m.problema_reportado LIKE '%prueba%' OR m.problema_reportado LIKE '%Prueba%';

DELETE am FROM asignaciones_mantenimiento am
  JOIN mantenimientos m ON m.id = am.mantenimiento_id
  WHERE m.folio LIKE '%DEMO%' OR m.folio LIKE '%PRUEBA%' OR m.folio LIKE '%TEST%'
     OR m.problema_reportado LIKE '%prueba%' OR m.problema_reportado LIKE '%Prueba%';

DELETE mn FROM mantenimiento_norma mn
  JOIN mantenimientos m ON m.id = mn.mantenimiento_id
  WHERE m.folio LIKE '%DEMO%' OR m.folio LIKE '%PRUEBA%' OR m.folio LIKE '%TEST%'
     OR m.problema_reportado LIKE '%prueba%' OR m.problema_reportado LIKE '%Prueba%';

DELETE opm FROM ocurrencias_plan_mantenimiento opm
  JOIN mantenimientos m ON m.id = opm.mantenimiento_id
  WHERE m.folio LIKE '%DEMO%' OR m.folio LIKE '%PRUEBA%' OR m.folio LIKE '%TEST%'
     OR m.problema_reportado LIKE '%prueba%' OR m.problema_reportado LIKE '%Prueba%';

-- 2.2 Documentos adjuntos a equipos/mantenimientos/normas de prueba
DELETE dr FROM documento_relacionado dr
  JOIN equipos e ON e.id = dr.relacionado_id AND dr.relacionado_type LIKE '%Equipo%'
  WHERE e.descripcion LIKE '%prueba%' OR e.descripcion LIKE '%Prueba%' OR e.descripcion LIKE '%demo%'
     OR e.codigo_activo LIKE '%PRUEBA%' OR e.codigo_activo LIKE '%AUDIT%' OR e.codigo_activo = 'OLA';

-- 2.3 Las propias órdenes/solicitudes/planes/ocurrencias de prueba
DELETE opm FROM ocurrencias_plan_mantenimiento opm
  JOIN planes_mantenimiento p ON p.id = opm.plan_id
  WHERE p.nombre LIKE '%prueba%' OR p.nombre LIKE '%Prueba%' OR p.nombre LIKE '%demo%' OR p.nombre LIKE '%test%';

DELETE FROM mantenimientos
  WHERE folio LIKE '%DEMO%' OR folio LIKE '%PRUEBA%' OR folio LIKE '%TEST%'
     OR problema_reportado LIKE '%prueba%' OR problema_reportado LIKE '%Prueba%';

DELETE FROM solicitudes_mantenimiento
  WHERE folio LIKE '%DEMO%' OR folio LIKE '%PRUEBA%' OR folio LIKE '%TEST%'
     OR descripcion LIKE '%prueba%' OR descripcion LIKE '%Prueba%' OR descripcion LIKE '%demo%';

DELETE FROM planes_mantenimiento
  WHERE nombre LIKE '%prueba%' OR nombre LIKE '%Prueba%' OR nombre LIKE '%demo%' OR nombre LIKE '%test%';

-- 2.4 Equipos de prueba (y su historial de ubicación / normas asociadas)
DELETE hu FROM historial_ubicacion_equipo hu
  JOIN equipos e ON e.id = hu.equipo_id
  WHERE e.descripcion LIKE '%prueba%' OR e.descripcion LIKE '%Prueba%' OR e.descripcion LIKE '%demo%'
     OR e.codigo_activo LIKE '%PRUEBA%' OR e.codigo_activo LIKE '%AUDIT%' OR e.codigo_activo = 'OLA';

DELETE en FROM equipo_norma en
  JOIN equipos e ON e.id = en.equipo_id
  WHERE e.descripcion LIKE '%prueba%' OR e.descripcion LIKE '%Prueba%' OR e.descripcion LIKE '%demo%'
     OR e.codigo_activo LIKE '%PRUEBA%' OR e.codigo_activo LIKE '%AUDIT%' OR e.codigo_activo = 'OLA';

DELETE FROM equipos
  WHERE descripcion LIKE '%prueba%' OR descripcion LIKE '%Prueba%' OR descripcion LIKE '%PRUEBA%'
     OR descripcion LIKE '%demo%' OR descripcion LIKE '%Demo%'
     OR descripcion LIKE '%test%' OR descripcion LIKE '%Test%'
     OR codigo_activo LIKE '%PRUEBA%' OR codigo_activo LIKE '%AUDIT%' OR codigo_activo LIKE '%TEST%'
     OR codigo_activo = 'OLA';

-- 2.5 Ubicaciones de prueba (borra primero las que no tengan hijas)
DELETE FROM ubicaciones
  WHERE (nombre LIKE '%prueba%' OR nombre LIKE '%Prueba%' OR nombre LIKE '%demo%' OR nombre LIKE '%test%')
    AND id NOT IN (SELECT padre_id FROM ubicaciones WHERE padre_id IS NOT NULL);

-- 2.6 Usuarios y sucursales de prueba — SOLO si la PARTE 1 confirmó que son
--     basura real y no cuentas/sedes reales (por eso van comentados: se
--     descomentan a mano después de revisar, nunca se corren "a ciegas").
-- DELETE FROM usuarios
--   WHERE email LIKE '%test%' OR email LIKE '%prueba%' OR email LIKE '%demo%' OR email LIKE '%example.com%';
-- DELETE FROM sucursales
--   WHERE nombre LIKE '%prueba%' OR nombre LIKE '%Prueba%' OR nombre LIKE '%demo%' OR nombre LIKE '%test%';

-- 2.7 Bitácora y notificaciones huérfanas que hayan quedado apuntando a
--     registros que ya no existen (limpieza de cierre, no afecta nada vivo)
DELETE ra FROM registros_auditoria ra
  LEFT JOIN equipos e ON ra.auditable_type LIKE '%Equipo%' AND ra.auditable_id = e.id
  LEFT JOIN mantenimientos m ON ra.auditable_type LIKE '%Mantenimiento%' AND ra.auditable_id = m.id
  LEFT JOIN solicitudes_mantenimiento s ON ra.auditable_type LIKE '%Solicitud%' AND ra.auditable_id = s.id
  LEFT JOIN planes_mantenimiento p ON ra.auditable_type LIKE '%Plan%' AND ra.auditable_id = p.id
  WHERE ra.auditable_id IS NOT NULL
    AND e.id IS NULL AND m.id IS NULL AND s.id IS NULL AND p.id IS NULL;
