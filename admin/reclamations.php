<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$message = '';

// Ajouter une réclamation (Admin peut aussi en créer une pour un employé)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $employe_id = $_POST['employe_id'];
    $type_reclamation = $_POST['type_reclamation'];
    $motif = $_POST['motif'];
    
    $stmt = $pdo->prepare("INSERT INTO reclamation (employe_id, type_reclamation, motif, statut, traite_par) VALUES (?, ?, ?, 'traité', ?)");
    if ($stmt->execute([$employe_id, $type_reclamation, $motif, $_SESSION['user_id']])) {
        creerNotification($employe_id, "Une réclamation a été ajoutée et traitée pour vous par l'administrateur.", $pdo);
        $message = "✅ Réclamation ajoutée avec succès";
    }
}

$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();
$titre = "📝 Gestion des réclamations";

$stmt = $pdo->query("SELECT r.*, e.nom, e.prenom, e.matricule, p.date_pointage 
                     FROM reclamation r 
                     JOIN employe e ON r.employe_id = e.id 
                     LEFT JOIN pointage p ON r.pointage_id = p.id 
                     ORDER BY r.id DESC");
$reclamations = $stmt->fetchAll();

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
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
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
    background: #ede9fe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #7c3aed;
    font-size: 0.65rem;
    flex-shrink: 0;
}

/* ══ ALERT ══ */
.alert { 
    padding: 12px 16px; 
    border-radius: 10px; 
    margin-bottom: 20px; 
    font-size: 0.85rem;
    border-left: 4px solid #10b981;
    background: #d1fae5;
    color: #065f46;
}
.alert i {
    margin-right: 8px;
}

