<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$titre = "📋 Gestion des absences";
$message = '';
$erreur = '';

// Ajouter une absence
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $employe_id = $_POST['employe_id'];
    $type_absence = $_POST['type_absence'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $motif = $_POST['motif'];
    $statut = $_POST['statut'];
    
    $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut, valide_par) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$employe_id, $type_absence, $date_debut, $date_fin, $motif, $statut, $_SESSION['user_id']])) {
        creerNotification($employe_id, "Une absence ($type_absence) a été enregistrée pour vous par votre manager.", $pdo);
        $message = "✅ Absence ajoutée avec succès";
    } else {
        $erreur = "❌ Erreur lors de l'ajout";
    }
}

// Valider/refuser une absence
if (isset($_GET['valider'])) {
    $id = $_GET['valider'];
    $stmt_emp = $pdo->prepare("SELECT employe_id FROM absence WHERE id = ?");
    $stmt_emp->execute([$id]);
    $emp_id = $stmt_emp->fetchColumn();

    $pdo->prepare("UPDATE absence SET statut = 'validé', valide_par = ? WHERE id = ?")->execute([$_SESSION['user_id'], $id]);
    creerNotification($emp_id, "Votre demande d'absence a été validée par votre manager.", $pdo);
    $message = "✅ Absence validée";
}
if (isset($_GET['refuser'])) {
    $id = $_GET['refuser'];
    $stmt_emp = $pdo->prepare("SELECT employe_id FROM absence WHERE id = ?");
    $stmt_emp->execute([$id]);
    $emp_id = $stmt_emp->fetchColumn();

    $pdo->prepare("UPDATE absence SET statut = 'refusé', valide_par = ? WHERE id = ?")->execute([$_SESSION['user_id'], $id]);
    creerNotification($emp_id, "Votre demande d'absence a été refusée par votre manager.", $pdo);
    $message = "❌ Absence refusée";
}

// Récupérer toutes les absences
$absences = $pdo->query("
    SELECT a.*, e.nom, e.prenom, e.matricule 
    FROM absence a 
    JOIN employe e ON a.employe_id = e.id 
    ORDER BY a.date_debut DESC
")->fetchAll();

// Récupérer les employés
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
    --radius:      12px;
    --shadow:      0 2px 12px rgba(30,45,77,0.07);
    --green-bg:    #f0fdf4;
    --green-text:  #15803d;
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --yellow-bg:   #fffbeb;
    --yellow-text: #b45309;
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

/* ══ BADGES ══ */
.badge { 
    display: inline-flex; 
    align-items: center; 
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
.btn-success { background: var(--green-bg); color: var(--green-text); border: 1px solid #dcfce7; }
.btn-danger { background: var(--red-bg); color: var(--red-text); border: 1px solid #fee2e2; }
.btn-submit { 
    background: var(--blue); 
    color: white; 
    padding: 10px 22px; 
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
        <i class="fas fa-calendar-times"></i>
    </div>
    <div class="header-content">
        <h1>Gestion des absences</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-user-clock"></i>
            </span>
            Gérez les absences des employés de votre équipe
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
        <div class="number"><?php echo count($absences); ?></div>
        <div class="label"><i class="fas fa-list"></i> Total absences</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php 
            $en_attente = 0;
            foreach ($absences as $a) {
                if ($a['statut'] == 'en_attente') $en_attente++;
            }
            echo $en_attente;
        ?></div>
        <div class="label"><i class="fas fa-clock"></i> En attente</div>
    </div>
</div>

<!-- CARD : AJOUTER -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle"></i> Ajouter une absence
        <span class="badge-count"><?php echo count($absences); ?> absence(s)</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-3">
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
                <div class="col-md-2">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Type</label>
                        <select name="type_absence" class="form-control" required>
                            <option value="conge">🌴 Congé payé</option>
                            <option value="maladie">🤒 Maladie</option>
                            <option value="deplacement">🚗 Déplacement</option>
                            <option value="autorisation">📋 Autorisation</option>
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
                <div class="col-md-2">
                    <div class="form-group">
                        <label><i class="fas fa-flag-checkered"></i> Statut</label>
                        <select name="statut" class="form-control">
                            <option value="valide">✅ Validé</option>
                            <option value="en_attente">⏳ En attente</option>
                            <option value="refuse">❌ Refusé</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-pen-alt"></i> Motif</label>
                        <input type="text" name="motif" class="form-control" placeholder="Motif de l'absence">
                    </div>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Ajouter l'absence
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- CARD : LISTE -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Liste des absences
        <span class="badge-count"><?php echo count($absences); ?> absence(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto; padding: 0;">
        <?php if (count($absences) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Matricule</th>
                    <th>Type</th>
                    <th>Période</th>
                    <th>Motif</th>
                    <th>Statut</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($absences as $a): ?>
                <tr>
                    <td><strong><?php echo $a['prenom'] . ' ' . $a['nom']; ?></strong></td>
                    <td><?php echo $a['matricule']; ?></td>
                    <td><?php echo ucfirst($a['type_absence']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($a['date_debut'])) . ' → ' . date('d/m/Y', strtotime($a['date_fin'])); ?></td>
                    <td title="<?php echo htmlspecialchars($a['motif']); ?>">
                        <?php echo htmlspecialchars($a['motif'] ?: '-'); ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $a['statut'] == 'validé' ? 'badge-success' : ($a['statut'] == 'refusé' ? 'badge-danger' : 'badge-warning'); ?>">
                            <?php echo $a['statut'] == 'validé' ? '✅ Validé' : ($a['statut'] == 'refusé' ? '❌ Refusé' : '⏳ En attente'); ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($a['statut'] == 'en_attente'): ?>
                            <a href="absences.php?valider=<?php echo $a['id']; ?>" class="btn-sm btn-success" onclick="return confirm('Valider cette absence ?')">
                                <i class="fas fa-check"></i> Valider
                            </a>
                            <a href="absences.php?refuser=<?php echo $a['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Refuser cette absence ?')">
                                <i class="fas fa-times"></i> Refuser
                            </a>
                        <?php else: ?>
                            <span style="color: var(--muted); font-size: 0.75rem;">
                                <i class="fas fa-check-circle"></i> Traité
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <p>Aucune absence enregistrée</p>
            <small>Ajoutez une absence ci-dessus</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>