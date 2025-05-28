<?php
/**
 * Sistema de Gestión Empresarial - TiendaDB
 * Panel Administrativo Profesional
 */

require_once 'config.php';

// Procesar peticiones AJAX
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add_proveedor':
            $datos = limpiarDatos($_POST);
            $error = validarRequeridos($datos, ['nombre', 'contacto', 'ciudad']);
            if ($error) {
                respuestaJSON(['success' => false, 'message' => $error]);
            }
            $success = insertar('Proveedores', [
                'nombre' => $datos['nombre'],
                'contacto' => $datos['contacto'],
                'direccion' => $datos['direccion'],
                'ciudad' => $datos['ciudad']
            ]);
            respuestaJSON(['success' => $success]);
            break;
            
        case 'add_producto':
            $datos = limpiarDatos($_POST);
            $error = validarRequeridos($datos, ['nombre', 'categoria', 'precio_unitario', 'cantidad_stock', 'proveedor_id']);
            if ($error) {
                respuestaJSON(['success' => false, 'message' => $error]);
            }
            $success = insertar('Productos', [
                'nombre' => $datos['nombre'],
                'categoria' => $datos['categoria'],
                'precio_unitario' => $datos['precio_unitario'],
                'cantidad_stock' => $datos['cantidad_stock'],
                'proveedor_id' => $datos['proveedor_id']
            ]);
            respuestaJSON(['success' => $success]);
            break;
            
        case 'add_cliente':
            $datos = limpiarDatos($_POST);
            $error = validarRequeridos($datos, ['nombre', 'documento']);
            if ($error) {
                respuestaJSON(['success' => false, 'message' => $error]);
            }
            $success = insertar('Clientes', [
                'nombre' => $datos['nombre'],
                'documento' => $datos['documento'],
                'correo' => $datos['correo'],
                'telefono' => $datos['telefono']
            ]);
            respuestaJSON(['success' => $success]);
            break;
            
        case 'add_empleado':
            $datos = limpiarDatos($_POST);
            $error = validarRequeridos($datos, ['nombre', 'documento']);
            if ($error) {
                respuestaJSON(['success' => false, 'message' => $error]);
            }
            $success = insertar('Empleados', [
                'nombre' => $datos['nombre'],
                'documento' => $datos['documento'],
                'correo' => $datos['correo']
            ]);
            respuestaJSON(['success' => $success]);
            break;
            
        case 'registrar_venta':
            $pdo = conectarDB();
            try {
                $pdo->beginTransaction();
                
                // Insertar venta
                $stmt = $pdo->prepare("INSERT INTO Ventas (cliente_id, empleado_id) VALUES (?, ?)");
                $stmt->execute([$_POST['cliente_id'], $_POST['empleado_id']]);
                $venta_id = $pdo->lastInsertId();
                
                // Insertar detalles
                $productos = json_decode($_POST['productos'], true);
                foreach ($productos as $producto) {
                    $stmt = $pdo->prepare("INSERT INTO DetalleVenta (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$venta_id, $producto['id'], $producto['cantidad'], $producto['precio']]);
                    
                    // Actualizar stock
                    $stmt = $pdo->prepare("UPDATE Productos SET cantidad_stock = cantidad_stock - ? WHERE id = ?");
                    $stmt->execute([$producto['cantidad'], $producto['id']]);
                }
                
                $pdo->commit();
                respuestaJSON(['success' => true, 'venta_id' => $venta_id]);
            } catch(Exception $e) {
                $pdo->rollback();
                respuestaJSON(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'get_productos':
            $sql = "SELECT p.*, pr.nombre as proveedor_nombre FROM Productos p LEFT JOIN Proveedores pr ON p.proveedor_id = pr.id ORDER BY p.nombre";
            $stmt = ejecutarConsulta($sql);
            respuestaJSON($stmt->fetchAll());
            break;
            
        case 'get_clientes':
            respuestaJSON(obtenerTodos('Clientes'));
            break;
            
        case 'get_empleados':
            respuestaJSON(obtenerTodos('Empleados'));
            break;
            
        case 'get_proveedores':
            respuestaJSON(obtenerTodos('Proveedores'));
            break;
            
        case 'stock_bajo':
            $limite = $_POST['limite'] ?? 10;
            $sql = "SELECT p.*, pr.nombre as proveedor_nombre FROM Productos p LEFT JOIN Proveedores pr ON p.proveedor_id = pr.id WHERE p.cantidad_stock <= ? ORDER BY p.cantidad_stock ASC";
            $stmt = ejecutarConsulta($sql, [$limite]);
            respuestaJSON($stmt->fetchAll());
            break;
            
        case 'top_productos':
            $sql = "SELECT p.nombre, SUM(dv.cantidad) as total_vendido, SUM(dv.cantidad * dv.precio_unitario) as total_ingresos
                    FROM DetalleVenta dv 
                    JOIN Productos p ON dv.producto_id = p.id 
                    GROUP BY p.id, p.nombre 
                    ORDER BY total_vendido DESC 
                    LIMIT 5";
            $stmt = ejecutarConsulta($sql);
            respuestaJSON($stmt->fetchAll());
            break;
            
        case 'ventas_cliente':
            $sql = "SELECT c.nombre, c.documento, COUNT(v.id) as total_compras, 
                           COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) as total_gastado
                    FROM Clientes c 
                    LEFT JOIN Ventas v ON c.id = v.cliente_id 
                    LEFT JOIN DetalleVenta dv ON v.id = dv.venta_id 
                    GROUP BY c.id, c.nombre, c.documento 
                    ORDER BY total_gastado DESC";
            $stmt = ejecutarConsulta($sql);
            respuestaJSON($stmt->fetchAll());
            break;
            
        case 'ventas_fecha':
            $sql = "SELECT v.id, v.fecha, c.nombre as cliente, e.nombre as empleado,
                           SUM(dv.cantidad * dv.precio_unitario) as total
                    FROM Ventas v 
                    JOIN Clientes c ON v.cliente_id = c.id 
                    JOIN Empleados e ON v.empleado_id = e.id 
                    JOIN DetalleVenta dv ON v.id = dv.venta_id 
                    WHERE DATE(v.fecha) BETWEEN ? AND ? 
                    GROUP BY v.id 
                    ORDER BY v.fecha DESC";
            $stmt = ejecutarConsulta($sql, [$_POST['fecha_inicio'], $_POST['fecha_fin']]);
            respuestaJSON($stmt->fetchAll());
            break;
            
        // FUNCIONES DE EDICIÓN
        case 'get_producto_by_id':
            $producto = obtenerPorId('Productos', $_POST['id']);
            respuestaJSON($producto);
            break;
            
        case 'update_producto':
            $datos = limpiarDatos($_POST);
            $error = validarRequeridos($datos, ['id', 'nombre', 'categoria', 'precio_unitario', 'cantidad_stock', 'proveedor_id']);
            if ($error) {
                respuestaJSON(['success' => false, 'message' => $error]);
            }
            $pdo = conectarDB();
            $sql = "UPDATE Productos SET nombre=?, categoria=?, precio_unitario=?, cantidad_stock=?, proveedor_id=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $datos['nombre'], $datos['categoria'], $datos['precio_unitario'], 
                $datos['cantidad_stock'], $datos['proveedor_id'], $datos['id']
            ]);
            respuestaJSON(['success' => $success]);
            break;
            
        case 'delete_producto':
            // Verificar si tiene ventas asociadas
            $pdo = conectarDB();
            $check = $pdo->prepare("SELECT COUNT(*) as count FROM DetalleVenta WHERE producto_id = ?");
            $check->execute([$_POST['id']]);
            $result = $check->fetch();
            
            if ($result['count'] > 0) {
                respuestaJSON(['success' => false, 'message' => 'No se puede eliminar: producto tiene ventas asociadas']);
            } else {
                $stmt = $pdo->prepare("DELETE FROM Productos WHERE id = ?");
                $success = $stmt->execute([$_POST['id']]);
                respuestaJSON(['success' => $success]);
            }
            break;
            
        case 'update_cliente':
            $datos = limpiarDatos($_POST);
            $error = validarRequeridos($datos, ['id', 'nombre', 'documento']);
            if ($error) {
                respuestaJSON(['success' => false, 'message' => $error]);
            }
            $pdo = conectarDB();
            $sql = "UPDATE Clientes SET nombre=?, documento=?, correo=?, telefono=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$datos['nombre'], $datos['documento'], $datos['correo'], $datos['telefono'], $datos['id']]);
            respuestaJSON(['success' => $success]);
            break;
            
        case 'delete_cliente':
            // Verificar si tiene ventas asociadas
            $pdo = conectarDB();
            $check = $pdo->prepare("SELECT COUNT(*) as count FROM Ventas WHERE cliente_id = ?");
            $check->execute([$_POST['id']]);
            $result = $check->fetch();
            
            if ($result['count'] > 0) {
                respuestaJSON(['success' => false, 'message' => 'No se puede eliminar: cliente tiene ventas asociadas']);
            } else {
                $stmt = $pdo->prepare("DELETE FROM Clientes WHERE id = ?");
                $success = $stmt->execute([$_POST['id']]);
                respuestaJSON(['success' => $success]);
            }
            break;
            
        case 'update_proveedor':
            $datos = limpiarDatos($_POST);
            $error = validarRequeridos($datos, ['id', 'nombre', 'contacto', 'ciudad']);
            if ($error) {
                respuestaJSON(['success' => false, 'message' => $error]);
            }
            $pdo = conectarDB();
            $sql = "UPDATE Proveedores SET nombre=?, contacto=?, direccion=?, ciudad=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$datos['nombre'], $datos['contacto'], $datos['direccion'], $datos['ciudad'], $datos['id']]);
            respuestaJSON(['success' => $success]);
            break;
            
        case 'delete_proveedor':
            // Verificar si tiene productos asociados
            $pdo = conectarDB();
            $check = $pdo->prepare("SELECT COUNT(*) as count FROM Productos WHERE proveedor_id = ?");
            $check->execute([$_POST['id']]);
            $result = $check->fetch();
            
            if ($result['count'] > 0) {
                respuestaJSON(['success' => false, 'message' => 'No se puede eliminar: proveedor tiene productos asociados']);
            } else {
                $stmt = $pdo->prepare("DELETE FROM Proveedores WHERE id = ?");
                $success = $stmt->execute([$_POST['id']]);
                respuestaJSON(['success' => $success]);
            }
            break;
            
        case 'update_empleado':
            $datos = limpiarDatos($_POST);
            $error = validarRequeridos($datos, ['id', 'nombre', 'documento']);
            if ($error) {
                respuestaJSON(['success' => false, 'message' => $error]);
            }
            $pdo = conectarDB();
            $sql = "UPDATE Empleados SET nombre=?, documento=?, correo=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$datos['nombre'], $datos['documento'], $datos['correo'], $datos['id']]);
            respuestaJSON(['success' => $success]);
            break;
            
        case 'delete_empleado':
            // Verificar si tiene ventas asociadas
            $pdo = conectarDB();
            $check = $pdo->prepare("SELECT COUNT(*) as count FROM Ventas WHERE empleado_id = ?");
            $check->execute([$_POST['id']]);
            $result = $check->fetch();
            
            if ($result['count'] > 0) {
                respuestaJSON(['success' => false, 'message' => 'No se puede eliminar: empleado tiene ventas asociadas']);
            } else {
                $stmt = $pdo->prepare("DELETE FROM Empleados WHERE id = ?");
                $success = $stmt->execute([$_POST['id']]);
                respuestaJSON(['success' => $success]);
            }
            break;
            
        case 'get_dashboard_stats':
            $stats = [];
            
            // Contar productos
            $stmt = ejecutarConsulta("SELECT COUNT(*) as total FROM Productos");
            $stats['productos'] = $stmt->fetch()['total'];
            
            // Contar clientes
            $stmt = ejecutarConsulta("SELECT COUNT(*) as total FROM Clientes");
            $stats['clientes'] = $stmt->fetch()['total'];
            
            // Contar empleados
            $stmt = ejecutarConsulta("SELECT COUNT(*) as total FROM Empleados");
            $stats['empleados'] = $stmt->fetch()['total'];
            
            // Ventas del mes
            $stmt = ejecutarConsulta("SELECT COUNT(*) as total FROM Ventas WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())");
            $stats['ventas_mes'] = $stmt->fetch()['total'];
            
            // Productos con stock bajo
            $stmt = ejecutarConsulta("SELECT COUNT(*) as total FROM Productos WHERE cantidad_stock <= 10");
            $stats['stock_bajo'] = $stmt->fetch()['total'];
            
            // Ingresos del mes
            $stmt = ejecutarConsulta("SELECT COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) as total FROM DetalleVenta dv JOIN Ventas v ON dv.venta_id = v.id WHERE MONTH(v.fecha) = MONTH(CURRENT_DATE()) AND YEAR(v.fecha) = YEAR(CURRENT_DATE())");
            $stats['ingresos_mes'] = $stmt->fetch()['total'];
            
            respuestaJSON($stats);
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TiendaDB - Sistema de Gestión Empresarial</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>



