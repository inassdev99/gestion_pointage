<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$titre = "📅 Tous les pointages";
$message = '';
$erreur = '';
$date = $_GET['date'] ?? date('Y-m-d');

// Handle form submission for creating actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_type'])) {
    $employe_id_form = $_POST['employe_id_form'];
    $action_type = $_POST['action_type'];
    $notification_message = '';

    switch ($action_type) {
        case 'deplacement':
            $date_debut = $_POST['date_debut_deplacement'];
            $date_fin = $_POST['date_fin_deplacement'];
            $motif = $_POST['motif_deplacement'];
            $statut = $_POST['statut_deplacement'] ?? 'validé';

            if ($date_debut > $date_fin) {
                $erreur = "❌ La date de début doit être antérieure à la date de fin pour le déplacement.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut, valide_par) VALUES (?, 'déplacement', ?, ?, ?, ?, ?)");
                if ($stmt->execute([$employe_id_form, $date_debut, $date_fin, $motif, $statut, $_SESSION['user_id']])) {
                    $notification_message = "Un déplacement professionnel a été enregistré pour vous (Statut: $statut).";
                    $message = "✅ Déplacement ajouté avec succès.";
                } else {
                    $erreur = "❌ Erreur lors de l'ajout du déplacement.";
                }
            }
            break;
        case 'conges':
            $date_debut = $_POST['date_debut_conges'];
            $date_fin = $_POST['date_fin_conges'];
            $motif = $_POST['motif_conges'];
            $statut = $_POST['statut_conges'] ?? 'validé';

            if ($date_debut > $date_fin) {
                $erreur = "❌ La date de début doit être antérieure à la date de fin pour le congé.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut, valide_par) VALUES (?, 'congé', ?, ?, ?, ?, ?)");
                if ($stmt->execute([$employe_id_form, $date_debut, $date_fin, $motif, $statut, $_SESSION['user_id']])) {
                    $notification_message = "Un congé a été enregistré pour vous (Statut: $statut).";
                    $message = "✅ Congé ajouté avec succès.";
                } else {
                    $erreur = "❌ Erreur lors de l'ajout du congé.";
                }
            }
            break;
        case 'reclamation':
            $type_reclamation = $_POST['type_reclamation_form'];
            $motif = $_POST['motif_reclamation'];
            $statut = $_POST['statut_reclamation'] ?? 'traité';

            $stmt = $pdo->prepare("INSERT INTO reclamation (employe_id, type_reclamation, motif, statut, traite_par) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$employe_id_form, $type_reclamation, $motif, $statut, $_SESSION['user_id']])) {
                $notification_message = "Une réclamation a été créée pour vous par l'admin (Statut: $statut).";
                $message = "✅ Réclamation ajoutée avec succès.";
            } else {
                $erreur = "❌ Erreur lors de l'ajout de la réclamation.";
            }
            break;
        case 'absence':
            $date_debut = $_POST['date_debut_absence'];
            $date_fin = $_POST['date_fin_absence'];
            $motif = $_POST['motif_absence'];
            $statut = $_POST['statut_absence'] ?? 'validé';

            if ($date_debut > $date_fin) {
                $erreur = "❌ La date de début doit être antérieure à la date de fin pour l'absence.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO absence (employe_id, type_absence, date_debut, date_fin, motif, statut, valide_par) VALUES (?, 'absence', ?, ?, ?, ?, ?)");
                if ($stmt->execute([$employe_id_form, $date_debut, $date_fin, $motif, $statut, $_SESSION['user_id']])) {
                    $notification_message = "Une absence a été enregistrée pour vous (Statut: $statut).";
                    $message = "✅ Absence ajoutée avec succès.";
                } else {
                    $erreur = "❌ Erreur lors de l'ajout de l'absence.";
                }
            }
            break;
    }

    if ($notification_message && !$erreur) {
        creerNotification($pdo, $employe_id_form, $notification_message);
    }
    header("Location: pointages.php?date=" . ($_GET['date'] ?? date('Y-m-d')));
    exit();
}

