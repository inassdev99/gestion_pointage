<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$titre = "🏖️ Gestion des congés";
$message = '';

// Ajouter un congé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $employe_id = $_POST['employe_id'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $motif = $_POST['motif'];
    
    $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut, valide_par) VALUES (?, 'congé', ?, ?, ?, 'validé', ?)");
    if ($stmt->execute([$employe_id, $date_debut, $date_fin, $motif, $_SESSION['user_id']])) {
        $message = "✅ Congé ajouté avec succès";
    } else {
        $message = "❌ Erreur lors de l'ajout";
    }
}

// Supprimer un congé
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM absence WHERE id = ? AND type_absence = 'congé'")->execute([$_GET['delete']]);
    $message = "✅ Congé supprimé";
}

// Récupérer tous les congés
$conges = $pdo->query("
    SELECT a.*, e.nom, e.prenom, e.matricule 
    FROM absence a 
    JOIN employe e ON a.employe_id = e.id 
    WHERE a.type_absence = 'congé'
    ORDER BY a.date_debut DESC
")->fetchAll();

$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();

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
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --orange:      #f59e0b;
    --orange-light:#fef3c7;
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
    background: linear-gradient(135deg, var(--orange) 0%, #d97706 100%);
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
    background: var(--orange-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--orange);
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
    color: var(--orange);
}
.stat-card .label {
    font-size: 0.75rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ══ ALERT ══ */
.alert { 
    padding: 12px 16px; 
    border-radius: 10px; 
    margin-bottom: 20px; 
    font-size: 0.85rem;
}
.alert-success { 
    background: #d1fae5; 
    color: #065f46; 
    border-left: 4px solid #10b981;
}
.alert i {
    margin-right: 8px;
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
.card-header i { color: var(--orange); }
.card-header .badge-count {
    margin-left: auto;
    background: var(--orange-light);
    color: var(--orange);
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
}
.card-body { padding: 20px; }

/* ══ FORM ══ */
.form-group { margin-bottom: 15px; }
.form-group label { 
    display: block; 
    margin-bottom: 6px; 
    font-size: 0.82rem; 
    font-weight: 600; 
    color: #374151; 
}
.form-group label i {
    margin-right: 6px;
    color: var(--orange);
}
.form-control {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1.5px solid var(--border);
    font-size: 0.85rem;
    background: #fafbfe;
    outline: none;
    transition: all 0.2s;
}
.form-control:focus { 
    border-color: var(--orange); 
    background: #fff;
    box-shadow: 0 0 0 3px rgba(245,158,11,0.08);
}

/* ══ ROW ══ */
.row { display: flex; gap: 15px; flex-wrap: wrap; }
.row .form-group { flex: 1; min-width: 150px; }

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
td {
    padding: 11px 13px;
    font-size: 0.82rem;
    color: var(--text);
    border-bottom: 1px solid #f0f2f8;
}
tbody tr:hover { background: #f8fafc; }

/* ══ BOUTONS ══ */
.btn-sm {
    padding: 6px 12px;
    border-radius: 8px;
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
.btn-danger { 
    background: var(--red-bg); 
    color: var(--red-text); 
    border: 1px solid #fecaca;
}
.btn-submit { 
    background: var(--orange); 
    color: white; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-submit:hover {
    background: #d97706;
    transform: translateY(-1px);
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
    .row { flex-direction: column; gap: 0; }
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-umbrella-beach"></i>
    </div>
    <div class="header-content">
        <h1>Gestion des congés</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-calendar-check"></i>
            </span>
            Gérez les congés des employés de votre équipe
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>

<!-- STATISTIQUES -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?php echo count($conges); ?></div>
        <div class="label"><i class="fas fa-list"></i> Total congés</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php 
            $total_jours = 0;
            foreach ($conges as $c) {
                $debut = new DateTime($c['date_debut']);
                $fin = new DateTime($c['date_fin']);
                $total_jours += $debut->diff($fin)->days + 1;
            }
            echo $total_jours;
        ?></div>
        <div class="label"><i class="fas fa-calendar-day"></i> Jours total</div>
    </div>
</div>

<!-- CARD : AJOUTER -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle"></i> Ajouter un congé
        <span class="badge-count"><?php echo count($conges); ?> congé(s)</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Employé</label>
                        <select name="employe_id" class="form-control" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($employes as $e): ?>
                                <option value="<?php echo $e['id']; ?>"><?php echo $e['matricule'] . ' - ' . $e['prenom'] . ' ' . $e['nom']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Date début</label>
                        <input type="date" name="date_debut" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-week"></i> Date fin</label>
                        <input type="date" name="date_fin" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><i class="fas fa-pen-alt"></i> Motif</label>
                        <input type="text" name="motif" class="form-control" placeholder="Motif">
                    </div>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Ajouter le congé
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- CARD : LISTE -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Liste des congés
        <span class="badge-count"><?php echo count($conges); ?> congé(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($conges) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Matricule</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Durée</th>
                    <th>Motif</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conges as $c): 
                    $debut = new DateTime($c['date_debut']);
                    $fin = new DateTime($c['date_fin']);
                    $duree = $debut->diff($fin)->days + 1;
                ?>
                <tr>
                    <td><strong><?php echo $c['prenom'] . ' ' . $c['nom']; ?></strong></td>
                    <td><?php echo $c['matricule']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($c['date_debut'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($c['date_fin'])); ?></td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 5px; background: var(--orange-light); color: var(--orange); padding: 2px 10px; border-radius: 20px; font-weight: 600;">
                            <i class="fas fa-calendar"></i> <?php echo $duree; ?> jour(s)
                        </span>
                    </td>
                    <td title="<?php echo htmlspecialchars($c['motif']); ?>">
                        <?php echo htmlspecialchars($c['motif'] ?: '-'); ?>
                    </td>
                    <td style="text-align: center;">
                        <a href="conges.php?delete=<?php echo $c['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('⚠️ Supprimer ce congé ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-umbrella-beach"></i>
            <p>Aucun congé enregistré</p>
            <small>Ajoutez un congé ci-dessus</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>