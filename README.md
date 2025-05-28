¡Manos a la obra! ¿Cómo lo instalamos? 🛠️
¡No te preocupes, es más fácil de lo que parece! Sigue estos pasos y tendrás tu sistema funcionando en un abrir y cerrar de ojos.

Paso 1: Lo que necesitas (Requisitos)
Antes de empezar, asegúrate de tener lo siguiente:

Un servidor web (como XAMPP o WAMP, ¡son muy populares y fáciles de usar!).
PHP 7.4 o superior (es el lenguaje en el que está hecho el sistema).
MySQL/MariaDB (para guardar toda la información de tu tienda).
Un navegador web moderno (Chrome, Firefox, Edge... ¡el que uses a diario!).
Paso 2: Preparamos la base de datos
Abre tu phpMyAdmin o el cliente de MySQL que prefieras.
Busca el archivo database.sql en esta carpeta y ejecútalo.
¡Listo! Deberías ver una nueva base de datos llamada "TiendaDB" con todas sus tablas.
Paso 3: Conectamos todo
Abre el archivo config.php (¡recuerda, está en esta misma carpeta!).
Si tu configuración es diferente a la estándar, puedes modificar estas líneas:
DB_HOST (normalmente es localhost)
DB_NAME (ya sabes, TiendaDB)
DB_USER (casi siempre root)
DB_PASS (por defecto, ¡está vacío!)
Paso 4: Llevamos los archivos a su lugar
Copia todos los archivos de este proyecto a la carpeta de tu servidor web (por ejemplo, htdocs/sistema-tienda/ si usas XAMPP).
Asegúrate de que los archivos tengan los permisos de lectura adecuados.
Paso 5: ¡A disfrutar!
Abre tu navegador web favorito.
Escribe en la barra de direcciones: http://localhost/sistema-tienda/
¡Y listo! Tu sistema debería cargar sin problemas.
¡A usar el sistema! ¿Qué puedo hacer? 💡
Una vez dentro, te moverás con facilidad. Aquí te cuento lo que puedes hacer:

Navegación rápida
Dashboard: Una vista general con las estadísticas más importantes de tu tienda.
Productos: Aquí controlarás todo tu inventario.
Ventas: Para registrar cada transacción.
Reportes: ¡El lugar para analizar tus datos y ver cómo va el negocio!
Funcionalidades principales
Gestión de Productos:
Crea y consulta tus productos.
Lleva un control de stock para que nunca te quedes sin nada.
Asocia cada producto con su proveedor.
Gestión de Clientes:
Registra a tus clientes.
Consulta su historial de compras.
Gestión de Empleados:
Lleva un registro de tu personal.
Asígnalos a las ventas.
Gestión de Proveedores:
Registra a quienes te surten.
Asocia los productos que te venden.
Registro de Ventas:
Un práctico carrito de compras para tus transacciones.
El stock se actualiza automáticamente.
¡Genera facturas!
Reportes y Análisis:
Descubre qué productos tienen stock bajo.
Conoce el Top 5 de los productos más vendidos.
Consulta las ventas por cliente.
Analiza las ventas en un rango de fechas específico.
¿Qué hay detrás de escena? (Operaciones SQL) 🧠
Hemos trabajado para que el sistema realice estas operaciones clave en la base de datos:

✅ Creación, edición y eliminación de productos, clientes y proveedores.
✅ Registro completo de una venta con todos sus detalles.
✅ Consultas inteligentes como: productos con stock bajo, Top 5 vendidos, total de ventas por cliente y ventas entre dos fechas.
Las herramientas que usamos (Tecnologías) 💻
Queremos que sepas con qué construimos este sistema:

Backend: PHP 7.4+ (el cerebro que procesa la lógica).
Base de Datos: MySQL (donde guardamos toda tu información de forma segura).
Frontend: HTML5, CSS3, JavaScript (para que todo se vea bien y sea interactivo).
Framework CSS: Bulma (¡para un diseño moderno y responsivo!).
Iconos: Font Awesome (los pequeños dibujos que hacen todo más intuitivo).
Comunicación asíncrona: AJAX (para que el sistema sea rápido y fluido).
¡Datos de ejemplo para empezar! 📊
Para que no te encuentres con un sistema vacío, hemos incluido algunos datos de prueba:

3 Proveedores
8 Productos
3 Clientes
3 Empleados
3 Ventas con sus detalles
¿Algún problema? ¡Aquí te ayudamos! (Solución de Problemas) 🕵️‍♀️
Si algo no funciona como esperas, revisa esto:

ERROR: "No se puede conectar a la base de datos"
¿Está MySQL ejecutándose? (¡Asegúrate de que tu XAMPP o WAMP esté encendido!).
¿Revisaste las credenciales en config.php?
¿Se creó la base de datos TiendaDB?
ERROR: "Página en blanco"
Echa un vistazo a los logs de errores de PHP (a veces están en la carpeta de tu servidor).
¿Los archivos tienen los permisos de lectura correctos?
¿config.php es accesible?
ERROR: "No aparecen datos"
¿Ejecutaste el archivo database.sql?
¿Las tablas de la base de datos tienen información?
Revisa la consola de tu navegador (F12) por si hay errores de JavaScript.
¡Así está organizado! (Estructura de Archivos) 📁
Para que te ubiques fácilmente, así es como están distribuidos los archivos:

sistema-tienda/
├── index.php      (¡El sistema principal!)
├── config.php     (Aquí configuras la base de datos)
├── database.sql   (El script para crear la base de datos)
└── README.txt     (Este mismo archivo)