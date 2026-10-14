<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';
$message = $data['message'] ?? '';
$destinataire_id = $data['destinataire_id'] ?? 0;

if ($type == 'email') {
    // Envoyer un email (à configurer avec PHPMailer)
    $sujet = "Notification GestiPoint";
    $headers = "From: noreply@gestipoint.ma\r\n";
    mail($destinataire_id, $sujet, $message, $headers);
    
    echo json_encode(['success' => true, 'method' => 'email']);
    
} elseif ($type == 'sms') {
    // Envoyer un SMS (à configurer avec une API SMS)
    // Exemple avec Twilio ou API SMS Maroc
    
    echo json_encode(['success' => true, 'method' => 'sms']);
    
} else {
    // Notification dans la base de données
    $stmt = $pdo->prepare("INSERT INTO notification (employe_id, admin_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$destinataire_id, null, $message]);
    
    echo json_encode(['success' => true, 'method' => 'database']);
}
?>