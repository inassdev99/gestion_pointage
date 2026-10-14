<?php
require_once 'config.php';
require_once 'fonctions.php';

if (!estConnecte()) {
    rediriger('../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_nom = $_SESSION['user_nom'];
$user_prenom = $_SESSION['user_prenom'];
$user_role = $_SESSION['user_role'];
$user_type = $_SESSION['user_type'] ?? null;
?>