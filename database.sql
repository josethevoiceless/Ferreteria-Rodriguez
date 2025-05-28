-- Crear base de datos
CREATE DATABASE IF NOT EXISTS TiendaDB;
USE TiendaDB;

-- Tabla de proveedores
CREATE TABLE IF NOT EXISTS Proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    contacto VARCHAR(100),
    direccion VARCHAR(150),
    ciudad VARCHAR(100)
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS Productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    cantidad_stock INT NOT NULL DEFAULT 0,
    proveedor_id INT,
    FOREIGN KEY (proveedor_id) REFERENCES Proveedores(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS Clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(50) NOT NULL UNIQUE,
    correo VARCHAR(100),
    telefono VARCHAR(20)
);

-- Tabla de empleados
CREATE TABLE IF NOT EXISTS Empleados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(50) NOT NULL UNIQUE,
    correo VARCHAR(100)
);

-- Tabla de ventas
CREATE TABLE IF NOT EXISTS Ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    cliente_id INT NOT NULL,
    empleado_id INT NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES Clientes(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES Empleados(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla detalle de ventas
CREATE TABLE IF NOT EXISTS DetalleVenta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES Ventas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES Productos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Datos de ejemplo

-- Proveedores
INSERT INTO Proveedores (nombre, contacto, direccion, ciudad) VALUES
('Proveedor ABC', 'Juan Pérez', 'Calle 123 #45-67', 'Bogotá'),
('Distribuidora XYZ', 'María García', 'Carrera 10 #20-30', 'Medellín'),
('Comercial 123', 'Carlos López', 'Avenida 50 #15-25', 'Cali');

-- Productos
INSERT INTO Productos (nombre, categoria, precio_unitario, cantidad_stock, proveedor_id) VALUES
('Laptop HP', 'Electrónicos', 2500000.00, 15, 1),
('Mouse Inalámbrico', 'Electrónicos', 45000.00, 50, 1),
('Teclado Mecánico', 'Electrónicos', 120000.00, 25, 1),
('Monitor 24"', 'Electrónicos', 800000.00, 8, 2),
('Auriculares', 'Electrónicos', 85000.00, 30, 2),
('Camiseta Polo', 'Ropa', 65000.00, 40, 3),
('Pantalón Jean', 'Ropa', 120000.00, 20, 3),
('Zapatos Deportivos', 'Calzado', 180000.00, 15, 3);

-- Clientes
INSERT INTO Clientes (nombre, documento, correo, telefono) VALUES
('Ana Rodríguez', '12345678', 'ana@email.com', '300-123-4567'),
('Pedro Martínez', '87654321', 'pedro@email.com', '300-987-6543'),
('Laura Sánchez', '11223344', 'laura@email.com', '300-555-1234');

-- Empleados
INSERT INTO Empleados (nombre, documento, correo) VALUES
('Sofía González', '98765432', 'sofia@tienda.com'),
('Miguel Torres', '55667788', 'miguel@tienda.com'),
('Carmen Ruiz', '99887766', 'carmen@tienda.com');

-- Ventas
INSERT INTO Ventas (cliente_id, empleado_id) VALUES
(1, 1),
(2, 2),
(3, 1);

-- Detalle de ventas
INSERT INTO DetalleVenta (venta_id, producto_id, cantidad, precio_unitario) VALUES
(1, 1, 1, 2500000.00),
(1, 2, 2, 45000.00),
(2, 4, 1, 800000.00),
(2, 5, 1, 85000.00),
(3, 6, 3, 65000.00),
(3, 8, 1, 180000.00);
