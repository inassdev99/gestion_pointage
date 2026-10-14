<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'employe') {
    rediriger('../login.php');
}

$titre = "📅 Mes absences & congés";

// Récupérer les absences et congés de l'employé
$stmt = $pdo->prepare("SELECT * FROM absence WHERE employe_id = ? ORDER BY date_debut DESC");
$stmt->execute([$user_id]);
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
    --radius:      12px;
    --shadow:      0 2px 12px rgba(30,45,77,0.07);
}

body { background-color: var(--bg) !important; }
.container-fluid { max-width: 1100px; margin: 0 auto; padding: 20px; }

.page-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
}

.header-icon {
    width: 45px; height: 45px;
    background: var(--blue);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1.2rem;
}

.card {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    margin-bottom: 25px;
    border-top: 4px solid var(--blue);
}

.card-header { background: #fff; font-weight: 700; padding: 15px 20px; border-bottom: 1px solid var(--border); }
.card-body { padding: 20px; }

table { width: 100%; margin-top: 10px; }
th { font-size: 0.75rem; text-transform: uppercase; color: var(--muted); padding: 12px; border-bottom: 1px solid var(--border); }
td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

.badge-status { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
.status-en_attente { background: #fffbeb; color: #b45309; }
.status-validé { background: #f0fdf4; color: #15803d; }
.status-refusé { background: #fef2f2; color: #b91c1c; }

.btn-voir {
    background: var(--blue-light);
    color: var(--blue);
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
}
.btn-voir:hover { background: var(--blue); color: white; }

.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: white; padding: 25px; border-radius: 12px; width: 500px; max-width: 90%; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-icon"><i class="fas fa-calendar-check"></i></div>
        <div>
            <h1 class="h4 mb-0">Mes absences & congés</h1>
            <small class="text-muted">Consultez l'état de vos demandes d'absence et vos congés</small>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Historique des absences</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Période</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($absences)): ?>
                            <tr><td colspan="4" class="text-center p-4 text-muted">Aucune donnée enregistrée</td></tr>
                        <?php endif; ?>
                        <?php foreach ($absences as $a): ?>
                        <tr>
                            <td><strong class="text-dark small"><?= ucfirst($a['type_absence']) ?></strong></td>
                            <td class="text-muted small">Du <?= date('d/m/Y', strtotime($a['date_debut'])) ?> au <?= date('d/m/Y', strtotime($a['date_fin'])) ?></td>
                            <td>
                                <span class="badge-status status-<?= $a['statut'] ?>">
                                    <?= $a['statut'] == 'en_attente' ? '⏳ En attente' : ($a['statut'] == 'validé' ? '✅ Validé' : '❌ Refusé') ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-voir" onclick='voirDetails(<?= json_encode($a) ?>)'><i class="fas fa-eye"></i> Voir</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="modalDetails" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="h5 mb-0">Détails de l'absence</h3>
            <button onclick="document.getElementById('modalDetails').style.display='none'" style="background:none; border:none; font-size:1.5rem;">&times;</button>
        </div>
        <div id="modalBody" class="small"></div>
    </div>
</div>

<script>
function voirDetails(data) {
    const body = document.getElementById('modalBody');
    body.innerHTML = `
        <p><strong>Type :</strong> ${data.type_absence.toUpperCase()}</p>
        <p><strong>Période :</strong> Du ${data.date_debut} au ${data.date_fin}</p>
        <p><strong>Statut :</strong> ${data.statut}</p>
        <hr>
        <p><strong>Motif :</strong></p>
        <div style="background:#f8fafc; padding:10px; border-radius:8px; border:1px solid #e5e9f2;">${data.motif || 'Aucun motif renseigné'}</div>
    `;
    document.getElementById('modalDetails').style.display = 'flex';
}
</script>

<?php include '../includes/footer.php'; ?>