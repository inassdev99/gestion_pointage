<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$titre = "🏢 Gestion des départements";
$message = '';
$erreur = '';

// Ajouter un département
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    
    $stmt = $pdo->prepare("INSERT INTO departement (nom, description) VALUES (?, ?)");
    if ($stmt->execute([$nom, $description])) {
        $message = "✅ Département ajouté avec succès";
    } else {
        $erreur = "❌ Erreur lors de l'ajout";
    }
}

// Supprimer un département
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM departement WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "🗑️ Département supprimé";
}

$departements = $pdo->query("SELECT * FROM departement ORDER BY nom")->fetchAll();

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

/* ══ ALERT ══ */
.alert-success { 
    background: #d1fae5; 
    color: #065f46; 
    padding: 12px 16px; 
    border-radius: 10px; 
    margin-bottom: 20px; 
    font-size: 0.85rem;
    border-left: 4px solid #10b981;
}
.alert-success i {
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
    outline: none;
    background: #fafbfe;
    transition: all 0.2s;
}
.form-control:focus { 
    border-color: var(--blue); 
    background: #fff;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
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
.btn-danger { 
    padding: 6px 12px; 
    border-radius: 8px; 
    text-decoration: none; 
    display: inline-block; 
    background: var(--red-bg); 
    color: var(--red-text); 
    font-size: 0.75rem; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-danger:hover { background: #fecaca; }

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
            Organisez votre structure d'entreprise par services ou départements
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #ef4444;">
        <i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?>
    </div>
<?php endif; ?>

<!-- CARD : AJOUTER -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle"></i> Ajouter un nouveau département
        <span class="badge-count"><?php echo count($departements); ?> département(s)</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Nom du département *</label>
                    <input type="text" name="nom" class="form-control" placeholder="ex: Ressources Humaines" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Description du département">
                </div>
                <div style="display: flex; align-items: flex-end;">
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
                        <a href="departements.php?delete=<?php echo $d['id']; ?>" class="btn-danger" onclick="return confirm('⚠️ Supprimer définitivement ce département ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
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

<?php include '../includes/footer.php'; ?>