<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$titre = "🌐 IP autorisées";
$message = '';
$erreur = '';

// Ajouter une IP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $adresse = $_POST['adresse'];
    $emplacement = $_POST['emplacement'];
    
    $stmt = $pdo->prepare("INSERT INTO ip_autorisee (adresse, emplacement) VALUES (?, ?)");
    if ($stmt->execute([$adresse, $emplacement])) {
        $message = "✅ IP ajoutée avec succès";
    } else {
        $erreur = "❌ Erreur lors de l'ajout";
    }
}

// Supprimer une IP
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM ip_autorisee WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $message = "🗑️ IP supprimée";
}

$ips = $pdo->query("SELECT * FROM ip_autorisee ORDER BY adresse")->fetchAll();

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
    --green:       #10b981;
    --green-light: #d1fae5;
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
    padding: 10px 24px; 
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

/* ══ IP BADGE ══ */
.ip-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--blue-light);
    color: var(--blue);
    font-family: 'Courier New', monospace;
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
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-network-wired"></i>
    </div>
    <div class="header-content">
        <h1>Adresses IP autorisées</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-shield-alt"></i>
            </span>
            Autorisez les adresses IP depuis lesquelles le pointage est permis
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?></div>
<?php endif; ?>

<!-- CARD : AJOUTER UNE IP -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle"></i> Ajouter une IP autorisée
        <span class="badge-count"><?php echo count($ips); ?> IP(s)</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-ip"></i> Adresse IP *</label>
                        <input type="text" name="adresse" class="form-control" placeholder="ex: 192.168.1.%" required>
                        <small style="color: var(--muted); font-size: 0.7rem;">
                            <i class="fas fa-info-circle"></i> Utilisez % pour une plage (ex: 192.168.1.%)
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-map-pin"></i> Emplacement</label>
                        <input type="text" name="emplacement" class="form-control" placeholder="ex: Bureau principal">
                    </div>
                </div>
                <div class="col-md-2" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn-submit" style="width: 100%;">
                        <i class="fas fa-save"></i> Ajouter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- CARD : LISTE DES IP -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Liste des IP autorisées
        <span class="badge-count"><?php echo count($ips); ?> IP(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto; padding:0;">
        <?php if (count($ips) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Adresse IP</th>
                    <th>Emplacement</th>
                    <th>Statut</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ips as $ip): ?>
                <tr>
                    <td>
                        <span class="ip-badge">
                            <i class="fas fa-network-wired"></i>
                            <?php echo htmlspecialchars($ip['adresse']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($ip['emplacement'] ?: '-'); ?></td>
                    <td>
                        <span class="ip-badge" style="background: #d1fae5; color: #15803d;">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Actif
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <a href="ips_autorisees.php?delete=<?php echo $ip['id']; ?>" class="btn-danger" onclick="return confirm('⚠️ Supprimer cette IP ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-network-wired"></i>
            <p>Aucune IP autorisée</p>
            <small>Ajoutez votre première IP ci-dessus</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>