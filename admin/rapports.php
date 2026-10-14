<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$titre = "📊 Rapports";

$mois = isset($_GET['mois']) ? $_GET['mois'] : date('Y-m');
$date_debut = $mois . '-01';
$date_fin = date('Y-m-t', strtotime($date_debut));

// Statistiques avec vérification
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT employe_id) as total FROM pointage WHERE date_pointage BETWEEN ? AND ? AND heure_depart IS NOT NULL");
$stmt->execute([$date_debut, $date_fin]);
$result = $stmt->fetch();
$nb_presents = $result ? $result['total'] : 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM employe WHERE statut = 'actif'");
$stmt->execute();
$result = $stmt->fetch();
$nb_employes = $result ? $result['total'] : 0;

$taux_presence = $nb_employes > 0 ? round(($nb_presents / $nb_employes) * 100) : 0;

// Top employés
$stmt = $pdo->prepare("SELECT e.nom, e.prenom, COUNT(p.id) as nb_pointages 
                       FROM pointage p 
                       JOIN employe e ON p.employe_id = e.id 
                       WHERE p.date_pointage BETWEEN ? AND ? AND p.heure_depart IS NOT NULL
                       GROUP BY e.id 
                       ORDER BY nb_pointages DESC 
                       LIMIT 5");
$stmt->execute([$date_debut, $date_fin]);
$top_employes = $stmt->fetchAll();

// Absences par type
$stmt = $pdo->prepare("SELECT a.type_absence, COUNT(*) as total 
                       FROM absence a 
                       WHERE a.date_debut BETWEEN ? AND ? 
                       GROUP BY a.type_absence");
$stmt->execute([$date_debut, $date_fin]);
$absences_par_type = $stmt->fetchAll();

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
    --green:       #10b981;
    --green-light: #d1fae5;
    --red:         #ef4444;
    --red-light:   #fee2e2;
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
    margin-bottom: 24px; 
    display: flex; 
    gap: 12px; 
    align-items: center; 
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
    background: #fafbfe;
    font-size: 0.85rem;
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
    font-size: 0.85rem;
    transition: all 0.2s;
}
.filter-form button:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

/* ══ EXPORT BUTTONS ══ */
.export-buttons { 
    display: flex; 
    gap: 12px; 
    margin-bottom: 24px; 
    flex-wrap: wrap;
}
.btn-excel { 
    background: var(--green); 
    color: white; 
    padding: 10px 20px; 
    border-radius: 8px; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    font-size: 0.85rem; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-excel:hover { 
    background: #059669; 
    transform: translateY(-2px);
    color: white;
}
.btn-pdf { 
    background: var(--red); 
    color: white; 
    padding: 10px 20px; 
    border-radius: 8px; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    font-size: 0.85rem; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-pdf:hover { 
    background: #dc2626; 
    transform: translateY(-2px);
    color: white;
}

/* ══ STATS GRID ══ */
.stats-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 18px; 
    margin-bottom: 30px; 
}
.stat-card { 
    background: var(--card); 
    padding: 20px 24px; 
    border-radius: var(--radius); 
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: all 0.2s;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.stat-card .stat-icon {
    font-size: 1.5rem;
    color: var(--blue);
    margin-bottom: 8px;
}
.stat-card h2 { 
    font-size: 2rem; 
    font-weight: 800; 
    color: var(--text); 
    margin: 0 0 5px 0; 
}
.stat-card p { 
    font-size: 0.8rem; 
    color: var(--muted); 
    margin: 0; 
}
.stat-card .stat-taux {
    color: var(--blue);
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

/* ══ EMPTY STATE ══ */
.empty-state {
    text-align: center;
    padding: 30px 20px;
    color: var(--muted);
}
.empty-state i {
    font-size: 2rem;
    margin-bottom: 8px;
    opacity: 0.5;
}

/* ══ ROW ══ */
.row {
    display: flex;
    gap: 25px;
}
.row .col-md-6 {
    flex: 1;
    min-width: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-form { flex-wrap: wrap; }
    .row { flex-direction: column; gap: 0; }
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-chart-pie"></i>
    </div>
    <div class="header-content">
        <h1>Rapports et statistiques</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-file-alt"></i>
            </span>
            Analysez les performances et la présence des employés par période
        </p>
    </div>
</div>

<!-- FILTRE -->
<form method="GET" class="filter-form">
    <i class="fas fa-calendar-alt" style="color: var(--blue);"></i>
    <label for="mois">Période :</label>
    <input type="month" name="mois" id="mois" value="<?php echo $mois; ?>">
    <button type="submit"><i class="fas fa-filter"></i> Filtrer</button>
    <span style="color: var(--muted); font-size: 0.8rem; margin-left: auto;">
        <i class="fas fa-calendar-week"></i> <?php echo date('d/m/Y', strtotime($date_debut)); ?> au <?php echo date('d/m/Y', strtotime($date_fin)); ?>
    </span>
</form>

<!-- BOUTONS D'EXPORT -->
<div class="export-buttons">
    <a href="export_excel.php?mois=<?php echo $mois; ?>" class="btn-excel">
        <i class="fas fa-file-excel"></i> Exporter Excel
    </a>
    <a href="export_pdf.php?mois=<?php echo $mois; ?>" class="btn-pdf">
        <i class="fas fa-file-pdf"></i> Exporter PDF
    </a>
</div>

<!-- STATISTIQUES GLOBALES -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <h2><?php echo $nb_employes; ?></h2>
        <p>Total employés actifs</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-fingerprint"></i></div>
        <h2><?php echo $nb_presents; ?></h2>
        <p>Employés ayant pointé</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-percent"></i></div>
        <h2 class="stat-taux"><?php echo $taux_presence; ?>%</h2>
        <p>Taux de présence</p>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        <h2><?php echo date('F Y', strtotime($mois . '-01')); ?></h2>
        <p>Période analysée</p>
    </div>
</div>

<!-- GRAPHIQUES (2 COLONNES) -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-trophy"></i> Top 5 employés les plus ponctuels
                <span class="badge-count"><?php echo count($top_employes); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($top_employes) > 0): ?>
                <table>
                    <thead>
                        <tr><th>#</th><th>Employé</th><th>Pointages</th></tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($top_employes as $e): ?>
                        <tr>
                            <td>
                                <?php if ($rank == 1): ?>
                                    <span style="color: #f59e0b;">🥇</span>
                                <?php elseif ($rank == 2): ?>
                                    <span style="color: #94a3b8;">🥈</span>
                                <?php elseif ($rank == 3): ?>
                                    <span style="color: #d97706;">🥉</span>
                                <?php else: ?>
                                    <span style="color: var(--muted);">#<?php echo $rank; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $e['prenom'] . ' ' . $e['nom']; ?></strong></td>
                            <td><?php echo $e['nb_pointages']; ?></td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-trophy"></i>
                    <p>Aucune donnée disponible</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Absences par type
                <span class="badge-count"><?php echo count($absences_par_type); ?></span>
            </div>
            <div class="card-body">
                <?php if (count($absences_par_type) > 0): ?>
                <table>
                    <thead>
                        <tr><th>Type d'absence</th><th>Nombre</th><th>%</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_abs = array_sum(array_column($absences_par_type, 'total'));
                        foreach ($absences_par_type as $a): 
                            $pourcent = $total_abs > 0 ? round(($a['total'] / $total_abs) * 100) : 0;
                            $type_label = ucfirst($a['type_absence']);
                            $icon = match($a['type_absence']) {
                                'maladie' => '🤒',
                                'conge' => '🌴',
                                'deplacement' => '🚗',
                                'autorisation' => '📋',
                                default => '📝'
                            };
                        ?>
                        <tr>
                            <td><?php echo $icon . ' ' . $type_label; ?></td>
                            <td><?php echo $a['total']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex:1; height:6px; background:var(--border); border-radius:3px; overflow:hidden; max-width:100px;">
                                        <div style="height:100%; width:<?php echo $pourcent; ?>%; background:var(--blue); border-radius:3px;"></div>
                                    </div>
                                    <span style="font-size:0.7rem; color:var(--muted);"><?php echo $pourcent; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-pie"></i>
                    <p>Aucune donnée disponible</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>