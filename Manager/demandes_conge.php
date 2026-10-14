<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$titre = "Ajouter un congé";
$message = '';
$erreur = '';

$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employe_id = $_POST['employe_id'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $motif = $_POST['motif'];
    
    if ($date_debut > $date_fin) {
        $erreur = "La date de début doit être antérieure à la date de fin";
    } else {
        $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut, valide_par) VALUES (?, 'congé', ?, ?, ?, 'validé', ?)");
        if ($stmt->execute([$employe_id, $date_debut, $date_fin, $motif, $_SESSION['user_id']])) {
            $message = "✅ Congé ajouté avec succès";
        } else {
            $erreur = "❌ Erreur lors de l'ajout";
        }
    }
}

include '../includes/header.php';
?>

<style>
.card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
.card-header { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 600; background: white; }
.card-header i { margin-right: 8px; color: #1e3c72; }
.card-body { padding: 25px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
.form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; }
.btn-submit { background: #1e3c72; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
</style>

<h1>Ajouter un congé pour un employé</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert alert-danger"><?php echo $erreur; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-umbrella-beach"></i> Formulaire de congé</div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group"><label>Employé</label><select name="employe_id" class="form-control" required><option value="">-- Sélectionner --</option><?php foreach ($employes as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo $e['matricule'] . ' - ' . $e['prenom'] . ' ' . $e['nom']; ?></option><?php endforeach; ?></select></div>
            <div class="row"><div class="col-md-6"><div class="form-group"><label>Date début</label><input type="date" name="date_debut" class="form-control" required></div></div>
            <div class="col-md-6"><div class="form-group"><label>Date fin</label><input type="date" name="date_fin" class="form-control" required></div></div></div>
            <div class="form-group"><label>Motif</label><textarea name="motif" class="form-control" rows="3" placeholder="Motif..."></textarea></div>
            <button type="submit" class="btn-submit">Ajouter le congé</button>
            <a href="dashboard.php" style="margin-left: 10px;">Annuler</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>