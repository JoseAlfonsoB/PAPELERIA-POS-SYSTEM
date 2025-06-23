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
    <title>Ventas - POS Papelería</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Punto de Venta</h1>
            <nav class="nav-menu">
                <a href="dashboard.php">Inicio</a>
                <a href="inventario.php">Inventario</a>
                <a href="ventas.php">Ventas</a>
                <a href="proveedores.php">Proveedores</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </div>

        <div class="form-container">
            <div class="form-row">
                <div class="form-col">
                    <h2>Productos Disponibles</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT p.id_producto, p.nombre_producto, p.precio_unitario, p.stock_actual
                                     FROM productos p
                                     WHERE p.stock_actual > 0
                                     ORDER BY p.nombre_producto";
                            $result = $conn->query($query);
                            
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['nombre_producto']}</td>
                                        <td>$" . number_format($row['precio_unitario'], 2) . "</td>
                                        <td>{$row['stock_actual']}</td>
                                        <td><button class='btn-add-to-cart' data-id='{$row['id_producto']}' data-name='{$row['nombre_producto']}' data-price='{$row['precio_unitario']}' data-stock='{$row['stock_actual']}'>Agregar</button></td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-col">
                    <h2>Carrito de Compras</h2>
                    <div id="cartItems"></div>
                    <div id="cartTotal" class="cart-total"></div>
                    
                    <div style="margin-top: 20px;">
                        <button onclick="clearCart()" class="btn-clear-cart">Vaciar Carrito</button>
                        <button onclick="completeSale()" class="btn-complete-sale">Finalizar Venta</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para el ticket -->
    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('ticketModal')">&times;</span>
            <h2>Ticket de Venta</h2>
            <div id="ticketContent">
                <p>Venta completada con éxito!</p>
                <p>Aquí iría el contenido del ticket...</p>
            </div>
            <div style="margin-top: 20px;">
                <button onclick="printTicket()" class="btn-print-ticket">Imprimir Ticket</button>
                <button onclick="sendTicketByEmail()" class="btn-send-ticket">Enviar por Email</button>
            </div>
        </div>
    </div>

    <!-- Modal para cantidad de productos -->
<div id="quantityModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close-modal" onclick="closeModal('quantityModal')">&times;</span>
        <h2 id="quantityModalTitle">Agregar producto</h2>
        <div class="form-group">
            <label for="productQuantity">Cantidad:</label>
            <input type="number" id="productQuantity" min="1" value="1" class="quantity-input">
            <span id="maxStockInfo" style="display: block; margin-top: 5px; color: #666;"></span>
        </div>
        <div class="modal-buttons">
            <button onclick="closeModal('quantityModal')" class="btn-cancel">Cancelar</button>
            <button onclick="confirmAddToCart()" class="btn-confirm">Agregar</button>
        </div>
    </div>
</div>

<!-- Modal para email -->
<div id="emailModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close-modal" onclick="closeModal('emailModal')">&times;</span>
        <h2>Enviar ticket por email</h2>
        <div class="form-group">
            <label for="customerEmail">Email del cliente:</label>
            <input type="email" id="customerEmail" placeholder="ejemplo@cliente.com" class="email-input">
        </div>
        <div class="modal-buttons">
            <button onclick="closeModal('emailModal')" class="btn-cancel">Cancelar</button>
            <button onclick="sendTicket()" class="btn-confirm">Enviar</button>
        </div>
    </div>