/* ══ CARD ══ */
.card {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    border: 1px solid var(--border);
    margin-bottom: 25px;
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
.card-header i { color: #7c3aed; }

/* ══ FORM ══ */
.form-group { margin-bottom: 15px; }
.form-group label { 
    display: block; 
    margin-bottom: 6px; 
    font-size: 0.82rem; 
    font-weight: 600; 
    color: #374151; 
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
    border-color: #7c3aed; 
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

/* ══ BOUTONS ══ */
.btn-sm { 
    padding: 6px 14px; 
    border-radius: 8px; 
    text-decoration: none; 
    display: inline-block; 
    background: #7c3aed; 
    color: white; 
    font-size: 0.75rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-sm:hover { background: #6d28d9; transform: translateY(-1px); }

.btn-info-sm {
    padding: 6px 12px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 600;
}
.btn-primary { 
    background: #7c3aed; 
    color: white; 
    border: none; 
    padding: 8px 20px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-primary:hover { background: #6d28d9; transform: translateY(-1px); }
.btn-modal { 
    background: var(--blue); 
    color: white; 
    border: none; 
    padding: 8px 20px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-modal:hover { background: #1d4ed8; }

/* ══ MODAL ══ */
.modal { 
    display: none; 
    position: fixed; 
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%; 
    background: rgba(0,0,0,0.5); 
    justify-content: center; 
    align-items: center; 
    z-index: 1000; 
}
.modal-content { 
    background: white; 
    padding: 25px; 
    border-radius: 12px; 
    width: 450px; 
    max-width: 90%;
}
.modal-content h3 {
    margin-top: 0;
    color: var(--text);
}
.modal-content textarea { 
    width: 100%; 
    padding: 10px; 
    margin: 10px 0; 
    border-radius: 8px; 
    border: 1.5px solid var(--border);
    font-family: inherit;
}
.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .row { flex-direction: column; gap: 0; }
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
}
</style>

<script>
function repondre(id, employe, motif) {
    fermerModalVoir();
    document.getElementById('reclamation_id').value = id;
    document.getElementById('reponse_employe').innerHTML = employe;
    document.getElementById('reponse_motif').innerHTML = motif;
    document.getElementById('modal').style.display = 'flex';
}
function fermerModal() { document.getElementById('modal').style.display = 'none'; }

function voirDetail(nom, matricule, type, motif, reponse, statut) {
    document.getElementById('v_nom').innerText = nom + ' (' + matricule + ')';
    document.getElementById('v_type').innerText = type;
    document.getElementById('v_motif').innerText = motif;
    document.getElementById('v_statut').innerText = statut;
    
    if (reponse) {
        document.getElementById('v_reponse_section').style.display = 'block';
        document.getElementById('v_reponse').innerText = reponse;
    } else {
        document.getElementById('v_reponse_section').style.display = 'none';
    }
    
    document.getElementById('modal_voir').style.display = 'flex';
}
function fermerModalVoir() { document.getElementById('modal_voir').style.display = 'none'; }
</script>

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
            Gérez les réclamations des employés (pointage oublié, heure incorrecte, autre)
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-plus-circle"></i> Remplir une nouvelle réclamation</div>
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
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Enregistrer la réclamation
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-list"></i> Liste des réclamations</div>
    <div class="card-body" style="overflow-x: auto; padding: 0;">
        <table>
            <thead>
                <tr><th>Date</th><th>Employé</th><th>Type</th><th>Motif</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($reclamations as $r): ?>
                <tr>
                    <td><?php echo isset($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : '-'; ?></td>
                    <td><strong><?php echo $r['prenom'] . ' ' . $r['nom']; ?></strong> (<?php echo $r['matricule']; ?>)</td>
                    <td><?php echo $r['type_reclamation']; ?></td>
                    <td title="<?php echo htmlspecialchars($r['motif']); ?>">
                        <?php echo substr($r['motif'], 0, 50) . (strlen($r['motif']) > 50 ? '...' : ''); ?>
                    </td>
                    <td><span class="badge badge-<?php echo $r['statut'] == 'en_attente' ? 'warning' : ($r['statut'] == 'traité' ? 'success' : 'danger'); ?>"><?php echo $r['statut']; ?></span></td>
                    <td class="actions">
                        <button onclick="voirDetail('<?php echo addslashes($r['prenom'] . ' ' . $r['nom']); ?>', '<?php echo $r['matricule']; ?>', '<?php echo $r['type_reclamation']; ?>', '<?php echo addslashes($r['motif']); ?>', '<?php echo addslashes($r['reponse']); ?>', '<?php echo $r['statut']; ?>')" class="btn-info-sm">
                            <i class="fas fa-eye"></i> Voir
                        </button>

                        <?php if ($r['statut'] == 'en_attente'): ?>
                        <button onclick="repondre(<?php echo $r['id']; ?>, '<?php echo $r['prenom'] . ' ' . $r['nom']; ?>', '<?php echo addslashes($r['motif']); ?>')" class="btn-sm">
                            <i class="fas fa-reply"></i> Répondre
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Réponse -->
<div id="modal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-reply"></i> Répondre à la réclamation</h3>
        <p><strong>Employé :</strong> <span id="reponse_employe"></span></p>
        <p><strong>Motif :</strong> <span id="reponse_motif"></span></p>
        <form method="POST" action="reclamation_repondre.php">
            <input type="hidden" name="id" id="reclamation_id">
            <textarea name="reponse" rows="4" placeholder="Votre réponse..." required></textarea>
            <div class="modal-actions">
                <button type="submit" class="btn-modal"><i class="fas fa-paper-plane"></i> Envoyer</button>
                <button type="button" onclick="fermerModal()" style="padding:8px 20px; border-radius:8px; border:1.5px solid var(--border); background:white; cursor:pointer; font-weight:600;">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Voir Détails -->
<div id="modal_voir" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-info-circle"></i> Détails de la réclamation</h3>
        <div style="text-align: left;">
            <p><strong>Employé :</strong> <span id="v_nom"></span></p>
            <p><strong>Type :</strong> <span id="v_type"></span></p>
            <p><strong>Statut :</strong> <span id="v_statut"></span></p>
            <hr>
            <p><strong>Motif / Description :</strong></p>
            <div id="v_motif" style="background:#f8fafc; padding:10px; border-radius:8px; border:1px solid var(--border); white-space: pre-wrap;"></div>
            
            <div id="v_reponse_section" style="margin-top:15px; display:none;">
                <p><strong>Réponse apportée :</strong></p>
                <div id="v_reponse" style="background:#f0fdf4; padding:10px; border-radius:8px; border:1px solid #15803d; color:#15803d; white-space: pre-wrap;"></div>
            </div>
        </div>
        <div class="modal-actions" style="justify-content: flex-end; margin-top:20px;">
            <button type="button" onclick="fermerModalVoir()" class="btn-primary">Fermer</button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>