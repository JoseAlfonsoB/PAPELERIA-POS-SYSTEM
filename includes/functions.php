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
    
    exit();
}
?>