<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'viewer') {
    header("Location: ../login.php");
    exit();
}

$titre = "✈️ Consultation des déplacements";

$deplacements = $pdo->query("
    SELECT a.*, e.nom, e.prenom, e.matricule
    FROM absence a
    JOIN employe e ON a.employe_id = e.id
    WHERE a.type_absence = 'déplacement'
    ORDER BY a.date_debut DESC
")->fetchAll();

// Statistiques
$total_deplacements = count($deplacements);
$valides = 0;
$en_attente = 0;
foreach ($deplacements as $d) {
    if ($d['statut'] == 'validé') $valides++;
    elseif ($d['statut'] == 'en_attente') $en_attente++;
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
    --radius:      12px;
    --shadow:      0 2px 12px rgba(30,45,77,0.07);
    --green-bg:    #f0fdf4;
    --green-text:  #15803d;
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --yellow-bg:   #fffbeb;
    --yellow-text: #b45309;
    --teal:        #0d9488;
    --teal-light:  #ccfbf1;
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
    background: linear-gradient(135deg, var(--teal) 0%, #0f766e 100%);
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
    background: var(--teal-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--teal);
    font-size: 0.65rem;
    flex-shrink: 0;
}

/* ══ STATS GRID ══ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 15px 20px;
    text-align: center;
    border: 1px solid var(--border);
    transition: all 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
}
.stat-card .number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--teal);
}
.stat-card .number.green { color: var(--green-text); }
.stat-card .number.yellow { color: var(--yellow-text); }
.stat-card .label {
    font-size: 0.75rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ══ BUTTONS ══ */
.btn-print { 
    background: var(--muted); 
    color: white; 
    border: none; 
    padding: 10px 22px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600; 
    font-size: 0.85rem; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px;
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
.card-header i { color: var(--teal); }
.card-header .badge-count {
    margin-left: auto;
    background: var(--teal-light);
    color: var(--teal);
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
.badge-warning::before { background: var(--yellow-text); }
.badge-success::before { background: var(--green-text); }
.badge-warning { background: var(--yellow-bg); color: var(--yellow-text); }
.badge-success { background: var(--green-bg); color: var(--green-text); }

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
    .navbar, .sidebar, .btn-print, .logout-btn, .user-info, .page-header { display: none; }
    .main-content { margin: 0; padding: 0; }
    .card { box-shadow: none; border: 1px solid #ddd; }
}

/* Responsive */
@media (max-width: 768px) {
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-plane"></i>
    </div>
    <div class="header-content">
        <h1>Consultation des déplacements</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-map-marked-alt"></i>
            </span>
            Consultez l'historique des déplacements professionnels des employés
        </p>
    </div>
</div>

<!-- STATISTIQUES -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?php echo $total_deplacements; ?></div>
        <div class="label"><i class="fas fa-list"></i> Total déplacements</div>
    </div>
    <div class="stat-card">
        <div class="number green"><?php echo $valides; ?></div>
        <div class="label"><i class="fas fa-check-circle"></i> Validés</div>
    </div>
    <div class="stat-card">
        <div class="number yellow"><?php echo $en_attente; ?></div>
        <div class="label"><i class="fas fa-clock"></i> En attente</div>
    </div>
</div>

<!-- CARD : LISTE DES DÉPLACEMENTS -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-plane"></i> Liste des déplacements</span>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="badge-count"><?php echo $total_deplacements; ?> déplacement(s)</span>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($deplacements) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Matricule</th>
                    <th>Période</th>
                    <th>Durée</th>
                    <th>Motif</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deplacements as $d): 
                    $debut = new DateTime($d['date_debut']);
                    $fin = new DateTime($d['date_fin']);
                    $duree = $debut->diff($fin)->days + 1;
                ?>
                <tr>
                    <td><strong><?php echo $d['prenom'] . ' ' . $d['nom']; ?></strong></td>
                    <td><?php echo $d['matricule']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($d['date_debut'])) . ' → ' . date('d/m/Y', strtotime($d['date_fin'])); ?></td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 5px; background: var(--teal-light); color: var(--teal); padding: 2px 10px; border-radius: 20px; font-weight: 600; font-size: 0.75rem;">
                            <i class="fas fa-calendar"></i> <?php echo $duree; ?> jour(s)
                        </span>
                    </td>
                    <td title="<?php echo htmlspecialchars($d['motif']); ?>">
                        <?php echo htmlspecialchars($d['motif'] ?: '-'); ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $d['statut'] == 'validé' ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $d['statut'] == 'validé' ? '✅ Validé' : '⏳ En attente'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-plane"></i>
            <p>Aucun déplacement trouvé</p>
            <small>Aucun déplacement enregistré dans le système</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>