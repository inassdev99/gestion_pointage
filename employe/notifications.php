<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'employe') {
    rediriger('../login.php');
}

$titre = "🔔 Mes notifications";

// Marquer une notification comme lue
if (isset($_GET['marquer_lu'])) {
    $id = $_GET['marquer_lu'];
    $pdo->prepare("UPDATE notification SET est_lu = 1 WHERE id = ? AND employe_id = ?")->execute([$id, $user_id]);
    header("Location: notifications.php");
    exit();
}

// Marquer toutes comme lues
if (isset($_GET['marquer_tout_lu'])) {
    $pdo->prepare("UPDATE notification SET est_lu = 1 WHERE employe_id = ? AND est_lu = 0")->execute([$user_id]);
    header("Location: notifications.php");
    exit();
}

// Supprimer une notification
if (isset($_GET['supprimer'])) {
    $id = $_GET['supprimer'];
    $pdo->prepare("DELETE FROM notification WHERE id = ? AND employe_id = ?")->execute([$id, $user_id]);
    header("Location: notifications.php");
    exit();
}

// Récupérer les notifications
$notifications = $pdo->prepare("
    SELECT * FROM notification 
    WHERE employe_id = ? 
    ORDER BY created_at DESC
");
$notifications->execute([$user_id]);
$notifications = $notifications->fetchAll();

// Compter les non lues
$stmt = $pdo->prepare("SELECT COUNT(*) as nb FROM notification WHERE employe_id = ? AND est_lu = 0");
$stmt->execute([$user_id]);
$nb_non_lues = $stmt->fetch()['nb'];

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
    --red-bg:      #fef2f2;
    --red-text:    #b91c1c;
    --yellow-bg:   #fffbeb;
    --yellow-text: #b45309;
    --radius:      12px;
    --shadow:      0 2px 12px rgba(30,45,77,0.07);
}

body {
    background-color: var(--bg) !important;
    font-family: 'Inter', system-ui, sans-serif;
}

.container {
    max-width: 900px;
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
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
    background: #fef3c7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d97706;
    font-size: 0.65rem;
    flex-shrink: 0;
}

/* ══ STATS ══ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}
.stat-card {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 15px 20px;
    text-align: center;
    border: 1px solid var(--border);
    transition: all 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
}
.stat-card .number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--blue);
}
.stat-card .number.orange { color: #f59e0b; }
.stat-card .label {
    font-size: 0.75rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ══ ACTIONS BAR ══ */
.actions-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
}
.btn-actions {
    padding: 8px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-actions:hover {
    transform: translateY(-1px);
}
.btn-mark-all {
    background: var(--blue);
    color: white;
}
.btn-mark-all:hover {
    background: #1d4ed8;
    color: white;
}
.btn-refresh {
    background: var(--bg);
    color: var(--text);
    border: 1px solid var(--border);
}
.btn-refresh:hover {
    background: var(--border);
}

/* ══ NOTIFICATION CARD ══ */
.notif-card {
    background: var(--card);
    border-radius: var(--radius);
    padding: 18px 22px;
    margin-bottom: 12px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    transition: all 0.2s;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}
.notif-card:hover {
    transform: scale(1.005);
}
.notif-card.non_lu {
    border-left: 4px solid var(--blue);
    background: #fafcff;
}
.notif-card.lu {
    opacity: 0.7;
}
.notif-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}
.notif-icon.default { background: #dbeafe; color: #2563eb; }

.notif-content {
    flex: 1;
}
.notif-content .notif-message {
    color: var(--text);
    margin: 0 0 6px 0;
    font-size: 0.95rem;
    line-height: 1.5;
}
.notif-content .notif-date {
    color: var(--muted);
    font-size: 0.7rem;
}
.notif-content .notif-date i {
    margin-right: 4px;
}

.notif-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}
.btn-notif {
    padding: 5px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.7rem;
    font-weight: 600;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}
