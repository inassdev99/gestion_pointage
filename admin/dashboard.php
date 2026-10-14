<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$titre = "Dashboard Admin";
include '../includes/header.php';

// ═══════════════════════════════════════════════════════════
// ═══ RÉCUPÉRER LES EMPLOYÉS AVEC LEURS POINTAGES ═══
// ═══════════════════════════════════════════════════════════
$date = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT 
        e.id as employe_id,
        e.matricule, 
        e.nom, 
        e.prenom, 
        e.email, 
        d.nom as departement_nom,
        p.heure_arrivee,
        p.heure_pause_debut,
        p.heure_pause_fin,
        p.heure_depart,
        p.statut as pointage_statut,
        -- Absences du jour
        a.id as absence_id,
        a.type_absence,
        a.date_debut,
        a.date_fin,
        a.statut as absence_statut,
        a.motif as absence_motif
    FROM employe e
    LEFT JOIN departement d ON e.departement_id = d.id
    LEFT JOIN pointage p ON e.id = p.employe_id AND p.date_pointage = ?
    LEFT JOIN absence a ON e.id = a.employe_id 
        AND ? BETWEEN a.date_debut AND a.date_fin
        AND a.statut = 'validé'
    WHERE e.statut = 'actif'
    ORDER BY e.nom ASC
");
$stmt->execute([$date, $date]);
$employes_data = $stmt->fetchAll();

// Statistiques
$nb_employes = count($employes_data);
$nb_presents = 0;
$nb_retards = 0;
$nb_absents = 0;
$nb_absences = 0;
$nb_en_cours = 0;
$nb_termine = 0;

foreach ($employes_data as $emp) {
    if ($emp['absence_id']) {
        $nb_absences++;
        continue;
    }
    
    if ($emp['heure_arrivee']) {
        $nb_presents++;
        if ($emp['pointage_statut'] == 'retard') {
            $nb_retards++;
        }
        if ($emp['heure_depart']) {
            $nb_termine++;
        } else {
            $nb_en_cours++;
        }
    } else {
        $nb_absents++;
    }
}

$taux_presence = $nb_employes > 0 ? round(($nb_presents / $nb_employes) * 100) : 0;

// Récupérer les pointages récents (7 derniers jours)
$stmt_recent = $pdo->prepare("
    SELECT 
        p.*,
        e.nom, 
        e.prenom, 
        e.matricule,
        d.nom as departement_nom
    FROM pointage p
    JOIN employe e ON p.employe_id = e.id
    LEFT JOIN departement d ON e.departement_id = d.id
    WHERE p.date_pointage >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY p.date_pointage DESC, p.heure_arrivee DESC
    LIMIT 20
");
$stmt_recent->execute();
$pointages_recents = $stmt_recent->fetchAll();
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
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --yellow-bg:   #fffbeb;
    --yellow-text: #b45309;
    --orange-bg:   #fff7ed;
    --orange-text: #c2410c;
    --teal-bg:     #ccfbf1;
    --teal-text:   #0d9488;
    --radius:      12px;
    --shadow:      0 2px 12px rgba(30,45,77,0.07);
}

/* ══ RESET PARTIEL ══ */
*, *::before, *::after { box-sizing: border-box; }

/* ══ WELCOME BANNER ══ */
.welcome-message {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: #fff;
    padding: 22px 26px;
    border-radius: var(--radius);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 4px 16px rgba(30,60,114,0.18);
}
.welcome-message i {
    font-size: 2rem;
    opacity: 0.9;
    flex-shrink: 0;
}
.welcome-message h1 {
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0 0 5px 0;
}
.welcome-message p {
    font-size: 0.83rem;
    opacity: 0.88;
    margin: 0;
    line-height: 1.5;
}

/* ══ STATS GRID ══ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--card);
    padding: 18px 20px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: all 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.stat-icon i { font-size: 1.25rem; }
.si-blue   { background: var(--blue-light);  }
.si-blue i { color: var(--blue); }
.si-green   { background: var(--green-bg);  }
.si-green i { color: var(--green-text); }
.si-red   { background: var(--red-bg);  }
.si-red i { color: var(--red-text); }
.si-yellow   { background: var(--yellow-bg);  }
.si-yellow i { color: var(--yellow-text); }
.si-orange   { background: var(--orange-bg);  }
.si-orange i { color: var(--orange-text); }
.si-teal   { background: var(--teal-bg);  }
.si-teal i { color: var(--teal-text); }
.si-purple   { background: #f3f0ff;  }
.si-purple i { color: #7c3aed; }

.stat-info h3 {
    font-size: 1.65rem;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 3px 0;
    line-height: 1;
}
.stat-info p {
    font-size: 0.76rem;
    color: var(--muted);
    margin: 0;
}
.stat-info small {
    font-size: 0.7rem;
    color: var(--muted);
    display: block;
    margin-top: 2px;
}

/* ══ MAIN CARD ══ */
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
    background: var(--card);
    display: flex;
    align-items: center;
    gap: 8px;
}
.card-header i { color: var(--blue); margin: 0; }
.card-header .badge-count {
    margin-left: auto;
    background: var(--blue-light);
    color: var(--blue);
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
}

