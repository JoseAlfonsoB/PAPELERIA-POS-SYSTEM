<?php
function isLoggedIn() {
    return isset($_SESSION['usuario']);
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// Verificar login
if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === 'admin' && $_POST['password'] === '1234') {
        $_SESSION['usuario'] = 'Administrador';
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>