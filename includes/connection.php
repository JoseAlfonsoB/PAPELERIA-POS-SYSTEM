<?php

// Database connection settings
$host = 'localhost'; // Database host
$username = 'root'; // Database username
$password = ''; // Database password
$database = 'papeleria_db'; // Database name

// Create a connection
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    // Solo mostrar error si no es una petición AJAX
    if (!isset($_GET['action'])) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        // Para peticiones AJAX, devolver JSON
        header('Content-Type: application/json');
        die(json_encode(['error' => 'Database connection failed']));
    }
}

// Establecer charset
$conn->set_charset("utf8");
?>