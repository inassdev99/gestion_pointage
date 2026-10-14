<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$titre = "📍 Zones GPS autorisées";
$message = '';
$erreur = '';

// Ajouter une zone
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $nom = trim($_POST['nom']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $rayon = $_POST['rayon'];
    
    if (empty($nom) || empty($latitude) || empty($longitude)) {
        $erreur = "Tous les champs marqués d'une * sont obligatoires.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO zone_gps (nom, latitude, longitude, rayon) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nom, $latitude, $longitude, $rayon])) {
            $message = "✅ Zone GPS ajoutée avec succès !";
        } else {
            $erreur = "❌ Erreur lors de l'ajout de la zone.";
        }
    }
}

// Supprimer une zone
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM zone_gps WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "✅ Zone GPS supprimée avec succès.";
    }
}

// Récupérer toutes les zones
$zones = $pdo->query("SELECT * FROM zone_gps ORDER BY nom ASC")->fetchAll();

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
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --purple:      #8b5cf6;
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
    background: linear-gradient(135deg, var(--purple) 0%, #7c3aed 100%);
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
    color: var(--purple);
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
    border-color: var(--purple); 
    background: #fff;
    box-shadow: 0 0 0 3px rgba(139,92,246,0.08);
}
.row { display: flex; gap: 15px; flex-wrap: wrap; }
.row .form-group { flex: 1; min-width: 150px; }

/* ══ BOUTONS ══ */
.btn-submit { 
    background: var(--purple); 
    color: white; 
    padding: 10px 24px; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-submit:hover {
    background: #7c3aed;
    transform: translateY(-1px);
}
.btn-gps { 
    background: var(--blue); 
    color: white; 
    border: none; 
    padding: 8px 16px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-size: 0.8rem; 
    font-weight: 600; 
    transition: all 0.2s;
}
.btn-gps:hover { 
    background: #1d4ed8; 
    transform: translateY(-1px);
}
.btn-danger { 
    padding: 6px 12px; 
    border-radius: 8px; 
    text-decoration: none; 
    display: inline-block; 
    background: var(--red-bg); 
    color: var(--red-text); 
    font-size: 0.75rem; 
    font-weight: 600;
    transition: all 0.2s;
}
.btn-danger:hover { background: #fecaca; }

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

/* ══ ZONE BADGE ══ */
.zone-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    background: var(--purple-light);
    color: var(--purple);
}

/* Responsive */
@media (max-width: 768px) {
    .row { flex-direction: column; gap: 0; }
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-map-marker-alt"></i>
    </div>
    <div class="header-content">
        <h1>Zones GPS autorisées</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-shield-alt"></i>
            </span>
            Définissez les emplacements géographiques où le pointage est autorisé
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?></div>
<?php endif; ?>

<!-- CARD : AJOUTER UNE ZONE -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-plus-circle"></i> Ajouter une nouvelle zone
        <span class="badge-count"><?php echo count($zones); ?> zone(s)</span>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Nom de la zone *</label>
                        <input type="text" name="nom" class="form-control" placeholder="ex: Siège Social" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-arrow-up"></i> Latitude *</label>
                        <input type="text" name="latitude" id="lat" class="form-control" placeholder="ex: 33.5731" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-arrow-right"></i> Longitude *</label>
                        <input type="text" name="longitude" id="lng" class="form-control" placeholder="ex: -7.5898" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><i class="fas fa-expand"></i> Rayon (mètres) *</label>
                        <input type="number" name="rayon" class="form-control" value="100" required>
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 5px;">
                <button type="button" class="btn-gps" onclick="getLocation()">
                    <i class="fas fa-crosshairs"></i> Ma position actuelle
                </button>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Enregistrer la zone
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CARD : LISTE DES ZONES -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Liste des zones enregistrées
        <span class="badge-count"><?php echo count($zones); ?> zone(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto; padding:0;">
        <?php if (count($zones) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Coordonnées</th>
                    <th>Rayon</th>
                    <th>Statut</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zones as $z): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($z['nom']); ?></strong></td>
                    <td>
                        <span class="zone-badge">
                            <i class="fas fa-map-pin"></i>
                            <?php echo $z['latitude'] . ', ' . $z['longitude']; ?>
                        </span>
                    </td>
                    <td><?php echo $z['rayon']; ?> m</td>
                    <td>
                        <span class="zone-badge" style="background: #d1fae5; color: #15803d;">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Actif
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <a href="zones_gps.php?delete=<?php echo $z['id']; ?>" class="btn-danger" onclick="return confirm('⚠️ Supprimer cette zone ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-map-marker-alt"></i>
            <p>Aucune zone définie</p>
            <small>Ajoutez votre première zone ci-dessus</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('lat').value = position.coords.latitude;
            document.getElementById('lng').value = position.coords.longitude;
            // Animation feedback
            const latField = document.getElementById('lat');
            const lngField = document.getElementById('lng');
            latField.style.borderColor = '#10b981';
            lngField.style.borderColor = '#10b981';
            setTimeout(() => {
                latField.style.borderColor = '';
                lngField.style.borderColor = '';
            }, 2000);
        }, function(error) {
            alert("Erreur lors de la récupération de la position : " + error.message);
        });
    } else {
        alert("La géolocalisation n'est pas supportée par ce navigateur.");
    }
}
</script>

<?php include '../includes/footer.php'; ?>