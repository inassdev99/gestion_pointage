<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    http_response_code(403);
    exit('Accès interdit');
}

$employe_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Récupérer l'employé
$stmt = $pdo->prepare("SELECT matricule, nom, prenom FROM employe WHERE id = ?");
$stmt->execute([$employe_id]);
$employe = $stmt->fetch();

if (!$employe) {
    die('Employé non trouvé');
}

// Générer une chaîne unique pour le QR code
$qr_data = json_encode([
    'type' => 'pointage',
    'employe_id' => $employe_id,
    'matricule' => $employe['matricule'],
    'timestamp' => time()
]);

// Encoder en base64
$qr_code = base64_encode($qr_data);

// Sauvegarder dans la base de données
$stmt = $pdo->prepare("UPDATE employe SET qr_code = ? WHERE id = ?");
$stmt->execute([$qr_code, $employe_id]);

// Utiliser une API gratuite pour générer l'image QR code
$api_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
header("Location: " . $api_url);
exit();
?>