</div>

        <script>
    // Carrito de compras - Versión con modales personalizados
    (function() {
        // Funciones para manejar modales
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            }
        };
        
        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        };
        
        // Cerrar modales al hacer clic fuera del contenido
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        };

        // Variables globales
        const cart = [];
        const cartItemsElement = document.getElementById('cartItems');
        const cartTotalElement = document.getElementById('cartTotal');
        let currentProduct = null; // Para almacenar temporalmente los datos del producto

        // Función para mostrar el modal de cantidad
        window.showQuantityModal = function(productId, productName, price, stock) {
            currentProduct = { id: productId, name: productName, price: price, stock: stock };
            document.getElementById('quantityModalTitle').textContent = `Agregar ${productName}`;
            document.getElementById('productQuantity').value = 1;
            document.getElementById('productQuantity').max = stock;
            document.getElementById('maxStockInfo').textContent = `Stock disponible: ${stock}`;
            openModal('quantityModal');
        };
        
        // Confirmar agregar al carrito desde el modal
        window.confirmAddToCart = function() {
            try {
                const quantityInput = document.getElementById('productQuantity');
                const quantity = parseInt(quantityInput.value);
                const stock = currentProduct.stock;
                
                if (isNaN(quantity) || quantity <= 0) {
                    showError('La cantidad debe ser mayor a cero');
                    return;
                }
                
                if (quantity > stock) {
                    showError(`No hay suficiente stock disponible (máximo: ${stock})`);
                    return;
                }
                
                // Buscar si el producto ya está en el carrito
                const existingItem = cart.find(item => item.id === currentProduct.id);
                
                if (existingItem) {
                    existingItem.quantity += quantity;
                } else {
                    cart.push({
                        id: currentProduct.id,
                        name: currentProduct.name,
                        price: currentProduct.price,
                        quantity: quantity
                    });
                }
                
                updateCartDisplay();
                closeModal('quantityModal');
                showSuccess(`${quantity} ${currentProduct.name} agregado(s) al carrito`);
            } catch (error) {
                console.error('Error en confirmAddToCart:', error);
                showError('Ocurrió un error al agregar el producto');
            }
        };
        
        // Mostrar mensaje de error
        function showError(message) {
            const errorElement = document.createElement('div');
            errorElement.className = 'message error';
            errorElement.textContent = message;
            
            const modalContent = document.querySelector('#quantityModal .modal-content');
            const existingMessage = modalContent.querySelector('.message');
            
            if (existingMessage) {
                modalContent.replaceChild(errorElement, existingMessage);
            } else {
                modalContent.insertBefore(errorElement, modalContent.firstChild);
            }
            
            setTimeout(() => {
                errorElement.style.opacity = '0';
                setTimeout(() => errorElement.remove(), 300);
            }, 3000);
        }
        
        // Mostrar mensaje de éxito
        function showSuccess(message) {
            const successElement = document.createElement('div');
            successElement.className = 'message success';
            successElement.textContent = message;
            
            document.body.appendChild(successElement);
            
            setTimeout(() => {
                successElement.style.opacity = '0';
                setTimeout(() => successElement.remove(), 300);
            }, 2000);
        }
        
        // Función para enviar ticket por email
        window.sendTicketByEmail = function() {
            openModal('emailModal');
        };
        
        window.sendTicket = function() {
            const email = document.getElementById('customerEmail').value;
            
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('Por favor ingrese un email válido');
                return;
            }
            
            // Aquí iría la lógica real para enviar el email
            showSuccess(`Ticket enviado a ${email}`);
            closeModal('emailModal');
            closeModal('ticketModal');
        };
        
        // Resto del código se mantiene igual...
        // [Aquí irían todas las demás funciones que ya tenías: updateCartDisplay, removeFromCart, etc.]
        // Función para actualizar la visualización del carrito
