<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'employe') {
    rediriger('../login.php');
}

$titre = "Tableau de bord";

$prenom = $user_prenom;
$nom = $user_nom;
$civilite = ($prenom == 'Karim') ? 'M.' : 'Mme';

include '../includes/header.php';
?>

<style>
* { box-sizing: border-box; }

/* Force le fond de page en blanc pour un rendu épuré */
body {
    background-color: #ffffff !important;
}

.dashboard-container {
    max-width: 100%;
    margin: 0;
    padding: 0;
    background: #ffffff;
}

.welcome-card {
    background: #ffffff;
    border-radius: 0;
    padding: 0 40px;
    box-shadow: none;
    border: none;
    text-align: center;
    position: relative;
    overflow: hidden;
    min-height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* ── Vague bleue en bas (conservée) ── */
.welcome-card .wave-bg {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 260px;
    z-index: 0;
    min-width: 1440px; /* Empêche la compression excessive sur petits écrans */
}

/* ── Animation des vagues ── */
.wave-bg path {
    animation: wave-flow 15s ease-in-out infinite;
    transform-origin: center bottom;
}

.wave-bg path:nth-child(2) {
    animation-duration: 12s;
    animation-delay: -2s;
}

.wave-bg path:nth-child(3) {
    animation-duration: 9s;
    animation-delay: -5s;
}

.welcome-icon {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: #dbeafe;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 28px;
    position: relative;
    z-index: 2;
}

.welcome-icon i {
    font-size: 3rem;
    color: #2563eb;
}

.welcome-card h1 {
    font-size: 3.2rem;
    color: #1e293b;
    font-weight: 800;
    line-height: 1.35;
    margin-bottom: 14px;
    position: relative;
    z-index: 2;
}

.welcome-card h1 .brand {
    color: #2563eb;
}

.welcome-card p {
    color: #94a3b8;
    font-size: 1.4rem;
    line-height: 1.75;
    font-weight: 600;
    margin-bottom: 34px;
    position: relative;
    z-index: 2;
}

.btn-pointer {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: #2563eb;
    color: white;
    text-decoration: none;
    font-weight: 900;
    font-size: 1.3rem;
    position: relative;
    z-index: 2;
    box-shadow: 0 8px 28px rgba(37,99,235,0.38);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.btn-pointer i { font-size: 3.2rem; }

.btn-pointer:hover {
    transform: translateY(-3px) scale(1.04);
    box-shadow: 0 14px 40px rgba(37,99,235,0.48);
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .welcome-card { padding: 44px 24px 0; min-height: auto; }
    .welcome-icon { width: 72px; height: 72px; }
    .welcome-icon i { font-size: 1.9rem; }
    .welcome-card h1 { font-size: 1.45rem; }
    .btn-pointer { width: 118px; height: 118px; font-size: 0.9rem; }
    .btn-pointer i { font-size: 1.6rem; }
}
</style>

<div class="dashboard-container">
    <div class="welcome-card">

        <div class="welcome-icon">
            <i class="fas fa-users"></i>
        </div>

        <h1>
            Bienvenue sur la plateforme<br>
            <span class="brand">Gestipoint</span>
            <?php echo $civilite . ' ' . $prenom . ' ' . $nom; ?>
        </h1>

        <p>
            Nous sommes ravis de vous retrouver.<br>
            Cliquez sur le bouton ci-dessous pour enregistrer votre pointage.
        </p>

        <a href="pointage.php" class="btn-pointer">
            <i class="fas fa-clock"></i>
            Pointer ici
        </a>

        <!-- Vague décorative bleue multi-couches finalisée -->
        <svg class="wave-bg" viewBox="0 0 1440 260" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,100 C280,180 520,30 760,90 C1000,150 1220,60 1440,105 L1440,260 L0,260 Z" fill="#f0f7ff" opacity="0.6"/>
            <path d="M0,145 C220,215 500,80 720,120 C910,155 1100,95 1300,135 C1380,150 1420,140 1440,138 L1440,260 L0,260 Z" fill="#dbeafe" opacity="0.5"/>
            <path d="M0,195 C260,158 540,225 800,180 C1020,145 1200,190 1440,170 L1440,260 L0,260 Z" fill="#bfdbfe" opacity="0.4"/>
        </svg>

        <!-- Machine + plante supprimées -->

    </div>
</div>

<?php include '../includes/footer.php'; ?>