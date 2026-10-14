<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'viewer') {
    header("Location: ../login.php");
    exit();
}

$titre = "📅 Consultation des pointages";

$mois = isset($_GET['mois']) ? $_GET['mois'] : date('Y-m');
$date_debut = $mois . '-01';
$date_fin = date('Y-m-t', strtotime($date_debut));

// ═══════════════════════════════════════════════════════════
// ═══ REQUÊTE MODIFIÉE POUR INCLURE LES ABSENCES ═══
// ═══════════════════════════════════════════════════════════
$stmt = $pdo->prepare("
    SELECT 
        e.id as employe_id,
        e.nom, 
        e.prenom, 
        e.matricule,
        d.nom as departement_nom,
        p.date_pointage,
        p.heure_arrivee,
        p.heure_pause_debut,
        p.heure_pause_fin,
        p.heure_depart,
        p.statut as pointage_statut,
        -- Absences du mois
        a.id as absence_id,
        a.type_absence,
        a.date_debut,
        a.date_fin,
        a.statut as absence_statut,
        a.motif as absence_motif
    FROM employe e
    LEFT JOIN departement d ON e.departement_id = d.id
    LEFT JOIN pointage p ON e.id = p.employe_id AND p.date_pointage BETWEEN ? AND ?
    LEFT JOIN absence a ON e.id = a.employe_id 
        AND a.date_debut BETWEEN ? AND ?
        AND a.statut = 'validé'
    WHERE e.statut = 'actif'
    ORDER BY e.nom ASC, p.date_pointage DESC
");
$stmt->execute([$date_debut, $date_fin, $date_debut, $date_fin]);
$employes_data = $stmt->fetchAll();

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
    --orange-bg:   #fff7ed;
    --orange-text: #c2410c;
    --teal-bg:     #ccfbf1;
    --teal-text:   #0d9488;
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --radius:      12px;
    --shadow:      0 2px 12px rgba(30,45,77,0.07);
}

body {
    background-color: var(--bg) !important;
    font-family: 'Inter', system-ui, sans-serif;
}

.container {
    max-width: 1200px;
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
    gap: 12px; 
    align-items: center; 
    margin-bottom: 24px; 
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
    padding: 8px 18px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600; 
    font-size: 0.85rem;
    transition: all 0.2s;
}
.filter-form button:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}
.filter-form .period-info {
    color: var(--muted);
    font-size: 0.85rem;
    margin-left: auto;
}
.filter-form .period-info i {
    margin-right: 5px;
}

/* ══ BUTTONS ══ */
.btn-print { 
    background: var(--muted); 
    color: white; 
    border: none; 
    padding: 8px 20px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600; 
    font-size: 0.85rem;
    transition: all 0.2s;
}
.btn-print:hover { 
    background: #4b5563; 
    transform: translateY(-1px);
}

/* ══ CARD ══ */
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
.badge-danger::before { background: var(--red-text); }
.badge-success { background: var(--green-bg); color: var(--green-text); }
.badge-warning { background: var(--yellow-bg); color: var(--yellow-text); }
.badge-danger { background: var(--red-bg); color: var(--red-text); }

