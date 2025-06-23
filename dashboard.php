<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

include 'includes/connection.php';
include 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - POS Papelería</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenido, <?php echo $_SESSION['usuario']; ?></h1>
            <nav class="nav-menu">
                <a href="dashboard.php">Inicio</a>
                <a href="inventario.php">Inventario</a>
                <a href="ventas.php">Ventas</a>
                <a href="proveedores.php">Proveedores</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </div>

        <div class="card-container">
            <div class="card">
                <h3>Productos con stock bajo</h3>
                <?php
                $query = "SELECT p.nombre_producto, p.stock_actual, p.stock_minimo 
                          FROM productos p 
                          WHERE p.stock_actual <= p.stock_minimo + 2 
                          ORDER BY p.stock_actual ASC 
                          LIMIT 5";
                $result = $conn->query($query);
                
                if ($result->num_rows > 0) {
                    echo "<ul>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<li class='low-stock'>{$row['nombre_producto']} ({$row['stock_actual']}/{$row['stock_minimo']}</li>";
                    }
                    echo "</ul>";
                    echo "<a href='inventario.php'>Ver más</a>";
                } else {
                    echo "<p>No hay productos con stock bajo</p>";
                }
                ?>
            </div>

            <div class="card">
                <h3>Últimas ventas</h3>
                <?php
                $query = "SELECT v.id_venta, v.fecha_venta, v.total 
                          FROM ventas v 
                          ORDER BY v.fecha_venta DESC 
                          LIMIT 5";
                $result = $conn->query($query);
                
                if ($result->num_rows > 0) {
                    echo "<ul>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<li>Venta #{$row['id_venta']} - $" . number_format($row['total'], 2) . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No hay ventas registradas</p>";
                }
                ?>
            </div>

            <div class="card">
                <h3>Resumen de inventario</h3>
                <?php
                $query = "SELECT 
                            COUNT(*) as total_productos,
                            SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) as productos_bajo_stock,
                            SUM(CASE WHEN stock_actual >= stock_maximo THEN 1 ELSE 0 END) as productos_alto_stock
                          FROM productos";
                $result = $conn->query($query);
                $row = $result->fetch_assoc();
                
                echo "<p>Total productos: {$row['total_productos']}</p>";
                echo "<p class='low-stock'>Productos con stock bajo: {$row['productos_bajo_stock']}</p>";
                echo "<p class='high-stock'>Productos con stock alto: {$row['productos_alto_stock']}</p>";
                ?>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>