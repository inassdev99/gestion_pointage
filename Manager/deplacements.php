<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'manager') {
    rediriger('../login.php');
}

$message = '';
$erreur = '';

// Ajouter un déplacement
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
                               VALUES (?, 'déplacement', ?, ?, ?, ?, ?)");
        if ($stmt->execute([$employe_id, $date_debut, $date_fin, $motif, $statut, $_SESSION['user_id']])) {
            // 🔔 NOTIFICATION
            $message_notif = "Un déplacement professionnel a été enregistré pour vous (Statut: $statut).";
            creerNotification($employe_id, $message_notif, $pdo);
            $message = "✅ Déplacement ajouté avec succès";
            header("Refresh:1; url=deplacements.php");
        } else {
            $erreur = "❌ Erreur lors de l'ajout";
        }
    }
}

// Valider un déplacement
if (isset($_GET['valider'])) {
    $id = $_GET['valider'];
    $stmt_emp = $pdo->prepare("SELECT employe_id FROM absence WHERE id = ?");
    $stmt_emp->execute([$id]);
    $emp_id = $stmt_emp->fetchColumn();
    
    $pdo->prepare("UPDATE absence SET statut = 'validé', valide_par = ? WHERE id = ?")->execute([$_SESSION['user_id'], $id]);
    
    // 🔔 NOTIFICATION
    creerNotification($emp_id, "Votre déplacement professionnel a été validé.", $pdo);
    $message = "✅ Déplacement validé";
    header("Refresh:1; url=deplacements.php");
}

// Refuser un déplacement
if (isset($_GET['refuser'])) {
    $id = $_GET['refuser'];
    $stmt_emp = $pdo->prepare("SELECT employe_id FROM absence WHERE id = ?");
    $stmt_emp->execute([$id]);
    $emp_id = $stmt_emp->fetchColumn();
    
    $pdo->prepare("UPDATE absence SET statut = 'refusé', valide_par = ? WHERE id = ?")->execute([$_SESSION['user_id'], $id]);
    
    // 🔔 NOTIFICATION
    creerNotification($emp_id, "Votre déplacement professionnel a été refusé.", $pdo);
    $message = "❌ Déplacement refusé";
    header("Refresh:1; url=deplacements.php");
}

// Récupérer les employés pour le formulaire
$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();

$titre = "🚗 Déplacements Équipe";

