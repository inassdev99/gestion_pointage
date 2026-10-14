<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$mois = isset($_GET['mois']) ? $_GET['mois'] : date('Y-m');
$date_debut = $mois . '-01';
$date_fin = date('Y-m-t', strtotime($date_debut));

$stmt = $pdo->prepare("
    SELECT e.nom, e.prenom, d.nom as departement, 
           p.date_pointage, p.heure_arrivee, p.heure_depart,
           p.heure_pause_debut, p.heure_pause_fin, p.ip_adresse
    FROM pointage p
    JOIN employe e ON p.employe_id = e.id
    LEFT JOIN departement d ON e.departement_id = d.id
    WHERE p.date_pointage BETWEEN ? AND ?
    ORDER BY p.date_pointage DESC, e.nom ASC");
$stmt->execute([$date_debut, $date_fin]);
$stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport mensuel - <?php echo $mois; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #1e3c72; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #1e3c72; color: white; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <h1>Rapport mensuel - <?php echo date('F Y', strtotime($mois . '-01')); ?></h1>
    <p>Date d'édition : <?php echo date('d/m/Y H:i'); ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employé</th>
                <th>Département</th>
                <th>Entrée</th>
                <th>Sortie</th>
                <th>Pause</th>
                <th>Total Heures</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stats as $s): 
                $heures = calculerHeures($s['heure_arrivee'], $s['heure_pause_debut'], $s['heure_pause_fin'], $s['heure_depart']);
                $nb_pauses = ($s['heure_pause_debut'] && $s['heure_pause_fin']) ? 1 : 0;
            ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($s['date_pointage'])); ?></td>
                <td><?php echo $s['prenom'] . ' ' . $s['nom']; ?></td>
                <td><?php echo htmlspecialchars($s['departement'] ?: '-'); ?></td>
                <td><?php echo $s['heure_arrivee'] ? date('H:i', strtotime($s['heure_arrivee'])) : '-'; ?></td>
                <td><?php echo $s['heure_depart'] ? date('H:i', strtotime($s['heure_depart'])) : '-'; ?></td>
                <td><?php echo $nb_pauses; ?></td>
                <td><?php echo $heures; ?></td>
                <td><?php echo htmlspecialchars($s['ip_adresse'] ?: '-'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">GestiPoint - Système de gestion de pointage</div>

    <div class="no-print" style="text-align:center; margin-top:20px;"><button onclick="window.print()">Imprimer / Sauvegarder PDF</button></div>
    
    <script>
        window.print();
    </script>
</body>
</html>