<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestiPoint - <?php echo isset($titre) ? $titre : 'Accueil'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { 
            margin: 0; padding: 0; box-sizing: border-box; 
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        *::-webkit-scrollbar { display: none; }

        body { font-family: 'Manrope', sans-serif; background: #ffffff; }
        
        /* Navbar */
        .navbar { 
            background: #ffffff; 
            color: #111827; 
            padding: 0.85rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: fixed; 
            top: 0; 
            left: 0; 
            right: 0; 
            z-index: 100; 
            box-shadow: 0 1px 0 rgba(0,0,0,0.05);
            border-bottom: 1px solid #e5e7eb;
            height: 70px;
        }
        .navbar a { color: #111827; text-decoration: none; }
        .navbar-brand { 
            font-size: 1.45rem; 
            font-weight: 800; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            letter-spacing: -0.02em;
        }
        .logo-icon {
            width: 38px; height: 38px; background: #1a56db;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
        }
        .logo-icon svg { width: 22px; height: 22px; }
        
        .user-info { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            font-weight: 600; 
            font-size: 0.95rem; 
        }
        
        /* Notification dans la navbar */
        .notif-nav {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #475569;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
        }
        .notif-nav:hover {
            background: #e2e8f0;
            color: #1a56db;
        }
        .notif-nav .notif-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.55rem;
            font-weight: 700;
            min-width: 18px;
            text-align: center;
            animation: pulse 1s infinite;
        }
        
        .logout-btn { 
            background: #f1f5f9; 
            color: #475569;
            padding: 8px 18px; 
            border-radius: 10px; 
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .logout-btn:hover { background: #fee2e2; color: #dc2626 !important; }
        
        /* Sidebar */
        .sidebar { 
            width: 260px; 
            background: #ffffff; 
            position: fixed; 
            top: 70px; 
            bottom: 0; 
            border-right: 1px solid #f1f5f9;
            overflow-y: auto; 
        }
        .sidebar a:first-of-type {
            margin-top: 20px;
        }
        .sidebar a { 
            display: block; 
            padding: 10px 20px; 
            color: #475569; 
            text-decoration: none; 
            border-left: 3px solid transparent; 
            transition: 0.3s;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .sidebar a:hover { 
            background: #f8fafc; 
            color: #1a56db; 
        }
        .sidebar a.active { 
            background: #eff6ff; 
            border-left-color: #1a56db; 
            color: #1a56db; 
            font-weight: 700; 
        }
        .sidebar a i { margin-right: 12px; width: 20px; }
        .sidebar hr { margin: 10px 0; border-color: #e2e8f0; }
        
        /* Badge pour les alertes */
        .badge-alerte {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.65rem;
            font-weight: 700;
            margin-left: 8px;
            display: inline-block;
            animation: pulse 1s infinite;
        }
        
        .badge-menu {
            float: right;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.6rem;
            font-weight: 700;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Main Content */
        .main-content { 
            margin-left: 260px; 
            margin-top: 70px; 
            padding: 25px; 
            min-height: 100vh; 
        }
        
        @media (max-width: 768px) { 
            .sidebar { transform: translateX(-100%); transition: 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <a href="<?php 
            $role_path = ($_SESSION['user_role'] == 'employe') ? 'employe' : $_SESSION['user_role'];
            echo "../$role_path/dashboard.php";
        ?>" class="navbar-brand">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <span>GestiPoint</span>
        </a>
        <div class="user-info">
            <span class="d-none d-md-inline">
                <i class="fas fa-user-circle" style="color: #64748b; font-size: 1.2rem; vertical-align: middle; margin-right: 5px;"></i>
                <?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>
            </span>
            
            <!-- ⭐ NOTIFICATIONS DANS LA NAVBAR (EMPLOYE UNIQUEMENT) ⭐ -->
            <?php if ($_SESSION['user_role'] == 'employe'): ?>
                <?php
                // Compter les notifications non lues
                $count_notif = 0;
                if (isset($_SESSION['user_id'])) {
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) as nb FROM notification WHERE employe_id = ? AND est_lu = 0");
                        $stmt->execute([$_SESSION['user_id']]);
                        $count_notif = $stmt->fetch()['nb'];
                    }
                    catch (Exception $e) {
                        $count_notif = 0;
                    }
                }
                ?>
                <a href="../employe/notifications.php" class="notif-nav" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($count_notif > 0): ?>
                        <span class="notif-badge"><?php echo $count_notif; ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <!-- Tableau de bord -->
            <a href="../admin/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
            
            <!-- Gestion des employés -->
            <a href="../admin/employes.php"><i class="fas fa-users"></i> Employés</a>
            <a href="../admin/departements.php"><i class="fas fa-building"></i> Départements</a>
            
           
            <!-- Pointages -->
            <a href="../admin/pointages.php"><i class="fas fa-fingerprint"></i> Pointages</a>
            <a href="../admin/historique_pointages.php"><i class="fas fa-history"></i> Historique pointages</a>
            
            <!-- Rapports -->
            <a href="../admin/rapports.php"><i class="fas fa-chart-line"></i> Rapports</a>
            
            <!-- Sécurité -->
            <a href="../admin/zones_gps.php"><i class="fas fa-map-marker-alt"></i> Zones GPS</a>
            <a href="../admin/ips_autorisees.php"><i class="fas fa-network-wired"></i> IP autorisées</a>
            
            <!-- Alertes fraude -->
            <?php
            $count_alertes = 0;
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as nb FROM alerte WHERE statut = 'non_lu'");
                $count_alertes = $stmt->fetch()['nb'];
            } catch (Exception $e) {
                $count_alertes = 0;
            }
            ?>
            <a href="../admin/alertes_fraude.php">
                <i class="fas fa-shield-alt"></i> Alertes Fraude
                <?php if ($count_alertes > 0): ?>
                    <span class="badge-menu"><?php echo $count_alertes; ?></span>
                <?php endif; ?>
            </a>
            
            <hr>
            <a href="../logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            
        <?php elseif ($_SESSION['user_role'] == 'manager'): ?>
            <!-- Tableau de bord -->
            <a href="../manager/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
            
            <!-- Gestion des employés -->
            <a href="../manager/employes.php"><i class="fas fa-users"></i> Employés</a>
            <a href="../manager/departements.php"><i class="fas fa-building"></i> Départements</a>
            <a href="../manager/equipes.php"><i class="fas fa-users"></i> Équipes par département</a>
                        
            
            <!-- Pointages -->
            <a href="../manager/pointages.php"><i class="fas fa-calendar-check"></i> Pointages</a>
            <a href="../manager/historique_pointages.php"><i class="fas fa-history"></i> Historique pointages</a>
            
            <!-- Rapports -->
            <a href="../manager/rapports.php"><i class="fas fa-chart-line"></i> Rapports</a>
            
            <hr>
            <a href="../logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            
        <?php elseif ($_SESSION['user_role'] == 'viewer'): ?>
            <!-- Menu VIEWER (consultation uniquement) -->
            <a href="../viewer/dashboard.php" class="active"><i class="fas fa-eye"></i> Tableau de bord</a>
            <a href="../viewer/pointages.php"><i class="fas fa-fingerprint"></i> Pointages</a>
            <a href="../viewer/absences.php"><i class="fas fa-calendar-times"></i> Absences</a>
            <a href="../viewer/conges.php"><i class="fas fa-umbrella-beach"></i> Congés</a>
            <a href="../viewer/reclamations.php"><i class="fas fa-comment-dots"></i> Réclamations</a>
            <a href="../viewer/deplacements.php"><i class="fas fa-plane"></i> Déplacements</a>
            <hr>
            <a href="../logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            
        <?php elseif ($_SESSION['user_role'] == 'employe'): ?>
            <!-- Menu EMPLOYE -->
            <a href="../employe/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
            <a href="../employe/pointage.php"><i class="fas fa-fingerprint"></i> Pointer</a>
            <a href="../employe/profil.php"><i class="fas fa-id-card"></i> Mon profil</a>
            <hr>
            <a href="../logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        <?php endif; ?>
    </div>
    
    <div class="main-content">