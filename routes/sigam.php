<?php

use App\Http\Controllers\Admin\RolController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Catalogo\EstadoEquipoController;
use App\Http\Controllers\Catalogo\EstadoMantenimientoController;
use App\Http\Controllers\Catalogo\MarcaController;
use App\Http\Controllers\Catalogo\MaterialController;
use App\Http\Controllers\Catalogo\PrioridadController;
use App\Http\Controllers\Catalogo\TipoAreaController;
use App\Http\Controllers\Catalogo\TipoEquipoController;
use App\Http\Controllers\Catalogo\TipoLimpiezaController;
use App\Http\Controllers\Catalogo\TipoMantenimientoController;
use App\Http\Controllers\Catalogo\TipoUbicacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\Formato\FormatoController;
use App\Http\Controllers\Inventario\EquipoController;
use App\Http\Controllers\Inventario\NormaController;
use App\Http\Controllers\Inventario\ProveedorController;
use App\Http\Controllers\Inventario\SucursalController;
use App\Http\Controllers\Inventario\UbicacionController;
use App\Http\Controllers\Mantenimiento\AsignacionController;
use App\Http\Controllers\Mantenimiento\CalendarioController;
use App\Http\Controllers\Mantenimiento\MantenimientoController;
use App\Http\Controllers\Mantenimiento\MaterialMantenimientoController;
use App\Http\Controllers\Mantenimiento\ObservacionController;
use App\Http\Controllers\Mantenimiento\PlanMantenimientoController;
use App\Http\Controllers\Mantenimiento\SolicitudMantenimientoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\Tareas\TareaController;
use App\Http\Controllers\Tareas\TareaResponsableController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la aplicación SIGAM
|--------------------------------------------------------------------------
| La autorización fina se resuelve en cada controlador con
| $this->authorize('<modulo>.<accion>') contra los permisos de spatie.
*/

