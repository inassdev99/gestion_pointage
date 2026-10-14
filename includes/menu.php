<?php
$role = $_SESSION['user_role'];
?>

<ul class="nav flex-column">
    <!-- Tableau de bord -->
    <li class="nav-item">
        <a class="nav-link" href="<?php echo $role == 'employe' ? '../employe/dashboard.php' : ($role == 'manager' ? '../manager/dashboard.php' : '../admin/dashboard.php'); ?>">
            <i class="fas fa-tachometer-alt"></i> Tableau de bord
        </a>
    </li>
    
    <?php if ($role == 'admin'): ?>
        <!-- Gestion des employés -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/employes.php">
                <i class="fas fa-user-friends"></i> Employés
            </a>
        </li>
        
        <!-- Gestion des départements -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/departements.php">
                <i class="fas fa-sitemap"></i> Départements
            </a>
        </li>
        
        <!-- Demandes d'absence -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/demandes_absence.php">
                <i class="fas fa-calendar-plus"></i> Demandes d'absence
            </a>
        </li>
        
        <!-- Demandes de congé -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/demandes_conge.php">
                <i class="fas fa-umbrella-beach"></i> Demandes de congé
            </a>
        </li>
        
        <!-- Demandes de réclamation -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/demandes_reclamation.php">
                <i class="fas fa-comment"></i> Demandes de réclamation
            </a>
        </li>
        
        <!-- Gestion des absences -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/absences.php">
                <i class="fas fa-calendar-times"></i> Gestion absences
            </a>
        </li>
        
        <!-- Gestion des congés -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/conges.php">
                <i class="fas fa-umbrella-beach"></i> Gestion congés
            </a>
        </li>
        
        <!-- Gestion des réclamations -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/reclamations.php">
                <i class="fas fa-comment-dots"></i> Gestion réclamations
            </a>
        </li>
        
        <!-- Gestion des déplacements -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/deplacements.php">
                <i class="fas fa-car"></i> Gestion déplacements
            </a>
        </li>
        
        <!-- Gestion des déplacements -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/deplacements.php">
                <i class="fas fa-car"></i> Gestion déplacements
            </a>
        </li>
        
        <!-- Pointages -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/pointages.php">
                <i class="fas fa-fingerprint"></i> Pointages
            </a>
        </li>
        
        <!-- Historique des pointages -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/historique_pointages.php">
                <i class="fas fa-history"></i> Historique pointages
            </a>
        </li>
        
        <!-- Rapports -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/rapports.php">
                <i class="fas fa-chart-line"></i> Rapports
            </a>
        </li>
        
        <!-- Sécurité - Zones GPS -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/zones_gps.php">
                <i class="fas fa-map-marker-alt"></i> Zones GPS
            </a>
        </li>
        
        <!-- Sécurité - IP autorisées -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/ips_autorisees.php">
                <i class="fas fa-network-wired"></i> IP autorisées
            </a>
        </li>
        
        <!-- Alertes fraude -->
        <li class="nav-item">
            <a class="nav-link" href="../admin/alertes_fraude.php">
                <i class="fas fa-shield-alt"></i> Alertes fraude
                <?php
                // Compter les alertes non lues
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM alerte WHERE statut = 'non_lu'");
                    $count_alertes = $stmt->fetch()['nb'];
                    if ($count_alertes > 0) {
                        echo '<span class="badge badge-danger" style="float:right; background:#ef4444; color:white; border-radius:50%; padding:2px 8px; font-size:10px;">' . $count_alertes . '</span>';
                    }
                } catch (Exception $e) {
                    // Table n'existe pas encore
                }
                ?>
            </a>
        </li>
        
    <?php elseif ($role == 'manager'): ?>
        <!-- Tableau de bord manager -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Tableau de bord
            </a>
        </li>
        
        <!-- Mon équipe -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/equipe.php">
                <i class="fas fa-users"></i> Mon équipe
            </a>
        </li>
        
        <!-- Pointages équipe -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/pointages_equipe.php">
                <i class="fas fa-calendar-check"></i> Pointages équipe
            </a>
        </li>
        
        <!-- Absences à valider -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/absence_equipe.php">
                <i class="fas fa-calendar-times"></i> Absences à valider
            </a>
        </li>
        
        <!-- Gestion absences -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/absences.php">
                <i class="fas fa-calendar-times"></i> Gestion absences
            </a>
        </li>
        
        <!-- Gestion congés -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/conges.php">
                <i class="fas fa-umbrella-beach"></i> Gestion congés
            </a>
        </li>
        
        <!-- Gestion réclamations -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/reclamations.php">
                <i class="fas fa-comment-dots"></i> Gestion réclamations
            </a>
        </li>
        
        <!-- Historique pointages -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/historique_pointages.php">
                <i class="fas fa-history"></i> Historique pointages
            </a>
        </li>
        
        <!-- Rapports -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/rapports.php">
                <i class="fas fa-chart-line"></i> Rapports
            </a>
        </li>
        
    <?php elseif ($role == 'employe'): ?>
        <!-- Tableau de bord employé -->
        <li class="nav-item">
            <a class="nav-link" href="../employe/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Tableau de bord
            </a>
        </li>
        
        <!-- Pointer -->
        <li class="nav-item">
            <a class="nav-link" href="../employe/pointage.php">
                <i class="fas fa-fingerprint"></i> Pointer
            </a>
        </li>
        
        <!-- Mes absences -->
        <li class="nav-item">
            <a class="nav-link" href="../employe/mes_absences.php">
                <i class="fas fa-calendar-times"></i> Mes absences
            </a>
        </li>
        
        <!-- Mes réclamations -->
        <li class="nav-item">
            <a class="nav-link" href="../employe/mes_reclamations.php">
                <i class="fas fa-comment-dots"></i> Mes réclamations
            </a>
        </li>
        
        <!-- Mon profil -->
        <li class="nav-item">
            <a class="nav-link" href="../employe/profil.php">
                <i class="fas fa-id-card"></i> Mon profil
            </a>
        </li>
        
    <?php elseif ($role == 'viewer'): ?>
        <!-- Tableau de bord viewer -->
        <li class="nav-item">
            <a class="nav-link" href="../viewer/dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Tableau de bord
            </a>
        </li>
        
        <!-- Gestion déplacements -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/deplacements.php">
                <i class="fas fa-car"></i> Gestion déplacements
            </a>
        </li>
        
        <!-- Gestion déplacements -->
        <li class="nav-item">
            <a class="nav-link" href="../manager/deplacements.php">
                <i class="fas fa-car"></i> Gestion déplacements
            </a>
        </li>
        
        <!-- Pointages -->
        <li class="nav-item">
            <a class="nav-link" href="../viewer/pointages.php">
                <i class="fas fa-fingerprint"></i> Pointages
            </a>
        </li>
        
        <!-- Absences -->
        <li class="nav-item">
            <a class="nav-link" href="../viewer/absences.php">
                <i class="fas fa-calendar-times"></i> Absences
            </a>
        </li>
        
        <!-- Congés -->
        <li class="nav-item">
            <a class="nav-link" href="../viewer/conges.php">
                <i class="fas fa-umbrella-beach"></i> Congés
            </a>
        </li>
        
        <!-- Réclamations -->
        <li class="nav-item">
            <a class="nav-link" href="../viewer/reclamations.php">
                <i class="fas fa-comment-dots"></i> Réclamations
            </a>
        </li>
        
        <!-- Déplacements -->
        <li class="nav-item">
            <a class="nav-link" href="../viewer/deplacements.php">
                <i class="fas fa-plane"></i> Déplacements
            </a>
        </li>
    <?php endif; ?>
    
    <!-- Séparateur -->
    <li class="nav-item mt-3">
        <hr style="border-color: var(--border);">
    </li>
    
    <!-- Déconnexion -->
    <li class="nav-item">
        <a class="nav-link text-danger" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </li>
</ul>