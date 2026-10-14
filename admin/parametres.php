<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$titre = "Paramètres système";
$message = '';

// Mettre à jour les paramètres
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $heure_arrivee = $_POST['heure_arrivee'];
    $heure_depart = $_POST['heure_depart'];
    $seuil_retard = $_POST['seuil_retard'];
    $rayon_gps = $_POST['rayon_gps'];
    
    // Stocker les paramètres dans la session ou un fichier
    $_SESSION['parametres'] = [
        'heure_arrivee' => $heure_arrivee,
        'heure_depart' => $heure_depart,
        'seuil_retard' => $seuil_retard,
        'rayon_gps' => $rayon_gps
    ];
    
    $message = "Paramètres enregistrés avec succès";
}

// Récupérer les paramètres actuels
$params = isset($_SESSION['parametres']) ? $_SESSION['parametres'] : [
    'heure_arrivee' => '08:30',
    'heure_depart' => '17:30',
    'seuil_retard' => '15',
    'rayon_gps' => '100'
];

include '../includes/header.php';
?>

<style>
/* ══ VARIABLES ══ */
:root {
    --blue:        #2563eb;
    --blue-light:  #eff6ff;
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
.card-body { padding: 20px; }
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
.btn-submit { 
    background: var(--blue); 
    color: white; 
    padding: 12px 25px; 
    border: none; 
    border-radius: 10px; 
    cursor: pointer; 
    font-weight: 700;
}
.alert-success { 
    background: #d1fae5; 
    color: #065f46; 
    padding: 12px; 
    border-radius: 10px; 
    margin-bottom: 20px; 
    font-size: 0.85rem; 
}
</style>

<h1>Paramètres système</h1>

<?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-cog"></i> Configuration générale</div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group"><label>Heure d'arrivée par défaut</label><input type="time" name="heure_arrivee" class="form-control" value="<?php echo $params['heure_arrivee']; ?>" required></div>
            <div class="form-group"><label>Heure de départ par défaut</label><input type="time" name="heure_depart" class="form-control" value="<?php echo $params['heure_depart']; ?>" required></div>
            <div class="form-group"><label>Seuil de retard (minutes)</label><input type="number" name="seuil_retard" class="form-control" value="<?php echo $params['seuil_retard']; ?>" required></div>
            <div class="form-group"><label>Rayon GPS par défaut (mètres)</label><input type="number" name="rayon_gps" class="form-control" value="<?php echo $params['rayon_gps']; ?>" required></div>
            <button type="submit" class="btn-submit">Enregistrer</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>