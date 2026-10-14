<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'viewer') {
    header("Location: ../login.php");
    exit();
}

$titre = "💬 Consultation des réclamations";

$reclamations = $pdo->query("
    SELECT r.*, e.nom, e.prenom, e.matricule
    FROM reclamation r
    JOIN employe e ON r.employe_id = e.id
    ORDER BY r.id DESC
")->fetchAll();

// Statistiques
$total_reclamations = count($reclamations);
$traitees = 0;
$en_attente = 0;
foreach ($reclamations as $r) {
    if ($r['statut'] == 'traité') $traitees++;
    elseif ($r['statut'] == 'en_attente') $en_attente++;
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
    --purple:      #7c3aed;
    --purple-light:#ede9fe;
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
    background: linear-gradient(135deg, var(--purple) 0%, #6d28d9 100%);
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
    background: var(--purple-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--purple);
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
    color: var(--purple);
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
.card-header i { color: var(--purple); }
.card-header .badge-count {
    margin-left: auto;
    background: var(--purple-light);
    color: var(--purple);
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
        <i class="fas fa-comment-dots"></i>
    </div>
    <div class="header-content">
        <h1>Consultation des réclamations</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-pen-fancy"></i>
            </span>
            Consultez l'historique des réclamations des employés
        </p>
    </div>
</div>

<!-- STATISTIQUES -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?php echo $total_reclamations; ?></div>
        <div class="label"><i class="fas fa-list"></i> Total réclamations</div>
    </div>
    <div class="stat-card">
        <div class="number green"><?php echo $traitees; ?></div>
        <div class="label"><i class="fas fa-check-circle"></i> Traitées</div>
    </div>
    <div class="stat-card">
        <div class="number yellow"><?php echo $en_attente; ?></div>
        <div class="label"><i class="fas fa-clock"></i> En attente</div>
    </div>
</div>

<!-- CARD : LISTE DES RÉCLAMATIONS -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-comment-dots"></i> Liste des réclamations</span>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="badge-count"><?php echo $total_reclamations; ?> réclamation(s)</span>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($reclamations) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employé</th>
                    <th>Matricule</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Réponse</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reclamations as $r): ?>
                <tr>
                    <td><?php echo isset($r['created_at']) ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-'; ?></td>
                    <td><strong><?php echo $r['prenom'] . ' ' . $r['nom']; ?></strong></td>
                    <td><?php echo $r['matricule']; ?></td>
                    <td>
                        <?php 
                        $type_icon = match($r['type_reclamation']) {
                            'pointage_oublie' => '⏰',
                            'heure_incorrecte' => '🕐',
                            'absence_non_justifiee' => '📋',
                            default => '📝'
                        };
                        echo $type_icon . ' ' . $r['type_reclamation'];
                        ?>
                    </td>
                    <td title="<?php echo htmlspecialchars($r['motif']); ?>">
                        <?php echo substr($r['motif'], 0, 60) . (strlen($r['motif']) > 60 ? '...' : ''); ?>
                    </td>
                    <td title="<?php echo htmlspecialchars($r['reponse']); ?>">
                        <?php echo $r['reponse'] ? substr($r['reponse'], 0, 60) . (strlen($r['reponse']) > 60 ? '...' : '') : '-'; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $r['statut'] == 'traité' ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $r['statut'] == 'traité' ? '✅ Traité' : '⏳ En attente'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-comment-dots"></i>
            <p>Aucune réclamation trouvée</p>
            <small>Aucune réclamation enregistrée dans le système</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>