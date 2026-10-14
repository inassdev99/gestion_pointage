<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$titre = "💬 Gestion des réclamations";
$message = '';

// Répondre à une réclamation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['repondre'])) {
    $id = $_POST['id'];
    $reponse = $_POST['reponse'];
    
    $stmt_emp = $pdo->prepare("SELECT employe_id FROM reclamation WHERE id = ?");
    $stmt_emp->execute([$id]);
    $emp_id = $stmt_emp->fetchColumn();

    $pdo->prepare("UPDATE reclamation SET reponse = ?, statut = 'traité', traite_par = ? WHERE id = ?")->execute([$reponse, $_SESSION['user_id'], $id]);
    creerNotification($emp_id, "Votre manager a répondu à votre réclamation.", $pdo);
    $message = "✅ Réponse envoyée";
}

// Ajouter une réclamation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $employe_id = $_POST['employe_id'];
    $type_reclamation = $_POST['type_reclamation'];
    $motif = $_POST['motif'];
    
    $stmt = $pdo->prepare("INSERT INTO reclamation (employe_id, type_reclamation, motif, statut, traite_par) VALUES (?, ?, ?, 'traité', ?)");
    if ($stmt->execute([$employe_id, $type_reclamation, $motif, $_SESSION['user_id']])) {
        creerNotification($employe_id, "Une réclamation a été créée pour vous par votre manager.", $pdo);
        $message = "✅ Réclamation ajoutée avec succès";
    }
}

// Récupérer les employés pour le formulaire
$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();