// Récupérer les déplacements des employés
$stmt = $pdo->query("SELECT a.*, e.nom, e.prenom, e.matricule 
                     FROM absence a 
                     JOIN employe e ON a.employe_id = e.id 
                     WHERE a.type_absence = 'déplacement'
                     ORDER BY a.date_debut DESC");
$deplacements = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
    :root {
        --blue: #2563eb;
        --blue-light: #eff6ff;
        --bg: #f4f6fb;
        --card: #fff;
        --border: #e5e9f2;
        --text: #1a2340;
        --muted: #6b7a99;
        --green-bg: #f0fdf4;
        --green-text: #15803d;
        --red-bg: #fef2f2;
        --red-text: #b91c1c;
        --yellow-bg: #fffbeb;
        --yellow-text: #b45309;
        --teal: #0d9488;
        --teal-light: #ccfbf1;
        --radius: 12px;
        --shadow: 0 2px 12px rgba(30,45,77,0.07);
    }
    body { background-color: var(--bg) !important; font-family: 'Inter', system-ui, sans-serif; }

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
        background: linear-gradient(135deg, var(--teal) 0%, #0f766e 100%);
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
        background: var(--teal-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--teal);
        font-size: 0.65rem;
        flex-shrink: 0;
    }

    .card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 25px; overflow: hidden; }
    .card-header { padding: 15px 20px; border-bottom: 1px solid var(--border); font-weight: 600; background: #fff; display: flex; align-items: center; gap: 8px; }
    .card-header i { color: var(--teal); }
    .card-body { padding: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th { padding: 12px; font-size: 0.75rem; font-weight: 600; color: var(--muted); text-align: left; background: #f8fafc; border-bottom: 1px solid var(--border); text-transform: uppercase; }
    td { padding: 12px; font-size: 0.85rem; color: var(--text); border-bottom: 1px solid #f0f2f8; }
    .badge { padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
    .badge-success { background: var(--green-bg); color: var(--green-text); }
    .badge-danger { background: var(--red-bg); color: var(--red-text); }
    .badge-warning { background: var(--yellow-bg); color: var(--yellow-text); }
    .btn-sm { padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s; }
    .btn-sm:hover { transform: scale(1.05); }
    .btn-success { background: var(--green-bg); color: var(--green-text); border: 1px solid #bbf7d0; }
    .btn-danger { background: var(--red-bg); color: var(--red-text); border: 1px solid #fecaca; }
    .btn-primary { background: var(--teal); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s; }
    .btn-primary:hover { background: #0f766e; transform: translateY(-1px); }
    .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1.5px solid var(--border); background: #fafbfe; transition: all 0.2s; }
    .form-control:focus { border-color: var(--teal); outline: none; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
    .form-label { font-weight: 600; font-size: 0.85rem; color: var(--text); }
    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; border-left: 4px solid; }
    .alert-success { background: #d1fae5; color: #065f46; border-color: #10b981; }
    .alert-danger { background: #fee2e2; color: #991b1b; border-color: #ef4444; }

    @media (max-width: 768px) {
        .page-header .header-content h1 { font-size: 1.3rem; }
    }
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-plane"></i>
    </div>
    <div class="header-content">
        <h1>Déplacements de l'équipe</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-map-marked-alt"></i>
            </span>
            Gérez les déplacements professionnels de votre équipe
        </p>
    </div>
</div>

<?php if ($message): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div><?php endif; ?>
<?php if ($erreur): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?></div><?php endif; ?>

<!-- CARD : AJOUTER UN DÉPLACEMENT -->
<div class="card">
    <div class="card-header"><i class="fas fa-plus-circle"></i> Enregistrer un déplacement professionnel</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label"><i class="fas fa-user"></i> Employé</label>
                    <select name="employe_id" class="form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($employes as $e): ?>
                            <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['prenom'] . ' ' . $e['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label"><i class="fas fa-calendar-day"></i> Date début</label>
                    <input type="date" name="date_debut" class="form-control" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label"><i class="fas fa-calendar-week"></i> Date fin</label>
                    <input type="date" name="date_fin" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><i class="fas fa-map-pin"></i> Destination / Motif</label>
                    <input type="text" name="motif" class="form-control" placeholder="ex: Client Casablanca">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label"><i class="fas fa-flag-checkered"></i> Statut</label>
                    <select name="statut" class="form-control">
                        <option value="validé">✅ Validé</option>
                        <option value="en_attente">⏳ En attente</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        </form>
    </div>
</div>

<!-- CARD : LISTE DES DÉPLACEMENTS -->
<div class="card">
    <div class="card-header"><i class="fas fa-list"></i> Suivi des déplacements professionnels</div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Matricule</th>
                        <th>Période</th>
                        <th>Destination / Motif</th>
                        <th>Statut</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deplacements)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--muted);">
                            <i class="fas fa-plane" style="font-size: 2rem; opacity: 0.5;"></i><br>
                            Aucun déplacement enregistré
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($deplacements as $d): 
                        $status_class = match($d['statut']) {
                            'validé' => 'success',
                            'refusé' => 'danger',
                            default => 'warning'
                        };
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($d['prenom'] . ' ' . $d['nom']); ?></strong></td>
                        <td><?php echo $d['matricule']; ?></td>
                        <td>Du <?php echo date('d/m/Y', strtotime($d['date_debut'])); ?> au <?php echo date('d/m/Y', strtotime($d['date_fin'])); ?></td>
                        <td><?php echo htmlspecialchars($d['motif']); ?></td>
                        <td><span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($d['statut']); ?></span></td>
                        <td style="text-align: center;">
                            <?php if ($d['statut'] == 'en_attente'): ?>
                                <a href="deplacements.php?valider=<?php echo $d['id']; ?>" class="btn-sm btn-success" title="Valider">
                                    <i class="fas fa-check"></i> Valider
                                </a>
                                <a href="deplacements.php?refuser=<?php echo $d['id']; ?>" class="btn-sm btn-danger" title="Refuser">
                                    <i class="fas fa-times"></i> Refuser
                                </a>
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-check-circle"></i> Traité</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>