<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'employe') {
    rediriger('../login.php');
}

$titre = "📝 Mes réclamations";

// Récupérer les réclamations de l'employé
$stmt = $pdo->prepare("SELECT * FROM reclamation WHERE employe_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$reclamations = $stmt->fetchAll();

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

.form-control { border-radius: 8px; border: 1.5px solid var(--border); padding: 10px; }
.form-control:focus { border-color: var(--blue); box-shadow: none; }

table { width: 100%; margin-top: 10px; }
th { font-size: 0.75rem; text-transform: uppercase; color: var(--muted); padding: 12px; border-bottom: 1px solid var(--border); }
td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }

.btn-voir {
    background: var(--blue-light);
    color: var(--blue);
    border: none;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
}
.btn-voir:hover {
    background: var(--blue);
    color: white;
}

.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: white; padding: 25px; border-radius: 12px; width: 500px; max-width: 90%; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
.modal-body { font-size: 0.9rem; color: var(--text); line-height: 1.6; }

.badge-status { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
.status-en_attente { background: #fffbeb; color: #b45309; }
.status-traité { background: #f0fdf4; color: #15803d; }

.reponse-admin {
    background: #f8fafc;
    border-left: 3px solid var(--blue);
    padding: 10px;
    margin-top: 10px;
    border-radius: 0 8px 8px 0;
    font-size: 0.85rem;
}

.reponse-box {
    background: #f0fdf4;
    border-left: 4px solid #15803d;
    padding: 15px;
    margin-top: 15px;
    border-radius: 4px;
}
.reponse-box h4 { margin: 0 0 8px 0; font-size: 0.9rem; color: #15803d; }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-icon"><i class="fas fa-comment-dots"></i></div>
        <div>
            <h1 class="h4 mb-0">Mes réclamations</h1>
            <small class="text-muted">Gérez vos demandes et consultez les réponses de l'administration</small>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Historique de mes demandes</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Détails & Réponses</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reclamations)): ?>
                            <tr><td colspan="5" class="text-center p-4 text-muted">Aucune réclamation pour le moment</td></tr>
                        <?php endif; ?>
                        <?php foreach ($reclamations as $r): ?>
                        <tr>
                            <td class="text-muted small"><?= date('d/m/Y', strtotime($r['created_at'] ?? 'now')) ?></td>
                            <td><strong class="text-dark small"><?= str_replace('_', ' ', ucfirst($r['type_reclamation'])) ?></strong></td>
                            <td>
                                <div class="text-muted small"><?= htmlspecialchars($r['motif']) ?></div>
                            </td>
                            <td>
                                <span class="badge-status status-<?= $r['statut'] ?>">
                                    <?= $r['statut'] == 'en_attente' ? '⏳ En attente' : '✅ Traitée' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-voir" onclick='voirReclamation(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>)'>
                                    <i class="fas fa-eye"></i> Voir
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>