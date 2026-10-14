<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$titre = "Ajouter une réclamation";
$message = '';
$erreur = '';

$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employe_id = $_POST['employe_id'];
    $type_reclamation = $_POST['type_reclamation'];
    $motif = $_POST['motif'];
    
    $stmt = $pdo->prepare("INSERT INTO reclamation (employe_id, type_reclamation, motif, statut, traite_par) VALUES (?, ?, ?, 'traité', ?)");
    if ($stmt->execute([$employe_id, $type_reclamation, $motif, $_SESSION['user_id']])) {
        creerNotification($employe_id, "Une réclamation a été ajoutée pour vous par le manager.", $pdo);
        $message = "✅ Réclamation ajoutée avec succès";
    } else {
        $erreur = "❌ Erreur lors de l'ajout";
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

<h1>Ajouter une réclamation pour un employé</h1>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert alert-danger"><?php echo $erreur; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-comment-dots"></i> Formulaire de réclamation</div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group"><label>Employé</label><select name="employe_id" class="form-control" required><option value="">-- Sélectionner --</option><?php foreach ($employes as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo $e['matricule'] . ' - ' . $e['prenom'] . ' ' . $e['nom']; ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Type</label><select name="type_reclamation" class="form-control" required><option value="pointage_oublie">Pointage oublié</option><option value="heure_incorrecte">Heure incorrecte</option><option value="absence_non_justifiée">Absence non justifiée</option><option value="autre">Autre</option></select></div>
            <div class="form-group"><label>Description</label><textarea name="motif" class="form-control" rows="5" placeholder="Décrivez la réclamation..." required></textarea></div>
            <button type="submit" class="btn-submit">Ajouter la réclamation</button>
            <a href="dashboard.php" style="margin-left: 10px;">Annuler</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>