    <?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

include 'includes/connection.php';
include 'includes/functions.php';

// Procesar eliminación de producto
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $query = "DELETE FROM productos WHERE id_producto = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $delete_id);
    
    if ($stmt->execute()) {
        $success_msg = "Producto eliminado correctamente";
    } else {
        $error_msg = "Error al eliminar el producto";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - POS Papelería</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Inventario</h1>
            <nav class="nav-menu">
                <a href="dashboard.php">Inicio</a>
                <a href="inventario.php">Inventario</a>
                <a href="ventas.php">Ventas</a>
                <a href="proveedores.php">Proveedores</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="message success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="message error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Productos en inventario</h2>
            <button onclick="openAddProductModal()" class="btn-add" style="margin-bottom: 15px;">+ Agregar Producto</button>
            <!-- Agrega esto justo después del botón "Agregar Producto" -->
<div class="filtros-container" style="margin-bottom: 20px;">
    <div class="form-row">
        <div class="form-col">
            <label for="filtroNombre">Buscar por nombre:</label>
            <input type="text" id="filtroNombre" placeholder="Nombre del producto">
        </div>
        <div class="form-col">
            <label for="filtroCategoria">Filtrar por categoría:</label>
            <select id="filtroCategoria">
                <option value="">Todas las categorías</option>
                <?php
                $query = "SELECT id_categoria, nombre_categoria FROM categorias";
                $result = $conn->query($query);
                while ($cat = $result->fetch_assoc()) {
                    echo "<option value='{$cat['id_categoria']}'>{$cat['nombre_categoria']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-col">
            <label for="filtroStock">Filtrar por stock:</label>
            <select id="filtroStock">
                <option value="">Todos</option>
                <option value="bajo">Stock Bajo</option>
                <option value="normal">Stock Normal</option>
                <option value="alto">Stock Alto</option>
            </select>
        </div>
    </div>
</div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Stock Máximo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.id_producto, p.nombre_producto, c.nombre_categoria, 
                             p.precio_unitario, p.stock_actual, p.stock_minimo, p.stock_maximo
                             FROM productos p
                             JOIN categorias c ON p.id_categoria = c.id_categoria
                             ORDER BY p.nombre_producto";
                    $result = $conn->query($query);
                    
                    while ($row = $result->fetch_assoc()) {
                        $stockClass = '';
                        if ($row['stock_actual'] <= $row['stock_minimo']) {
                            $stockClass = 'low-stock';
                        } elseif ($row['stock_actual'] >= $row['stock_maximo']) {
                            $stockClass = 'high-stock';
                        } else {
                            $stockClass = 'adequate-stock';
                        }
                        
                        echo "<tr>
                                <td>{$row['id_producto']}</td>
                                <td>{$row['nombre_producto']}</td>
                                <td>{$row['nombre_categoria']}</td>
                                <td>$" . number_format($row['precio_unitario'], 2) . "</td>
                                <td class='{$stockClass}'>{$row['stock_actual']}</td>
                                <td>{$row['stock_minimo']}</td>
                                <td>{$row['stock_maximo']}</td>
                                <td class='{$stockClass}'>" . getStockStatus($row['stock_actual'], $row['stock_minimo'], $row['stock_maximo']) . "</td>
                                <td>
                                    <button onclick=\"editProduct({$row['id_producto']})\" class='btn-edit'>Editar</button>
                                    <button onclick=\"confirmDelete({$row['id_producto']}, '{$row['nombre_producto']}')\" class='btn-delete'>Eliminar</button>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para editar producto -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
            <h2>Editar Producto</h2>
            <form id="editProductForm">
                <input type="hidden" id="editProductId">
                <div class="form-group">
                    <label for="editProductName">Nombre:</label>
                    <input type="text" id="editProductName" required>
                </div>
                <div class="form-group">
                    <label for="editProductPrice">Precio:</label>
                    <input type="number" id="editProductPrice" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="editProductStock">Stock Actual:</label>
                    <input type="number" id="editProductStock" required>
                </div>
                <div class="form-group">
                    <label for="editProductMinStock">Stock Mínimo:</label>
                    <input type="number" id="editProductMinStock" required>
                </div>
                <div class="form-group">
                    <label for="editProductMaxStock">Stock Máximo:</label>
                    <input type="number" id="editProductMaxStock" required>
                </div>
                <button type="submit">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <!-- Modal para agregar nuevo producto -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('addProductModal')">&times;</span>
        <h2>Agregar Nuevo Producto</h2>
        <form id="addProductForm">
            <div class="form-group">
                <label for="addProductName">Nombre:</label>
                <input type="text" id="addProductName" required>
            </div>
            <div class="form-group">
                <label for="addProductCategory">Categoría:</label>
                <select id="addProductCategory" required>
                    <?php
                    $query = "SELECT id_categoria, nombre_categoria FROM categorias";
                    $result = $conn->query($query);
                    while ($cat = $result->fetch_assoc()) {
                        echo "<option value='{$cat['id_categoria']}'>{$cat['nombre_categoria']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="addProductPrice">Precio Unitario:</label>
                <input type="number" id="addProductPrice" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="addProductStock">Stock Actual:</label>
                <input type="number" id="addProductStock" value="5" required>
            </div>
            <div class="form-group">
                <label for="addProductMinStock">Stock Mínimo:</label>
                <input type="number" id="addProductMinStock" value="5" required>
            </div>
            <div class="form-group">
                <label for="addProductMaxStock">Stock Máximo:</label>
                <input type="number" id="addProductMaxStock" value="50" required>
            </div>
            <button type="submit">Guardar Producto</button>
        </form>
    </div>
</div>

    <script src="assets/js/main.js"></script>
    <script>
        // Función para confirmar eliminación
        function confirmDelete(id, name) {
            if (confirm(`¿Estás seguro de eliminar el producto "${name}"?`)) {
                window.location.href = `inventario.php?delete_id=${id}`;
            }
        }

        // Función para abrir modal de edición
        function editProduct(id) {
            // Aquí deberías hacer una petición AJAX para obtener los datos del producto
            fetch(`includes/functions.php?action=getProduct&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    document.getElementById('editProductId').value = data.id_producto;
                    document.getElementById('editProductName').value = data.nombre_producto;
                    document.getElementById('editProductPrice').value = data.precio_unitario;
                    document.getElementById('editProductStock').value = data.stock_actual;
                    document.getElementById('editProductMinStock').value = data.stock_minimo;
                    document.getElementById('editProductMaxStock').value = data.stock_maximo;
                    
                    openModal('editModal');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del producto');
                });
        }

        // Manejar envío del formulario de edición
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productData = {
                id: document.getElementById('editProductId').value,
                nombre: document.getElementById('editProductName').value,
                precio: document.getElementById('editProductPrice').value,
                stock: document.getElementById('editProductStock').value,
                min_stock: document.getElementById('editProductMinStock').value,
                max_stock: document.getElementById('editProductMaxStock').value
            };
            
            // Enviar datos al servidor
            fetch('includes/functions.php?action=updateProduct', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(productData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Producto actualizado correctamente');
                    closeModal('editModal');
                    window.location.reload();
                } else {
                    alert(data.error || 'Error al actualizar el producto');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el producto');
            });
        });

        // Función para abrir modal de agregar producto
function openAddProductModal() {
    // Resetear el formulario
    document.getElementById('addProductForm').reset();
    // Establecer valores por defecto
    document.getElementById('addProductStock').value = 5;
    document.getElementById('addProductMinStock').value = 5;
    document.getElementById('addProductMaxStock').value = 50;
    openModal('addProductModal');
}

// Manejar envío del formulario de nuevo producto
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const productData = {
        nombre: document.getElementById('addProductName').value,
        categoria: document.getElementById('addProductCategory').value,
        precio: document.getElementById('addProductPrice').value,
        stock: document.getElementById('addProductStock').value,
        min_stock: document.getElementById('addProductMinStock').value,
        max_stock: document.getElementById('addProductMaxStock').value
    };
    
    // Validar stock mínimo y máximo
    if (parseInt(productData.min_stock) > parseInt(productData.max_stock)) {
        alert('El stock mínimo no puede ser mayor que el stock máximo');
        return;
    }
    
    if (parseInt(productData.stock) < parseInt(productData.min_stock) || 
        parseInt(productData.stock) > parseInt(productData.max_stock)) {
        alert('El stock actual debe estar entre el mínimo y el máximo');
        return;
    }
    
    // Enviar datos al servidor
    fetch('includes/functions.php?action=addProduct', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(productData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Producto agregado correctamente');
            closeModal('addProductModal');
            window.location.reload();
        } else {
            alert(data.error || 'Error al agregar el producto');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al agregar el producto');
    });
});

// Sistema de filtrado
document.addEventListener('DOMContentLoaded', function() {
    const filtroNombre = document.getElementById('filtroNombre');
    const filtroCategoria = document.getElementById('filtroCategoria');
    const filtroStock = document.getElementById('filtroStock');
    const filasProductos = document.querySelectorAll('tbody tr');

    function aplicarFiltros() {
        const textoBusqueda = filtroNombre.value.toLowerCase();
        const categoriaSeleccionada = filtroCategoria.value;
        const stockSeleccionado = filtroStock.value;

        filasProductos.forEach(fila => {
            const nombre = fila.cells[1].textContent.toLowerCase();
            const categoria = fila.cells[2].textContent;
            const estadoStock = fila.cells[7].textContent.toLowerCase();
            const categoriaId = fila.cells[2].getAttribute('data-categoria-id') || '';

            // Verificar si la fila cumple con todos los filtros
            const coincideNombre = nombre.includes(textoBusqueda);
            const coincideCategoria = categoriaSeleccionada === '' || categoriaId === categoriaSeleccionada;
            let coincideStock = true;

            if (stockSeleccionado !== '') {
                if (stockSeleccionado === 'bajo') {
                    coincideStock = estadoStock.includes('bajo');
                } else if (stockSeleccionado === 'normal') {
                    coincideStock = estadoStock.includes('normal');
                } else if (stockSeleccionado === 'alto') {
                    coincideStock = estadoStock.includes('alto');
                }
            }

            if (coincideNombre && coincideCategoria && coincideStock) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }

    // Modifica el bucle de productos para agregar data-categoria-id
    document.querySelectorAll('tbody tr').forEach(fila => {
        const categoriaId = fila.cells[2].getAttribute('data-categoria-id');
        if (!categoriaId) {
            const categoriaNombre = fila.cells[2].textContent;
            // Obtener el ID de la categoría (esto asume que tienes las categorías en un objeto JS)
            const categorias = <?php
                $cats = [];
                $query = "SELECT id_categoria, nombre_categoria FROM categorias";
                $result = $conn->query($query);
                while ($cat = $result->fetch_assoc()) {
                    $cats[$cat['nombre_categoria']] = $cat['id_categoria'];
                }
                echo json_encode($cats);
            ?>;
            if (categorias[categoriaNombre]) {
                fila.cells[2].setAttribute('data-categoria-id', categorias[categoriaNombre]);
            }
        }
    });

    // Event listeners para los filtros
    filtroNombre.addEventListener('input', aplicarFiltros);
    filtroCategoria.addEventListener('change', aplicarFiltros);
    filtroStock.addEventListener('change', aplicarFiltros);
});
    </script>
</body>
</html>