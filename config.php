<?php
/**
 * Configuración de Base de Datos
 * Sistema de Tienda - Proyecto Universitario
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'TiendaDB');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Conectar a la base de datos
 */
function conectarDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        
        // Configurar PDO para mostrar errores
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
        
    } catch(PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

/**
 * Ejecutar consulta simple
 */
function ejecutarConsulta($sql, $params = []) {
    $pdo = conectarDB();
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        die("Error en consulta: " . $e->getMessage());
    }
}

/**
 * Obtener todos los registros
 */
function obtenerTodos($tabla) {
    $sql = "SELECT * FROM $tabla ORDER BY id DESC";
    $stmt = ejecutarConsulta($sql);
    return $stmt->fetchAll();
}

/**
 * Obtener registro por ID
 */
function obtenerPorId($tabla, $id) {
    $sql = "SELECT * FROM $tabla WHERE id = ?";
    $stmt = ejecutarConsulta($sql, [$id]);
    return $stmt->fetch();
}

/**
 * Insertar registro
 */
function insertar($tabla, $datos) {
    $campos = implode(', ', array_keys($datos));
    $valores = ':' . implode(', :', array_keys($datos));
    $sql = "INSERT INTO $tabla ($campos) VALUES ($valores)";
    
    $stmt = ejecutarConsulta($sql, $datos);
    return $stmt->rowCount() > 0;
}

/**
 * Respuesta JSON
 */
function respuestaJSON($datos) {
    header('Content-Type: application/json');
    echo json_encode($datos);
    exit;
}

/**
 * Validar datos requeridos
 */
function validarRequeridos($datos, $campos) {
    foreach ($campos as $campo) {
        if (empty($datos[$campo])) {
            return "El campo $campo es requerido";
        }
    }
    return null;
}

/**
 * Limpiar datos de entrada
 */
function limpiarDatos($datos) {
    return array_map(function($valor) {
        return htmlspecialchars(strip_tags(trim($valor)));
    }, $datos);
}

?>