<body>
    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-logo">
                <i class="fas fa-store"></i>
                Ferretera Rodriguez
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="#" class="nav-link active" onclick="mostrarSeccion('dashboard')" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="mostrarSeccion('productos')" data-section="productos">
                    <i class="fas fa-boxes"></i>
                    Inventario
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="mostrarSeccion('ventas')" data-section="ventas">
                    <i class="fas fa-cash-register"></i>
                    Punto de Venta
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="mostrarSeccion('clientes')" data-section="clientes">
                    <i class="fas fa-users"></i>
                    Clientes
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="mostrarSeccion('empleados')" data-section="empleados">
                    <i class="fas fa-user-tie"></i>
                    Empleados
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="mostrarSeccion('proveedores')" data-section="proveedores">
                    <i class="fas fa-truck"></i>
                    Proveedores
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="mostrarSeccion('reportes')" data-section="reportes">
                    <i class="fas fa-chart-line"></i>
                    Reportes & Analytics
                </a>
            </div>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOP HEADER -->
        <div class="top-header">
            <div class="breadcrumb-container">
                <button class="btn-light btn-admin" onclick="toggleSidebar()" style="display: none;" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title" id="pageTitle">Dashboard</h1>
            </div>
            
            <div class="header-actions">
                <div class="user-info">
                    <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                    <span>Administrador</span>
                </div>
                <button class="btn-primary btn-admin" onclick="mostrarSeccion('ventas')">
                    <i class="fas fa-plus"></i>
                    Nueva Venta
                </button>
            </div>
        </div>

        <!-- CONTENT AREA -->
        <div class="content-area">
            <!-- DASHBOARD -->
            <div id="dashboard" class="section-content active">
                <!-- STATS CARDS -->
                <div class="stats-grid">
                    <div class="stat-card primary" onclick="mostrarSeccion('productos')">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="stat-productos">0</h3>
                                <p>Productos en Inventario</p>
                            </div>
                            <i class="fas fa-boxes stat-icon primary"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card success" onclick="mostrarSeccion('clientes')">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="stat-clientes">0</h3>
                                <p>Clientes Registrados</p>
                            </div>
                            <i class="fas fa-users stat-icon success"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card warning" onclick="mostrarSeccion('reportes')">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="stat-stock-bajo">0</h3>
                                <p>Productos Stock Bajo</p>
                            </div>
                            <i class="fas fa-exclamation-triangle stat-icon warning"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="stat-ventas-mes">0</h3>
                                <p>Ventas Este Mes</p>
                            </div>
                            <i class="fas fa-chart-line stat-icon danger"></i>
                        </div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="admin-card">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin">
                            <i class="fas fa-bolt"></i>
                            Acciones Rápidas
                        </h3>
                    </div>
                    <div class="card-body-admin">
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button class="btn-primary btn-admin" onclick="mostrarModal('productoModal')">
                                <i class="fas fa-plus"></i>
                                Nuevo Producto
                            </button>
                            <button class="btn-success btn-admin" onclick="mostrarSeccion('ventas')">
                                <i class="fas fa-cash-register"></i>
                                Registrar Venta
                            </button>
                            <button class="button is-warning btn-admin" onclick="mostrarSeccion('clientes')">
                                <i class="fas fa-user-plus"></i>
                                Nuevo Cliente
                            </button>
                            <button class="btn-light btn-admin" onclick="mostrarSeccion('reportes')">
                                <i class="fas fa-file-export"></i>
                                Generar Reporte
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RECENT ACTIVITY -->
                <div class="admin-card">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin">
                            <i class="fas fa-clock"></i>
                            Actividad Reciente
                        </h3>
                    </div>
                    <div class="card-body-admin">
                        <div id="actividad-reciente">
                            <p style="color: var(--text-muted); text-align: center; padding: 2rem;">
                                <i class="fas fa-info-circle"></i>
                                Las actividades recientes aparecerán aquí
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCTOS -->
            <div id="productos" class="section-content">
                <div class="admin-card">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin">
                            <i class="fas fa-boxes"></i>
                            Gestión de Inventario
                        </h3>
                    <button class="btn-primary btn-admin" onclick="mostrarModal('productoModal')">
                        <i class="fas fa-plus"></i>
                        Nuevo Producto
                    </button>
                    <button class="btn-link btn-admin ml-2" onclick="mostrarSeccion('proveedores')">
                        <i class="fas fa-truck"></i>
                        Ver Proveedores
                    </button>
                    </div>
                    <div class="card-body-admin">
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Proveedor</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaProductos">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VENTAS -->
            <div id="ventas" class="section-content">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <!-- FORMULARIO DE VENTA -->
                    <div class="admin-card">
                        <div class="card-header-admin">
                            <h3 class="card-title-admin">
                                <i class="fas fa-cash-register"></i>
                                Punto de Venta
                            </h3>
                        </div>
                        <div class="card-body-admin">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Cliente</label>
                                    <select class="form-control" id="ventaCliente">
                                        <option value="">Seleccionar cliente</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Empleado</label>
                                    <select class="form-control" id="ventaEmpleado">
                                        <option value="">Seleccionar empleado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Producto</label>
                                <select class="form-control" id="productoSelect">
                                    <option value="">Seleccionar producto</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Cantidad</label>
                                <input class="form-control" type="number" id="cantidadInput" min="1" value="1">
                            </div>
                            
                            <button class="btn-success btn-admin" onclick="agregarProducto()">
                                <i class="fas fa-plus"></i>
                                Agregar al Carrito
                            </button>
                        </div>
                    </div>
                    
                    <!-- CARRITO -->
                    <div class="admin-card">
                        <div class="card-header-admin">
                            <h3 class="card-title-admin">
                                <i class="fas fa-shopping-cart"></i>
                                Carrito de Compras
                            </h3>
                        </div>
                        <div class="card-body-admin">
                            <div id="carrito" style="min-height: 200px;">
                                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">
                                    <i class="fas fa-shopping-cart" style="font-size: 2rem; opacity: 0.3;"></i><br>
                                    Carrito vacío
                                </p>
                            </div>
                            <hr style="margin: 1.5rem 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <strong style="font-size: 1.25rem;">Total:</strong>
                                <strong style="font-size: 1.5rem; color: var(--success-color);">$<span id="total">0.00</span></strong>
                            </div>
                            <button class="btn-primary btn-admin" style="width: 100%;" onclick="procesarVenta()">
                                <i class="fas fa-credit-card"></i>
                                Procesar Venta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CLIENTES -->
            <div id="clientes" class="section-content">
                <div class="admin-card">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin">
                            <i class="fas fa-users"></i>
                            Gestión de Clientes
                        </h3>
                        <button class="btn-primary btn-admin" onclick="mostrarFormularioCliente()">
                            <i class="fas fa-user-plus"></i>
                            Nuevo Cliente
                        </button>
                    </div>
                    <div class="card-body-admin">
                        <!-- Filtros y búsqueda -->
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 250px;">
                                <input class="form-control" type="text" id="buscarCliente" placeholder="🔍 Buscar cliente por nombre o documento..." onkeyup="filtrarClientes()">
                            </div>
                            <div>
                                <select class="form-control" id="filtroClientes" onchange="filtrarClientes()">
                                    <option value="">Todos los clientes</option>
                                    <option value="con_email">Con email</option>
                                    <option value="sin_email">Sin email</option>
                                    <option value="con_telefono">Con teléfono</option>
                                    <option value="sin_telefono">Sin teléfono</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Estadísticas rápidas -->
                        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
                            <div class="stat-card primary" style="padding: 1rem;">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 id="total-clientes">0</h3>
                                        <p style="font-size: 0.8rem;">Total Clientes</p>
                                    </div>
                                    <i class="fas fa-users stat-icon primary" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="stat-card success" style="padding: 1rem;">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 id="clientes-activos">0</h3>
                                        <p style="font-size: 0.8rem;">Con Compras</p>
                                    </div>
                                    <i class="fas fa-shopping-cart stat-icon success" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="stat-card warning" style="padding: 1rem;">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 id="clientes-email">0</h3>
                                        <p style="font-size: 0.8rem;">Con Email</p>
                                    </div>
                                    <i class="fas fa-envelope stat-icon warning" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de clientes -->
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th onclick="ordenarClientes('id')" style="cursor: pointer;">
                                            ID <i class="fas fa-sort"></i>
                                        </th>
                                        <th onclick="ordenarClientes('nombre')" style="cursor: pointer;">
                                            Nombre <i class="fas fa-sort"></i>
                                        </th>
                                        <th onclick="ordenarClientes('documento')" style="cursor: pointer;">
                                            Documento <i class="fas fa-sort"></i>
                                        </th>
                                        <th>Contacto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaClientes">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Formulario de cliente (inicialmente oculto) -->
                <div id="formularioCliente" class="admin-card" style="display: none;">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin" id="tituloFormCliente">
                            <i class="fas fa-user-plus"></i>
                            Nuevo Cliente
                        </h3>
                        <button class="btn-light btn-admin" onclick="cancelarFormularioCliente()">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                    </div>
                    <div class="card-body-admin">
                        <form id="clienteForm">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Nombre Completo *
                                    </label>
                                    <input class="form-control" type="text" name="nombre" required placeholder="Ej: María García López">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-id-card"></i> Documento de Identidad *
                                    </label>
                                    <input class="form-control" type="text" name="documento" required placeholder="Ej: 12345678">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Correo Electrónico
                                    </label>
                                    <input class="form-control" type="email" name="correo" placeholder="ejemplo@correo.com">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input class="form-control" type="text" name="telefono" placeholder="300-123-4567">
                                </div>
                            </div>
                            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                                <button type="button" class="btn-light btn-admin" onclick="cancelarFormularioCliente()">
                                    <i class="fas fa-times"></i>
                                    Cancelar
                                </button>
                                <button type="button" class="btn-success btn-admin" onclick="guardarCliente()">
                                    <i class="fas fa-save"></i>
                                    <span id="textoBotonCliente">Guardar Cliente</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- EMPLEADOS -->
            <div id="empleados" class="section-content">
                <div class="admin-card">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin">
                            <i class="fas fa-user-tie"></i>
                            Gestión de Empleados
                        </h3>
                        <button class="btn-primary btn-admin" onclick="mostrarFormularioEmpleado()">
                            <i class="fas fa-id-badge"></i>
                            Nuevo Empleado
                        </button>
                    </div>
                    <div class="card-body-admin">
                        <!-- Filtros -->
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 250px;">
                                <input class="form-control" type="text" id="buscarEmpleado" placeholder="🔍 Buscar empleado por nombre o documento..." onkeyup="filtrarEmpleados()">
                            </div>
                            <div>
                                <select class="form-control" id="filtroEmpleados" onchange="filtrarEmpleados()">
                                    <option value="">Todos los empleados</option>
                                    <option value="activos">Empleados activos</option>
                                    <option value="con_ventas">Con ventas registradas</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
                            <div class="stat-card primary" style="padding: 1rem;">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 id="total-empleados">0</h3>
                                        <p style="font-size: 0.8rem;">Total Empleados</p>
                                    </div>
                                    <i class="fas fa-user-tie stat-icon primary" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="stat-card success" style="padding: 1rem;">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 id="empleados-ventas">0</h3>
                                        <p style="font-size: 0.8rem;">Con Ventas</p>
                                    </div>
                                    <i class="fas fa-chart-line stat-icon success" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de empleados -->
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Documento</th>
                                        <th>Correo</th>
                                        <th>Ventas</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaEmpleados">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Formulario de empleado -->
                <div id="formularioEmpleado" class="admin-card" style="display: none;">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin" id="tituloFormEmpleado">
                            <i class="fas fa-id-badge"></i>
                            Nuevo Empleado
                        </h3>
                        <button class="btn-light btn-admin" onclick="cancelarFormularioEmpleado()">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                    </div>
                    <div class="card-body-admin">
                        <form id="empleadoForm">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Nombre Completo *
                                    </label>
                                    <input class="form-control" type="text" name="nombre" required placeholder="Ej: Carlos Rodríguez">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-id-card"></i> Documento de Identidad *
                                    </label>
                                    <input class="form-control" type="text" name="documento" required placeholder="Ej: 87654321">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i> Correo Corporativo
                                    </label>
                                    <input class="form-control" type="email" name="correo" placeholder="empleado@empresa.com">
                                </div>
                            </div>
                            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                                <button type="button" class="btn-light btn-admin" onclick="cancelarFormularioEmpleado()">
                                    <i class="fas fa-times"></i>
                                    Cancelar
                                </button>
                                <button type="button" class="btn-success btn-admin" onclick="guardarEmpleado()">
                                    <i class="fas fa-save"></i>
                                    <span id="textoBotonEmpleado">Guardar Empleado</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- PROVEEDORES -->
            <div id="proveedores" class="section-content">
                <div class="admin-card">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin">
                            <i class="fas fa-truck"></i>
                            Gestión de Proveedores
                        </h3>
                        <button class="btn-primary btn-admin" onclick="mostrarFormularioProveedor()">
                            <i class="fas fa-plus"></i>
                            Nuevo Proveedor
                        </button>
                    </div>
                    <div class="card-body-admin">
                        <!-- Filtros -->
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 250px;">
                                <input class="form-control" type="text" id="buscarProveedor" placeholder="🔍 Buscar proveedor por nombre o ciudad..." onkeyup="filtrarProveedores()">
                            </div>
                            <div>
                                <select class="form-control" id="filtroProveedores" onchange="filtrarProveedores()">
                                    <option value="">Todos los proveedores</option>
                                    <option value="con_productos">Con productos</option>
                                    <option value="sin_productos">Sin productos</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
                            <div class="stat-card primary" style="padding: 1rem;">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 id="total-proveedores">0</h3>
                                        <p style="font-size: 0.8rem;">Total Proveedores</p>
                                    </div>
                                    <i class="fas fa-truck stat-icon primary" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="stat-card success" style="padding: 1rem;">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 id="proveedores-productos">0</h3>
                                        <p style="font-size: 0.8rem;">Con Productos</p>
                                    </div>
                                    <i class="fas fa-boxes stat-icon success" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                            <div class="stat-card info" style="padding: 1rem;">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 id="ciudades-proveedores">0</h3>
                                        <p style="font-size: 0.8rem;">Ciudades</p>
                                    </div>
                                    <i class="fas fa-map-marker-alt stat-icon info" style="font-size: 1.5rem;"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de proveedores -->
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Empresa</th>
                                        <th>Contacto</th>
                                        <th>Ubicación</th>
                                        <th>Productos</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaProveedores">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Formulario de proveedor -->
                <div id="formularioProveedor" class="admin-card" style="display: none;">
                    <div class="card-header-admin">
                        <h3 class="card-title-admin" id="tituloFormProveedor">
                            <i class="fas fa-plus"></i>
                            Nuevo Proveedor
                        </h3>
                        <button class="btn-light btn-admin" onclick="cancelarFormularioProveedor()">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                    </div>
                    <div class="card-body-admin">
                        <form id="proveedorForm">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-building"></i> Nombre de la Empresa *
                                    </label>
                                    <input class="form-control" type="text" name="nombre" required placeholder="Ej: Distribuidora ABC">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i> Persona de Contacto *
                                    </label>
                                    <input class="form-control" type="text" name="contacto" required placeholder="Ej: Juan Pérez">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Dirección
                                    </label>
                                    <input class="form-control" type="text" name="direccion" placeholder="Calle 123 #45-67">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-city"></i> Ciudad *
                                    </label>
                                    <input class="form-control" type="text" name="ciudad" required placeholder="Ej: Bogotá">
                                </div>
                            </div>
                            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                                <button type="button" class="btn-light btn-admin" onclick="cancelarFormularioProveedor()">
                                    <i class="fas fa-times"></i>
                                    Cancelar
                                </button>
                                <button type="button" class="btn-success btn-admin" onclick="guardarProveedor()">
                                    <i class="fas fa-save"></i>
                                    <span id="textoBotonProveedor">Guardar Proveedor</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="reportes" class="section-content">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
                    <!-- STOCK BAJO -->
                    <div class="admin-card">
                        <div class="card-header-admin">
                            <h3 class="card-title-admin">
                                <i class="fas fa-exclamation-triangle"></i>
                                Control de Stock
                            </h3>
                        </div>
                        <div class="card-body-admin">
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                                <input class="form-control" type="number" id="limiteStock" value="10" placeholder="Límite" style="flex: 1;">
                                <button class="btn-warning btn-admin" onclick="consultarStockBajo()">
                                    <i class="fas fa-search"></i>
                                    Consultar
                                </button>
                            </div>
                            <div id="resultadoStockBajo"></div>
                        </div>
                    </div>
                    
                    <!-- TOP PRODUCTOS -->
                    <div class="admin-card">
                        <div class="card-header-admin">
                            <h3 class="card-title-admin">
                                <i class="fas fa-trophy"></i>
                                Top Productos
                            </h3>
                        </div>
                        <div class="card-body-admin">
                            <button class="btn-success btn-admin" style="width: 100%; margin-bottom: 1rem;" onclick="consultarTopProductos()">
                                <i class="fas fa-chart-bar"></i>
                                Ver Top 5 Productos
                            </button>
                            <div id="resultadoTopProductos"></div>
                        </div>
                    </div>
                    
                    <!-- VENTAS POR CLIENTE -->
                    <div class="admin-card">
                        <div class="card-header-admin">
                            <h3 class="card-title-admin">
                                <i class="fas fa-users"></i>
                                Análisis de Clientes
                            </h3>
                        </div>
                        <div class="card-body-admin">
                            <button class="btn-primary btn-admin" style="width: 100%; margin-bottom: 1rem;" onclick="consultarVentasCliente()">
                                <i class="fas fa-user-chart"></i>
                                Ver Estadísticas de Clientes
                            </button>
                            <div id="resultadoVentasCliente"></div>
                        </div>
                    </div>
                    
                    <!-- VENTAS POR FECHA -->
                    <div class="admin-card">
                        <div class="card-header-admin">
                            <h3 class="card-title-admin">
                                <i class="fas fa-calendar"></i>
                                Ventas por Período
                            </h3>
                        </div>
                        <div class="card-body-admin">
                            <div class="form-group">
                                <label class="form-label">Fecha Inicio</label>
                                <input class="form-control" type="date" id="fechaInicio">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fecha Fin</label>
                                <input class="form-control" type="date" id="fechaFin">
                            </div>
                            <button class="btn-danger btn-admin" style="width: 100%;" onclick="consultarVentasFecha()">
                                <i class="fas fa-search"></i>
                                Generar Reporte
                            </button>
                            <div id="resultadoVentasFecha"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES -->
    
    <!-- Modal Producto -->
    <div class="modal-admin" id="productoModal">
        <div class="modal-content-admin">
            <div class="modal-header-admin">
                <h3 class="modal-title-admin">
                    <i class="fas fa-box"></i>
                    Nuevo Producto
                </h3>
                <button class="modal-close" onclick="cerrarModal('productoModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-admin">
                <form id="productoForm">
                    <div class="form-group">
                        <label class="form-label">Nombre del Producto</label>
                        <input class="form-control" type="text" name="nombre" required placeholder="Ej: Laptop HP Pavilion">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <input class="form-control" type="text" name="categoria" required placeholder="Ej: Electrónicos">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Precio Unitario</label>
                            <input class="form-control" type="number" step="0.01" name="precio_unitario" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cantidad en Stock</label>
                            <input class="form-control" type="number" name="cantidad_stock" required placeholder="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Proveedor</label>
                        <select class="form-control" name="proveedor_id" id="proveedorSelect" required>
                            <option value="">Seleccionar proveedor</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" class="btn-light btn-admin" onclick="cerrarModal('productoModal')">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="button" class="btn-success btn-admin" onclick="guardarProducto()">
                            <i class="fas fa-save"></i>
                            Guardar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Proveedor -->
    <div class="modal-admin" id="proveedorModal">
        <div class="modal-content-admin">
            <div class="modal-header-admin">
                <h3 class="modal-title-admin">
                    <i class="fas fa-truck"></i>
                    Gestión de Proveedores
                </h3>
                <button class="modal-close" onclick="cerrarModal('proveedorModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-admin">
                <form id="proveedorForm">
                    <div class="form-group">
                        <label class="form-label">Nombre de la Empresa</label>
                        <input class="form-control" type="text" name="nombre" required placeholder="Ej: Distribuidora ABC">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Persona de Contacto</label>
                        <input class="form-control" type="text" name="contacto" required placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <input class="form-control" type="text" name="direccion" placeholder="Calle 123 #45-67">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ciudad</label>
                        <input class="form-control" type="text" name="ciudad" required placeholder="Ej: Bogotá">
                    </div>
                    <button type="button" class="btn-success btn-admin" onclick="guardarProveedor()">
                        <i class="fas fa-plus"></i>
                        Agregar Proveedor
                    </button>
                </form>
                
                <hr style="margin: 2rem 0;">
                
                <div class="data-table">
                    <div class="table-header">
                        <h4 class="table-title">
                            <i class="fas fa-list"></i>
                            Lista de Proveedores
                        </h4>
                    </div>
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Contacto</th>
                                    <th>Ciudad</th>
                                </tr>
                            </thead>
                            <tbody id="tablaProveedores">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cliente -->
    <div class="modal-admin" id="clienteModal">
        <div class="modal-content-admin">
            <div class="modal-header-admin">
                <h3 class="modal-title-admin">
                    <i class="fas fa-users"></i>
                    Gestión de Clientes
                </h3>
                <button class="modal-close" onclick="cerrarModal('clienteModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-admin">
                <form id="clienteForm">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input class="form-control" type="text" name="nombre" required placeholder="Ej: María García López">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Documento de Identidad</label>
                        <input class="form-control" type="text" name="documento" required placeholder="Ej: 12345678">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Correo Electrónico</label>
                            <input class="form-control" type="email" name="correo" placeholder="ejemplo@correo.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input class="form-control" type="text" name="telefono" placeholder="300-123-4567">
                        </div>
                    </div>
                    <button type="button" class="btn-success btn-admin" onclick="guardarCliente()">
                        <i class="fas fa-user-plus"></i>
                        Agregar Cliente
                    </button>
                </form>
                
                <hr style="margin: 2rem 0;">
                
                <div class="data-table">
                    <div class="table-header">
                        <h4 class="table-title">
                            <i class="fas fa-address-book"></i>
                            Base de Datos de Clientes
                        </h4>
                    </div>
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Documento</th>
                                    <th>Correo</th>
                                    <th>Teléfono</th>
                                </tr>
                            </thead>
                            <tbody id="tablaClientes">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Empleado -->
    <div class="modal-admin" id="empleadoModal">
        <div class="modal-content-admin">
            <div class="modal-header-admin">
                <h3 class="modal-title-admin">
                    <i class="fas fa-user-tie"></i>
                    Gestión de Empleados
                </h3>
                <button class="modal-close" onclick="cerrarModal('empleadoModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-admin">
                <form id="empleadoForm">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input class="form-control" type="text" name="nombre" required placeholder="Ej: Carlos Rodríguez">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Documento de Identidad</label>
                        <input class="form-control" type="text" name="documento" required placeholder="Ej: 87654321">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Corporativo</label>
                        <input class="form-control" type="email" name="correo" placeholder="empleado@empresa.com">
                    </div>
                    <button type="button" class="btn-success btn-admin" onclick="guardarEmpleado()">
                        <i class="fas fa-id-badge"></i>
                        Agregar Empleado
                    </button>
                </form>
                
                <hr style="margin: 2rem 0;">
                
                <div class="data-table">
                    <div class="table-header">
                        <h4 class="table-title">
                            <i class="fas fa-users-cog"></i>
                            Personal de la Empresa
                        </h4>
                    </div>
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Documento</th>
                                    <th>Correo</th>
                                </tr>
                            </thead>
                            <tbody id="tablaEmpleados">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let carritoProductos = [];
        let currentSection = 'dashboard';
        let editMode = false;
        let currentEditId = null;

        // NAVEGACIÓN
        function mostrarSeccion(seccionId) {
            // Ocultar todas las secciones
            document.querySelectorAll('.section-content').forEach(el => {
                el.classList.remove('active');
            });
            
            // Mostrar la sección seleccionada
            document.getElementById(seccionId).classList.add('active');
            currentSection = seccionId;
            
            // Actualizar navegación activa
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`[data-section="${seccionId}"]`)?.classList.add('active');
            
            // Actualizar título de página
            const titles = {
                dashboard: 'Dashboard',
                productos: 'Gestión de Inventario',
                ventas: 'Punto de Venta',
                reportes: 'Reportes & Analytics'
            };
            document.getElementById('pageTitle').textContent = titles[seccionId] || 'TiendaDB Pro';
            
            // Cargar datos específicos
            if (seccionId === 'productos') {
                cargarProductos();
                cargarProveedores();
            } else if (seccionId === 'ventas') {
                cargarDatosVenta();
            } else if (seccionId === 'dashboard') {
                cargarDashboardStats();
            } else if (seccionId === 'clientes') {
                cargarVistaClientes();
            } else if (seccionId === 'empleados') {
                cargarVistaEmpleados();
            } else if (seccionId === 'proveedores') {
                cargarVistaProveedores();
            }
        }

        // MODALES
        function mostrarModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            if (modalId === 'proveedorModal') cargarProveedores();
            if (modalId === 'clienteModal') cargarClientes();
            if (modalId === 'empleadoModal') cargarEmpleados();
        }

        function cerrarModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            const formId = modalId.replace('Modal', 'Form');
            const form = document.getElementById(formId);
            if (form) form.reset();
            resetEditMode();
        }

        function resetEditMode() {
            editMode = false;
            currentEditId = null;
            
            // Restaurar títulos y botones de los modales
            document.querySelector('#productoModal .modal-title-admin').innerHTML = `
                <i class="fas fa-box"></i>
                Nuevo Producto
            `;
            document.querySelector('#productoModal .btn-success').innerHTML = `
                <i class="fas fa-save"></i>
                Guardar Producto
            `;
            
            // Restaurar formularios de vistas si existen
            const tituloCliente = document.getElementById('tituloFormCliente');
            if (tituloCliente) {
                tituloCliente.innerHTML = `<i class="fas fa-user-plus"></i> Nuevo Cliente`;
                document.getElementById('textoBotonCliente').textContent = 'Guardar Cliente';
            }
            
            const tituloEmpleado = document.getElementById('tituloFormEmpleado');
            if (tituloEmpleado) {
                tituloEmpleado.innerHTML = `<i class="fas fa-id-badge"></i> Nuevo Empleado`;
                document.getElementById('textoBotonEmpleado').textContent = 'Guardar Empleado';
            }
            
            const tituloProveedor = document.getElementById('tituloFormProveedor');
            if (tituloProveedor) {
                tituloProveedor.innerHTML = `<i class="fas fa-plus"></i> Nuevo Proveedor`;
                document.getElementById('textoBotonProveedor').textContent = 'Guardar Proveedor';
            }
        }

        // SIDEBAR TOGGLE
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // AJAX
        async function enviarDatos(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            Object.keys(data).forEach(key => formData.append(key, data[key]));
            
            try {
                const response = await fetch('', { method: 'POST', body: formData });
                return await response.json();
            } catch (error) {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión', 'error');
                return { success: false };
            }
        }

        // NOTIFICACIONES
        function mostrarNotificacion(mensaje, tipo = 'info', duracion = 4000) {
            const notification = document.createElement('div');
            notification.className = `notification-admin notification-${tipo}`;
            notification.innerHTML = `
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : tipo === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                ${mensaje}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, duracion);
        }

        // DASHBOARD STATS
        async function cargarDashboardStats() {
            const stats = await enviarDatos('get_dashboard_stats');
            if (stats) {
                document.getElementById('stat-productos').textContent = stats.productos || 0;
                document.getElementById('stat-clientes').textContent = stats.clientes || 0;
                document.getElementById('stat-stock-bajo').textContent = stats.stock_bajo || 0;
                document.getElementById('stat-ventas-mes').textContent = stats.ventas_mes || 0;
            }
        }

        // PRODUCTOS
        async function cargarProductos() {
            const productos = await enviarDatos('get_productos');
            const tbody = document.getElementById('tablaProductos');
            tbody.innerHTML = '';
            
            productos.forEach(producto => {
                const stockStatus = producto.cantidad_stock <= 10 ? 
                    '<span class="badge badge-danger">Stock Bajo</span>' : 
                    '<span class="badge badge-success">Stock OK</span>';
                
                tbody.innerHTML += `
                    <tr>
                        <td><strong>#${producto.id}</strong></td>
                        <td>
                            <div style="font-weight: 500;">${producto.nombre}</div>
                        </td>
                        <td><span class="badge badge-info">${producto.categoria}</span></td>
                        <td><strong style="color: var(--success-color);">${parseFloat(producto.precio_unitario).toLocaleString()}</strong></td>
                        <td style="color: ${producto.cantidad_stock <= 10 ? 'var(--danger-color)' : 'var(--success-color)'};">
                            <strong>${producto.cantidad_stock}</strong>
                        </td>
                        <td>${producto.proveedor_nombre || '<em>Sin proveedor</em>'}</td>
                        <td>${stockStatus}</td>
                        <td>
                            <div style="display: flex; gap: 0.25rem;">
                                <button class="btn-warning btn-admin" style="padding: 0.25rem 0.5rem;" onclick="editarProducto(${producto.id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-danger btn-admin" style="padding: 0.25rem 0.5rem;" onclick="eliminarProducto(${producto.id}, '${producto.nombre}')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        async function editarProducto(id) {
            const producto = await enviarDatos('get_producto_by_id', { id });
            if (producto) {
                editMode = true;
                currentEditId = id;
                
                // Llenar formulario
                const form = document.getElementById('productoForm');
                form.nombre.value = producto.nombre;
                form.categoria.value = producto.categoria;
                form.precio_unitario.value = producto.precio_unitario;
                form.cantidad_stock.value = producto.cantidad_stock;
                form.proveedor_id.value = producto.proveedor_id;
                
                // Cambiar título del modal
                document.querySelector('#productoModal .modal-title-admin').innerHTML = `
                    <i class="fas fa-edit"></i>
                    Editar Producto
                `;
                
                // Cambiar texto del botón
                document.querySelector('#productoModal .btn-success').innerHTML = `
                    <i class="fas fa-save"></i>
                    Actualizar Producto
                `;
                
                mostrarModal('productoModal');
            }
        }

        async function eliminarProducto(id, nombre) {
            if (confirm(`¿Está seguro de eliminar el producto "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const result = await enviarDatos('delete_producto', { id });
                if (result.success) {
                    mostrarNotificacion('Producto eliminado exitosamente', 'success');
                    cargarProductos();
                    cargarDashboardStats();
                } else {
                    mostrarNotificacion(result.message || 'Error al eliminar producto', 'error');
                }
            }
        }

        async function guardarProducto() {
            const form = document.getElementById('productoForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            let result;
            if (editMode) {
                data.id = currentEditId;
                result = await enviarDatos('update_producto', data);
            } else {
                result = await enviarDatos('add_producto', data);
            }
            
            if (result.success) {
                mostrarNotificacion(editMode ? 'Producto actualizado exitosamente' : 'Producto agregado exitosamente', 'success');
                cerrarModal('productoModal');
                cargarProductos();
                cargarDashboardStats();
            } else {
                mostrarNotificacion(result.message || 'Error al guardar producto', 'error');
            }
        }

        // PROVEEDORES
        async function cargarProveedores() {
            const proveedores = await enviarDatos('get_proveedores');
            
            // Actualizar select
            const select = document.getElementById('proveedorSelect');
            select.innerHTML = '<option value="">Seleccionar proveedor</option>';
            proveedores.forEach(p => {
                select.innerHTML += `<option value="${p.id}">${p.nombre}</option>`;
            });
            
            // Actualizar tabla
            const tbody = document.getElementById('tablaProveedores');
            tbody.innerHTML = '';
            proveedores.forEach(p => {
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${p.nombre}</strong></td>
                        <td>${p.contacto}</td>
                        <td><span class="badge badge-info">${p.ciudad}</span></td>
                        <td>
                            <div style="display: flex; gap: 0.25rem;">
                                <button class="btn-warning btn-admin" style="padding: 0.25rem 0.5rem;" onclick="editarProveedor(${p.id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-danger btn-admin" style="padding: 0.25rem 0.5rem;" onclick="eliminarProveedor(${p.id}, '${p.nombre}')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        async function editarProveedor(id) {
            const proveedor = await enviarDatos('get_proveedores');
            const proveedorData = proveedor.find(p => p.id == id);
            
            if (proveedorData) {
                editMode = true;
                currentEditId = id;
                
                // Llenar formulario
                const form = document.getElementById('proveedorForm');
                form.nombre.value = proveedorData.nombre;
                form.contacto.value = proveedorData.contacto;
                form.direccion.value = proveedorData.direccion || '';
                form.ciudad.value = proveedorData.ciudad;
                
                // Cambiar texto del botón
                document.querySelector('#proveedorModal .btn-success').innerHTML = `
                    <i class="fas fa-save"></i>
                    Actualizar Proveedor
                `;
            }
        }

        async function eliminarProveedor(id, nombre) {
            if (confirm(`¿Está seguro de eliminar el proveedor "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const result = await enviarDatos('delete_proveedor', { id });
                if (result.success) {
                    mostrarNotificacion('Proveedor eliminado exitosamente', 'success');
                    cargarProveedores();
                } else {
                    mostrarNotificacion(result.message || 'Error al eliminar proveedor', 'error');
                }
            }
        }

        async function guardarProveedor() {
            const form = document.getElementById('proveedorForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            let result;
            if (editMode) {
                data.id = currentEditId;
                result = await enviarDatos('update_proveedor', data);
            } else {
                result = await enviarDatos('add_proveedor', data);
            }
            
            if (result.success) {
                mostrarNotificacion(editMode ? 'Proveedor actualizado exitosamente' : 'Proveedor agregado exitosamente', 'success');
                form.reset();
                cargarProveedores();
                resetEditMode();
            } else {
                mostrarNotificacion(result.message || 'Error al guardar proveedor', 'error');
            }
        }

        // VISTAS PRINCIPALES DE CLIENTES, EMPLEADOS Y PROVEEDORES
        
        // ===== VISTA DE CLIENTES =====
        async function cargarVistaClientes() {
            await cargarClientes();
            calcularEstadisticasClientes();
        }

        async function cargarClientes() {
            const clientes = await enviarDatos('get_clientes');
            const tbody = document.getElementById('tablaClientes');
            tbody.innerHTML = '';
            
            clientes.forEach(c => {
                const estadoEmail = c.correo ? '<span class="badge badge-success">Con Email</span>' : '<span class="badge badge-warning">Sin Email</span>';
                const estadoTelefono = c.telefono ? '<span class="badge badge-info">Con Tel.</span>' : '';
                
                tbody.innerHTML += `
                    <tr>
                        <td><strong>#${c.id}</strong></td>
                        <td>
                            <div style="font-weight: 500;">${c.nombre}</div>
                        </td>
                        <td><code>${c.documento}</code></td>
                        <td>
                            <div>${c.correo || '<em style="color: var(--text-muted);">Sin correo</em>'}</div>
                            <div>${c.telefono || '<em style="color: var(--text-muted);">Sin teléfono</em>'}</div>
                        </td>
                        <td>
                            ${estadoEmail}
                            ${estadoTelefono}
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.25rem;">
                                <button class="btn-warning btn-admin" style="padding: 0.25rem 0.5rem;" onclick="editarCliente(${c.id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-danger btn-admin" style="padding: 0.25rem 0.5rem;" onclick="eliminarCliente(${c.id}, '${c.nombre}')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            // Actualizar contador
            document.getElementById('total-clientes').textContent = clientes.length;
        }

        async function calcularEstadisticasClientes() {
            const clientes = await enviarDatos('get_clientes');
            const clientesConEmail = clientes.filter(c => c.correo).length;
            
            document.getElementById('clientes-email').textContent = clientesConEmail;
            document.getElementById('clientes-activos').textContent = '0'; // Se puede calcular con ventas
        }

        function mostrarFormularioCliente() {
            document.getElementById('formularioCliente').style.display = 'block';
            document.getElementById('formularioCliente').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelarFormularioCliente() {
            document.getElementById('formularioCliente').style.display = 'none';
            document.getElementById('clienteForm').reset();
            resetEditMode();
        }

        async function editarCliente(id) {
            const clientes = await enviarDatos('get_clientes');
            const cliente = clientes.find(c => c.id == id);
            
            if (cliente) {
                editMode = true;
                currentEditId = id;
                
                // Mostrar formulario
                mostrarFormularioCliente();
                
                // Llenar formulario
                const form = document.getElementById('clienteForm');
                form.nombre.value = cliente.nombre;
                form.documento.value = cliente.documento;
                form.correo.value = cliente.correo || '';
                form.telefono.value = cliente.telefono || '';
                
                // Cambiar títulos
                document.getElementById('tituloFormCliente').innerHTML = `
                    <i class="fas fa-edit"></i>
                    Editar Cliente
                `;
                document.getElementById('textoBotonCliente').textContent = 'Actualizar Cliente';
            }
        }

        function filtrarClientes() {
            const busqueda = document.getElementById('buscarCliente').value.toLowerCase();
            const filtro = document.getElementById('filtroClientes').value;
            const filas = document.querySelectorAll('#tablaClientes tr');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                const mostrarPorBusqueda = texto.includes(busqueda);
                let mostrarPorFiltro = true;
                
                if (filtro === 'con_email') {
                    mostrarPorFiltro = fila.innerHTML.includes('Con Email');
                } else if (filtro === 'sin_email') {
                    mostrarPorFiltro = fila.innerHTML.includes('Sin Email');
                }
                
                fila.style.display = (mostrarPorBusqueda && mostrarPorFiltro) ? '' : 'none';
            });
        }

        // ===== VISTA DE EMPLEADOS =====
        async function cargarVistaEmpleados() {
            await cargarEmpleados();
            calcularEstadisticasEmpleados();
        }

        async function cargarEmpleados() {
            const empleados = await enviarDatos('get_empleados');
            const tbody = document.getElementById('tablaEmpleados');
            tbody.innerHTML = '';
            
            empleados.forEach(e => {
                const estadoEmail = e.correo ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-warning">Incompleto</span>';
                
                tbody.innerHTML += `
                    <tr>
                        <td><strong>#${e.id}</strong></td>
                        <td>
                            <div style="font-weight: 500;">${e.nombre}</div>
                        </td>
                        <td><code>${e.documento}</code></td>
                        <td>${e.correo || '<em style="color: var(--text-muted);">Sin correo</em>'}</td>
                        <td><span class="badge badge-info">0</span></td>
                        <td>${estadoEmail}</td>
                        <td>
                            <div style="display: flex; gap: 0.25rem;">
                                <button class="btn-warning btn-admin" style="padding: 0.25rem 0.5rem;" onclick="editarEmpleado(${e.id})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-danger btn-admin" style="padding: 0.25rem 0.5rem;" onclick="eliminarEmpleado(${e.id}, '${e.nombre}')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            document.getElementById('total-empleados').textContent = empleados.length;
        }

        async function calcularEstadisticasEmpleados() {
            const empleados = await enviarDatos('get_empleados');
            document.getElementById('empleados-ventas').textContent = '0'; // Se puede calcular con ventas
        }

        function mostrarFormularioEmpleado() {
            document.getElementById('formularioEmpleado').style.display = 'block';
            document.getElementById('formularioEmpleado').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelarFormularioEmpleado() {
            document.getElementById('formularioEmpleado').style.display = 'none';
            document.getElementById('empleadoForm').reset();
            resetEditMode();
        }

        async function editarEmpleado(id) {
            const empleados = await enviarDatos('get_empleados');
            const empleado = empleados.find(e => e.id == id);
            
            if (empleado) {
                editMode = true;
                currentEditId = id;
                
                mostrarFormularioEmpleado();
                
                const form = document.getElementById('empleadoForm');
                form.nombre.value = empleado.nombre;
                form.documento.value = empleado.documento;
                form.correo.value = empleado.correo || '';
                
                document.getElementById('tituloFormEmpleado').innerHTML = `
                    <i class="fas fa-edit"></i>
                    Editar Empleado
                `;
                document.getElementById('textoBotonEmpleado').textContent = 'Actualizar Empleado';
            }
        }

        function filtrarEmpleados() {
            const busqueda = document.getElementById('buscarEmpleado').value.toLowerCase();
            const filas = document.querySelectorAll('#tablaEmpleados tr');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(busqueda) ? '' : 'none';
            });
        }

        // ===== VISTA DE PROVEEDORES =====
        async function cargarVistaProveedores() {
            await cargarProveedores();
            calcularEstadisticasProveedores();
        }

        async function cargarProveedores() {
            const proveedores = await enviarDatos('get_proveedores');
            
            // Actualizar select de productos (si existe)
            const select = document.getElementById('proveedorSelect');
            if (select) {
                select.innerHTML = '<option value="">Seleccionar proveedor</option>';
                proveedores.forEach(p => {
                    select.innerHTML += `<option value="${p.id}">${p.nombre}</option>`;
                });
            }
            
            // Actualizar tabla de vista de proveedores
            const tbody = document.getElementById('tablaProveedores');
            if (tbody) {
                tbody.innerHTML = '';
                proveedores.forEach(p => {
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>#${p.id}</strong></td>
                            <td>
                                <div style="font-weight: 500;">${p.nombre}</div>
                            </td>
                            <td>${p.contacto}</td>
                            <td>
                                <div>${p.direccion || '<em style="color: var(--text-muted);">Sin dirección</em>'}</div>
                                <div><span class="badge badge-info">${p.ciudad}</span></div>
                            </td>
                            <td><span class="badge badge-primary">0</span></td>
                            <td><span class="badge badge-success">Activo</span></td>
                            <td>
                                <div style="display: flex; gap: 0.25rem;">
                                    <button class="btn-warning btn-admin" style="padding: 0.25rem 0.5rem;" onclick="editarProveedor(${p.id})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-danger btn-admin" style="padding: 0.25rem 0.5rem;" onclick="eliminarProveedor(${p.id}, '${p.nombre}')" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }
            
            document.getElementById('total-proveedores').textContent = proveedores.length;
        }

        async function calcularEstadisticasProveedores() {
            const proveedores = await enviarDatos('get_proveedores');
            const ciudades = [...new Set(proveedores.map(p => p.ciudad))].length;
            
            document.getElementById('ciudades-proveedores').textContent = ciudades;
            document.getElementById('proveedores-productos').textContent = '0'; // Se puede calcular con productos
        }

        function mostrarFormularioProveedor() {
            document.getElementById('formularioProveedor').style.display = 'block';
            document.getElementById('formularioProveedor').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelarFormularioProveedor() {
            document.getElementById('formularioProveedor').style.display = 'none';
            document.getElementById('proveedorForm').reset();
            resetEditMode();
        }

        async function editarProveedor(id) {
            const proveedores = await enviarDatos('get_proveedores');
            const proveedor = proveedores.find(p => p.id == id);
            
            if (proveedor) {
                editMode = true;
                currentEditId = id;
                
                mostrarFormularioProveedor();
                
                const form = document.getElementById('proveedorForm');
                form.nombre.value = proveedor.nombre;
                form.contacto.value = proveedor.contacto;
                form.direccion.value = proveedor.direccion || '';
                form.ciudad.value = proveedor.ciudad;
                
                document.getElementById('tituloFormProveedor').innerHTML = `
                    <i class="fas fa-edit"></i>
                    Editar Proveedor
                `;
                document.getElementById('textoBotonProveedor').textContent = 'Actualizar Proveedor';
            }
        }

        function filtrarProveedores() {
            const busqueda = document.getElementById('buscarProveedor').value.toLowerCase();
            const filas = document.querySelectorAll('#tablaProveedores tr');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(busqueda) ? '' : 'none';
            });
        }

        // FUNCIONES DE GUARDADO ACTUALIZADAS
        async function guardarCliente() {
            const form = document.getElementById('clienteForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            let result;
            if (editMode) {
                data.id = currentEditId;
                result = await enviarDatos('update_cliente', data);
            } else {
                result = await enviarDatos('add_cliente', data);
            }
            
            if (result.success) {
                mostrarNotificacion(editMode ? 'Cliente actualizado exitosamente' : 'Cliente agregado exitosamente', 'success');
                cancelarFormularioCliente();
                cargarVistaClientes();
                cargarDashboardStats();
            } else {
                mostrarNotificacion(result.message || 'Error al guardar cliente', 'error');
            }
        }

        async function guardarEmpleado() {
            const form = document.getElementById('empleadoForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            let result;
            if (editMode) {
                data.id = currentEditId;
                result = await enviarDatos('update_empleado', data);
            } else {
                result = await enviarDatos('add_empleado', data);
            }
            
            if (result.success) {
                mostrarNotificacion(editMode ? 'Empleado actualizado exitosamente' : 'Empleado agregado exitosamente', 'success');
                cancelarFormularioEmpleado();
                cargarVistaEmpleados();
                cargarDashboardStats();
            } else {
                mostrarNotificacion(result.message || 'Error al guardar empleado', 'error');
            }
        }

        async function guardarProveedor() {
            const form = document.getElementById('proveedorForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            let result;
            if (editMode) {
                data.id = currentEditId;
                result = await enviarDatos('update_proveedor', data);
            } else {
                result = await enviarDatos('add_proveedor', data);
            }
            
            if (result.success) {
                mostrarNotificacion(editMode ? 'Proveedor actualizado exitosamente' : 'Proveedor agregado exitosamente', 'success');
                cancelarFormularioProveedor();
                cargarVistaProveedores();
            } else {
                mostrarNotificacion(result.message || 'Error al guardar proveedor', 'error');
            }
        }
        // FUNCIONES DE ELIMINACIÓN
        async function eliminarCliente(id, nombre) {
            if (confirm(`¿Está seguro de eliminar el cliente "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const result = await enviarDatos('delete_cliente', { id });
                if (result.success) {
                    mostrarNotificacion('Cliente eliminado exitosamente', 'success');
                    cargarVistaClientes();
                    cargarDashboardStats();
                } else {
                    mostrarNotificacion(result.message || 'Error al eliminar cliente', 'error');
                }
            }
        }

        async function eliminarEmpleado(id, nombre) {
            if (confirm(`¿Está seguro de eliminar el empleado "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const result = await enviarDatos('delete_empleado', { id });
                if (result.success) {
                    mostrarNotificacion('Empleado eliminado exitosamente', 'success');
                    cargarVistaEmpleados();
                    cargarDashboardStats();
                } else {
                    mostrarNotificacion(result.message || 'Error al eliminar empleado', 'error');
                }
            }
        }

        async function eliminarProveedor(id, nombre) {
            if (confirm(`¿Está seguro de eliminar el proveedor "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const result = await enviarDatos('delete_proveedor', { id });
                if (result.success) {
                    mostrarNotificacion('Proveedor eliminado exitosamente', 'success');
                    cargarVistaProveedores();
                } else {
                    mostrarNotificacion(result.message || 'Error al eliminar proveedor', 'error');
                }
            }
        }

        // VENTAS
        async function cargarDatosVenta() {
            // Cargar clientes
            const clientes = await enviarDatos('get_clientes');
            const clienteSelect = document.getElementById('ventaCliente');
            clienteSelect.innerHTML = '<option value="">Seleccionar cliente</option>';
            clientes.forEach(c => {
                clienteSelect.innerHTML += `<option value="${c.id}">${c.nombre} - ${c.documento}</option>`;
            });

            // Cargar empleados
            const empleados = await enviarDatos('get_empleados');
            const empleadoSelect = document.getElementById('ventaEmpleado');
            empleadoSelect.innerHTML = '<option value="">Seleccionar empleado</option>';
            empleados.forEach(e => {
                empleadoSelect.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
            });

            // Cargar productos
            const productos = await enviarDatos('get_productos');
            const productoSelect = document.getElementById('productoSelect');
            productoSelect.innerHTML = '<option value="">Seleccionar producto</option>';
            productos.forEach(p => {
                if (p.cantidad_stock > 0) {
                    productoSelect.innerHTML += `<option value="${p.id}" data-precio="${p.precio_unitario}" data-stock="${p.cantidad_stock}">${p.nombre} - ${parseFloat(p.precio_unitario).toLocaleString()} (Stock: ${p.cantidad_stock})</option>`;
                }
            });
        }

        function agregarProducto() {
            const select = document.getElementById('productoSelect');
            const cantidad = parseInt(document.getElementById('cantidadInput').value);
            
            if (!select.value || cantidad <= 0) {
                mostrarNotificacion('Seleccione un producto y cantidad válida', 'warning');
                return;
            }
            
            const option = select.options[select.selectedIndex];
            const precio = parseFloat(option.dataset.precio);
            const stock = parseInt(option.dataset.stock);
            
            if (cantidad > stock) {
                mostrarNotificacion('La cantidad excede el stock disponible', 'error');
                return;
            }
            
            // Verificar si el producto ya está en el carrito
            const existingIndex = carritoProductos.findIndex(p => p.id === select.value);
            if (existingIndex >= 0) {
                carritoProductos[existingIndex].cantidad += cantidad;
                carritoProductos[existingIndex].subtotal = carritoProductos[existingIndex].cantidad * precio;
            } else {
                carritoProductos.push({
                    id: select.value,
                    nombre: option.text.split(' - ')[0],
                    precio: precio,
                    cantidad: cantidad,
                    subtotal: precio * cantidad
                });
            }
            
            actualizarCarrito();
            select.value = '';
            document.getElementById('cantidadInput').value = 1;
            mostrarNotificacion('Producto agregado al carrito', 'success');
        }

        function actualizarCarrito() {
            const div = document.getElementById('carrito');
            let total = 0;
            
            if (carritoProductos.length === 0) {
                div.innerHTML = `
                    <p style="color: var(--text-muted); text-align: center; padding: 2rem;">
                        <i class="fas fa-shopping-cart" style="font-size: 2rem; opacity: 0.3;"></i><br>
                        Carrito vacío
                    </p>
                `;
            } else {
                div.innerHTML = '';
                carritoProductos.forEach((p, i) => {
                    total += p.subtotal;
                    div.innerHTML += `
                        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem; background: #f8f9fa;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong style="color: var(--primary-color);">${p.nombre}</strong><br>
                                    <small style="color: var(--text-muted);">${p.cantidad} × ${p.precio.toLocaleString()} = <strong style="color: var(--success-color);">${p.subtotal.toLocaleString()}</strong></small>
                                </div>
                                <button class="btn-danger btn-admin" style="padding: 0.25rem 0.5rem;" onclick="eliminarProducto(${i})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
            }
            
            document.getElementById('total').textContent = total.toLocaleString();
        }

        function eliminarProducto(index) {
            carritoProductos.splice(index, 1);
            actualizarCarrito();
            mostrarNotificacion('Producto eliminado del carrito', 'info');
        }

        async function procesarVenta() {
            const clienteId = document.getElementById('ventaCliente').value;
            const empleadoId = document.getElementById('ventaEmpleado').value;
            
            if (!clienteId || !empleadoId || carritoProductos.length === 0) {
                mostrarNotificacion('Complete todos los campos y agregue productos al carrito', 'warning');
                return;
            }
            
            const result = await enviarDatos('registrar_venta', {
                cliente_id: clienteId,
                empleado_id: empleadoId,
                productos: JSON.stringify(carritoProductos)
            });
            
            if (result.success) {
                mostrarNotificacion(`¡Venta procesada exitosamente! Factura #${result.venta_id}`, 'success');
                carritoProductos = [];
                actualizarCarrito();
                document.getElementById('ventaCliente').value = '';
                document.getElementById('ventaEmpleado').value = '';
                cargarDatosVenta(); // Refrescar stock
                cargarDashboardStats();
            } else {
                mostrarNotificacion('Error al procesar la venta: ' + (result.error || 'Error desconocido'), 'error');
            }
        }

        // REPORTES
        async function consultarStockBajo() {
            const limite = document.getElementById('limiteStock').value || 10;
            const productos = await enviarDatos('stock_bajo', { limite });
            
            const div = document.getElementById('resultadoStockBajo');
            if (productos.length === 0) {
                div.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--success-color);">
                        <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h4>¡Perfecto!</h4>
                        <p>No hay productos con stock bajo</p>
                    </div>
                `;
            } else {
                let html = `
                    <div style="margin-top: 1rem;">
                        <div style="background: var(--warning-color); color: white; padding: 0.5rem 1rem; border-radius: 6px; margin-bottom: 1rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>${productos.length} productos requieren atención</strong>
                        </div>
                        <div class="table-container" style="max-height: 300px;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock</th>
                                        <th>Proveedor</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                productos.forEach(p => {
                    html += `
                        <tr>
                            <td><strong>${p.nombre}</strong></td>
                            <td><span class="badge badge-danger">${p.cantidad_stock}</span></td>
                            <td>${p.proveedor_nombre || '<em>Sin proveedor</em>'}</td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div></div>';
                div.innerHTML = html;
            }
        }

        async function consultarTopProductos() {
            const productos = await enviarDatos('top_productos');
            
            const div = document.getElementById('resultadoTopProductos');
            if (productos.length === 0) {
                div.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No hay datos de ventas disponibles</p>
                    </div>
                `;
            } else {
                let html = `
                    <div style="margin-top: 1rem;">
                        <div class="table-container" style="max-height: 300px;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Pos.</th>
                                        <th>Producto</th>
                                        <th>Vendidos</th>
                                        <th>Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                productos.forEach((p, index) => {
                    const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `#${index + 1}`;
                    html += `
                        <tr>
                            <td style="font-size: 1.2rem;">${medal}</td>
                            <td><strong>${p.nombre}</strong></td>
                            <td><span class="badge badge-info">${p.total_vendido}</span></td>
                            <td><strong style="color: var(--success-color);">${parseFloat(p.total_ingresos).toLocaleString()}</strong></td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div></div>';
                div.innerHTML = html;
            }
        }

        async function consultarVentasCliente() {
            const clientes = await enviarDatos('ventas_cliente');
            
            const div = document.getElementById('resultadoVentasCliente');
            if (clientes.length === 0) {
                div.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No hay datos de clientes disponibles</p>
                    </div>
                `;
            } else {
                let html = `
                    <div style="margin-top: 1rem;">
                        <div class="table-container" style="max-height: 300px;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Compras</th>
                                        <th>Total Gastado</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                clientes.forEach(c => {
                    html += `
                        <tr>
                            <td>
                                <strong>${c.nombre}</strong><br>
                                <small style="color: var(--text-muted);">${c.documento}</small>
                            </td>
                            <td><span class="badge badge-primary">${c.total_compras}</span></td>
                            <td><strong style="color: var(--success-color);">${parseFloat(c.total_gastado).toLocaleString()}</strong></td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div></div>';
                div.innerHTML = html;
            }
        }

        async function consultarVentasFecha() {
            const fechaInicio = document.getElementById('fechaInicio').value;
            const fechaFin = document.getElementById('fechaFin').value;
            
            if (!fechaInicio || !fechaFin) {
                mostrarNotificacion('Por favor seleccione ambas fechas', 'warning');
                return;
            }
            
            const ventas = await enviarDatos('ventas_fecha', { 
                fecha_inicio: fechaInicio, 
                fecha_fin: fechaFin 
            });
            
            const div = document.getElementById('resultadoVentasFecha');
            if (ventas.length === 0) {
                div.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No hay ventas en el período seleccionado</p>
                    </div>
                `;
            } else {
                let totalGeneral = 0;
                let html = `
                    <div style="margin-top: 1rem;">
                        <div style="background: var(--success-color); color: white; padding: 0.5rem 1rem; border-radius: 6px; margin-bottom: 1rem;">
                            <i class="fas fa-check-circle"></i>
                            <strong>${ventas.length} ventas encontradas</strong>
                        </div>
                        <div class="table-container" style="max-height: 300px;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Factura</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Empleado</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                ventas.forEach(v => {
                    const fecha = new Date(v.fecha).toLocaleDateString();
                    const hora = new Date(v.fecha).toLocaleTimeString();
                    totalGeneral += parseFloat(v.total);
                    html += `
                        <tr>
                            <td><strong>#${v.id}</strong></td>
                            <td>
                                ${fecha}<br>
                                <small style="color: var(--text-muted);">${hora}</small>
                            </td>
                            <td>${v.cliente}</td>
                            <td>${v.empleado}</td>
                            <td><strong style="color: var(--success-color);">${parseFloat(v.total).toLocaleString()}</strong></td>
                        </tr>
                    `;
                });
                html += `
                                </tbody>
                                <tfoot>
                                    <tr style="background: var(--light-bg); font-weight: bold;">
                                        <td colspan="4" style="text-align: right; padding: 1rem;">
                                            <strong>TOTAL GENERAL:</strong>
                                        </td>
                                        <td style="color: var(--success-color); font-size: 1.2rem;">
                                            <strong>${totalGeneral.toLocaleString()}</strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                `;
                div.innerHTML = html;
            }
        }

        // RESPONSIVE
        function checkResponsive() {
            const isMobile = window.innerWidth <= 768;
            const menuToggle = document.getElementById('menuToggle');
            
            if (isMobile) {
                menuToggle.style.display = 'inline-flex';
            } else {
                menuToggle.style.display = 'none';
                document.getElementById('sidebar').classList.remove('active');
            }
        }

        // INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', function() {
            mostrarSeccion('dashboard');
            checkResponsive();
            
            // Event listeners para responsive
            window.addEventListener('resize', checkResponsive);
            
            // Cerrar modales al hacer clic fuera
            document.querySelectorAll('.modal-admin').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        cerrarModal(this.id);
                    }
                });
            });
            
            // Establecer fechas por defecto
            const today = new Date().toISOString().split('T')[0];
            const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
            
            document.getElementById('fechaInicio').value = firstDay;
            document.getElementById('fechaFin').value = today;
            
            console.log('🚀 Sistema TiendaDB Pro iniciado correctamente');
        });
    </script>
</body>
</html>