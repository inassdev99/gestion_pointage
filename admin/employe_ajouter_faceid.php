<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$titre = "📝 Ajouter un employé - Face ID";
$message = '';
$erreur = '';
$face_image = '';

// Fonction pour sauvegarder l'image template
function sauvegarderTemplateFace($face_image_data, $matricule) {
    $upload_dir = '../assets/images/captures/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Supprimer l'en-tête base64
    $face_image_data = str_replace('data:image/jpeg;base64,', '', $face_image_data);
    $face_image_data = str_replace('data:image/png;base64,', '', $face_image_data);
    $face_image_data = str_replace(' ', '+', $face_image_data);
    
    // Générer un nom unique
    $filename = 'face_template_' . $matricule . '_' . time() . '.jpg';
    $filepath = $upload_dir . $filename;
    
    // Sauvegarder l'image
    file_put_contents($filepath, base64_decode($face_image_data));
    
    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $matricule = $_POST['matricule'] ?? '';
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $departement_id = $_POST['departement_id'] ?? null;
    $telephone = $_POST['telephone'] ?? '';
    $date_embauche = $_POST['date_embauche'] ?? date('Y-m-d');
    $face_score = $_POST['face_score'] ?? 0;
    $face_image_data = $_POST['face_image'] ?? '';
    
    // Vérifier si le matricule existe déjà
    $stmt = $pdo->prepare("SELECT id FROM employe WHERE matricule = ?");
    $stmt->execute([$matricule]);
    if ($stmt->fetch()) {
        $erreur = "❌ Ce matricule existe déjà !";
    }
    
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM employe WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $erreur = "❌ Cet email existe déjà !";
    }
    
    // Vérifier si le login existe déjà
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE login = ?");
    $stmt->execute([$login]);
    if ($stmt->fetch()) {
        $erreur = "❌ Cet identifiant existe déjà !";
    }
    
    // Vérification Face ID (score minimum 70%)
    if (!$face_image_data) {
        $erreur = "❌ Vous devez scanner le visage de l'employé.";
    } elseif ($face_score < 70) {
        $erreur = "❌ Reconnaissance faciale échouée (Score: " . $face_score . "%). Veuillez réessayer.";
    }
    
    if (empty($erreur)) {
        try {
            // Sauvegarder l'image Face ID (template)
            $face_template = sauvegarderTemplateFace($face_image_data, $matricule);
            
            // Insérer l'employé (sans la colonne poste)
            $stmt = $pdo->prepare("INSERT INTO employe (matricule, nom, prenom, email, telephone, departement_id, date_embauche, photo, face_id_template, statut) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif')");
            $stmt->execute([$matricule, $nom, $prenom, $email, $telephone, $departement_id, $date_embauche, null, $face_template]);
            $employe_id = $pdo->lastInsertId();
            
            // Insérer l'utilisateur
            $stmt = $pdo->prepare("INSERT INTO utilisateur (employe_id, login, mot_de_passe, role, statut) 
                                   VALUES (?, ?, ?, 'employe', 'actif')");
            $stmt->execute([$employe_id, $login, $password]);
            
            // Enregistrer la tentative Face ID réussie
            $stmt = $pdo->prepare("INSERT INTO verification_face (employe_id, score_confiance, image_capture, statut) 
                                   VALUES (?, ?, ?, 'succès')");
            $stmt->execute([$employe_id, $face_score, $face_template]);
            
            $message = "✅ Employé ajouté avec succès avec Face ID !";
            
            // Rediriger après 2 secondes
            header("Refresh:2; url=employes.php");
            
        } catch (PDOException $e) {
            $erreur = "❌ Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}

// Récupérer les départements
$departements = $pdo->query("SELECT id, nom FROM departement ORDER BY nom")->fetchAll();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter employé avec Face ID</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ══ VARIABLES THEME GESTIPOINT ══ */
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
            color: var(--text);
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
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .alert i {
            margin-right: 8px;
        }

        /* ══ CARD ══ */
        .card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border);
            max-width: 850px;
            margin: 0 auto;
        }

        .card-header {
            background: #fff;
            padding: 18px 25px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text);
        }

        .card-header p {
            margin: 0;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .card-body {
            padding: 25px;
        }

        /* ══ FORM ══ */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: var(--text);
            font-size: 0.85rem;
        }
        .form-group label i {
            margin-right: 6px;
            color: var(--blue);
        }
        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: #fafbfe;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--blue);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.08);
        }
        .row {
            display: flex;
            gap: 20px;
        }
        .row .form-group {
            flex: 1;
        }

        /* ══ BUTTONS ══ */
        .btn-faceid {
            background: #7c3aed;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .btn-faceid:hover {
            background: #6d28d9;
            transform: translateY(-2px);
        }
        .btn-submit {
            background: var(--blue);
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37,99,235,0.2);
        }
        .btn-submit:disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }
        .btn-retour {
            display: inline-block;
            margin-top: 15px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-retour:hover {
            color: var(--blue);
        }

        /* ══ FACE STATUS ══ */
        .face-status {
            background: #f8fafc;
            padding: 12px;
            border-radius: 10px;
            margin-top: 10px;
            text-align: center;
            font-size: 13px;
        }
        .face-status.success {
            background: #d1fae5;
            color: #065f46;
        }
        .face-status.error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ══ MODAL ══ */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 16px;
            max-width: 450px;
            width: 90%;
            text-align: center;
        }
        #faceVideo {
            width: 100%;
            border-radius: 8px;
            background: #000;
        }
        .close-modal {
            margin-top: 15px;
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
        }
        .close-modal:hover {
            background: #5a6268;
        }
        .progress-bar-container {
            margin-top: 10px;
            background: #e2e8f0;
            border-radius: 10px;
            height: 6px;
            overflow: hidden;
        }
        .progress-bar {
            background: #8b5cf6;
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        .required {
            color: #ef4444;
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
            .card-header {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- PAGE HEADER AVEC LOGO -->
    <div class="page-header">
        <div class="header-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="header-content">
            <h1>Ajouter un employé</h1>
            <p class="subtitle">
                <span class="sub-icon">
                    <i class="fas fa-fingerprint"></i>
                </span>
                Enregistrement biométrique avec reconnaissance faciale pour un pointage sécurisé
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erreur; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-id-card"></i> Formulaire d'ajout</h2>
            <p>Tous les champs marqués d'un <strong style="color: #fbbf24;">*</strong> sont obligatoires</p>
        </div>
        <div class="card-body">
            <form method="POST" id="employeForm">
                <div class="row">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Matricule <span class="required">*</span></label>
                        <input type="text" name="matricule" class="form-control" placeholder="EMP001" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nom <span class="required">*</span></label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" class="form-control" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Téléphone</label>
                        <input type="text" name="telephone" class="form-control" placeholder="06XXXXXXXX">
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Département</label>
                        <select name="departement_id" class="form-control">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($departements as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date d'embauche</label>
                        <input type="date" name="date_embauche" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Identifiant <span class="required">*</span></label>
                        <input type="text" name="login" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Mot de passe <span class="required">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                
                <input type="hidden" name="face_score" id="faceScore" value="0">
                <input type="hidden" name="face_image" id="faceImage" value="">
                
                <button type="button" class="btn-faceid" id="btnFaceId" onclick="ouvrirFaceID()">
                    <i class="fas fa-camera"></i> Scanner le visage <span style="color: #fbbf24;">(OBLIGATOIRE)</span>
                </button>
                
                <div id="faceStatus" class="face-status" style="display: none;"></div>
                
                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-save"></i> Enregistrer l'employé
                </button>
                
                <div class="text-center">
                    <a href="employes.php" class="btn-retour">
                        <i class="fas fa-arrow-left"></i> Retour à la liste des employés
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Face ID -->
<div id="faceModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-camera"></i> Enregistrement facial</h3>
        <video id="faceVideo" autoplay playsinline></video>
        <canvas id="faceCanvas" style="display: none;"></canvas>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        <p id="captureStatus" style="margin-top: 10px; color: #64748b;">Regardez la caméra...</p>
        <button class="close-modal" onclick="fermerFaceID()">Annuler</button>
    </div>
</div>

<script>
let streamVideo = null;

function ouvrirFaceID() {
    document.getElementById('faceModal').style.display = 'flex';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('captureStatus').innerHTML = '🔄 Initialisation de la caméra...';
    
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(stream) {
            streamVideo = stream;
            const video = document.getElementById('faceVideo');
            video.srcObject = stream;
            video.play();
            
            let countdown = 3;
            const countdownInterval = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    document.getElementById('captureStatus').innerHTML = `📸 Préparation... ${countdown}`;
                    const progress = ((3 - countdown) / 3) * 100;
                    document.getElementById('progressBar').style.width = progress + '%';
                } else {
                    clearInterval(countdownInterval);
                    document.getElementById('captureStatus').innerHTML = '📸 Capture en cours...';
                    document.getElementById('progressBar').style.width = '100%';
                    
                    setTimeout(() => {
                        const canvas = document.getElementById('faceCanvas');
                        const context = canvas.getContext('2d');
                        canvas.width = video.videoWidth || 640;
                        canvas.height = video.videoHeight || 480;
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        
                        const imageData = canvas.toDataURL('image/jpeg', 0.9);
                        // Simulation de score (dans la réalité, utilisez une API)
                        const score = Math.floor(Math.random() * 20) + 80; // 80-99%
                        
                        document.getElementById('faceImage').value = imageData;
                        document.getElementById('faceScore').value = score;
                        
                        const statusDiv = document.getElementById('faceStatus');
                        statusDiv.style.display = 'block';
                        
                        if (score >= 70) {
                            statusDiv.className = 'face-status success';
                            statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> ✅ Visage enregistré ! Score: ' + score + '%';
                            document.getElementById('submitBtn').disabled = false;
                            document.getElementById('btnFaceId').style.opacity = '0.6';
                        } else {
                            statusDiv.className = 'face-status error';
                            statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ❌ Qualité insuffisante. Réessayez.';
                            document.getElementById('submitBtn').disabled = true;
                        }
                        
                        fermerFaceID();
                    }, 500);
                }
            }, 1000);
        })
        .catch(function(err) {
            alert("Erreur caméra: " + err.message);
            fermerFaceID();
        });
}

function fermerFaceID() {
    document.getElementById('faceModal').style.display = 'none';
    if (streamVideo) {
        streamVideo.getTracks().forEach(track => track.stop());
        streamVideo = null;
    }
}
</script>

<?php include '../includes/footer.php'; ?>