/* ══ FILTERS BAR ══ */
.filters-bar {
    padding: 13px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.search-wrap {
    position: relative;
    flex: 1;
    min-width: 180px;
    max-width: 260px;
}
.search-wrap i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 0.8rem;
}
.search-wrap input {
    width: 100%;
    padding: 8px 10px 8px 30px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.82rem;
    font-family: inherit;
    background: #f8fafc;
    outline: none;
    color: var(--text);
}
.search-wrap input:focus { border-color: var(--blue); background: #fff; }
.search-wrap input::placeholder { color: var(--muted); }

.filter-date {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.82rem;
    background: #fff;
    color: var(--text);
}
.filter-date i { color: var(--muted); font-size: 0.78rem; }

.btn-export {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 16px;
    background: var(--blue);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
    font-family: inherit;
}
.btn-export:hover { background: #1d4ed8; }

/* ══ TABLE ══ */
.card-body { padding: 0; }
table {
    width: 100%;
    border-collapse: collapse;
}
th {
    padding: 11px 13px;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--muted);
    text-align: left;
    background: #f8fafc;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
td {
    padding: 11px 13px;
    font-size: 0.82rem;
    color: var(--text);
    border-bottom: 1px solid #f0f2f8;
    white-space: nowrap;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }

/* Employee cell */
.emp-cell {
    display: flex;
    align-items: center;
    gap: 9px;
}
.emp-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e0e7ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--blue);
    flex-shrink: 0;
}

