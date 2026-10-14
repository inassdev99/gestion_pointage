<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$mois = isset($_GET['mois']) ? $_GET['mois'] : date('Y-m');
$date_debut = $mois . '-01';
$date_fin = date('Y-m-t', strtotime($date_debut));

$stmt = $pdo->prepare("
    SELECT e.matricule, e.nom, e.prenom, d.nom as departement,
           p.date_pointage, p.heure_arrivee, p.heure_pause_debut, 
           p.heure_pause_fin, p.heure_depart, p.statut
    FROM pointage p
    JOIN employe e ON p.employe_id = e.id
    LEFT JOIN departement d ON e.departement_id = d.id
    WHERE p.date_pointage BETWEEN ? AND ?
    ORDER BY p.date_pointage DESC, e.nom ASC
");
$stmt->execute([$date_debut, $date_fin]);
$pointages = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pointages_' . $mois . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, ['Matricule', 'Nom', 'Prénom', 'Département', 'Date', 'Arrivée', 'Pause Début', 'Pause Fin', 'Départ', 'Heures', 'Statut'], ';');

foreach ($pointages as $p) {
    $heures = calculerHeures($p['heure_arrivee'], $p['heure_pause_debut'], $p['heure_pause_fin'], $p['heure_depart']);
    fputcsv($output, [
        $p['matricule'], $p['nom'], $p['prenom'], $p['departement'],
        date('d/m/Y', strtotime($p['date_pointage'])),
        $p['heure_arrivee'] ? date('H:i', strtotime($p['heure_arrivee'])) : '',
        $p['heure_pause_debut'] ? date('H:i', strtotime($p['heure_pause_debut'])) : '',
        $p['heure_pause_fin'] ? date('H:i', strtotime($p['heure_pause_fin'])) : '',
        $p['heure_depart'] ? date('H:i', strtotime($p['heure_depart'])) : '',
        $heures, $p['statut']
    ], ';');
}

fclose($output);
exit();
?>