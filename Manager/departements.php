<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'manager') {
    rediriger('../login.php');
}

$titre = "🏢 Gestion des départements";
$message = '';
$erreur = '';

// Ajouter un département
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    
    if (empty($nom)) {
        $erreur = "Le nom du département est requis";
    } else {
        $stmt = $pdo->prepare("INSERT INTO departement (nom, description) VALUES (?, ?)");
        if ($stmt->execute([$nom, $description])) {
            $message = "✅ Département ajouté avec succès";
        } else {
            $erreur = "❌ Erreur lors de l'ajout";
        }
    }
}

// Modifier un département
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    
    $stmt = $pdo->prepare("UPDATE departement SET nom = ?, description = ? WHERE id = ?");
    if ($stmt->execute([$nom, $description, $id])) {
        $message = "✅ Département modifié avec succès";
    } else {
        $erreur = "❌ Erreur lors de la modification";
    }
}

// Supprimer un département
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Vérifier si des employés sont dans ce département
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM employe WHERE departement_id = ?");
    $stmt->execute([$id]);
    $nb_employes = $stmt->fetch()['total'];
    
    if ($nb_employes > 0) {
        $erreur = "❌ Impossible de supprimer ce département car il contient $nb_employes employé(s)";
    } else {
        $stmt = $pdo->prepare("DELETE FROM departement WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "✅ Département supprimé avec succès";
        } else {
            $erreur = "❌ Erreur lors de la suppression";
        }
    }
}

// Récupérer tous les départements avec le nombre d'employés
$departements = $pdo->query("
    SELECT d.*, COUNT(e.id) as nb_employes 
    FROM departement d 
    LEFT JOIN employe e ON d.id = e.departement_id 
    GROUP BY d.id 
    ORDER BY d.nom ASC
")->fetchAll();

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
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --green-bg:    #f0fdf4;
    --green-text:  #15803d;
}

body {
    background-color: var(--bg) !important;
    font-family: 'Inter', system-ui, sans-serif;
}

.container {
    max-width: 1000px;
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
    color: var(--blue);
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
    border-color: var(--blue); 
    background: #fff;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
}

/* ══ ROW ══ */
.row { display: flex; gap: 15px; flex-wrap: wrap; }
.row .form-group, .row .col-md-4, .row .col-md-6, .row .col-md-2 { 
    flex: 1; 
    min-width: 150px; 
}
.row .col-md-2 { flex: 0 0 auto; }

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
.btn-warning { 
    background: #fffbeb; 
    color: #d97706; 
    border: 1px solid #fde68a;
}
.btn-danger { 
    background: var(--red-bg); 
    color: var(--red-text); 
    border: 1px solid #fecaca;
}
.btn-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.btn-submit { 
    background: var(--blue); 
    color: white; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-submit:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

/* ══ MODAL ══ */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: white;
    padding: 25px;
    border-radius: 16px;
    width: 450px;
    max-width: 90%;
}
.modal-content h3 {
    margin-top: 0;
    color: var(--text);
}
.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}
.modal-actions .btn-cancel {
    padding: 10px 20px;
    border-radius: 8px;
    border: 1.5px solid var(--border);
    background: white;
    cursor: pointer;
    font-weight: 600;
}
.modal-actions .btn-cancel:hover {
    background: #f9fafb;
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
    .stats-grid { grid-template-columns: 1fr; }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-building"></i>
    </div>
    <div class="header-content">
        <h1>Gestion des départements</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-sitemap"></i>
            </span>
            Gérez les départements de l'entreprise et consultez leurs effectifs
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?></div>
<?php endif; ?>

<!-- STATISTIQUES -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?php echo count($departements); ?></div>
        <div class="label"><i class="fas fa-building"></i> Départements</div>
    </div>
</div>

<!-- CARD : AJOUTER -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle"></i> Ajouter un département
        <span class="badge-count"><?php echo count($departements); ?> département(s)</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="form-group" style="flex: 2;">
                    <label><i class="fas fa-tag"></i> Nom du département *</label>
                    <input type="text" name="nom" class="form-control" placeholder="ex: Ressources Humaines" required>
                </div>
                <div class="form-group" style="flex: 3;">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Description du département">
                </div>
                <div style="display: flex; align-items: flex-end; padding-bottom: 2px;">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Ajouter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- CARD : LISTE -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Liste des départements
        <span class="badge-count"><?php echo count($departements); ?> département(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto; padding: 0;">
        <?php if (count($departements) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th style="text-align: center;">Employés</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departements as $d): ?>
                <tr>
                    <td><?php echo $d['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($d['nom']); ?></strong></td>
                    <td><?php echo htmlspecialchars($d['description'] ?: '-'); ?></td>
                    <td style="text-align: center;">
                        <span style="display: inline-flex; align-items: center; gap: 5px; background: var(--blue-light); color: var(--blue); padding: 2px 12px; border-radius: 20px; font-weight: 600;">
                            <i class="fas fa-user"></i> <?php echo $d['nb_employes']; ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <button onclick="ouvrirModalEdit(<?php echo $d['id']; ?>, '<?php echo addslashes($d['nom']); ?>', '<?php echo addslashes($d['description']); ?>')" class="btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <?php if ($d['nb_employes'] == 0): ?>
                            <a href="departements.php?delete=<?php echo $d['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('⚠️ Supprimer définitivement ce département ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        <?php else: ?>
                            <button class="btn-sm btn-danger" disabled title="Impossible de supprimer, des employés sont rattachés">
                                <i class="fas fa-lock"></i> Fixé
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-building"></i>
            <p>Aucun département enregistré</p>
            <small>Ajoutez votre premier département ci-dessus</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL MODIFICATION -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-edit" style="color: var(--blue);"></i> Modifier le département</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Nom *</label>
                <input type="text" name="nom" id="edit_nom" class="form-control" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="button" class="btn-cancel" onclick="fermerModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function ouvrirModalEdit(id, nom, description) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nom').value = nom;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('editModal').style.display = 'flex';
}

function fermerModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Fermer le modal en cliquant à l'extérieur
window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target == modal) {
        fermerModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>