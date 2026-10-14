<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'employe') {
    rediriger('../login.php');
}

$titre = "Mon profil";

// Récupérer les informations de l'employé
$stmt = $pdo->prepare("SELECT e.*, d.nom as departement_nom 
                       FROM employe e 
                       LEFT JOIN departement d ON e.departement_id = d.id 
                       WHERE e.id = ?");
$stmt->execute([$user_id]);
$employe = $stmt->fetch();

include '../includes/header.php';
?>

<style>
/* Styles pour la page profil */
.profile-container {
    max-width: 800px;
    margin: 0 auto;
}

.profile-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    padding: 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.profile-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    background: white;
}

.default-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 4px solid white;
}

.default-photo i {
    font-size: 80px;
    color: white;
}

.profile-header h2 {
    margin: 15px 0 5px;
    font-size: 1.5rem;
}

.profile-header p {
    opacity: 0.9;
    font-size: 0.9rem;
}

.profile-body {
    padding: 30px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}
.info-table tr {
    border-bottom: 1px solid var(--border);
}
.info-table th, .info-table td {
    padding: 14px;
    text-align: left;
}
.info-table th {
    width: 180px;
    color: var(--muted);
    font-weight: 600;
}
.info-table td {
    color: var(--text);
    font-weight: 700;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.stat-card {
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
}

.stat-card h3 {
    font-size: 1.5rem;
    color: #1e3c72;
    margin-bottom: 5px;
}

.stat-card p {
    font-size: 0.75rem;
    color: #64748b;
}

@media (max-width: 768px) {
    .info-table th {
        width: 120px;
    }
    .profile-header {
        padding: 20px;
    }
}
</style>

<div class="profile-container">
    <div class="profile-card">
        <!-- En-tête avec photo -->
        <div class="profile-header">
            <?php if ($employe['photo'] && file_exists('../assets/images/employes/' . $employe['photo'])): ?>
                <img src="../assets/images/employes/<?php echo $employe['photo']; ?>" class="profile-photo" alt="Photo de profil">
            <?php else: ?>
                <div class="default-photo">
                    <i class="fas fa-user-circle"></i>
                </div>
            <?php endif; ?>
            <h2><?php echo $employe['prenom'] . ' ' . $employe['nom']; ?></h2>
            <p><i class="fas fa-id-badge"></i> Matricule: <?php echo $employe['matricule']; ?></p>
        </div>
        
        <!-- Corps avec informations -->
        <div class="profile-body">
            <table class="info-table">
                <tr>
                    <th><i class="fas fa-user"></i> Nom complet</th>
                    <td><?php echo $employe['nom'] . ' ' . $employe['prenom']; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-id-card"></i> Matricule</th>
                    <td><?php echo $employe['matricule']; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-envelope"></i> Email</th>
                    <td><?php echo $employe['email']; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-phone"></i> Téléphone</th>
                    <td><?php echo $employe['telephone'] ?: '<span class="text-muted">Non renseigné</span>'; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-building"></i> Département</th>
                    <td><?php echo $employe['departement_nom'] ?: '<span class="text-muted">Non affecté</span>'; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-calendar-alt"></i> Date d'embauche</th>
                    <td><?php echo date('d/m/Y', strtotime($employe['date_embauche'])); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-toggle-on"></i> Statut</th>
                    <td>
                        <span class="badge <?php echo $employe['statut'] == 'actif' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $employe['statut'] == 'actif' ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </td>
                </tr>
                <?php if ($employe['qr_code']): ?>
                <tr>
                    <th><i class="fas fa-qrcode"></i> QR Code</th>
                    <td>
                        <img src="../assets/images/qrcodes/<?php echo $employe['qr_code']; ?>" width="80" alt="QR Code">
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            
            <!-- Statistiques rapides -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3 id="nbJours">--</h3>
                    <p>Jours travaillés ce mois</p>
                </div>
                <div class="stat-card">
                    <h3 id="totalHeures">--</h3>
                    <p>Total heures ce mois</p>
                </div>
                <div class="stat-card">
                    <h3 id="moyenne">--</h3>
                    <p>Moyenne / jour</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Charger les statistiques via AJAX
fetch('../api/stats_employe.php')
    .then(response => response.json())
    .then(data => {
        document.getElementById('nbJours').innerText = data.nb_jours || 0;
        document.getElementById('totalHeures').innerText = data.total_heures || '0h';
        document.getElementById('moyenne').innerText = data.moyenne || '0h';
    })
    .catch(error => console.error('Erreur:', error));
</script>

<?php include '../includes/footer.php'; ?>