// Récupérer toutes les réclamations
$reclamations = $pdo->query("
    SELECT r.*, e.nom, e.prenom, e.matricule 
    FROM reclamation r 
    JOIN employe e ON r.employe_id = e.id 
    ORDER BY r.id DESC
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
    --green-bg:    #f0fdf4;
    --green-text:  #15803d;
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --yellow-bg:   #fffbeb;
    --yellow-text: #b45309;
    --purple:      #7c3aed;
    --purple-light:#ede9fe;
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
    color: var(--purple);
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

/* ══ CARDS ══ */
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
    align-items: center;
    gap: 8px;
    background: #fff;
}
.card-header i { color: var(--purple); }
.card-header .badge-count {
    margin-left: auto;
    background: var(--purple-light);
    color: var(--purple);
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
    color: var(--purple);
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
    border-color: var(--purple); 
    background: #fff;
    box-shadow: 0 0 0 3px rgba(124,58,237,0.08);
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
.badge-warning::before { background: var(--yellow-text); }
.badge-success { background: var(--green-bg); color: var(--green-text); }
.badge-warning { background: var(--yellow-bg); color: var(--yellow-text); }

/* ══ BOUTONS ══ */
.btn-repondre { 
    background: var(--purple); 
    color: white; 
    padding: 8px 16px; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    font-size: 0.75rem; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-repondre:hover {
    background: #6d28d9;
    transform: scale(1.05);
}
.btn-submit { 
    background: var(--purple); 
    color: white; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-submit:hover {
    background: #6d28d9;
    transform: translateY(-1px);
}
.btn-cancel {
    padding: 10px 20px;
    border-radius: 8px;
    border: 1.5px solid var(--border);
    background: white;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}
.btn-cancel:hover {
    background: #f9fafb;
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
    max-width: 450px; 
    width: 90%; 
}
.modal-content h3 {
    margin-top: 0;
    color: var(--text);
}
.modal-content textarea {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1.5px solid var(--border);
    font-family: inherit;
    margin: 10px 0;
}
.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
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
        <i class="fas fa-comment-dots"></i>
    </div>
    <div class="header-content">
        <h1>Gestion des réclamations</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-pen-fancy"></i>
            </span>
            Gérez les réclamations des employés et apportez des réponses
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>

<!-- STATISTIQUES -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?php echo count($reclamations); ?></div>
        <div class="label"><i class="fas fa-list"></i> Total réclamations</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php 
            $en_attente = 0;
            foreach ($reclamations as $r) {
                if ($r['statut'] == 'en_attente') $en_attente++;
            }
            echo $en_attente;
        ?></div>
        <div class="label"><i class="fas fa-clock"></i> En attente</div>
    </div>
</div>

<!-- CARD : AJOUTER -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle"></i> Remplir une nouvelle réclamation
        <span class="badge-count"><?php echo count($reclamations); ?> réclamation(s)</span>
    </div>
    <div class="card-body" style="padding: 20px;">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Employé</label>
                        <select name="employe_id" class="form-control" required>
                            <option value="">-- Sélectionner un employé --</option>
                            <?php foreach ($employes as $e): ?>
                                <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['prenom'] . ' ' . $e['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Type de réclamation</label>
                        <select name="type_reclamation" class="form-control" required>
                            <option value="pointage_oublie">⏰ Pointage oublié</option>
                            <option value="heure_incorrecte">🕐 Heure incorrecte</option>
                            <option value="absence_non_justifiee">📋 Absence non justifiée</option>
                            <option value="autre">📝 Autre</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-pen-alt"></i> Motif / Description</label>
                        <input type="text" name="motif" class="form-control" placeholder="Ex: Oubli de pointage le 12/06" required>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-submit" style="margin-top: 5px;">
                <i class="fas fa-save"></i> Enregistrer la réclamation
            </button>
        </form>
    </div>
</div>

<!-- CARD : LISTE -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Liste des réclamations
        <span class="badge-count"><?php echo count($reclamations); ?> réclamation(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (count($reclamations) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employé</th>
                    <th>Matricule</th>
                    <th>Type</th>
                    <th>Motif</th>
                    <th>Réponse</th>
                    <th>Statut</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reclamations as $r): ?>
                <tr>
                    <td><?php echo isset($r['created_at']) ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-'; ?></td>
                    <td><strong><?php echo $r['prenom'] . ' ' . $r['nom']; ?></strong></td>
                    <td><?php echo $r['matricule']; ?></td>
                    <td><?php echo $r['type_reclamation']; ?></td>
                    <td style="max-width: 200px;" title="<?php echo htmlspecialchars($r['motif']); ?>">
                        <?php echo substr($r['motif'], 0, 100) . (strlen($r['motif']) > 100 ? '...' : ''); ?>
                    </td>
                    <td style="max-width: 200px;" title="<?php echo htmlspecialchars($r['reponse']); ?>">
                        <?php echo $r['reponse'] ? substr($r['reponse'], 0, 80) . (strlen($r['reponse']) > 80 ? '...' : '') : '-'; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $r['statut'] == 'traité' ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $r['statut'] == 'traité' ? '✅ Traité' : '⏳ En attente'; ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($r['statut'] == 'en_attente'): ?>
                            <button onclick="ouvrirModal(<?php echo $r['id']; ?>, '<?php echo addslashes($r['motif']); ?>')" class="btn-repondre">
                                <i class="fas fa-reply"></i> Répondre
                            </button>
                        <?php else: ?>
                            <span style="color: var(--green-text); font-size: 0.75rem; font-weight: 600;">
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
            <i class="fas fa-comment-dots"></i>
            <p>Aucune réclamation enregistrée</p>
            <small>Ajoutez une réclamation ci-dessus</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL RÉPONSE -->
<div id="reponseModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-reply" style="color: var(--purple);"></i> Répondre à la réclamation</h3>
        <form method="POST">
            <input type="hidden" name="id" id="reclamation_id">
            <textarea name="reponse" id="reponse_text" rows="4" placeholder="Votre réponse..." required></textarea>
            <div class="modal-actions">
                <button type="submit" name="repondre" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
                <button type="button" class="btn-cancel" onclick="fermerModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function ouvrirModal(id, motif) {
    document.getElementById('reclamation_id').value = id;
    document.getElementById('reponseModal').style.display = 'flex';
}

function fermerModal() {
    document.getElementById('reponseModal').style.display = 'none';
}

// Fermer le modal en cliquant à l'extérieur
window.onclick = function(event) {
    var modal = document.getElementById('reponseModal');
    if (event.target == modal) {
        fermerModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>