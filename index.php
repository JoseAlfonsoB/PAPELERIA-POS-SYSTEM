<?php
session_start();
include 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS Papelería</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Sistema POS Papelería</h1>
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Iniciar Sesión</button>
            <div id="loginMessage" class="message">
                <?php if (isset($error)) echo $error; ?>
            </div>
        </form>
    </div>

    <!-- Eliminamos el JavaScript de login ya que usaremos PHP -->
</body>
</html>