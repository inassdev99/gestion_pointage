<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Marquer une alerte comme traitée
if (isset($_GET['marquer_traite'])) {
    $id = $_GET['marquer_traite'];
    $pdo->prepare("UPDATE alerte SET statut = 'traité', traite_par = ? WHERE id = ?")->execute([$_SESSION['user_id'], $id]);
    header("Location: alertes_fraude.php");
    exit();
}

// Supprimer une alerte
if (isset($_GET['supprimer'])) {
    $id = $_GET['supprimer'];
    $pdo->prepare("DELETE FROM alerte WHERE id = ?")->execute([$id]);
    header("Location: alertes_fraude.php");
    exit();
}

// Récupérer les alertes avec les infos employé via pointage
$statut_filtre = isset($_GET['statut']) ? $_GET['statut'] : 'en_attente';
$where = "";
if ($statut_filtre != 'all') {
    $where = "WHERE a.statut = '$statut_filtre'";
}

// Requête adaptée - Utilisation de employe pour l'utilisateur qui a généré l'alerte
$stmt = $pdo->prepare("
    SELECT a.*, 
           p.employe_id,
           p.date_pointage,
           p.latitude,
           p.longitude,
           COALESCE(e.nom, e2.nom, e3.nom) as nom, 
           COALESCE(e.prenom, e2.prenom, e3.prenom) as prenom, 
           COALESCE(e.matricule, e2.matricule, e3.matricule) as matricule,
           et.nom as traite_nom, et.prenom as traite_prenom,
           COALESCE(u.login, u2.login) as user_login,
           a.date_creation
    FROM alerte a
    LEFT JOIN pointage p ON a.pointage_id = p.id
    LEFT JOIN employe e ON p.employe_id = e.id  /* Employé via pointage réussi */
    
    /* Cas 1: user_id dans alerte est l'ID de l'employé */
    LEFT JOIN employe e2 ON a.user_id = e2.id
    LEFT JOIN utilisateur u ON e2.id = u.employe_id
    
    /* Cas 2: user_id dans alerte est l'ID de l'utilisateur (compte) */
    LEFT JOIN utilisateur u2 ON a.user_id = u2.id
    LEFT JOIN employe e3 ON u2.employe_id = e3.id
    
    LEFT JOIN employe et ON a.traite_par = et.id
    $where
    ORDER BY a.id DESC
");
$stmt->execute();
$alertes = $stmt->fetchAll();

// Statistiques
$stats = $pdo->query("
    SELECT 
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'traité' THEN 1 ELSE 0 END) as traite,
        COUNT(*) as total
    FROM alerte
")->fetch();

// Types d'alertes
$types_stats = $pdo->query("
    SELECT type_alerte, COUNT(*) as nb 
    FROM alerte 
    GROUP BY type_alerte
")->fetchAll();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertes de sécurité - Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
            --orange-bg:   #fff7ed;
            --orange-text: #c2410c;
            --radius:      12px;
            --shadow:      0 2px 12px rgba(30,45,77,0.07);
        }

        body { background-color: var(--bg) !important; color: var(--text); font-family: 'Inter', system-ui, sans-serif; }

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
            background: linear-gradient(135deg, var(--red-text) 0%, #991b1b 100%);
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
            background: var(--red-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--red-text);
            font-size: 0.65rem;
            flex-shrink: 0;
        }

        /* ══ STATS GRID ══ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card);
            padding: 18px 20px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon i { font-size: 1.2rem; }
        .si-red { background: var(--red-bg); color: var(--red-text); }
        .si-green { background: var(--green-bg); color: var(--green-text); }
        .si-yellow { background: var(--yellow-bg); color: var(--yellow-text); }

        .stat-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--text);
        }
        .stat-card p {
            font-size: 0.76rem;
            color: var(--muted);
            margin: 0;
        }

        /* ══ FILTERS BAR ══ */
        .filters-bar {
            background: var(--card);
            padding: 12px 20px;
            border-radius: var(--radius);
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            box-shadow: var(--shadow);
            align-items: center;
            border: 1px solid var(--border);
        }
        .filter-btn {
            padding: 8px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            background: var(--card);
            color: var(--muted);
            border: 1px solid var(--border);
            transition: all 0.2s;
            font-family: inherit;
        }
        .filter-btn:hover { background: var(--blue-light); color: var(--blue); }
        .filter-btn.active {
            background: var(--blue);
            color: white;
            border-color: var(--blue);
        }

        /* ══ ALERTE CARD ══ */
        .alerte-card {
            background: var(--card);
            border-radius: var(--radius);
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .alerte-card:hover { transform: scale(1.005); }
        .alerte-card.en_attente {
            border-left: 5px solid var(--red-text);
        }
        .alerte-card.traité {
            border-left: 5px solid var(--green-text);
            opacity: 0.85;
            background: #fcfdfe;
        }

        .alerte-header {
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--border);
        }
        .alerte-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
        }
        .alerte-title i { font-size: 1.3rem; }

        .alerte-badge {
            padding: 6px 15px;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-en_attente { background: var(--red-bg); color: var(--red-text); }
        .badge-traite { background: var(--green-bg); color: var(--green-text); }

        .alerte-body {
            padding: 20px 25px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        @media (max-width: 992px) { .alerte-body { grid-template-columns: 1fr; } }

        .alerte-info p {
            margin: 10px 0;
            font-size: 0.9rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alerte-info strong { color: var(--muted); font-weight: 600; width: 160px; display: inline-block; }
        .alerte-info span.val { color: var(--text); font-weight: 700; }

        .alerte-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.82rem;
            text-align: center;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-success { background: var(--blue); color: white; }
        .btn-success:hover { background: #1d4ed8; transform: translateY(-1px); }
        .btn-danger { background: var(--red-bg); color: var(--red-text); }
        .btn-danger:hover { background: #fee2e2; color: #991b1b; }
        .btn-info { background: #f1f5f9; color: var(--blue); }
        .btn-info:hover { background: #e2e8f0; }

        .type-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .type-badge.gps { background: #e0f2fe; color: #0369a1; }
        .type-badge.ip { background: #fef3c7; color: #92400e; }
        .type-badge.app { background: #fee2e2; color: #991b1b; }
        .type-badge.face { background: #fae8ff; color: #86198f; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }
        .empty-state i {
            font-size: 3rem;
            color: var(--green-text);
            margin-bottom: 15px;
        }
        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--text);
        }
        .empty-state p {
            color: var(--muted);
        }

        .auto-refresh {
            background: var(--card);
            padding: 8px 15px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            border: 1px solid var(--border);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header .header-content h1 { font-size: 1.3rem; }
            .container { padding: 0 15px; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- PAGE HEADER AVEC LOGO -->
    <div class="page-header">
        <div class="header-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="header-content">
            <h1>Alertes de sécurité</h1>
            <p class="subtitle">
                <span class="sub-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </span>
                Surveillez et gérez les alertes de fraude potentielles
            </p>
        </div>
    </div>

    <!-- STATISTIQUES -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon si-red"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['en_attente'] ?? 0; ?></h3>
                <p>En attente</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-green"><i class="fas fa-check-double"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['traite'] ?? 0; ?></h3>
                <p>Traitées</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-yellow"><i class="fas fa-shield-virus"></i></div>
            <div class="stat-info">
                <h3><?php echo $stats['total'] ?? 0; ?></h3>
                <p>Total historisé</p>
            </div>
        </div>
    </div>

    <!-- TYPES D'ALERTES -->
    <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <?php foreach ($types_stats as $type): 
            $type_label = '';
            $icon = '';
            switch($type['type_alerte']) {
                case 'hors_zone': $type_label = 'GPS hors zone'; $icon = '📍'; break;
                case 'ip_invalide': $type_label = 'IP non autorisée'; $icon = '🌐'; break;
                case 'multi_appareil': $type_label = 'Multi-appareil'; $icon = '📱'; break;
                case 'face_non_reconnue': $type_label = 'Face ID échec'; $icon = '👤'; break;
            }
        ?>
            <div style="background: white; padding: 6px 14px; border-radius: 8px; font-size: 0.78rem; border: 1px solid var(--border); color: var(--muted); font-weight: 600;">
                <?php echo $icon; ?> <?php echo $type_label; ?>: <strong><?php echo $type['nb']; ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- FILTRES -->
    <div class="filters-bar">
        <span style="font-size: 0.85rem; font-weight: 700; color: var(--muted); margin-right: 10px;">Filtrer :</span>
        <a href="?statut=en_attente" class="filter-btn <?php echo $statut_filtre == 'en_attente' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> En attente
        </a>
        <a href="?statut=traité" class="filter-btn <?php echo $statut_filtre == 'traité' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Traitées
        </a>
        <a href="?statut=all" class="filter-btn <?php echo $statut_filtre == 'all' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> Toutes
        </a>
    </div>

    <!-- LISTE DES ALERTES -->
    <?php if (count($alertes) == 0): ?>
        <div class="empty-state">
            <i class="fas fa-shield-alt"></i>
            <h3>Aucune alerte à afficher</h3>
            <p>Toutes les vérifications de sécurité sont passées !</p>
        </div>
    <?php endif; ?>

    <?php foreach ($alertes as $alerte): 
        $type_icon = '';
        $type_class = '';
        $type_label = '';
        switch($alerte['type_alerte']) {
            case 'hors_zone': 
                $type_icon = '<i class="fas fa-map-marker-alt"></i>'; 
                $type_class = 'type-badge gps';
                $type_label = 'GPS hors zone autorisée';
                break;
            case 'ip_invalide': 
                $type_icon = '<i class="fas fa-network-wired"></i>'; 
                $type_class = 'type-badge ip';
                $type_label = 'IP non autorisée';
                break;
            case 'multi_appareil': 
                $type_icon = '<i class="fas fa-mobile-alt"></i>'; 
                $type_class = 'type-badge app';
                $type_label = 'Multi-appareil détecté';
                break;
            case 'face_non_reconnue': 
                $type_icon = '<i class="fas fa-user-secret"></i>'; 
                $type_class = 'type-badge face';
                $type_label = 'Reconnaissance faciale échouée';
                break;
        }
    ?>
    <div class="alerte-card <?php echo $alerte['statut']; ?>">
        <div class="alerte-header">
            <div class="alerte-title type-<?php echo $alerte['type_alerte']; ?>">
                <?php echo $type_icon; ?>
                <strong><?php echo $type_label; ?></strong>
                <span class="<?php echo $type_class; ?>"><?php echo $alerte['type_alerte']; ?></span>
            </div>
            <span class="alerte-badge badge-<?php echo $alerte['statut']; ?>">
                <?php echo $alerte['statut'] == 'en_attente' ? '🔴 En attente' : '🟢 Traité'; ?>
            </span>
        </div>
        <div class="alerte-body">
            <div class="alerte-info">
                <?php if ($alerte['type_alerte'] == 'face_non_reconnue'): ?>
                    <!-- Focus sur l'usurpateur identifié -->
                    <p>
                        <i class="fas fa-user-secret" style="color: var(--red-text); font-size: 1.2rem;"></i> 
                        <strong>Coupable identifié :</strong> 
                        <span class="val" style="color: var(--red-text); font-weight: 800; font-size: 1.1rem;">
                            <?php echo $alerte['nom'] ? htmlspecialchars($alerte['prenom'] . ' ' . $alerte['nom']) . ' (' . $alerte['matricule'] . ')' : 'Visage non répertorié'; ?>
                        </span>
                    </p>
                    <p>
                        <i class="fas fa- door-open"></i> 
                        <strong>Espace piraté :</strong> 
                        <span class="val">Session ouverte par l'utilisateur lié à cette alerte</span>
                    </p>
                <?php else: ?>
                    <p><i class="fas fa-user"></i> <strong>Employé :</strong> <span class="val"><?php echo $alerte['nom'] ? htmlspecialchars($alerte['prenom'] . ' ' . $alerte['nom']) : 'Inconnu'; ?></span></p>
                <?php endif; ?>

                <!-- Date de l'alerte (date_creation) -->
                <p><i class="fas fa-calendar-plus"></i> <strong>Date alerte :</strong> <span class="val"><?php echo isset($alerte['date_creation']) ? date('d/m/Y H:i:s', strtotime($alerte['date_creation'])) : 'N/A'; ?></span></p>
                
                <!-- Description -->
                <div style="background: #fff5f5; padding: 15px; border-radius: 10px; border: 1px solid var(--red-text); margin: 15px 0;">
                    <p style="margin:0; color: var(--red-text); font-weight: 700; line-height: 1.4;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo nl2br(htmlspecialchars($alerte['description'])); ?>
                    </p>
                </div>
                
                <!-- Position GPS -->
                <?php if ($alerte['latitude'] && $alerte['longitude']): ?>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Position :</strong> <span class="val"><?php echo $alerte['latitude']; ?>, <?php echo $alerte['longitude']; ?></span></p>
                <?php endif; ?>
                
                <!-- Traité par -->
                <?php if ($alerte['traite_nom']): ?>
                <p style="color: var(--green-text);"><i class="fas fa-check-double"></i> <strong>Traité par :</strong> <span class="val"><?php echo $alerte['traite_prenom'] . ' ' . $alerte['traite_nom']; ?></span></p>
                <?php endif; ?>
            </div>
            <div class="alerte-actions">
                <?php if ($alerte['statut'] == 'en_attente'): ?>
                    <a href="?marquer_traite=<?php echo $alerte['id']; ?>" class="btn btn-success" 
                       onclick="return confirm('Confirmer que cette alerte a été traitée ?')">
                        <i class="fas fa-check"></i> Marquer traitée
                    </a>
                <?php endif; ?>
                <a href="?supprimer=<?php echo $alerte['id']; ?>" class="btn btn-danger" 
                   onclick="return confirm('Supprimer définitivement cette alerte ?')">
                    <i class="fas fa-trash"></i> Supprimer
                </a>
                <?php if ($alerte['pointage_id']): ?>
                <a href="pointages.php?voir=<?php echo $alerte['pointage_id']; ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> Voir pointage
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- AUTO-REFRESH -->
    <div class="auto-refresh">
        <i class="fas fa-sync-alt fa-spin"></i>
        <span>Rafraîchissement automatique dans 30s</span>
    </div>
</div>

<script>
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php include '../includes/footer.php'; ?>