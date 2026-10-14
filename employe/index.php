<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'employe') {
    rediriger('../dashboard.php');
}

$titre = "Mon espace";

// Statistiques du mois
$mois_courant = date('Y-m');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pointage WHERE employe_id = ? AND date_pointage LIKE ? AND heure_depart IS NOT NULL");
$stmt->execute([$user_id, "$mois_courant%"]);
$nb_jours_travailles = $stmt->fetch()['total'];

// Récupérer le pointage du jour
$aujourdhui = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM pointage WHERE employe_id = ? AND date_pointage = ?");
$stmt->execute([$user_id, $aujourdhui]);
$pointage = $stmt->fetch();

include '../includes/header.php';
?>

<style>
/* Styles spécifiques espace employé */
.dashboard-header {
    margin-bottom: 30px;
}
.dashboard-header h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 5px;
}
.dashboard-header p {
    color: #64748b;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
    border: 1px solid #e2e8f0;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.stat-icon {
    width: 55px;
    height: 55px;
    background: rgba(30, 60, 114, 0.1);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.stat-icon i {
    font-size: 1.6rem;
    color: #1e3c72;
}
.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 5px;
    color: #1e293b;
}
.stat-info p {
    color: #64748b;
    margin: 0;
    font-size: 0.8rem;
}
.card {
    background: white;
    border-radius: 20px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    overflow: hidden;
}
.card-header {
    background: white;
    padding: 15px 20px;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 600;
    color: #1e293b;
}
.card-header i {
    margin-right: 8px;
    color: #1e3c72;
}
.card-body {
    padding: 20px;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}
.btn-success {
    background: #10b981;
    color: white;
}
.btn-success:hover {
    background: #059669;
    transform: translateY(-2px);
}
.btn-outline-primary {
    background: transparent;
    border: 1px solid #1e3c72;
    color: #1e3c72;
}
.btn-outline-primary:hover {
    background: #1e3c72;
    color: white;
}
.btn-outline-secondary {
    background: transparent;
    border: 1px solid #64748b;
    color: #64748b;
}
.btn-outline-secondary:hover {
    background: #64748b;
    color: white;
}
.d-grid {
    display: grid;
    gap: 12px;
}
.d-grid .btn {
    justify-content: center;
}
</style>

<div class="dashboard-header">
    <h1><i class="fas fa-user-clock"></i> Mon espace</h1>
    <p>Bienvenue <?php echo $user_prenom . ' ' . $user_nom; ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?php echo $nb_jours_travailles; ?></h3>
            <p>Jours travaillés ce mois</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo $pointage && $pointage['heure_arrivee'] ? date('H:i', strtotime($pointage['heure_arrivee'])) : '--:--'; ?></h3>
            <p>Arrivée aujourd'hui</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-sign-out-alt"></i></div>
        <div class="stat-info">
            <h3><?php echo $pointage && $pointage['heure_depart'] ? date('H:i', strtotime($pointage['heure_depart'])) : '--:--'; ?></h3>
            <p>Départ aujourd'hui</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3><?php echo $pointage && $pointage['heure_arrivee'] && !$pointage['heure_depart'] ? 'En cours' : ($pointage && $pointage['heure_depart'] ? 'Terminé' : 'Non pointé'); ?></h3>
            <p>Statut</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-fingerprint"></i> Pointage du jour
            </div>
            <div class="card-body text-center">
                <?php if (!$pointage || !$pointage['heure_arrivee']): ?>
                    <i class="fas fa-clock" style="font-size: 60px; color: #dc3545;"></i>
                    <h4 class="mt-3">Vous n'avez pas encore pointé</h4>
                    <a href="pointage.php" class="btn btn-success mt-3">
                        <i class="fas fa-fingerprint"></i> Pointer maintenant
                    </a>
                <?php elseif ($pointage['heure_arrivee'] && !$pointage['heure_depart']): ?>
                    <i class="fas fa-play-circle" style="font-size: 60px; color: #ffc107;"></i>
                    <h4 class="mt-3">En cours de travail</h4>
                    <p>Arrivée : <strong><?php echo date('H:i:s', strtotime($pointage['heure_arrivee'])); ?></strong></p>
                    <a href="pointage.php" class="btn btn-warning mt-2">
                        <i class="fas fa-sign-out-alt"></i> Pointer départ
                    </a>
                <?php else: ?>
                    <i class="fas fa-check-circle" style="font-size: 60px; color: #28a745;"></i>
                    <h4 class="mt-3">Pointage terminé</h4>
                    <p>Arrivée : <?php echo date('H:i', strtotime($pointage['heure_arrivee'])); ?></p>
                    <p>Départ : <?php echo date('H:i', strtotime($pointage['heure_depart'])); ?></p>
                    <?php
                    $heures = calculerHeures($pointage['heure_arrivee'], $pointage['heure_pause_debut'], $pointage['heure_pause_fin'], $pointage['heure_depart']);
                    ?>
                    <p>Heures travaillées : <strong><?php echo $heures; ?></strong></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bell"></i> Actions rapides
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="pointage.php" class="btn btn-outline-primary">
                        <i class="fas fa-fingerprint"></i> Pointer
                    </a>
                    <a href="profil.php" class="btn btn-outline-secondary">
                        <i class="fas fa-id-card"></i> Mon profil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>