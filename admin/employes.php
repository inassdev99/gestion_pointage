<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$titre = "👥 Gestion du personnel - Employés";

// Récupérer tous les employés avec leur département
$stmt = $pdo->query("
    SELECT e.*, d.nom as departement_nom 
    FROM employe e 
    LEFT JOIN departement d ON e.departement_id = d.id 
    ORDER BY e.nom ASC
");
$employes = $stmt->fetchAll();

// Statistiques
$total_actifs = $pdo->query("SELECT COUNT(*) as total FROM employe WHERE statut = 'actif'")->fetch()['total'];
$total_inactifs = $pdo->query("SELECT COUNT(*) as total FROM employe WHERE statut = 'inactif'")->fetch()['total'];
$total_employes = $total_actifs + $total_inactifs;

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

/* ══ STATS CARDS ══ */
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
    color: var(--text);
}
.stat-card .label {
    font-size: 0.75rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stat-card.blue .number { color: var(--blue); }
.stat-card.green .number { color: var(--green-text); }
.stat-card.red .number { color: var(--red-text); }

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
    background: var(--card);
}
.card-header i { color: var(--blue); }
.card-header .badge-count {
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
.btn-primary {
    background: var(--blue);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 8px 16px;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.actions {
    display: flex;
    gap: 6px;
    align-items: center;
}
.btn-sm { 
    padding: 6px 10px; 
    border-radius: 6px; 
    text-decoration: none; 
    display: inline-flex; 
    align-items: center;
    gap: 4px;
    font-size: 0.85rem; 
    transition: all 0.2s;
}
.btn-sm:hover {
    transform: scale(1.05);
}
.btn-warning { 
    color: #d97706; 
    background: #fffbeb; 
    border: 1px solid #fde68a;
}
.btn-danger { 
    color: #dc2626; 
    background: #fef2f2; 
    border: 1px solid #fecaca;
}

/* ══ EMPTY STATE ══ */
.empty-state {
    text-align: center;
    padding: 50px 20px;
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
        <i class="fas fa-users-cog"></i>
    </div>
    <div class="header-content">
        <h1>Gestion du personnel</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-address-card"></i>
            </span>
            Gérez l'ensemble des employés, leurs informations et leur statut
        </p>
    </div>
</div>

<!-- STATISTIQUES -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="number"><?php echo $total_employes; ?></div>
        <div class="label"><i class="fas fa-users"></i> Total employés</div>
    </div>
    <div class="stat-card green">
        <div class="number"><?php echo $total_actifs; ?></div>
        <div class="label"><i class="fas fa-check-circle"></i> Actifs</div>
    </div>
    <div class="stat-card red">
        <div class="number"><?php echo $total_inactifs; ?></div>
        <div class="label"><i class="fas fa-user-slash"></i> Inactifs</div>
    </div>
</div>

<!-- CARD : LISTE DES EMPLOYÉS -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-list"></i> Liste des employés</span>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="badge-count"><?php echo $total_employes; ?> employé(s)</span>
            <a href="employe_ajouter_faceid.php" class="btn-primary">
                <i class="fas fa-user-plus"></i> Ajouter
            </a>
        </div>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($employes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
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
                    <td><?php echo $e['id']; ?></td>
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
                        <div class="actions" style="justify-content: center;">
                            <a href="employe_modifier.php?id=<?php echo $e['id']; ?>" class="btn-sm btn-warning" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="employe_supprimer.php?id=<?php echo $e['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('⚠️ Supprimer définitivement cet employé ?')" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>Aucun employé enregistré</p>
            <small>Ajoutez votre premier employé ci-dessus</small>
            <div style="margin-top: 15px;">
                <a href="employe_ajouter_faceid.php" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Ajouter le premier employé
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>