<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    exit(json_encode(['error' => 'Non autorisé']));
}

$stmt = $pdo->query("SELECT COUNT(*) as nouvelles FROM alertes_fraude WHERE statut = 'non_lu'");
$result = $stmt->fetch();

echo json_encode(['nouvelles' => $result['nouvelles']]);
?>