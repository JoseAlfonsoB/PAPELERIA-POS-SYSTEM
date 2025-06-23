<?php
function getStockStatus($stockActual, $stockMinimo, $stockMaximo) {
    if ($stockActual <= $stockMinimo) {
        return 'Stock Bajo';
    } elseif ($stockActual >= $stockMaximo) {
        return 'Stock Alto';
    } else {
        return 'Normal';
    }
}

// Manejar solicitudes AJAX
if (isset($_GET['action'])) {
    include 'connection.php';
    header('Content-Type: application/json');
    
    if ($_GET['action'] == 'getProduct') {
        $productId = intval($_GET['id']);
        $query = "SELECT * FROM productos WHERE id_producto = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Producto no encontrado']);
        }
    }
    elseif ($_GET['action'] == 'updateProduct') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar valores de stock
        $minStock = intval($data['min_stock']);
        $maxStock = intval($data['max_stock']);
        $currentStock = intval($data['stock']);
        
        if ($minStock < 5) {
            echo json_encode(['error' => 'El stock mínimo no puede ser menor a 5']);
            exit();
        }
        
        if ($maxStock > 50) {
            echo json_encode(['error' => 'El stock máximo no puede ser mayor a 50']);
            exit();
        }
        
        if ($minStock > $maxStock) {
            echo json_encode(['error' => 'El stock mínimo no puede ser mayor que el stock máximo']);
            exit();
        }
        
        if ($currentStock < $minStock || $currentStock > $maxStock) {
            echo json_encode(['error' => 'El stock actual debe estar entre el mínimo y el máximo']);
            exit();
        }

        $query = "UPDATE productos SET 
                  nombre_producto = ?, 
                  precio_unitario = ?, 
                  stock_actual = ?, 
                  stock_minimo = ?, 
                  stock_maximo = ? 
                  WHERE id_producto = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            'sdiiii',
            $data['nombre'],
            $data['precio'],
            $data['stock'],
            $data['min_stock'],
            $data['max_stock'],
            $data['id']
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al actualizar el producto']);
        }
    }
    elseif ($_GET['action'] == 'addProduct') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $query = "INSERT INTO productos (id_categoria, nombre_producto, precio_unitario, 
                  stock_actual, stock_minimo, stock_maximo) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            'isdiii',
            $data['categoria'],
            $data['nombre'],
            $data['precio'],
            $data['stock'],
            $data['min_stock'],
            $data['max_stock']
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al agregar el producto']);
        }
    }
    elseif ($_GET['action'] == 'getProductInfo') {
        $productId = intval($_GET['id']);
        $query = "SELECT stock_actual, stock_maximo FROM productos WHERE id_producto = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Producto no encontrado']);
        }
    }
    elseif ($_GET['action'] == 'registerPurchase') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // 1. Obtener o crear proveedor
        $query_proveedor = "SELECT id_proveedor FROM proveedores LIMIT 1";
        $result = $conn->query($query_proveedor);
        
        if ($result->num_rows > 0) {
            $proveedor = $result->fetch_assoc();
            $id_proveedor = $proveedor['id_proveedor'];
        } else {
            // Crear proveedor por defecto si no existe
            $query = "INSERT INTO proveedores (nombre_proveedor) VALUES ('Proveedor General')";
            $conn->query($query);
            $id_proveedor = $conn->insert_id;
        }
        
        // 2. Registrar la compra
        $query = "INSERT INTO compras_proveedor (id_proveedor, id_usuario, total, estado) 
                 VALUES (?, ?, 0, 'recibida')";
        $stmt = $conn->prepare($query);
        $id_usuario = $_SESSION['id_usuario'] ?? 1;
        $stmt->bind_param('ii', $id_proveedor, $id_usuario);
        $stmt->execute();
        $id_compra = $conn->insert_id;
        
        // 3. Calcular subtotal
        $subtotal = $data['cantidad'] * $data['precio'];
        
        // 4. Registrar detalle de compra
        $query = "INSERT INTO detalle_compra 
                 (id_compra, id_producto, cantidad, precio_unitario, subtotal) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            'iiidd',
            $id_compra,
            $data['producto_id'],
            $data['cantidad'],
            $data['precio'],
            $subtotal
        );
        $stmt->execute();
        
        // 5. Actualizar el total en la compra
        $query = "UPDATE compras_proveedor SET total = ? WHERE id_compra = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('di', $subtotal, $id_compra);
        $stmt->execute();
        
        // Confirmar transacción
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        echo json_encode(['error' => 'Error al registrar la compra: ' . $e->getMessage()]);
    }
}
    
    exit();
}
?>