/* Badges */
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
    display: inline-block;
    flex-shrink: 0;
}
.badge-success  { background: var(--green-bg);  color: var(--green-text); }
.badge-success::before  { background: var(--green-text); }
.badge-danger   { background: var(--red-bg);    color: var(--red-text); }
.badge-danger::before   { background: var(--red-text); }
.badge-warning  { background: var(--yellow-bg); color: var(--yellow-text); }
.badge-warning::before  { background: var(--yellow-text); }
.badge-absence { background: var(--orange-bg); color: var(--orange-text); }
.badge-absence::before { background: var(--orange-text); }
.badge-deplacement { background: var(--teal-bg); color: var(--teal-text); }
.badge-deplacement::before { background: var(--teal-text); }
.badge-conge { background: #fef3c7; color: #b45309; }
.badge-conge::before { background: #b45309; }
.badge-en-cours { background: #fef3c7; color: #b45309; }
.badge-en-cours::before { background: #b45309; }

/* Dash placeholder */
.dash { color: #cbd5e1; }

/* ══ PAGINATION ══ */
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 20px;
    border-top: 1px solid var(--border);
    font-size: 0.8rem;
    color: var(--muted);
}
.page-btns { display: flex; align-items: center; gap: 4px; }
.page-btn {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    background: #fff;
    font-size: 0.78rem;
    cursor: pointer;
    color: var(--text);
    transition: background 0.12s, color 0.12s;
    font-family: inherit;
}
.page-btn:hover  { background: var(--blue-light); color: var(--blue); border-color: var(--blue); }
.page-btn.active { background: var(--blue); color: #fff; border-color: var(--blue); }

/* Empty state */
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
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .filters-bar { flex-wrap: wrap; }
    .search-wrap { max-width: 100%; }
    .btn-export { margin-left: 0; width: 100%; justify-content: center; }
}
</style>

<!-- ══ WELCOME BANNER ══ -->
<div class="welcome-message">
    <i class="fas fa-user-shield"></i>
    <div>
        <h1>Bonjour et bienvenue, <?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?> !</h1>
        <p>Vous êtes connecté en tant qu'<strong>Administrateur</strong> sur la plateforme GestiPoint.<br>
           Gérez les employés, les pointages, les absences et les réclamations depuis votre tableau de bord.</p>
    </div>
</div>

<!-- ══ STATISTIQUES ══ -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon si-blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $nb_employes; ?></h3>
            <p>Employés actifs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?php echo $nb_presents; ?></h3>
            <p>Présents aujourd'hui</p>
            <small><?php echo $taux_presence; ?>% de l'équipe</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-yellow"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo $nb_retards; ?></h3>
            <p>Retards</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-red"><i class="fas fa-user-slash"></i></div>
        <div class="stat-info">
            <h3><?php echo $nb_absents; ?></h3>
            <p>Absents</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-teal"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?php echo $nb_absences; ?></h3>
            <p>Congés/Déplacements</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-purple"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-info">
            <h3 style="font-size:1.1rem"><?php echo date('d/m/Y'); ?></h3>
            <p>Date du jour</p>
        </div>
    </div>
</div>

<!-- ══ TABLEAU EMPLOYÉS ══ -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-users"></i> Liste des employés et pointages du jour
        <span class="badge-count"><?php echo $nb_employes; ?> employé(s)</span>
    </div>

    <!-- Barre de filtres -->
    <div class="filters-bar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Rechercher un employé..." onkeyup="filterTable()">
        </div>
        <div class="filter-date">
            <i class="fas fa-calendar"></i> <?php echo date('d/m/Y'); ?>
        </div>
        <button class="btn-export" onclick="window.print()">
            <i class="fas fa-download"></i> Exporter
        </button>
    </div>

    <div class="card-body" style="overflow-x: auto;">
        <table id="mainTable">
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Nom &amp; Prénom</th>
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
                    $initiales = strtoupper(substr($emp['prenom'], 0, 1) . substr($emp['nom'], 0, 1));
                    
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
                            $badge_class = 'badge-warning';
                        } elseif ($emp['heure_depart']) {
                            $status_label = 'Terminé';
                            $badge_class = 'badge-success';
                        } else {
                            $status_label = 'En cours';
                            $badge_class = 'badge-en-cours';
                        }
                    } else {
                        $status_label = 'Absent';
                        $badge_class = 'badge-danger';
                    }
                    
                    $heures = $emp['heure_arrivee'] ? calculerHeures($emp['heure_arrivee'], $emp['heure_pause_debut'], $emp['heure_pause_fin'], $emp['heure_depart']) : '-';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($emp['matricule']); ?></td>
                    <td>
                        <div class="emp-cell">
                            <div class="emp-avatar"><?php echo $initiales; ?></div>
                            <?php echo htmlspecialchars($emp['prenom'] . ' ' . $emp['nom']); ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($emp['departement_nom'] ?: '–'); ?></td>
                    <td><?php echo $emp['heure_arrivee'] ? date('H:i', strtotime($emp['heure_arrivee'])) : '<span class="dash">--:--</span>'; ?></td>
                    <td><?php echo $emp['heure_pause_debut'] ? date('H:i', strtotime($emp['heure_pause_debut'])) : '-'; ?></td>
                    <td><?php echo $emp['heure_pause_fin'] ? date('H:i', strtotime($emp['heure_pause_fin'])) : '-'; ?></td>
                    <td><?php echo $emp['heure_depart'] ? date('H:i', strtotime($emp['heure_depart'])) : '-'; ?></td>
                    <td><strong><?php echo $heures; ?></strong></td>
                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status_label; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-bar">
        <span>Affichage de <?php echo $nb_employes; ?> employé(s) actif(s)</span>
        <div class="page-btns">
            <button class="page-btn"><i class="fas fa-chevron-left"></i></button>
            <button class="page-btn active">1</button>
            <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</div>

<!-- ══ CARD : POINTAGES RÉCENTS ══ -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-history"></i> Pointages récents (7 derniers jours)</span>
        <span class="badge-count"><?php echo count($pointages_recents); ?> pointage(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (!empty($pointages_recents)): ?>
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
                <?php foreach ($pointages_recents as $p): 
                    $heures = calculerHeures($p['heure_arrivee'], $p['heure_pause_debut'], $p['heure_pause_fin'], $p['heure_depart']);
                    $statut = $p['statut'] ?: 'normal';
                    $badge = $statut == 'retard' ? 'badge-warning' : 'badge-success';
                ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($p['date_pointage'])); ?></td>
                    <td><strong><?php echo $p['prenom'] . ' ' . $p['nom']; ?></strong></td>
                    <td><?php echo $p['matricule']; ?></td>
                    <td><?php echo $p['departement_nom'] ?: '-'; ?></td>
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
            <p>Aucun pointage récent</p>
            <small>Aucun pointage enregistré dans les 7 derniers jours</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterTable() {
    var input = document.getElementById('searchInput').value.toLowerCase();
    var rows  = document.querySelectorAll('#mainTable tbody tr');
    rows.forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(input) ? '' : 'none';
    });
}
</script>

<?php include '../includes/footer.php'; ?>