window.updateCartDisplay = function() {
    try {
        cartItemsElement.innerHTML = '';
        
        if (cart.length === 0) {
            cartItemsElement.innerHTML = '<p>El carrito está vacío</p>';
            cartTotalElement.innerHTML = '';
            return;
        }
        
        // Crear tabla para los items del carrito
        const table = document.createElement('table');
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio Unitario</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="cartItemsBody"></tbody>
        `;
        
        const tbody = table.querySelector('#cartItemsBody');
        let subtotal = 0;
        
        cart.forEach((item, index) => {
            const itemSubtotal = item.price * item.quantity;
            subtotal += itemSubtotal;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.name}</td>
                <td>$${item.price.toFixed(2)}</td>
                <td>${item.quantity}</td>
                <td>$${itemSubtotal.toFixed(2)}</td>
                <td>
                    <button class="btn-remove-from-cart" data-index="${index}">Eliminar</button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
        // Calcular IVA (16%) y total
        const iva = subtotal * 0.16;
        const total = subtotal + iva;
        
        // Asignar eventos a los botones de eliminar
        table.querySelectorAll('.btn-remove-from-cart').forEach(button => {
            button.addEventListener('click', function() {
                removeFromCart(parseInt(this.getAttribute('data-index')));
            });
        });
        
        cartItemsElement.appendChild(table);
        
        // Mostrar el desglose de precios con IVA
        cartTotalElement.innerHTML = `
            <div class="price-breakdown">
                <p><strong>Subtotal:</strong> $${subtotal.toFixed(2)}</p>
                <p><strong>IVA (16%):</strong> $${iva.toFixed(2)}</p>
                <p><strong>Total:</strong> $${total.toFixed(2)}</p>
            </div>
        `;
    } catch (error) {
        console.error('Error en updateCartDisplay:', error);
        showError('Ocurrió un error al actualizar el carrito');
    }
};

// Función para eliminar un item del carrito
window.removeFromCart = function(index) {
    try {
        if (index >= 0 && index < cart.length) {
            const removedItem = cart.splice(index, 1)[0];
            showSuccess(`${removedItem.name} eliminado del carrito`);
            updateCartDisplay();
        }
    } catch (error) {
        console.error('Error en removeFromCart:', error);
        showError('Ocurrió un error al eliminar el producto');
    }
};

// Función para vaciar el carrito
window.clearCart = function() {
    try {
        if (cart.length > 0) {
            cart.length = 0;
            showSuccess('Carrito vaciado correctamente');
            updateCartDisplay();
        } else {
            showError('El carrito ya está vacío');
        }
    } catch (error) {
        console.error('Error en clearCart:', error);
        showError('Ocurrió un error al vaciar el carrito');
    }
};

// Función para completar la venta
window.completeSale = function() {
    try {
        if (cart.length === 0) {
            showError('El carrito está vacío');
            return;
        }
        
        // Calcular subtotal, IVA y total
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        const iva = subtotal * 0.16;
        const total = subtotal + iva;
        
        // Crear objeto con los datos de la venta
        const saleData = {
            productos: cart,
            subtotal: subtotal,
            iva: iva,
            total: total,
            metodo_pago: 'efectivo' // Puedes modificar esto para que el usuario seleccione
        };
        
        // Enviar datos al servidor
        fetch('process_sale.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(saleData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Generar contenido del ticket con desglose de IVA
                let ticketContent = '<h3>Ticket de Venta</h3>';
                ticketContent += `<p>Folio: ${data.folio}</p>`;
                ticketContent += '<p>Fecha: ' + new Date().toLocaleString() + '</p>';
                ticketContent += '<table><thead><tr><th>Producto</th><th>Cantidad</th><th>Subtotal</th></tr></thead><tbody>';
                
                cart.forEach(item => {
                    const itemSubtotal = item.price * item.quantity;
                    ticketContent += `<tr><td>${item.name}</td><td>${item.quantity}</td><td>$${itemSubtotal.toFixed(2)}</td></tr>`;
                });
                
                ticketContent += '</tbody></table>';
                ticketContent += `
                    <div class="ticket-totals">
                        <p><strong>Subtotal:</strong> $${subtotal.toFixed(2)}</p>
                        <p><strong>IVA (16%):</strong> $${iva.toFixed(2)}</p>
                        <p><strong>Total:</strong> $${total.toFixed(2)}</p>
                    </div>
                `;
                
                document.getElementById('ticketContent').innerHTML = ticketContent;
                openModal('ticketModal');
                
                // Limpiar el carrito
                cart.length = 0;
                updateCartDisplay();
                
                showSuccess('Venta registrada correctamente');
            } else {
                showError(data.message || 'Error al procesar la venta');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error al conectar con el servidor');
        });
        
    } catch (error) {
        console.error('Error en completeSale:', error);
        showError('Ocurrió un error al completar la venta');
    }
};

// Función para imprimir el ticket
window.printTicket = function() {
    try {
        const printContent = document.getElementById('ticketContent').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
        updateCartDisplay(); // Restaurar el estado original
        
        showSuccess('Ticket impreso correctamente');
    } catch (error) {
        console.error('Error en printTicket:', error);
        showError('Ocurrió un error al imprimir el ticket');
    }
};
        
        // Modifica la inicialización para usar showQuantityModal en lugar de addToCart
        function initializeSystem() {
            try {
                // Asignar eventos a los botones de agregar al carrito
                document.querySelectorAll('.btn-add-to-cart').forEach(button => {
                    button.addEventListener('click', function() {
                        showQuantityModal(
                            parseInt(this.getAttribute('data-id')),
                            this.getAttribute('data-name'),
                            parseFloat(this.getAttribute('data-price')),
                            parseInt(this.getAttribute('data-stock'))
                        );
                    });
                });
                
                // Resto de la inicialización...
            } catch (error) {
                console.error('Error en la inicialización:', error);
            }
        }
        
        // Iniciar el sistema
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(initializeSystem, 1);
        } else {
            document.addEventListener('DOMContentLoaded', initializeSystem);
        }
    })();
</script>
</body>
</html>
