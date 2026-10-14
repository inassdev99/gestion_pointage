<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$message = '';
$erreur = '';

// Ajouter un congé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $employe_id = $_POST['employe_id'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $motif = $_POST['motif'];
    $statut = $_POST['statut'] ?? 'validé';
    
    if ($date_debut > $date_fin) {
        $erreur = "❌ La date de début doit être antérieure à la date de fin";
    } else {
        $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut, valide_par) 
                               VALUES (?, 'congé', ?, ?, ?, ?, ?)");
        if ($stmt->execute([$employe_id, $date_debut, $date_fin, $motif, $statut, $_SESSION['user_id']])) {
            creerNotification($employe_id, "Un congé a été enregistré pour vous (Statut: $statut).", $pdo);
            $message = "✅ Congé ajouté avec succès";
        } else {
            $erreur = "❌ Erreur lors de l'ajout";
        }
    }
}

// Valider un congé
if (isset($_GET['valider'])) {
    $id = $_GET['valider'];
    $stmt_emp = $pdo->prepare("SELECT employe_id FROM absence WHERE id = ?");
    $stmt_emp->execute([$id]);
    $emp_id = $stmt_emp->fetchColumn();

    $pdo->prepare("UPDATE absence SET statut = 'validé', valide_par = ? WHERE id = ?")->execute([$_SESSION['user_id'], $id]);
    creerNotification($emp_id, "Votre demande de congé a été validée par l'administrateur.", $pdo);
    $message = "✅ Congé validé";
    header("Refresh:1; url=conges.php");
}

// Refuser un congé
if (isset($_GET['refuser'])) {
    $id = $_GET['refuser'];
    $stmt_emp = $pdo->prepare("SELECT employe_id FROM absence WHERE id = ?");
    $stmt_emp->execute([$id]);
    $emp_id = $stmt_emp->fetchColumn();

    $pdo->prepare("UPDATE absence SET statut = 'refusé', valide_par = ? WHERE id = ?")->execute([$_SESSION['user_id'], $id]);
    creerNotification($emp_id, "Votre demande de congé a été refusée par l'administrateur.", $pdo);
    $message = "❌ Congé refusé";
    header("Refresh:1; url=conges.php");
}

// Supprimer un congé
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM absence WHERE id = ?")->execute([$id]);
    $message = "🗑️ Congé supprimé";
    header("Refresh:1; url=conges.php");
}

$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();
$titre = "🏖️ Gestion des congés";

