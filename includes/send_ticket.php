<?php
session_start();
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Configuración básica de seguridad
    header('Content-Type: application/json');
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $ticketContent = $_POST['ticketContent'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email no válido']);
        exit;
    }

    $mail = new PHPMailer(true);
    
    try {
        // Configuración SMTP (Ejemplo para Gmail)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'reyessanchezviridiana14@gmail.com'; // Cambiar esto
        $mail->Password = 'dhew erra tgme jipj'; // Cambiar esto
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom('ventas@papeleria.com', 'Papelería POS');
        $mail->addAddress($email);

        // Contenido del ticket
        $mail->isHTML(true);
        $mail->Subject = 'Ticket de Compra - ' . date('d/m/Y');
        
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 300px; margin: auto; border: 1px solid #eee; padding: 20px;">
            <h2 style="text-align: center; color: #333;">Papelería POS</h2>
            <p style="text-align: center;"><small>'.date('d/m/Y H:i').'</small></p>
            '.$ticketContent.'
            <p style="text-align: center; margin-top: 20px; font-size: 0.9em; color: #777;">
                Gracias por su compra
            </p>
        </div>';

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Ticket enviado a '.$email]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
    }
} else {
    header("HTTP/1.1 403 Forbidden");
    echo 'Acceso no permitido';
}
?>