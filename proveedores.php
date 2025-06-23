<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

include 'includes/connection.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - POS Papelería</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Proveedores</h1>
            <nav class="nav-menu">
                <a href="dashboard.php">Inicio</a>
                <a href="inventario.php">Inventario</a>
                <a href="ventas.php">Ventas</a>
                <a href="proveedores.php">Proveedores</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </div>

        <div class="form-container">
            <h2>Agregar Productos al Inventario</h2>
            <form id="supplierForm">
                <div class="form-row">
                    <div class="form-col">
                        <label for="proveedor">Proveedor:</label>
                        <select id="proveedor" name="proveedor" required>
                            <option value="">Seleccione un proveedor</option>
                            <?php
                            $query = "SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor";
                            $result = $conn->query($query);
                            
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id_proveedor']}'>{$row['nombre_proveedor']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="fecha">Fecha de Compra:</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label for="producto">Producto:</label>
                        <select id="producto" name="producto" required>
                            <option value="">Seleccione un producto</option>
                            <?php
                            $query = "SELECT id_producto, nombre_producto FROM productos ORDER BY nombre_producto";
                            $result = $conn->query($query);
                            
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id_producto']}'>{$row['nombre_producto']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="cantidad">Cantidad:</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label for="precio">Precio Unitario:</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                    </div>
                    <div class="form-col">
                        <label for="stockActual">Stock Actual:</label>
                        <input type="number" id="stockActual" name="stockActual" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label for="stockMaximo">Stock Máximo:</label>
                        <input type="number" id="stockMaximo" name="stockMaximo" readonly>
                    </div>
                    <div class="form-col">
                        <label for="nuevoStock">Nuevo Stock:</label>
                        <input type="number" id="nuevoStock" name="nuevoStock" readonly>
                    </div>
                </div>

                <button type="submit">Registrar Compra</button>
            </form>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h2>Historial de Compras</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT cp.fecha_compra, pr.nombre_proveedor, p.nombre_producto, 
                             dc.cantidad, dc.precio_unitario, dc.subtotal
                             FROM compras_proveedor cp
                             JOIN proveedores pr ON cp.id_proveedor = pr.id_proveedor
                             JOIN detalle_compra dc ON cp.id_compra = dc.id_compra
                             JOIN productos p ON dc.id_producto = p.id_producto
                             ORDER BY cp.fecha_compra DESC
                             LIMIT 10";
                    $result = $conn->query($query);
                    
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['fecha_compra']}</td>
                                <td>{$row['nombre_proveedor']}</td>
                                <td>{$row['nombre_producto']}</td>
                                <td>{$row['cantidad']}</td>
                                <td>$" . number_format($row['precio_unitario'], 2) . "</td>
                                <td>$" . number_format($row['subtotal'], 2) . "</td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Actualizar información de stock al seleccionar un producto
        document.getElementById('producto').addEventListener('change', function() {
            const productId = this.value;
            
            if (productId) {
                fetch(`includes/functions.php?action=getProductInfo&id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('stockActual').value = data.stock_actual;
                        document.getElementById('stockMaximo').value = data.stock_maximo;
                        calculateNewStock();
                    });
            } else {
                document.getElementById('stockActual').value = '';
                document.getElementById('stockMaximo').value = '';
                document.getElementById('nuevoStock').value = '';
            }
        });
        
        // Calcular nuevo stock al cambiar la cantidad
        document.getElementById('cantidad').addEventListener('input', calculateNewStock);
        
        function calculateNewStock() {
            const stockActual = parseInt(document.getElementById('stockActual').value) || 0;
            const cantidad = parseInt(document.getElementById('cantidad').value) || 0;
            const stockMaximo = parseInt(document.getElementById('stockMaximo').value) || 0;
            
            const nuevoStock = stockActual + cantidad;
            document.getElementById('nuevoStock').value = nuevoStock;
            
            if (nuevoStock > stockMaximo) {
                document.getElementById('nuevoStock').style.backgroundColor = '#ffdddd';
            } else {
                document.getElementById('nuevoStock').style.backgroundColor = '';
            }
        }
        
        // Validar formulario antes de enviar
        document.getElementById('supplierForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const stockActual = parseInt(document.getElementById('stockActual').value) || 0;
            const cantidad = parseInt(document.getElementById('cantidad').value) || 0;
            const stockMaximo = parseInt(document.getElementById('stockMaximo').value) || 0;
            
            if (stockActual + cantidad > stockMaximo) {
                alert('La cantidad ingresada excede el stock máximo permitido para este producto');
                return;
            }
            
            // Aquí iría la lógica para enviar el formulario al servidor
            alert('Compra registrada con éxito!');
            this.reset();
        });
    </script>
</body>
</html>