.btn-notif:hover {
    transform: scale(1.05);
}
.btn-notif-lu { background: var(--blue-light); color: var(--blue); }
.btn-notif-lu:hover { background: #bfdbfe; }
.btn-notif-supp { background: var(--red-bg); color: var(--red-text); }
.btn-notif-supp:hover { background: #fecaca; }

/* ══ EMPTY STATE ══ */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
}
.empty-state i {
    font-size: 3rem;
    color: var(--muted);
    margin-bottom: 15px;
    opacity: 0.5;
}
.empty-state h3 {
    margin-bottom: 10px;
    color: var(--text);
}
.empty-state p {
    color: var(--muted);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header .header-content h1 { font-size: 1.3rem; }
    .container { padding: 0 15px; }
    .notif-card { flex-direction: column; align-items: stretch; }
    .notif-actions { justify-content: flex-end; }
}
</style>

<!-- PAGE HEADER AVEC LOGO -->
<div class="page-header">
    <div class="header-icon">
        <i class="fas fa-bell"></i>
    </div>
    <div class="header-content">
        <h1>Mes notifications</h1>
        <p class="subtitle">
            <span class="sub-icon">
                <i class="fas fa-bell"></i>
            </span>
            Consultez vos notifications
        </p>
    </div>
</div>

<!-- STATISTIQUES -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="number <?php echo $nb_non_lues > 0 ? 'orange' : ''; ?>"><?php echo $nb_non_lues; ?></div>
        <div class="label"><i class="fas fa-circle" style="color: var(--blue); font-size: 0.5rem;"></i> Non lues</div>
    </div>
    <div class="stat-card">
        <div class="number"><?php echo count($notifications); ?></div>
        <div class="label"><i class="fas fa-list"></i> Total</div>
    </div>
</div>

<!-- ACTIONS -->
<div class="actions-bar">
    <?php if ($nb_non_lues > 0): ?>
        <a href="?marquer_tout_lu" class="btn-actions btn-mark-all" onclick="return confirm('Marquer toutes les notifications comme lues ?')">
            <i class="fas fa-check-double"></i> Tout marquer comme lu
        </a>
    <?php endif; ?>
    <a href="notifications.php" class="btn-actions btn-refresh">
        <i class="fas fa-sync-alt"></i> Rafraîchir
    </a>
</div>

<!-- LISTE DES NOTIFICATIONS -->
<?php if (count($notifications) > 0): ?>
    <?php foreach ($notifications as $notif): ?>
    <div class="notif-card <?php echo $notif['est_lu'] == 0 ? 'non_lu' : 'lu'; ?>">
        <div class="notif-icon default">
            <i class="fas fa-bell"></i>
        </div>
        <div class="notif-content">
            <p class="notif-message">
                <?php echo htmlspecialchars($notif['message']); ?>
                <?php if ($notif['est_lu'] == 0): ?>
                    <span style="display: inline-block; width: 8px; height: 8px; background: var(--blue); border-radius: 50%; margin-left: 8px;"></span>
                <?php endif; ?>
            </p>
            <p class="notif-date">
                <i class="fas fa-clock"></i> 
                <?php echo date('d/m/Y à H:i', strtotime($notif['created_at'])); ?>
                <?php if ($notif['est_lu'] == 1): ?>
                    <span style="color: var(--green-text);"> • Lu</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="notif-actions">
            <?php if (stripos($notif['message'], 'réclamation') !== false): ?>
                <a href="mes_reclamations.php" class="btn-notif" style="background: #f3f0ff; color: #7c3aed; display: flex; align-items: center; gap: 5px; text-decoration: none;">
                    <i class="fas fa-eye"></i> Voir
                </a>
            <?php elseif (stripos($notif['message'], 'absence') !== false || stripos($notif['message'], 'congé') !== false): ?>
                <a href="mes_absences.php" class="btn-notif" style="background: #eff6ff; color: #2563eb; display: flex; align-items: center; gap: 5px; text-decoration: none;">
                    <i class="fas fa-eye"></i> Voir
                </a>
            <?php elseif (stripos($notif['message'], 'déplacement') !== false): ?>
                <a href="mes_deplacements.php" class="btn-notif" style="background: #f0fdfa; color: #0d9488; display: flex; align-items: center; gap: 5px; text-decoration: none;">
                    <i class="fas fa-eye"></i> Voir
                </a>
            <?php endif; ?>
            <?php if ($notif['est_lu'] == 0): ?>
                <a href="?marquer_lu=<?php echo $notif['id']; ?>" class="btn-notif btn-notif-lu" title="Marquer comme lue">
                    <i class="fas fa-check"></i>
                </a>
            <?php endif; ?>
            <a href="?supprimer=<?php echo $notif['id']; ?>" class="btn-notif btn-notif-supp" onclick="return confirm('Supprimer cette notification ?')" title="Supprimer">
                <i class="fas fa-trash"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-bell-slash"></i>
        <h3>Aucune notification</h3>
        <p>Vous n'avez pas encore de notifications</p>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>