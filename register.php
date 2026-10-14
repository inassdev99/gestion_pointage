<?php
session_start();
require_once 'includes/config.php';

$message = '';
$erreur = '';
$face_score = 0;
$face_image = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $matricule = $_POST['matricule'] ?? '';
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $face_score = $_POST['face_score'] ?? 0;
    $face_image_data = $_POST['face_image'] ?? '';
    
    // Vérification Face ID (score minimum 70%)
    if ($face_score < 70) {
        $erreur = "❌ Reconnaissance faciale échouée (Score: " . $face_score . "%). Veuillez réessayer.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM employe WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreur = "❌ Cet email existe déjà !";
        } else {
            // Sauvegarder l'image Face ID
            $face_filename = null;
            if ($face_image_data) {
                $face_filename = 'face_' . time() . '_' . rand(1000, 9999) . '.jpg';
                $face_image_data = str_replace('data:image/jpeg;base64,', '', $face_image_data);
                $face_image_data = str_replace(' ', '+', $face_image_data);
                $face_image_binary = base64_decode($face_image_data);
                
                $upload_dir = 'assets/images/captures/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                file_put_contents($upload_dir . $face_filename, $face_image_binary);
            }
            
            // Insérer l'employé
            $stmt = $pdo->prepare("INSERT INTO employe (matricule, nom, prenom, email, date_embauche, photo, face_id_template, statut) 
                                   VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'actif')");
            $stmt->execute([$matricule, $nom, $prenom, $email, null, $face_filename]);
            $employe_id = $pdo->lastInsertId();
            
            // Insérer l'utilisateur
            $stmt = $pdo->prepare("INSERT INTO utilisateur (employe_id, login, mot_de_passe, role, statut) 
                                   VALUES (?, ?, ?, 'employe', 'actif')");
            $stmt->execute([$employe_id, $login, $password]);
            
            // Enregistrer la tentative Face ID
            $stmt = $pdo->prepare("INSERT INTO verification_face (employe_id, score_confiance, image_capture, statut) 
                                   VALUES (?, ?, ?, 'succès')");
            $stmt->execute([$employe_id, $face_score, $face_filename]);
            
            $message = "✅ Inscription réussie ! Vous pouvez maintenant vous connecter.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - GestiPoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        .register-header i {
            font-size: 50px;
            margin-bottom: 10px;
        }
        .register-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }
        .register-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .register-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e293b;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .btn-faceid {
            background: #8b5cf6;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 10px;
        }
        .btn-faceid:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        .btn-submit {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30,60,114,0.3);
        }
        .btn-retour {
            display: inline-block;
            margin-top: 15px;
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            text-align: center;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .face-status {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }
        .face-status i {
            margin-right: 8px;
        }
        .face-status.success {
            background: #d1fae5;
            color: #065f46;
        }
        .face-status.error {
            background: #fee2e2;
            color: #991b1b;
        }
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
        .modal-content h3 {
            margin-bottom: 15px;
            color: #1e3c72;
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
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #8b5cf6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .row {
            display: flex;
            gap: 15px;
        }
        .row .form-group {
            flex: 1;
        }
        @media (max-width: 768px) {
            .row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h2>Inscription</h2>
                <p>Créez votre compte employé</p>
            </div>
            <div class="register-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <form method="POST" id="registerForm">
                    <div class="row">
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label>Matricule *</label>
                            <input type="text" name="matricule" class="form-control" placeholder="EMP001" required>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label>Identifiant *</label>
                            <input type="text" name="login" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Mot de passe *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    
                    <input type="hidden" name="face_score" id="faceScore" value="0">
                    <input type="hidden" name="face_image" id="faceImage" value="">
                    
                    <button type="button" class="btn-faceid" onclick="ouvrirFaceID()">
                        <i class="fas fa-camera"></i> Scanner mon visage (OBLIGATOIRE)
                    </button>
                    
                    <div id="faceStatus" class="face-status" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> En attente de validation...
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <i class="fas fa-check-circle"></i> S'inscrire
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn-retour">
                            <i class="fas fa-arrow-left"></i> Déjà un compte ? Se connecter
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Face ID -->
    <div id="faceModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-camera"></i> Reconnaissance faciale</h3>
            <video id="faceVideo" autoplay playsinline style="width: 100%; border-radius: 8px;"></video>
            <canvas id="faceCanvas" style="display: none;"></canvas>
            <p style="margin-top: 10px;">Regardez la caméra pendant 2 secondes...</p>
            <button class="close-modal" onclick="fermerFaceID()">Annuler</button>
        </div>
    </div>
    
    <script>
        let streamVideo = null;
        
        // Ouvrir la caméra pour Face ID
        function ouvrirFaceID() {
            document.getElementById('faceModal').style.display = 'flex';
            
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    streamVideo = stream;
                    const video = document.getElementById('faceVideo');
                    video.srcObject = stream;
                    video.play();
                    
                    // Compterdown de 2 secondes
                    let countdown = 2;
                    const statusDiv = document.getElementById('faceStatus');
                    statusDiv.style.display = 'block';
                    statusDiv.innerHTML = '<div class="loading"></div> Préparation de la caméra...';
                    
                    setTimeout(function() {
                        statusDiv.innerHTML = '<div class="loading"></div> Analyse du visage en cours...';
                        
                        setTimeout(function() {
                            const canvas = document.getElementById('faceCanvas');
                            const context = canvas.getContext('2d');
                        canvas.width = video.videoWidth || 640; // Fallback pour la largeur
                            canvas.height = video.videoHeight;
                            context.drawImage(video, 0, 0, canvas.width, canvas.height);
                            
                            // Simulation de vérification faciale (score entre 0 et 100)
                            // Dans la réalité, utilisez une API comme Face++ ou Azure Face API
                            const score = Math.floor(Math.random() * 40) + 60; // 60-99%
                            
                            const imageData = canvas.toDataURL('image/jpeg', 0.8); // Qualité JPEG
                            document.getElementById('faceImage').value = imageData;
                            document.getElementById('faceScore').value = score;
                            
                            fermerFaceID();
                            
                            if (score >= 70) {
                                statusDiv.className = 'face-status success';
                                statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> ✅ Visage reconnu ! Score: ' + score + '%. Vous pouvez vous inscrire.';
                                document.getElementById('submitBtn').disabled = false;
                            } else {
                                statusDiv.className = 'face-status error';
                                statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ❌ Visage non reconnu. Score: ' + score + '%. Veuillez réessayer.';
                                document.getElementById('submitBtn').disabled = true;
                                document.getElementById('faceScore').value = 0;
                            }
                            
                            if (streamVideo) {
                                streamVideo.getTracks().forEach(track => track.stop());
                                streamVideo = null;
                            }
                        }, 2000);
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
            const video = document.getElementById('faceVideo');
            if (video) video.srcObject = null;
        }
    </script>
</body>
</html>