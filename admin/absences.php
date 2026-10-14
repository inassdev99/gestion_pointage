<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$message = '';

// Ajouter une absence (Quick add comme dans conge)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $employe_id = $_POST['employe_id'];
    $type_absence = $_POST['type_absence'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $motif = $_POST['motif'];
    
    $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut) VALUES (?, ?, ?, ?, ?, 'validé')");
    if ($stmt->execute([$employe_id, $type_absence, $date_debut, $date_fin, $motif])) {
        creerNotification($employe_id, "Une absence a été enregistrée pour vous par l'administrateur.", $pdo);
        $message = "✅ Absence enregistrée avec succès";
    }
}

$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();
$titre = "📋 Gestion des absences";
$stmt = $pdo->query("SELECT a.*, e.nom, e.prenom, e.matricule 
                     FROM absence a 
                     JOIN employe e ON a.employe_id = e.id 
                     ORDER BY a.date_debut DESC");
$absences = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
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
    color: var(--text) !important;
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
    background: var(--card);
}
.card-header i { color: var(--blue); }
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
.search-wrap input:focus { border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,0.05); }

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
    background: var(--blue-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--blue);
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
.btn-success:hover { background: #dcfce7; }
.btn-danger:hover { background: #fee2e2; }
.btn-primary { 
    background: var(--blue); 
    color: white; 
    border: none; 
    padding: 8px 20px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }

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
        <i class="fas fa-calendar-times"></i>
    </div>
    <div class="header-content">
        <h1>Gestion des absences</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-user-clock"></i>
            </span>
            Gérez les absences des employés (maladie, autorisation, congé)
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-plus-circle"></i> Enregistrer une absence</div>
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
                        <label><i class="fas fa-tag"></i> Type</label>
                        <select name="type_absence" class="form-control" required>
                            <option value="maladie">🤒 Maladie</option>
                            <option value="autorisation">📋 Autorisation</option>
                            <option value="conge">🌴 Congé</option>
                            <option value="deplacement">🚗 Déplacement</option>
                            <option value="autre">📝 Autre</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Début</label>
                        <input type="date" name="date_debut" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-week"></i> Fin</label>
                        <input type="date" name="date_fin" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-pen-alt"></i> Motif</label>
                        <input type="text" name="motif" class="form-control" placeholder="Ex: Rendez-vous médical">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Enregistrer l'absence
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-list"></i> Liste des absences</div>
    
    <div class="filters-bar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Rechercher un employé ou un motif..." onkeyup="filterTable()">
        </div>
    </div>

    <div class="card-body" style="overflow-x: auto;">
        <table id="absencesTable">
            <thead>
                <tr><th>Employé</th><th>Matricule</th><th>Type</th><th>Période</th><th>Motif</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($absences as $a): 
                    $initials = strtoupper(substr($a['prenom'], 0, 1) . substr($a['nom'], 0, 1));
                    $status_class = 'warning';
                    if($a['statut'] == 'validé') $status_class = 'success';
                    if($a['statut'] == 'refusé') $status_class = 'danger';
                ?>
                <tr class="absence-row">
                    <td>
                        <div class="emp-cell">
                            <div class="emp-avatar"><?php echo $initials; ?></div>
                            <?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']); ?>
                        </div>
                    </td>
                    <td><?php echo $a['matricule']; ?></td>
                    <td><span style="font-weight: 600;"><?php echo ucfirst($a['type_absence']); ?></span></td>
                    <td><?php echo date('d/m/Y', strtotime($a['date_debut'])) . ' - ' . date('d/m/Y', strtotime($a['date_fin'])); ?></td>
                    <td title="<?php echo htmlspecialchars($a['motif']); ?>">
                        <?php echo htmlspecialchars(substr($a['motif'], 0, 40)) . (strlen($a['motif']) > 40 ? '...' : ''); ?>
                    </td>
                    <td><span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($a['statut']); ?></span></td>
                    <td>
                        <?php if ($a['statut'] == 'en_attente'): ?>
                        <a href="absence_valider.php?id=<?php echo $a['id']; ?>&action=valider" class="btn-sm btn-success" onclick="return confirmer('Valider cette demande ?')"><i class="fas fa-check"></i> Valider</a>
                        <a href="absence_valider.php?id=<?php echo $a['id']; ?>&action=refuser" class="btn-sm btn-danger" onclick="return confirmer('Refuser cette demande ?')"><i class="fas fa-times"></i> Refuser</a>
                        <?php else: ?>
                        <span class="dash">✅ Traité</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('.absence-row');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}
function confirmer(msg) {
    return confirm(msg);
}
</script>

<?php include '../includes/footer.php'; ?>