// ═══════════════════════════════════════════════════════════
// ═══ REQUÊTE POUR INCLURE LES ABSENCES ═══
// ═══════════════════════════════════════════════════════════
$stmt = $pdo->prepare("
    SELECT 
        e.id as employe_id,
        e.nom, 
        e.prenom, 
        e.matricule,
        p.heure_arrivee,
        p.heure_pause_debut,
        p.heure_pause_fin,
        p.heure_depart,
        p.statut as pointage_statut,
        -- Absences du jour
        a.id as absence_id,
        a.type_absence,
        a.date_debut,
        a.date_fin,
        a.statut as absence_statut,
        a.motif as absence_motif
    FROM employe e
    LEFT JOIN pointage p ON e.id = p.employe_id AND p.date_pointage = ?
    LEFT JOIN absence a ON e.id = a.employe_id 
        AND ? BETWEEN a.date_debut AND a.date_fin
        AND a.statut = 'validé'
    WHERE e.statut = 'actif'
    ORDER BY e.nom ASC
");
$stmt->execute([$date, $date]);
$employes_data = $stmt->fetchAll();

// Récupérer les employés pour le formulaire
$employes = $pdo->query("SELECT id, nom, prenom, matricule FROM employe WHERE statut = 'actif' ORDER BY nom")->fetchAll();

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
    --yellow-bg:   #fffbeb;
    --yellow-text: #b45309;
    --orange-bg:   #fff7ed;
    --orange-text: #c2410c;
    --teal-bg:     #ccfbf1;
    --teal-text:   #0d9488;
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
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

/* ══ ALERTS ══ */
.alert { 
    padding: 12px 16px; 
    border-radius: 10px; 
    margin-bottom: 20px; 
    font-size: 0.85rem;
    border-left: 4px solid;
}
.alert-success { 
    background: #d1fae5; 
    color: #065f46; 
    border-color: #10b981;
}
.alert-danger { 
    background: #fee2e2; 
    color: #991b1b; 
    border-color: #ef4444;
}
.alert i {
    margin-right: 8px;
}

/* ══ FILTER FORM ══ */
.filter-form { 
    display: flex; 
    gap: 12px; 
    margin-bottom: 24px; 
    align-items: center; 
    background: var(--card); 
    padding: 15px 20px; 
    border-radius: var(--radius); 
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}
.filter-form input { 
    padding: 8px 12px; 
    border-radius: 8px; 
    border: 1.5px solid var(--border); 
    background: #fafbfe;
    font-family: inherit;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.filter-form input:focus { 
    border-color: var(--blue); 
    outline: none;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
}
.filter-form button { 
    background: var(--blue); 
    color: white; 
    border: none; 
    padding: 8px 20px; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s;
}
.filter-form button:hover { 
    background: #1d4ed8; 
    transform: translateY(-1px);
}
.filter-form .btn-ajd {
    color: var(--blue);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 8px 14px;
    border-radius: 8px;
    transition: all 0.2s;
}
.filter-form .btn-ajd:hover {
    background: var(--blue-light);
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
.card-header i { color: var(--blue); }
.card-header .badge-count {
    margin-left: auto;
    background: var(--blue-light);
    color: var(--blue);
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
}
.card-body { padding: 0; }

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
.badge-success::before { background: var(--green-text); }
.badge-warning::before { background: var(--yellow-text); }
.badge-success { background: var(--green-bg); color: var(--green-text); }
.badge-warning { background: var(--yellow-bg); color: var(--yellow-text); }

/* Badges pour absence/déplacement/congé */
.badge-absence { background: var(--orange-bg); color: var(--orange-text); }
.badge-absence::before { background: var(--orange-text); }
.badge-deplacement { background: var(--teal-bg); color: var(--teal-text); }
.badge-deplacement::before { background: var(--teal-text); }
.badge-conge { background: #fef3c7; color: #b45309; }
.badge-conge::before { background: #b45309; }
.badge-retard { background: var(--yellow-bg); color: var(--yellow-text); }
.badge-retard::before { background: var(--yellow-text); }
.badge-present { background: var(--green-bg); color: var(--green-text); }
.badge-present::before { background: var(--green-text); }
.badge-absent { background: var(--red-bg); color: var(--red-text); }
.badge-absent::before { background: var(--red-text); }

.dash { color: #cbd5e1; }

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

/* ══ ACTION BUTTON ══ */
.btn-action { 
    background: var(--blue); 
    color: white; 
    border: none; 
    padding: 6px 14px; 
    border-radius: 6px; 
    cursor: pointer; 
    font-size: 0.75rem; 
    font-weight: 600;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-action:hover { 
    background: #1d4ed8; 
    transform: scale(1.05);
}

/* ══ MODAL ══ */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.modal-content {
    background: white;
    border-radius: 16px;
    padding: 30px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}
.modal-content h2 {
    font-size: 1.4rem;
    margin-bottom: 20px;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 10px;
}
.modal-content h2 i {
    color: var(--blue);
}
.modal-content .close-button {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 1.8rem;
    cursor: pointer;
    color: var(--muted);
    transition: all 0.2s;
    background: none;
    border: none;
}
.modal-content .close-button:hover {
    color: var(--red-text);
    transform: rotate(90deg);
}
.form-label {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--text);
    display: block;
    margin-bottom: 6px;
}
.form-control {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1.5px solid var(--border);
    background: #fafbfe;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.form-control:focus {
    border-color: var(--blue);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
select.form-control {
    appearance: auto;
}
textarea.form-control {
    resize: vertical;
    min-height: 60px;
}
.mb-3 {
    margin-bottom: 15px;
}
.mt-3 {
    margin-top: 15px;
}
.form-section {
    display: none;
    padding: 15px;
    background: var(--bg);
    border-radius: 10px;
    margin-top: 15px;
    border: 1px solid var(--border);
}
.form-section h4 {
    font-size: 1rem;
    color: var(--text);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.form-section h4 i {
    color: var(--blue);
}
.btn-primary {
    background: var(--blue);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}
.btn-secondary {
    background: #e2e8f0;
    color: var(--text);
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.btn-secondary:hover {
    background: #cbd5e1;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-form { flex-wrap: wrap; }
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
    .modal-content { padding: 20px; }
    .form-section { padding: 10px; }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-fingerprint"></i>
    </div>
    <div class="header-content">
        <h1>Tous les pointages</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-clock"></i>
            </span>
            Consultez les pointages et absences des employés par date
        </p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
<?php endif; ?>
<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?></div>
<?php endif; ?>

<!-- FILTRE -->
<form method="GET" class="filter-form">
    <i class="fas fa-calendar" style="color: var(--muted);"></i>
    <input type="date" name="date" value="<?php echo $date; ?>">
    <button type="submit"><i class="fas fa-filter"></i> Filtrer</button>
    <a href="pointages.php" class="btn-ajd"><i class="fas fa-calendar-day"></i> Aujourd'hui</a>
</form>

<!-- CARD : LISTE DES POINTAGES ET ABSENCES -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> Pointages du <?php echo date('d/m/Y', strtotime($date)); ?>
        <span class="badge-count"><?php echo count($employes_data); ?> employé(s)</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (!empty($employes_data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Employé</th>
                    <th>Matricule</th>
                    <th>Arrivée</th>
                    <th>Pause début</th>
                    <th>Pause fin</th>
                    <th>Départ</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody> 
                <?php foreach ($employes_data as $emp): 
                    // ═══════════════════════════════════════════
                    // DÉTERMINER LE STATUT (UNIQUEMENT UN MOT)
                    // ═══════════════════════════════════════════
                    $status_label = 'Absent';
                    $badge_class = 'badge-absent';
                    
                    if ($emp['absence_id']) {
                        // L'employé a une absence validée
                        switch($emp['type_absence']) {
                            case 'déplacement':
                                $status_label = 'Déplacement';
                                $badge_class = 'badge-deplacement';
                                break;
                            case 'congé':
                                $status_label = 'Congé';
                                $badge_class = 'badge-conge';
                                break;
                            default:
                                $status_label = 'Absent';
                                $badge_class = 'badge-absence';
                        }
                    } elseif ($emp['heure_arrivee']) {
                        // L'employé a pointé
                        if ($emp['pointage_statut'] == 'retard') {
                            $status_label = 'Retard';
                            $badge_class = 'badge-retard';
                        } else {
                            $status_label = 'Présent';
                            $badge_class = 'badge-present';
                        }
                    } else {
                        // L'employé n'a pas pointé → ABSENT
                        $status_label = 'Absent';
                        $badge_class = 'badge-absent';
                    }
                    
                    $heures = $emp['heure_arrivee'] ? calculerHeures($emp['heure_arrivee'], $emp['heure_pause_debut'], $emp['heure_pause_fin'], $emp['heure_depart']) : '-';
                ?>
                <tr>
                    <td><strong><?php echo $emp['prenom'] . ' ' . $emp['nom']; ?></strong></td>
                    <td><?php echo $emp['matricule']; ?></td>
                    <td><?php echo $emp['heure_arrivee'] ? date('H:i', strtotime($emp['heure_arrivee'])) : '<span class="dash">--:--</span>'; ?></td>
                    <td><?php echo $emp['heure_pause_debut'] ? date('H:i', strtotime($emp['heure_pause_debut'])) : '-'; ?></td>
                    <td><?php echo $emp['heure_pause_fin'] ? date('H:i', strtotime($emp['heure_pause_fin'])) : '-'; ?></td>
                    <td><?php echo $emp['heure_depart'] ? date('H:i', strtotime($emp['heure_depart'])) : '-'; ?></td>
                    <td><strong><?php echo $heures; ?></strong></td>
                    <td>
                        <span class="badge <?php echo $badge_class; ?>">
                            <?php echo $status_label; ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <button class="btn-action" onclick="openActionModal(<?php echo $emp['employe_id']; ?>)">
                            <i class="fas fa-plus"></i> Action
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <p>Aucun pointage trouvé pour cette date</p>
            <small>Aucun employé n'a pointé le <?php echo date('d/m/Y', strtotime($date)); ?></small>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ACTION MODAL -->
<div id="actionModal" class="modal">
    <div class="modal-content">
        <button class="close-button" onclick="closeActionModal()">&times;</button>
        <h2><i class="fas fa-plus-circle"></i> Créer une nouvelle action</h2>
        
        <form method="POST">
            <div class="mb-3">
                <label for="employe_id_form" class="form-label"><i class="fas fa-user"></i> Employé</label>
                <select name="employe_id_form" id="employe_id_form" class="form-control" required>
                    <option value="">-- Sélectionner un employé --</option>
                    <?php foreach ($employes as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['prenom'] . ' ' . $emp['nom'] . ' (' . $emp['matricule'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="action_type" class="form-label"><i class="fas fa-tag"></i> Type d'action</label>
                <select name="action_type" id="action_type" class="form-control" onchange="showFormSection()" required>
                    <option value="">-- Sélectionner le type --</option>
                    <option value="deplacement">🚗 Déplacement</option>
                    <option value="conges">🏖️ Congé</option>
                    <option value="reclamation">💬 Réclamation</option>
                    <option value="absence">📋 Absence</option>
                </select>
            </div>

            <!-- Déplacement Form Section -->
            <div id="deplacement_form_section" class="form-section">
                <h4><i class="fas fa-plane"></i> Détails du déplacement</h4>
                <div class="mb-3">
                    <label for="date_debut_deplacement" class="form-label">Date début</label>
                    <input type="date" name="date_debut_deplacement" id="date_debut_deplacement" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="date_fin_deplacement" class="form-label">Date fin</label>
                    <input type="date" name="date_fin_deplacement" id="date_fin_deplacement" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="motif_deplacement" class="form-label">Motif / Destination</label>
                    <textarea name="motif_deplacement" id="motif_deplacement" class="form-control" placeholder="ex: Client Casablanca"></textarea>
                </div>
                <div class="mb-3">
                    <label for="statut_deplacement" class="form-label">Statut</label>
                    <select name="statut_deplacement" id="statut_deplacement" class="form-control">
                        <option value="validé">✅ Validé</option>
                        <option value="en_attente">⏳ En attente</option>
                        <option value="refusé">❌ Refusé</option>
                    </select>
                </div>
            </div>

            <!-- Congés Form Section -->
            <div id="conges_form_section" class="form-section">
                <h4><i class="fas fa-umbrella-beach"></i> Détails du congé</h4>
                <div class="mb-3">
                    <label for="date_debut_conges" class="form-label">Date début</label>
                    <input type="date" name="date_debut_conges" id="date_debut_conges" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="date_fin_conges" class="form-label">Date fin</label>
                    <input type="date" name="date_fin_conges" id="date_fin_conges" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="motif_conges" class="form-label">Motif</label>
                    <textarea name="motif_conges" id="motif_conges" class="form-control" placeholder="Motif du congé"></textarea>
                </div>
                <div class="mb-3">
                    <label for="statut_conges" class="form-label">Statut</label>
                    <select name="statut_conges" id="statut_conges" class="form-control">
                        <option value="validé">✅ Validé</option>
                        <option value="en_attente">⏳ En attente</option>
                        <option value="refusé">❌ Refusé</option>
                    </select>
                </div>
            </div>

            <!-- Réclamation Form Section -->
            <div id="reclamation_form_section" class="form-section">
                <h4><i class="fas fa-comment-dots"></i> Détails de la réclamation</h4>
                <div class="mb-3">
                    <label for="type_reclamation_form" class="form-label">Type de réclamation</label>
                    <select name="type_reclamation_form" id="type_reclamation_form" class="form-control">
                        <option value="pointage_oublie">⏰ Pointage oublié</option>
                        <option value="heure_incorrecte">🕐 Heure incorrecte</option>
                        <option value="absence_non_justifiee">📋 Absence non justifiée</option>
                        <option value="autre">📝 Autre motif</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="motif_reclamation" class="form-label">Motif</label>
                    <textarea name="motif_reclamation" id="motif_reclamation" class="form-control" placeholder="Description de la réclamation"></textarea>
                </div>
                <div class="mb-3">
                    <label for="statut_reclamation" class="form-label">Statut</label>
                    <select name="statut_reclamation" id="statut_reclamation" class="form-control">
                        <option value="traité">✅ Traité</option>
                        <option value="en_attente">⏳ En attente</option>
                    </select>
                </div>
            </div>

            <!-- Absence Form Section -->
            <div id="absence_form_section" class="form-section">
                <h4><i class="fas fa-calendar-times"></i> Détails de l'absence</h4>
                <div class="mb-3">
                    <label for="date_debut_absence" class="form-label">Date début</label>
                    <input type="date" name="date_debut_absence" id="date_debut_absence" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="date_fin_absence" class="form-label">Date fin</label>
                    <input type="date" name="date_fin_absence" id="date_fin_absence" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="motif_absence" class="form-label">Motif</label>
                    <textarea name="motif_absence" id="motif_absence" class="form-control" placeholder="Motif de l'absence"></textarea>
                </div>
                <div class="mb-3">
                    <label for="statut_absence" class="form-label">Statut</label>
                    <select name="statut_absence" id="statut_absence" class="form-control">
                        <option value="validé">✅ Validé</option>
                        <option value="en_attente">⏳ En attente</option>
                        <option value="refusé">❌ Refusé</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <button type="button" class="btn-secondary" onclick="closeActionModal()"><i class="fas fa-times"></i> Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openActionModal(employeId = null) {
        const modal = document.getElementById('actionModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (employeId) {
            document.getElementById('employe_id_form').value = employeId;
        }
        document.getElementById('action_type').value = '';
        showFormSection();
    }

    function closeActionModal() {
        const modal = document.getElementById('actionModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function showFormSection() {
        const actionType = document.getElementById('action_type').value;
        const formSections = document.querySelectorAll('.form-section');
        formSections.forEach(section => {
            section.style.display = 'none';
            section.querySelectorAll('input, textarea, select').forEach(input => {
                input.disabled = true;
            });
        });

        if (actionType) {
            const selectedSection = document.getElementById(actionType + '_form_section');
            if (selectedSection) {
                selectedSection.style.display = 'block';
                selectedSection.querySelectorAll('input, textarea, select').forEach(input => {
                    input.disabled = false;
                });
            }
        }
    }

    window.onclick = function(event) {
        const modal = document.getElementById('actionModal');
        if (event.target == modal) {
            closeActionModal();
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeActionModal();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>