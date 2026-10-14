<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$titre = "📜 Historique des pointages";

$mois = isset($_GET['mois']) ? $_GET['mois'] : date('Y-m');
$date_debut = $mois . '-01';
$date_fin = date('Y-m-t', strtotime($date_debut));

// Récupérer tous les pointages du mois
$pointages = $pdo->prepare("
    SELECT p.*, e.nom, e.prenom, e.matricule, d.nom as departement_nom
    FROM pointage p
    JOIN employe e ON p.employe_id = e.id
    LEFT JOIN departement d ON e.departement_id = d.id
    WHERE p.date_pointage BETWEEN ? AND ?
    ORDER BY p.date_pointage DESC, e.nom ASC
");
$pointages->execute([$date_debut, $date_fin]);
$pointages = $pointages->fetchAll();

// Statistiques par employé
$stats = [];
foreach ($pointages as $p) {
    if (!isset($stats[$p['employe_id']])) {
        $stats[$p['employe_id']] = [
            'nom' => $p['nom'],
            'prenom' => $p['prenom'],
            'matricule' => $p['matricule'],
            'departement' => $p['departement_nom'],
            'nb_jours' => 0,
            'total_heures' => 0
        ];
    }
    $stats[$p['employe_id']]['nb_jours']++;
    if ($p['heure_arrivee'] && $p['heure_depart']) {
        $heures = calculerHeures($p['heure_arrivee'], $p['heure_pause_debut'], $p['heure_pause_fin'], $p['heure_depart']);
        $parts = explode(':', $heures);
        $stats[$p['employe_id']]['total_heures'] += (int)$parts[0] + ((int)$parts[1] / 60);
    }
}

include '../includes/header.php';
?>

<style>
/* ══ VARIABLES ══ */
:root {
    --blue:        #2563eb;
    --blue-light:  #eff6ff;
    --bg:          #f4f6fb;
    --card:        #fff;
    --border:      #e5e9f2;
    --text:        #1a2340;
    --muted:       #6b7a99;
    --green-bg:    #f0fdf4;
    --green-text:  #15803d;
    --yellow-bg:   #fffbeb;
    --yellow-text: #b45309;
    --radius:      12px;
    --shadow:      0 2px 12px rgba(30,45,77,0.07);
}

body {
    background-color: var(--bg) !important;
    font-family: 'Inter', system-ui, sans-serif;
}

.container {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 25px;
}

/* ══ PAGE HEADER AVEC LOGOS ══ */
.page-header {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
}

.page-header .header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.page-header .header-content {
    flex: 1;
}

.page-header .header-content h1 {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 4px 0;
}

.page-header .header-content .subtitle {
    color: var(--muted);
    font-size: 0.95rem;
    margin: 0;
    font-weight: 400;
    display: flex;
    align-items: center;
    gap: 8px;
}

.page-header .header-content .subtitle .sub-icon {
    width: 24px;
    height: 24px;
    background: var(--blue-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--blue);
    font-size: 0.65rem;
    flex-shrink: 0;
}

/* ══ FILTER FORM ══ */
.filter-form { 
    display: flex; 
    gap: 15px; 
    align-items: center; 
    margin-bottom: 20px; 
    flex-wrap: wrap;
    background: var(--card);
    padding: 15px 20px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}
.filter-form label {
    font-weight: 600;
    color: var(--text);
    font-size: 0.85rem;
}
.filter-form input { 
    padding: 8px 12px; 
    border-radius: 8px; 
    border: 1.5px solid var(--border); 
    font-size: 0.85rem; 
    background: #fafbfe;
    transition: all 0.2s;
}
.filter-form input:focus {
    border-color: var(--blue);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
}
.filter-form button { 
    background: var(--blue); 
    color: white; 
    border: none; 
    padding: 8px 20px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.filter-form button:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

/* ══ CARDS ══ */
.card { 
    background: var(--card); 
    border-radius: var(--radius); 
    box-shadow: var(--shadow); 
    margin-bottom: 25px; 
    overflow: hidden;
    border: 1px solid var(--border);
}
.card-header { 
    padding: 15px 20px; 
    border-bottom: 1px solid var(--border); 
    font-weight: 600; 
    font-size: 0.92rem;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
}
.card-header i { color: var(--blue); }
.card-header .badge-count {
    margin-left: auto;
    background: var(--blue-light);
    color: var(--blue);
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
}
.card-body { padding: 0; }

/* ══ TABLE ══ */
table { width: 100%; border-collapse: collapse; }
th { 
    padding: 11px 13px; 
    font-size: 0.76rem; 
    font-weight: 600; 
    color: var(--muted); 
    text-align: left; 
    background: #f8fafc; 
    border-bottom: 1px solid var(--border); 
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
td { padding: 11px 13px; font-size: 0.82rem; color: var(--text); border-bottom: 1px solid #f0f2f8; }
tbody tr:hover { background: #f8fafc; }

/* ══ BADGES ══ */
.badge { 
    display: inline-flex; 
    align-items: center; 
    gap: 5px; 
    padding: 4px 10px; 
    border-radius: 50px; 
    font-size: 0.72rem; 
    font-weight: 600; 
}
.badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.badge-success::before { background: var(--green-text); }
.badge-warning::before { background: var(--yellow-text); }
.badge-success { background: var(--green-bg); color: var(--green-text); }
.badge-warning { background: var(--yellow-bg); color: var(--yellow-text); }

/* ══ EMPTY STATE ══ */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--muted);
}
.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 10px;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-form { flex-wrap: wrap; }
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-history"></i>
    </div>
    <div class="header-content">
        <h1>Historique des pointages</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-chart-bar"></i>
            </span>
            Consultez l'historique complet des pointages par mois avec récapitulatif
        </p>
    </div>
</div>

<!-- FILTRE -->
<div class="filter-form">
    <i class="fas fa-calendar-alt" style="color: var(--blue);"></i>
    <label for="mois">Période :</label>
    <form method="GET" style="display: flex; gap: 15px; align-items: center; margin: 0;">
        <input type="month" name="mois" id="mois" value="<?php echo $mois; ?>">
        <button type="submit"><i class="fas fa-filter"></i> Filtrer</button>
    </form>
    <span style="color: var(--muted); font-size: 0.8rem; margin-left: auto;">
        <i class="fas fa-calendar-week"></i> <?php echo date('d/m/Y', strtotime($date_debut)); ?> au <?php echo date('d/m/Y', strtotime($date_fin)); ?>
    </span>
</div>

<!-- STATISTIQUES PAR EMPLOYÉ -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-chart-line"></i> Récapitulatif - <?php echo date('F Y', strtotime($mois . '-01')); ?>
        <span class="badge-count"><?php echo count($stats); ?> employé(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($stats) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Employé</th>
                    <th>Département</th>
                    <th>Jours travaillés</th>
                    <th>Total heures</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $s): ?>
                <tr>
                    <td><strong><?php echo $s['matricule']; ?></strong></td>
                    <td><?php echo $s['prenom'] . ' ' . $s['nom']; ?></td>
                    <td><?php echo $s['departement'] ?: '-'; ?></td>
                    <td><?php echo $s['nb_jours']; ?> jour(s)</td>
                    <td><strong><?php echo floor($s['total_heures']) . 'h ' . round(($s['total_heures'] - floor($s['total_heures'])) * 60) . 'min'; ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-line"></i>
            <p>Aucun pointage trouvé pour cette période</p>
            <small>Sélectionnez un autre mois</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- DÉTAIL DES POINTAGES (SANS IP) -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Détail des pointages
        <span class="badge-count"><?php echo count($pointages); ?> pointage(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($pointages) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employé</th>
                    <th>Matricule</th>
                    <th>Arrivée</th>
                    <th>Pause début</th>
                    <th>Pause fin</th>
                    <th>Départ</th>
                    <th>Total</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pointages as $p): 
                    $heures = calculerHeures($p['heure_arrivee'], $p['heure_pause_debut'], $p['heure_pause_fin'], $p['heure_depart']);
                    $statut = $p['statut'] ?? 'normal';
                    $badge = $statut == 'retard' ? 'badge-warning' : 'badge-success';
                ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($p['date_pointage'])); ?></td>
                    <td><strong><?php echo $p['prenom'] . ' ' . $p['nom']; ?></strong></td>
                    <td><?php echo $p['matricule']; ?></td>
                    <td><?php echo $p['heure_arrivee'] ? date('H:i', strtotime($p['heure_arrivee'])) : '-'; ?></td>
                    <td><?php echo $p['heure_pause_debut'] ? date('H:i', strtotime($p['heure_pause_debut'])) : '-'; ?></td>
                    <td><?php echo $p['heure_pause_fin'] ? date('H:i', strtotime($p['heure_pause_fin'])) : '-'; ?></td>
                    <td><?php echo $p['heure_depart'] ? date('H:i', strtotime($p['heure_depart'])) : '-'; ?></td>
                    <td><strong><?php echo $heures; ?></strong></td>
                    <td><span class="badge <?php echo $badge; ?>"><?php echo $statut; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <p>Aucun pointage trouvé pour cette période</p>
            <small>Sélectionnez un autre mois</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>