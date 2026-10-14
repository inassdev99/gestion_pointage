<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager')) {
    header("Location: ../login.php");
    exit();
}

$titre = "💬 Remplir une réclamation pour un employé";
$message = '';
$erreur = '';

$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employe_id = $_POST['employe_id'];
    $type_reclamation = $_POST['type_reclamation'];
    $motif = $_POST['motif'];
    
    $stmt = $pdo->prepare("INSERT INTO reclamation (employe_id, type_reclamation, motif, statut, traite_par) 
                           VALUES (?, ?, ?, 'traité', ?)");
    if ($stmt->execute([$employe_id, $type_reclamation, $motif, $_SESSION['user_id']])) {
        creerNotification($employe_id, "Une réclamation a été enregistrée pour vous par l'administrateur.", $pdo);
        $message = "✅ Réclamation ajoutée avec succès pour l'employé";
    } else {
        $erreur = "❌ Erreur lors de l'ajout";
    }
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
    --purple:      #7c3aed;
    --purple-light:#ede9fe;
}

body {
    background-color: var(--bg) !important;
    font-family: 'Inter', system-ui, sans-serif;
}

.container {
    max-width: 700px;
    margin: 30px auto;
    padding: 0 20px;
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

.card { 
    background: var(--card); 
    border-radius: var(--radius); 
    box-shadow: var(--shadow); 
    max-width: 650px; 
    margin: 0 auto; 
    overflow: hidden;
    border: 1px solid var(--border);
}
.card-header { 
    padding: 15px 20px; 
    border-bottom: 1px solid var(--border); 
    font-weight: 600; 
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
}
.card-header i { color: var(--purple); }
.card-body { padding: 22px; }

.form-group { margin-bottom: 18px; }
.form-group label { 
    display: block; 
    margin-bottom: 6px; 
    font-size: 0.82rem; 
    font-weight: 600; 
    color: #374151; 
}
.form-group label i {
    margin-right: 6px;
    color: var(--purple);
}
.form-control { 
    width: 100%; 
    padding: 10px 14px; 
    border-radius: 8px; 
    border: 1.5px solid var(--border); 
    font-size: 0.88rem;
    background: #fafbfe;
    transition: all 0.2s;
}
.form-control:focus { 
    border-color: var(--purple); 
    background: #fff; 
    outline: none;
    box-shadow: 0 0 0 3px rgba(124,58,237,0.08);
}

.row {
    display: flex;
    gap: 15px;
}
.row .form-group {
    flex: 1;
}

.btn-submit { 
    background: var(--purple); 
    color: white; 
    padding: 12px 28px; 
    border: none; 
    border-radius: 10px; 
    cursor: pointer; 
    font-weight: 700; 
    font-size: 0.9rem; 
    transition: all 0.2s; 
}
.btn-submit:hover { 
    background: #6d28d9; 
    transform: translateY(-1px); 
}
.btn-cancel {
    display: inline-block;
    padding: 12px 28px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text);
    background: #fff;
    border: 1.5px solid var(--border);
    transition: all 0.2s;
}
.btn-cancel:hover { 
    background: #f9fafb; 
    border-color: #cbd5e1; 
    color: #000; 
}

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
.alert-danger { 
    background: #fee2e2; 
    color: #991b1b; 
    padding: 12px 16px; 
    border-radius: 10px; 
    margin-bottom: 20px; 
    font-size: 0.85rem;
    border-left: 4px solid #ef4444;
}
.alert-danger i {
    margin-right: 8px;
}

@media (max-width: 768px) {
    .row {
        flex-direction: column;
        gap: 0;
    }
    .page-header .header-content h1 {
        font-size: 1.3rem;
    }
    .container {
        padding: 0 15px;
    }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-comment-dots"></i>
    </div>
    <div class="header-content">
        <h1>Remplir une réclamation pour un employé</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-pen-fancy"></i>
            </span>
            Enregistrez une réclamation (pointage oublié, heure incorrecte, absence non justifiée)
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-comment-dots"></i> Ajouter une réclamation</div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Employé *</label>
                <select name="employe_id" class="form-control" required>
                    <option value="">-- Sélectionner un employé --</option>
                    <?php foreach ($employes as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo $e['matricule'] . ' - ' . $e['prenom'] . ' ' . $e['nom']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Type de réclamation *</label>
                <select name="type_reclamation" class="form-control" required>
                    <option value="pointage_oublie">⏰ Pointage oublié</option>
                    <option value="heure_incorrecte">🕐 Heure incorrecte</option>
                    <option value="absence_non_justifiée">📋 Absence non justifiée</option>
                    <option value="autre">📝 Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-pen-alt"></i> Description *</label>
                <textarea name="motif" class="form-control" rows="5" placeholder="Décrivez la réclamation..." required></textarea>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Ajouter la réclamation
                </button>
                <a href="dashboard.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>