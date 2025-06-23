<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

include 'includes/connection.php';

// Obtener datos de la venta
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['productos']) || !is_array($data['productos'])) {
    echo json_encode(['success' => false, 'message' => 'Datos de venta inválidos']);
    exit();
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Obtener ID del usuario que realiza la venta
    $id_usuario = $_SESSION['id_usuario'] ?? 1; // Asumimos que hay un id_usuario en la sesión
    
    // Insertar la venta en la tabla ventas
    $stmt = $conn->prepare("INSERT INTO ventas (id_usuario, subtotal, iva, total, metodo_pago) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iddds", $id_usuario, $data['subtotal'], $data['iva'], $data['total'], $data['metodo_pago']);
    $stmt->execute();
    $id_venta = $conn->insert_id;
    $stmt->close();
    
    // Insertar detalles de la venta
    foreach ($data['productos'] as $producto) {
        $subtotal = $producto['price'] * $producto['quantity'];
        
        $stmt = $conn->prepare("INSERT INTO detalle_venta 
                               (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $id_venta, $producto['id'], $producto['quantity'], 
                         $producto['price'], $subtotal);
        $stmt->execute();
        $stmt->close();
    }
    
    // Generar folio para el ticket
    $folio = 'T-' . str_pad($id_venta, 6, '0', STR_PAD_LEFT);
    
    // Insertar registro en la tabla tickets
    $stmt = $conn->prepare("INSERT INTO tickets (id_venta, folio) VALUES (?, ?)");
    $stmt->bind_param("is", $id_venta, $folio);
    $stmt->execute();
    $stmt->close();
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'folio' => $folio,
        'message' => 'Venta registrada correctamente'
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la venta: ' . $e->getMessage()
    ]);
}
?>