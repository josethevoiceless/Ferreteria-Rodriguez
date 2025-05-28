# 🛠️ ¡Manos a la obra! ¿Cómo lo instalamos?

¡No te preocupes, es más fácil de lo que parece! Sigue estos pasos y tendrás tu sistema funcionando en un abrir y cerrar de ojos.

---

## ✅ Paso 1: Lo que necesitas (Requisitos)

Antes de empezar, asegúrate de tener lo siguiente:

- Un servidor web (como **XAMPP** o **WAMP**, ¡son muy populares y fáciles de usar!).
- **PHP 7.4** o superior.
- **MySQL/MariaDB** (para guardar toda la información de tu tienda).
- Un navegador web moderno (Chrome, Firefox, Edge... ¡el que uses a diario!).

---

## 🗄️ Paso 2: Preparamos la base de datos

1. Abre tu **phpMyAdmin** o el cliente de MySQL que prefieras.
2. Busca el archivo `database.sql` en esta carpeta y ejecútalo.
3. ¡Listo! Deberías ver una nueva base de datos llamada **`TiendaDB`** con todas sus tablas.

---

## 🔌 Paso 3: Conectamos todo

1. Abre el archivo `config.php` (¡recuerda, está en esta misma carpeta!).
2. Si tu configuración es diferente a la estándar, puedes modificar estas líneas:

```php
DB_HOST = "localhost";   // normalmente es localhost  
DB_NAME = "TiendaDB";    // nombre de la base de datos  
DB_USER = "root";        // usuario por defecto  
DB_PASS = "";            // contraseña por defecto (vacía)  
```

---

## 📁 Paso 4: Llevamos los archivos a su lugar

1. Copia todos los archivos de este proyecto a la carpeta de tu servidor web  
   (por ejemplo: `htdocs/sistema-tienda/` si usas XAMPP).
2. Asegúrate de que los archivos tengan los permisos de lectura adecuados.

---

## 🚀 Paso 5: ¡A disfrutar!

1. Abre tu navegador web favorito.
2. Escribe en la barra de direcciones:  
   `http://localhost/sistema-tienda/`
3. ¡Y listo! Tu sistema debería cargar sin problemas.

---

# 💡 ¡A usar el sistema! ¿Qué puedo hacer?

Una vez dentro, te moverás con facilidad. Aquí te cuento lo que puedes hacer:

## 🔎 Navegación rápida

- **Dashboard**: Estadísticas generales de tu tienda.
- **Productos**: Control total del inventario.
- **Ventas**: Registro de transacciones.
- **Reportes**: Análisis y desempeño del negocio.

## 🧰 Funcionalidades principales

### 🛒 Gestión de Productos

- Crear y consultar productos.
- Control de stock.
- Asociación con proveedores.

### 👥 Gestión de Clientes

- Registro de clientes.
- Historial de compras.

### 🧑‍💼 Gestión de Empleados

- Registro del personal.
- Asignación a ventas.

### 🚚 Gestión de Proveedores

- Registro de proveedores.
- Asociación con productos.

### 🧾 Registro de Ventas

- Carrito de compras.
- Actualización automática de stock.
- Generación de facturas.

### 📈 Reportes y Análisis

- Productos con stock bajo.
- Top 5 productos más vendidos.
- Ventas por cliente.
- Ventas en un rango de fechas.

---

# 🧠 ¿Qué hay detrás de escena? (Operaciones SQL)

El sistema realiza las siguientes operaciones clave:

- ✅ Creación, edición y eliminación de productos, clientes y proveedores.
- ✅ Registro completo de una venta con todos sus detalles.
- ✅ Consultas inteligentes:
  - Productos con stock bajo.
  - Top 5 productos más vendidos.
  - Total de ventas por cliente.
  - Ventas entre fechas específicas.

---

# 💻 Las herramientas que usamos (Tecnologías)

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Bulma
- **Iconos**: Font Awesome
- **Comunicación asíncrona**: AJAX

---

# 📊 ¡Datos de ejemplo para empezar!

Incluimos datos de prueba para que no comiences con un sistema vacío:

- 3 Proveedores  
- 8 Productos  
- 3 Clientes  
- 3 Empleados  
- 3 Ventas con sus detalles

---

# 🕵️‍♀️ ¿Algún problema? ¡Aquí te ayudamos!

### ❌ ERROR: "No se puede conectar a la base de datos"

- ¿Está **MySQL** ejecutándose?
- ¿Revisaste las credenciales en `config.php`?
- ¿Se creó la base de datos **TiendaDB**?

### ❌ ERROR: "Página en blanco"

- Revisa los **logs de errores** de PHP.
- Verifica los **permisos de lectura** de los archivos.
- Asegúrate de que `config.php` es accesible.

### ❌ ERROR: "No aparecen datos"

- ¿Ejecutaste el archivo `database.sql`?
- ¿Las tablas de la base de datos tienen información?
- Abre la consola del navegador (F12) y revisa si hay errores de JavaScript.

---

# 📁 ¡Así está organizado! (Estructura de Archivos)

```
sistema-tienda/
├── index.php       // ¡El sistema principal!
├── config.php      // Aquí configuras la base de datos
├── database.sql    // Script para crear la base de datos
└── README.md       // Este mismo archivo
```
