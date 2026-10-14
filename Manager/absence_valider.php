<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'manager') {
    rediriger('../login.php');
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($id && $action) {
    $statut = ($action == 'valider') ? 'validé' : 'refusé';
    
    $stmt = $pdo->prepare("UPDATE absence SET statut = ?, valide_par = ? WHERE id = ?");
    $stmt->execute([$statut, $user_id, $id]);
    
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Demande d\'absence ' . $statut . 'e'];
}

header("Location: absence_equipe.php");
exit();
?>