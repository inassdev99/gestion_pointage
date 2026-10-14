<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$id = isset($_GET['id']) ? $_GET['id'] : 0;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM employe WHERE id = ?");
    $stmt->execute([$id]);
}

rediriger('employes.php');
?>