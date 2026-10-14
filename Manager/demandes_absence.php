<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$titre = "Ajouter une absence";
$message = '';
$erreur = '';

$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employe_id = $_POST['employe_id'];
    $type_absence = $_POST['type_absence'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $motif = $_POST['motif'];
    
    if ($date_debut > $date_fin) {
        $erreur = "La date de début doit être antérieure à la date de fin";
    } else {
        $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut, valide_par) VALUES (?, ?, ?, ?, ?, 'validé', ?)");
        if ($stmt->execute([$employe_id, $type_absence, $date_debut, $date_fin, $motif, $_SESSION['user_id']])) {
            creerNotification($employe_id, "Une absence a été enregistrée pour vous par le manager.", $pdo);
            $message = "✅ Absence ajoutée avec succès";
        } else {
            $erreur = "❌ Erreur lors de l'ajout";
        }
    }
}

include '../includes/header.php';
?>

<style>
/* ══ VARIABLES ══ */
:root {
    --blue:        #2563eb;
    --card:        #fff;
    --border:      #e5e9f2;
    --text:        #1a2340;
    --muted:       #6b7a99;
    --radius:      12px;
    --shadow:      0 2px 12px rgba(30,45,77,0.07);
}

.card { 
    background: var(--card); 
    border-radius: var(--radius); 
    box-shadow: var(--shadow); 
    max-width: 600px; 
    margin: 0 auto; 
    overflow: hidden;
}
.card-header { 
    padding: 15px 20px; 
    border-bottom: 1px solid var(--border); 
    font-weight: 600; 
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.card-header i { color: var(--blue); }
.card-body { padding: 25px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 0.82rem; font-weight: 600; color: #374151; }
.form-control { 
    width: 100%; 
    padding: 10px; 
    border-radius: 8px; 
    border: 1px solid var(--border); 
    font-size: 0.88rem;
    background: #f8fafc;
}
.form-control:focus { border-color: var(--blue); background: #fff; outline: none; }
.btn-submit { background: var(--blue); color: white; padding: 12px 25px; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 0.9rem; transition: all 0.2s; }
.btn-submit:hover { background: #1d4ed8; transform: translateY(-1px); }
.btn-cancel {
    display: inline-block;
    padding: 12px 25px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text);
    background: #fff;
    border: 1.5px solid var(--border);
    transition: all 0.2s;
}
.btn-cancel:hover { background: #f9fafb; border-color: #cbd5e1; color: #000; }
</style>

<h1>Ajouter une absence pour un employé</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert alert-danger"><?php echo $erreur; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-calendar-times"></i> Formulaire d'absence</div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group"><label>Employé</label><select name="employe_id" class="form-control" required><option value="">-- Sélectionner --</option><?php foreach ($employes as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo $e['matricule'] . ' - ' . $e['prenom'] . ' ' . $e['nom']; ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Type</label><select name="type_absence" class="form-control" required><option value="maladie">Maladie</option><option value="déplacement">Déplacement</option><option value="autorisation">Autorisation</option></select></div>
            <div class="row"><div class="col-md-6"><div class="form-group"><label>Date début</label><input type="date" name="date_debut" class="form-control" required></div></div>
            <div class="col-md-6"><div class="form-group"><label>Date fin</label><input type="date" name="date_fin" class="form-control" required></div></div></div>
            <div class="form-group"><label>Motif</label><textarea name="motif" class="form-control" rows="3" placeholder="Motif..."></textarea></div>
            <button type="submit" class="btn-submit">Ajouter l'absence</button>
            <a href="dashboard.php" class="btn-cancel" style="margin-left: 10px;">Annuler</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>