.badge-absence { background: var(--orange-bg); color: var(--orange-text); }
.badge-absence::before { background: var(--orange-text); }
.badge-deplacement { background: var(--teal-bg); color: var(--teal-text); }
.badge-deplacement::before { background: var(--teal-text); }
.badge-conge { background: #fef3c7; color: #b45309; }
.badge-conge::before { background: #b45309; }
.badge-retard { background: var(--yellow-bg); color: var(--yellow-text); }
.badge-retard::before { background: var(--yellow-text); }
.badge-normal { background: var(--green-bg); color: var(--green-text); }
.badge-normal::before { background: var(--green-text); }

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

/* ══ PRINT ══ */
@media print {
    .navbar, .sidebar, .filter-form, .btn-print, .back-link, .logout-btn, .user-info, .page-header { display: none; }
    .main-content { margin: 0; padding: 0; }
    .card { box-shadow: none; border: 1px solid #ddd; }
}

/* Responsive */
@media (max-width: 768px) {
    .filter-form { flex-wrap: wrap; }
    .filter-form .period-info { margin-left: 0; width: 100%; }
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-fingerprint"></i>
    </div>
    <div class="header-content">
        <h1>Consultation des pointages</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-clock"></i>
            </span>
            Consultez l'historique des pointages et absences par période
        </p>
    </div>
</div>

<!-- FILTRE -->
<div class="filter-form">
    <i class="fas fa-calendar-alt" style="color: var(--blue);"></i>
    <form method="GET" style="display: flex; gap: 15px; align-items: center; margin: 0;">
        <label for="mois">Période :</label>
        <input type="month" name="mois" id="mois" value="<?php echo $mois; ?>">
        <button type="submit"><i class="fas fa-filter"></i> Filtrer</button>
    </form>
    <span class="period-info">
        <i class="fas fa-calendar-week"></i> <?php echo date('d/m/Y', strtotime($date_debut)); ?> au <?php echo date('d/m/Y', strtotime($date_fin)); ?>
    </span>
    <button onclick="window.print()" class="btn-print">
        <i class="fas fa-print"></i> Imprimer
    </button>
</div>

<!-- CARD : LISTE DES POINTAGES ET ABSENCES -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-list"></i> Pointages et absences - <?php echo date('F Y', strtotime($mois . '-01')); ?></span>
        <span class="badge-count"><?php echo count($employes_data); ?> employé(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($employes_data) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employé</th>
                    <th>Matricule</th>
                    <th>Département</th>
                    <th>Arrivée</th>
                    <th>Pause début</th>
                    <th>Pause fin</th>
                    <th>Départ</th>
                    <th>Total</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employes_data as $emp): 
                    // Déterminer le statut
                    $status_label = 'Absent';
                    $badge_class = 'badge-danger';
                    
                    if ($emp['absence_id']) {
                        switch($emp['type_absence']) {
                            case 'déplacement':
                                $status_label = 'Déplacement';
                                $badge_class = 'badge-deplacement';
                                break;
                            case 'congé':
                                $status_label = 'Congé';
                                $badge_class = 'badge-conge';
                                break;
                            default:
                                $status_label = 'Absent';
                                $badge_class = 'badge-absence';
                        }
                    } elseif ($emp['heure_arrivee']) {
                        if ($emp['pointage_statut'] == 'retard') {
                            $status_label = 'Retard';
                            $badge_class = 'badge-retard';
                        } else {
                            $status_label = 'Présent';
                            $badge_class = 'badge-normal';
                        }
                    } else {
                        $status_label = 'Absent';
                        $badge_class = 'badge-danger';
                    }
                    
                    $heures = $emp['heure_arrivee'] ? calculerHeures($emp['heure_arrivee'], $emp['heure_pause_debut'], $emp['heure_pause_fin'], $emp['heure_depart']) : '-';
                ?>
                <tr>
                    <td><?php echo $emp['date_pointage'] ? date('d/m/Y', strtotime($emp['date_pointage'])) : '-'; ?></td>
                    <td><strong><?php echo $emp['prenom'] . ' ' . $emp['nom']; ?></strong></td>
                    <td><?php echo $emp['matricule']; ?></td>
                    <td><?php echo $emp['departement_nom'] ?: '-'; ?></td>
                    <td><?php echo $emp['heure_arrivee'] ? date('H:i', strtotime($emp['heure_arrivee'])) : '-'; ?></td>
                    <td><?php echo $emp['heure_pause_debut'] ? date('H:i', strtotime($emp['heure_pause_debut'])) : '-'; ?></td>
                    <td><?php echo $emp['heure_pause_fin'] ? date('H:i', strtotime($emp['heure_pause_fin'])) : '-'; ?></td>
                    <td><?php echo $emp['heure_depart'] ? date('H:i', strtotime($emp['heure_depart'])) : '-'; ?></td>
                    <td><strong><?php echo $heures; ?></strong></td>
                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status_label; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <p>Aucun pointage trouvé</p>
            <small>Aucun pointage enregistré pour cette période</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>