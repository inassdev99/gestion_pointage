<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$qr_code = $data['qr_code'] ?? '';
$date_aujourdhui = date('Y-m-d');

if (empty($qr_code)) {
    echo json_encode(['success' => false, 'message' => 'QR code invalide']);
    exit();
}

// Décoder le QR code
$qr_data = json_decode(base64_decode($qr_code), true);

if (!$qr_data || $qr_data['type'] != 'pointage') {
    echo json_encode(['success' => false, 'message' => 'QR code non reconnu']);
    exit();
}

$employe_id = $qr_data['employe_id'];

// Vérifier si l'employé existe
$stmt = $pdo->prepare("SELECT * FROM employe WHERE id = ? AND statut = 'actif'");
$stmt->execute([$employe_id]);
$employe = $stmt->fetch();

if (!$employe) {
    echo json_encode(['success' => false, 'message' => 'Employé non trouvé']);
    exit();
}

// Vérifier le pointage du jour
$stmt = $pdo->prepare("SELECT * FROM pointage WHERE employe_id = ? AND date_pointage = ?");
$stmt->execute([$employe_id, $date_aujourdhui]);
$pointage = $stmt->fetch();

$response = ['success' => true, 'employe' => $employe];

if (!$pointage) {
    $response['action'] = 'arrivee';
    $response['message'] = 'Prêt à pointer arrivée';
} elseif ($pointage['heure_arrivee'] && !$pointage['heure_depart']) {
    $response['action'] = 'depart';
    $response['message'] = 'Prêt à pointer départ';
    $response['heure_arrivee'] = $pointage['heure_arrivee'];
} else {
    $response['action'] = 'termine';
    $response['message'] = 'Pointage déjà terminé';
}

echo json_encode($response);
?>