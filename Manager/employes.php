<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$titre = "👥 Consultation des employés";

$stmt = $pdo->query("
    SELECT e.*, d.nom as departement_nom 
    FROM employe e 
    LEFT JOIN departement d ON e.departement_id = d.id 
    WHERE e.statut = 'actif'
    ORDER BY e.nom ASC
");
$employes = $stmt->fetchAll();

// Statistiques
$total_employes = count($employes);

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
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
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
    color: var(--blue);
}
.stat-card .label {
    font-size: 0.75rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ══ CARD ══ */
.card {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 25px;
    border: 1px solid var(--border);
}
.card-header { 
    padding: 15px 20px; 
    border-bottom: 1px solid var(--border); 
    font-weight: 600; 
    font-size: 0.92rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
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
.badge-danger::before  { background: var(--red-text); }
.badge-success { background: var(--green-bg); color: var(--green-text); }
.badge-danger  { background: var(--red-bg); color: var(--red-text); }

/* ══ BUTTONS ══ */
.btn-sm { 
    padding: 6px 12px; 
    border-radius: 6px; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-sm:hover {
    transform: scale(1.05);
}
.btn-info { 
    background: var(--blue-light); 
    color: var(--blue);
    border: 1px solid #bfdbfe;
}
.btn-warning { 
    background: #fffbeb; 
    color: #d97706;
    border: 1px solid #fde68a;
}
.btn-success {
    background: var(--green-bg);
    color: var(--green-text);
    border: 1px solid #bbf7d0;
}

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
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-users"></i>
    </div>
    <div class="header-content">
        <h1>Consultation des employés</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-address-card"></i>
            </span>
            Consultez la liste des employés actifs et leurs informations
        </p>
    </div>
</div>

<!-- STATISTIQUES -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?php echo $total_employes; ?></div>
        <div class="label"><i class="fas fa-users"></i> Employés actifs</div>
    </div>
</div>

<!-- CARD : LISTE DES EMPLOYÉS -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-list"></i> Liste des employés</span>
        <span class="badge-count"><?php echo $total_employes; ?> employé(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($employes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Nom & Prénom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Département</th>
                    <th>Date embauche</th>
                    <th>Statut</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employes as $e): ?>
                <tr>
                    <td><strong><?php echo $e['matricule']; ?></strong></td>
                    <td><?php echo $e['nom'] . ' ' . $e['prenom']; ?></td>
                    <td><?php echo $e['email']; ?></td>
                    <td><?php echo $e['telephone'] ?: '-'; ?></td>
                    <td><?php echo $e['departement_nom'] ?: '-'; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($e['date_embauche'])); ?></td>
                    <td>
                        <span class="badge <?php echo $e['statut'] == 'actif' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $e['statut'] == 'actif' ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <a href="pointages.php?employe=<?php echo $e['id']; ?>" class="btn-sm btn-info" title="Voir les pointages">
                            <i class="fas fa-calendar-check"></i> Pointages
                        </a>
                        <a href="demandes_absence.php?employe=<?php echo $e['id']; ?>" class="btn-sm btn-warning" title="Ajouter une absence">
                            <i class="fas fa-calendar-times"></i> Absence
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>Aucun employé actif</p>
            <small>Aucun employé n'est actif dans votre département</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>