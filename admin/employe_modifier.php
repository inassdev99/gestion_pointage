<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';

if ($user_role != 'admin') {
    rediriger('../login.php');
}

$titre = "✏️ Modifier un employé";
$message = '';
$erreur = '';
$id = isset($_GET['id']) ? $_GET['id'] : 0;

$departements = $pdo->query("SELECT * FROM departement ORDER BY nom")->fetchAll();

// Récupérer l'employé
$stmt = $pdo->prepare("SELECT e.*, u.login FROM employe e LEFT JOIN utilisateur u ON e.id = u.employe_id WHERE e.id = ?");
$stmt->execute([$id]);
$employe = $stmt->fetch();

if (!$employe) {
    rediriger('employes.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $date_embauche = $_POST['date_embauche'];
    $departement_id = $_POST['departement_id'] ?: null;
    $statut = $_POST['statut'];
    $login = $_POST['login'];
    $password = $_POST['password'];
    
    // Mettre à jour employé
    $stmt = $pdo->prepare("UPDATE employe SET nom=?, prenom=?, email=?, telephone=?, date_embauche=?, departement_id=?, statut=? WHERE id=?");
    $stmt->execute([$nom, $prenom, $email, $telephone, $date_embauche, $departement_id, $statut, $id]);
    
    // Mettre à jour utilisateur
    if ($password) {
        $stmt = $pdo->prepare("UPDATE utilisateur SET login=?, mot_de_passe=? WHERE employe_id=?");
        $stmt->execute([$login, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateur SET login=? WHERE employe_id=?");
        $stmt->execute([$login, $id]);
    }
    
    $message = "✅ Employé modifié avec succès !";
    header("Refresh:1; url=employes.php");
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un employé</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            --radius:      12px;
            --shadow:      0 2px 12px rgba(30,45,77,0.07);
        }

        body {
            background-color: var(--bg) !important;
            font-family: 'Inter', system-ui, sans-serif;
        }

        .container {
            max-width: 1000px;
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

        /* ══ ALERT ══ */
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            border-left: 4px solid #10b981;
        }
        .alert-success i {
            margin-right: 8px;
        }

        /* ══ CARD ══ */
        .card { 
            background: var(--card); 
            border-radius: var(--radius); 
            box-shadow: var(--shadow); 
            max-width: 700px; 
            margin: 0 auto; 
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .card-header { 
            padding: 15px 20px; 
            border-bottom: 1px solid var(--border); 
            font-weight: 600; 
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
        }
        .card-header i { color: var(--blue); }
        .card-header .badge-id {
            margin-left: auto;
            font-size: 0.75rem;
            color: var(--muted);
            background: var(--blue-light);
            padding: 2px 10px;
            border-radius: 20px;
        }
        .card-body { padding: 25px; }

        /* ══ FORM ══ */
        .form-group { margin-bottom: 18px; }
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-size: 0.82rem; 
            font-weight: 600; 
            color: #374151; 
        }
        .form-group label i {
            margin-right: 6px;
            color: var(--blue);
        }
        .form-control { 
            width: 100%; 
            padding: 10px 14px; 
            border-radius: 8px; 
            border: 1.5px solid var(--border); 
            font-size: 0.88rem;
            background: #fafbfe;
            transition: all 0.2s;
        }
        .form-control:focus { 
            border-color: var(--blue); 
            background: #fff; 
            outline: none;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
        }

        /* ══ ROW ══ */
        .row {
            display: flex;
            gap: 15px;
        }
        .row .form-group {
            flex: 1;
        }

        /* ══ BUTTONS ══ */
        .btn-submit { 
            background: var(--blue); 
            color: white; 
            padding: 10px 28px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 700; 
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-cancel {
            display: inline-block;
            padding: 10px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
            background: #fff;
            border: 1.5px solid var(--border);
            transition: all 0.2s;
            margin-left: 10px;
        }
        .btn-cancel:hover {
            background: #f9fafb;
            border-color: #cbd5e1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .row {
                flex-direction: column;
                gap: 0;
            }
            .page-header .header-content h1 {
                font-size: 1.3rem;
            }
            .container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- PAGE HEADER AVEC LOGO -->
    <div class="page-header">
        <div class="header-icon">
            <i class="fas fa-user-edit"></i>
        </div>
        <div class="header-content">
            <h1>Modifier un employé</h1>
            <p class="subtitle">
                <span class="sub-icon">
                    <i class="fas fa-pen-alt"></i>
                </span>
                Mettez à jour les informations de <strong><?php echo htmlspecialchars($employe['prenom'] . ' ' . $employe['nom']); ?></strong>
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-id-card"></i> 
            Formulaire de modification
            <span class="badge-id">
                <i class="fas fa-user"></i> ID: <?php echo $employe['id']; ?>
            </span>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nom *</label>
                        <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($employe['nom']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Prénom *</label>
                        <input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($employe['prenom']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($employe['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Téléphone</label>
                    <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($employe['telephone']); ?>">
                </div>

                <div class="row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date d'embauche *</label>
                        <input type="date" name="date_embauche" class="form-control" value="<?php echo $employe['date_embauche']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Département</label>
                        <select name="departement_id" class="form-control">
                            <option value="">-- Aucun --</option>
                            <?php foreach ($departements as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo $employe['departement_id'] == $d['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-toggle-on"></i> Statut</label>
                    <select name="statut" class="form-control">
                        <option value="actif" <?php echo $employe['statut'] == 'actif' ? 'selected' : ''; ?>>✅ Actif</option>
                        <option value="inactif" <?php echo $employe['statut'] == 'inactif' ? 'selected' : ''; ?>>❌ Inactif</option>
                    </select>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Identifiant *</label>
                        <input type="text" name="login" class="form-control" value="<?php echo htmlspecialchars($employe['login']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Mot de passe</label>
                        <input type="password" name="password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                        <small style="color: var(--muted); font-size: 0.7rem;">
                            <i class="fas fa-info-circle"></i> Remplir uniquement si vous souhaitez modifier le mot de passe
                        </small>
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="employes.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>