// Récupérer tous les congés (type_absence = 'congé')
$stmt = $pdo->query("SELECT a.*, e.nom, e.prenom, e.matricule 
                     FROM absence a 
                     JOIN employe e ON a.employe_id = e.id 
                     WHERE a.type_absence = 'congé'
                     ORDER BY a.date_debut DESC");
$conges = $stmt->fetchAll();

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
.alert-danger { 
    background: #fee2e2; 
    color: #991b1b; 
    border-left: 4px solid #ef4444;
}
.alert i {
    margin-right: 8px;
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
.card-header i { color: var(--orange); }
.card-header .badge-count {
    margin-left: auto;
    background: var(--orange-light);
    color: var(--orange);
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
}
.card-body { padding: 0; }

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
    outline: none;
    background: #fafbfe;
    transition: all 0.2s;
}
.form-control:focus { 
    border-color: var(--orange); 
    background: #fff;
    box-shadow: 0 0 0 3px rgba(245,158,11,0.08);
}
.row { display: flex; gap: 15px; flex-wrap: wrap; }
.row .form-group { flex: 1; min-width: 150px; }

/* ══ FILTERS BAR ══ */
.filters-bar {
    padding: 13px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
}
.search-wrap {
    position: relative;
    flex: 1;
    max-width: 300px;
}
.search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 0.85rem;
}
.search-wrap input {
    width: 100%;
    padding: 9px 12px 9px 35px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 0.85rem;
    background: #f8fafc;
    outline: none;
    transition: all 0.2s;
}
.search-wrap input:focus { border-color: var(--orange); background: #fff; box-shadow: 0 0 0 3px rgba(245,158,11,0.05); }

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
.badge-danger::before  { background: var(--red-text); }
.badge-warning { background: var(--yellow-bg); color: var(--yellow-text); }
.badge-success { background: var(--green-bg); color: var(--green-text); }
.badge-danger  { background: var(--red-bg); color: var(--red-text); }

/* ══ EMPLOYEE CELL ══ */
.emp-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.emp-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--orange-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--orange);
}

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
}
.btn-success { background: var(--green-bg); color: var(--green-text); border: 1px solid #dcfce7; }
.btn-danger { background: var(--red-bg); color: var(--red-text); border: 1px solid #fee2e2; }
.btn-warning { background: var(--yellow-bg); color: var(--yellow-text); border: 1px solid #fde68a; }
.btn-success:hover { background: #dcfce7; }
.btn-danger:hover { background: #fee2e2; }
.btn-warning:hover { background: #fef3c7; }
.btn-primary { 
    background: var(--orange); 
    color: white; 
    border: none; 
    padding: 8px 20px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-primary:hover { background: #d97706; transform: translateY(-1px); }

.dash { color: #cbd5e1; }

/* Responsive */
@media (max-width: 768px) {
    .row { flex-direction: column; gap: 0; }
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
    .search-wrap { max-width: 100%; }
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
            Gérez les demandes de congé des employés (validation, refus, suppression)
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?></div>
<?php endif; ?>

<!-- CARD : AJOUTER UN CONGÉ -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle"></i> Ajouter un congé
        <span class="badge-count"><?php echo count($conges); ?> congé(s)</span>
    </div>
    <div class="card-body" style="padding: 20px;">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Employé</label>
                        <select name="employe_id" class="form-control" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($employes as $e): ?>
                                <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['prenom'] . ' ' . $e['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Date début</label>
                        <input type="date" name="date_debut" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-week"></i> Date fin</label>
                        <input type="date" name="date_fin" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-pen-alt"></i> Motif</label>
                        <input type="text" name="motif" class="form-control" placeholder="Ex: Vacances d'été">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><i class="fas fa-flag-checkered"></i> Statut</label>
                        <select name="statut" class="form-control">
                            <option value="validé">✅ Validé</option>
                            <option value="en_attente">⏳ En attente</option>
                            <option value="refusé">❌ Refusé</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Ajouter le congé
            </button>
        </form>
    </div>
</div>

<!-- CARD : LISTE DES CONGÉS -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Liste des congés
        <span class="badge-count"><?php echo count($conges); ?> congé(s)</span>
    </div>
    
    <div class="filters-bar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Rechercher un employé..." onkeyup="filterTable()">
        </div>
    </div>

    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($conges) > 0): ?>
        <table id="congesTable">
            <thead>
                <tr><th>Employé</th><th>Matricule</th><th>Période</th><th>Motif</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($conges as $c): 
                    $initials = strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1));
                    $status_class = 'warning';
                    if($c['statut'] == 'validé') $status_class = 'success';
                    if($c['statut'] == 'refusé') $status_class = 'danger';
                ?>
                <tr class="conge-row">
                    <td>
                        <div class="emp-cell">
                            <div class="emp-avatar"><?php echo $initials; ?></div>
                            <?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?>
                        </div>
                    </td>
                    <td><?php echo $c['matricule']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($c['date_debut'])) . ' → ' . date('d/m/Y', strtotime($c['date_fin'])); ?></td>
                    <td title="<?php echo htmlspecialchars($c['motif']); ?>">
                        <?php echo htmlspecialchars(substr($c['motif'], 0, 40)) . (strlen($c['motif']) > 40 ? '...' : ''); ?>
                    </td>
                    <td><span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($c['statut']); ?></span></td>
                    <td>
                        <?php if ($c['statut'] == 'en_attente'): ?>
                        <a href="conges.php?valider=<?php echo $c['id']; ?>" class="btn-sm btn-success" onclick="return confirm('Valider ce congé ?')">
                            <i class="fas fa-check"></i> Valider
                        </a>
                        <a href="conges.php?refuser=<?php echo $c['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Refuser ce congé ?')">
                            <i class="fas fa-times"></i> Refuser
                        </a>
                        <?php elseif ($c['statut'] == 'validé'): ?>
                        <span style="color: var(--green-text);"><i class="fas fa-check-circle"></i> Validé</span>
                        <?php else: ?>
                        <span style="color: var(--red-text);"><i class="fas fa-times-circle"></i> Refusé</span>
                        <?php endif; ?>
                        <a href="conges.php?delete=<?php echo $c['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Supprimer ce congé ?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: var(--muted);">
            <i class="fas fa-umbrella-beach" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 10px;"></i>
            <p>Aucun congé enregistré</p>
            <small>Ajoutez un congé ci-dessus</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('.conge-row');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}
</script>

<?php include '../includes/footer.php'; ?>