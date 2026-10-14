<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employe') {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$mois_courant = date('Y-m');

// Nombre de jours travaillés
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pointage 
                       WHERE employe_id = ? AND date_pointage LIKE ? AND heure_depart IS NOT NULL");
$stmt->execute([$user_id, "$mois_courant%"]);
$nb_jours = $stmt->fetch()['total'];

// Total des heures
$stmt = $pdo->prepare("SELECT * FROM pointage 
                       WHERE employe_id = ? AND date_pointage LIKE ? AND heure_depart IS NOT NULL");
$stmt->execute([$user_id, "$mois_courant%"]);
$pointages = $stmt->fetchAll();

$total_heures = 0;
foreach ($pointages as $p) {
    if ($p['heure_arrivee'] && $p['heure_depart']) {
        $heures = calculerHeures($p['heure_arrivee'], $p['heure_pause_debut'], $p['heure_pause_fin'], $p['heure_depart']);
        $parts = explode(':', $heures);
        $total_heures += (int)$parts[0] + ((int)$parts[1] / 60);
    }
}

$moyenne = $nb_jours > 0 ? round($total_heures / $nb_jours, 1) : 0;

echo json_encode([
    'nb_jours' => $nb_jours,
    'total_heures' => floor($total_heures) . 'h ' . round(($total_heures - floor($total_heures)) * 60) . 'min',
    'moyenne' => $moyenne . 'h'
]);
?>