Route::middleware(['auth', 'verified'])->group(function (): void {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Inventario ---------------------------------------------------
    Route::resource('sucursales', SucursalController::class)->parameters(['sucursales' => 'sucursal']);
    Route::put('sucursales/{sucursal}/reactivar', [SucursalController::class, 'restore'])->name('sucursales.restore');

    Route::resource('ubicaciones', UbicacionController::class)
        ->parameters(['ubicaciones' => 'ubicacion'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('equipos/importar/plantilla', [EquipoController::class, 'plantillaImportar'])->name('equipos.importar.plantilla');
    Route::post('equipos/importar', [EquipoController::class, 'importar'])->name('equipos.importar');
    Route::get('equipos/por-sucursal', [EquipoController::class, 'porSucursal'])->name('equipos.por_sucursal');
    Route::resource('equipos', EquipoController::class)->parameters(['equipos' => 'equipo']);
    Route::put('equipos/{equipo}/reactivar', [EquipoController::class, 'restore'])->name('equipos.restore');
    Route::get('equipos/{equipo}/qr', [EquipoController::class, 'qr'])->name('equipos.qr');
    Route::get('equipos/{equipo}/qr.json', [EquipoController::class, 'qrData'])->name('equipos.qr_data');
    Route::get('equipos/{equipo}/qr.pdf', [EquipoController::class, 'qrPdf'])->name('equipos.qr_pdf');
    Route::get('equipos/{equipo}/qr.jpg', [EquipoController::class, 'qrImagen'])->name('equipos.qr_img');
    Route::get('q/{token}', [EquipoController::class, 'escanear'])->name('equipos.escanear');

    Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedor']);
    Route::put('proveedores/{proveedor}/reactivar', [ProveedorController::class, 'restore'])->name('proveedores.restore');
    Route::resource('normas', NormaController::class)->parameters(['normas' => 'norma']);
    Route::put('normas/{norma}/reactivar', [NormaController::class, 'restore'])->name('normas.restore');

    Route::resource('formatos', FormatoController::class)->parameters(['formatos' => 'formato']);
    Route::put('formatos/{formato}/reactivar', [FormatoController::class, 'restore'])->name('formatos.restore');

    // --- Catálogos --------------------------------------------------
    foreach ([
        'catalogos/marcas' => MarcaController::class,
        'catalogos/tipos-equipo' => TipoEquipoController::class,
        'catalogos/tipos-ubicacion' => TipoUbicacionController::class,
        'catalogos/estados-equipo' => EstadoEquipoController::class,
        'catalogos/tipos-mantenimiento' => TipoMantenimientoController::class,
        'catalogos/prioridades' => PrioridadController::class,
        'catalogos/estados-mantenimiento' => EstadoMantenimientoController::class,
        'catalogos/materiales' => MaterialController::class,
        'catalogos/tipos-area' => TipoAreaController::class,
        'catalogos/tipos-limpieza' => TipoLimpiezaController::class,
    ] as $ruta => $controlador) {
        $nombre = str_replace(['catalogos/', '-'], ['catalogos.', '_'], $ruta);
        Route::get($ruta, [$controlador, 'index'])->name("{$nombre}.index");
        Route::post($ruta, [$controlador, 'store'])->name("{$nombre}.store");
        Route::put("{$ruta}/{id}", [$controlador, 'update'])->name("{$nombre}.update");
        Route::delete("{$ruta}/{id}", [$controlador, 'destroy'])->name("{$nombre}.destroy");
        Route::put("{$ruta}/{id}/activar", [$controlador, 'activar'])->name("{$nombre}.activar");
    }

    // --- Mantenimiento --------------------------------------------
    Route::resource('solicitudes', SolicitudMantenimientoController::class)
        ->parameters(['solicitudes' => 'solicitud'])
        ->only(['index', 'create', 'store', 'show', 'update']);
    Route::post('solicitudes/{solicitud}/autorizar', [SolicitudMantenimientoController::class, 'autorizar'])->name('solicitudes.autorizar');
    Route::post('solicitudes/{solicitud}/rechazar', [SolicitudMantenimientoController::class, 'rechazar'])->name('solicitudes.rechazar');

    Route::resource('mantenimientos', MantenimientoController::class)->parameters(['mantenimientos' => 'mantenimiento']);
    Route::post('mantenimientos/{mantenimiento}/transicion', [MantenimientoController::class, 'transicion'])->name('mantenimientos.transicion');
    Route::post('mantenimientos/{mantenimiento}/reprogramar', [MantenimientoController::class, 'reprogramar'])->name('mantenimientos.reprogramar');
    Route::post('mantenimientos/{mantenimiento}/asignaciones', [AsignacionController::class, 'store'])->name('mantenimientos.asignaciones.store');
    Route::delete('mantenimientos/{mantenimiento}/asignaciones/{asignacion}', [AsignacionController::class, 'destroy'])->name('mantenimientos.asignaciones.destroy');
    Route::post('mantenimientos/{mantenimiento}/observaciones', [ObservacionController::class, 'store'])->name('mantenimientos.observaciones.store');
    Route::post('mantenimientos/{mantenimiento}/materiales', [MaterialMantenimientoController::class, 'store'])->name('mantenimientos.materiales.store');
    Route::delete('mantenimientos/{mantenimiento}/materiales/{material}', [MaterialMantenimientoController::class, 'destroy'])->name('mantenimientos.materiales.destroy');

    Route::resource('planes', PlanMantenimientoController::class)->parameters(['planes' => 'plan']);
    Route::post('planes/{plan}/ocurrencias', [PlanMantenimientoController::class, 'generarOcurrencias'])->name('planes.ocurrencias.generar');
    Route::delete('planes/{plan}/ocurrencias/{ocurrencia}', [PlanMantenimientoController::class, 'destroyOcurrencia'])->name('planes.ocurrencias.destroy');
    Route::post('planes/{plan}/orden', [PlanMantenimientoController::class, 'generarOrden'])->name('planes.orden.generar');

    Route::get('calendario', [CalendarioController::class, 'index'])->name('calendario.index');

    // --- Tareas -------------------------------------------------------
    Route::resource('tareas', TareaController::class)
        ->parameters(['tareas' => 'tarea'])
        ->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
    Route::post('tareas/{tarea}/transicion', [TareaController::class, 'transicion'])->name('tareas.transicion');
    Route::post('tareas/{tarea}/responsables', [TareaResponsableController::class, 'store'])->name('tareas.responsables.store');
    Route::delete('tareas/{tarea}/responsables/{responsable}', [TareaResponsableController::class, 'destroy'])->name('tareas.responsables.destroy');

    // --- Documentos --------------------------------------------------
    Route::post('documentos', [DocumentoController::class, 'store'])->name('documentos.store');
    Route::get('documentos/{documento}/descargar', [DocumentoController::class, 'download'])->name('documentos.download');
    Route::get('documentos/{documento}/ver', [DocumentoController::class, 'previsualizar'])->name('documentos.ver');
    Route::delete('documentos/{documento}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');

    // --- Reportes ---------------------------------------------------
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/{clave}', [ReporteController::class, 'generar'])->name('reportes.generar');
    Route::get('reportes/{clave}/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');

    // --- Auditoría -------------------------------------------------
    Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::get('auditoria/exportar', [AuditoriaController::class, 'exportar'])->name('auditoria.exportar');

    // --- Notificaciones ------------------------------------------
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::get('notificaciones/no-leidas', [NotificacionController::class, 'noLeidas'])->name('notificaciones.no_leidas');
    Route::put('notificaciones/{notificacion}/leida', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leida');
    Route::put('notificaciones/leer-todas', [NotificacionController::class, 'marcarTodas'])->name('notificaciones.leer_todas');

    // --- Administración ------------------------------------------
    Route::resource('usuarios', UsuarioController::class)->parameters(['usuarios' => 'usuario']);
    Route::post('usuarios/{usuario}/restablecer-contrasena', [UsuarioController::class, 'restablecerContrasena'])->name('usuarios.restablecer_contrasena');
    Route::put('usuarios/{usuario}/reactivar', [UsuarioController::class, 'restore'])->name('usuarios.restore');

    Route::resource('roles', RolController::class)->parameters(['roles' => 'role'])->only(['index', 'show', 'store', 'update', 'destroy']);
});
