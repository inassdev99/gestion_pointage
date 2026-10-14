<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Récupérer la période
$mois = isset($_GET['mois']) ? $_GET['mois'] : date('Y-m');
$date_debut = $mois . '-01';
$date_fin = date('Y-m-t', strtotime($date_debut));

// Récupérer les données
$stmt = $pdo->prepare("
    SELECT 
        e.matricule, 
        e.nom, 
        e.prenom, 
        d.nom as departement,
        p.date_pointage, 
        p.heure_arrivee, 
        p.heure_pause_debut, 
        p.heure_pause_fin, 
        p.heure_depart,
        CASE 
            WHEN p.heure_pause_debut IS NOT NULL AND p.heure_pause_fin IS NOT NULL THEN 1
            ELSE 0
        END as nb_pauses,
        p.statut,
        p.ip_adresse
    FROM pointage p
    JOIN employe e ON p.employe_id = e.id
    LEFT JOIN departement d ON e.departement_id = d.id
    WHERE p.date_pointage BETWEEN ? AND ?
    ORDER BY p.date_pointage DESC, e.nom ASC
");
$stmt->execute([$date_debut, $date_fin]);
$pointages = $stmt->fetchAll();

// Création du fichier CSV (Excel)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="rapport_pointages_admin_' . $mois . '.csv"');

$output = fopen('php://output', 'w');

// UTF-8 BOM pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes du tableau
fputcsv($output, [
    'Matricule',
    'Nom',
    'Prénom',
    'Département',
    'Date',
    'Arrivée',
    'Sortie',
    'Pause Début',
    'Pause Fin',
    'Nombre de Pauses',
    'Heures Travaillées',
    'Statut',
    'Adresse IP'
], ';');

// Données
foreach ($pointages as $p) {
    // Calculer les heures travaillées
    $heures_travaillees = calculerHeures(
        $p['heure_arrivee'], 
        $p['heure_pause_debut'], 
        $p['heure_pause_fin'], 
        $p['heure_depart']
    );
    
    fputcsv($output, [
        $p['matricule'],
        $p['nom'],
        $p['prenom'],
        $p['departement'] ?: 'Non affecté',
        date('d/m/Y', strtotime($p['date_pointage'])),
        $p['heure_arrivee'] ? date('H:i', strtotime($p['heure_arrivee'])) : '',
        $p['heure_depart'] ? date('H:i', strtotime($p['heure_depart'])) : '',
        $p['heure_pause_debut'] ? date('H:i', strtotime($p['heure_pause_debut'])) : '',
        $p['heure_pause_fin'] ? date('H:i', strtotime($p['heure_pause_fin'])) : '',
        $p['nb_pauses'],
        $heures_travaillees,
        $p['statut'] ?: 'normal',
        $p['ip_adresse'] ?: '-'
    ], ';');
}

fclose($output);
exit();
?>