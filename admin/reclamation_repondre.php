<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $reponse = $_POST['reponse'];
    
    $stmt = $pdo->prepare("UPDATE reclamation SET reponse = ?, statut = 'traité', traite_par = ? WHERE id = ?");
    $stmt->execute([$reponse, $user_id, $id]);
}

rediriger('reclamations.php');
?>