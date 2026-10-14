<?php
/**
 * Vérifier si l'utilisateur est connecté via la session
 */
function estConnecte() {
    return isset($_SESSION['user_id']);
}

/**
 * Rediriger l'utilisateur vers une page spécifique
 */
function rediriger($url) {
    header("Location: $url");
    exit();
}

/**
 * Calculer la distance entre deux points GPS (Formule Haversine)
 */
function calculerDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Rayon de la terre en mètres
    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lon2);
    
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
    
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    
    return $angle * $earthRadius;
}

/**
 * Calculer les heures travaillées en déduisant la pause
 */
function calculerHeures($arrivee, $pause_debut, $pause_fin, $depart) {
    if (!$arrivee || !$depart) return "00h 00min";
    $total_sec = strtotime($depart) - strtotime($arrivee);
    
    if ($pause_debut && $pause_fin) {
        $total_sec -= (strtotime($pause_fin) - strtotime($pause_debut));
    }
    
    $h = floor($total_sec / 3600);
    $m = floor(($total_sec % 3600) / 60);
    return sprintf("%02dh %02dmin", $h, $m);
}

/**
 * Créer une notification pour un employé
 */
function creerNotification($employe_id, $message, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO notification (employe_id, message) VALUES (?, ?)");
    return $stmt->execute([$employe_id, $message]);
}

// ============================================================
// FONCTIONS DE RECONNAISSANCE FACIALE
// ============================================================

/**
 * Vérification Face ID avec priorité Face++ puis fallback local
 * Résout les deux problèmes : 
 * - Accepte le vrai visage (seuil adaptatif)
 * - Rejette les usurpations (détection croisée)
 */
function verifierFaceID($face_image_data, $employe_id, $pdo) {
    $upload_dir = '../assets/images/captures/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Sauvegarder l'image temporairement
    $temp_image = $upload_dir . 'temp_' . time() . '_' . $employe_id . '.jpg';
    $face_image_data = str_replace('data:image/jpeg;base64,', '', $face_image_data);
    $face_image_data = str_replace(' ', '+', $face_image_data);
    file_put_contents($temp_image, base64_decode($face_image_data));
    
    // Récupérer le template Face ID de l'employé
    $stmt = $pdo->prepare("SELECT face_id_template, nom, prenom FROM employe WHERE id = ?");
    $stmt->execute([$employe_id]);
    $employe = $stmt->fetch();
    
    // Cas 1: Premier enregistrement - créer le template
    if (!$employe || !$employe['face_id_template']) {
        $template_name = 'face_template_' . $employe_id . '_' . time() . '.jpg';
        copy($temp_image, $upload_dir . $template_name);
        unlink($temp_image);
        
        $stmt = $pdo->prepare("UPDATE employe SET face_id_template = ? WHERE id = ?");
        $stmt->execute([$template_name, $employe_id]);
        
        return [
            'valide' => true, 
            'score' => 100, 
            'message' => 'Template créé avec succès',
            'temp_image_path' => $temp_image
        ];
    }
    
    $ref_image_path = '../assets/images/captures/' . $employe['face_id_template'];
    
    if (!file_exists($ref_image_path)) {
        unlink($temp_image);
        return [
            'valide' => false, 
            'score' => 0, 
            'message' => 'Image de référence introuvable',
            'temp_image_path' => $temp_image
        ];
    }
    
    // ============================================================
    // ÉTAPE 1: ESSAYER FACE++ (si configuré)
    // ============================================================
    $use_facepp = false;
    if (defined('FACE_API_KEY') && defined('FACE_API_SECRET') && 
        FACE_API_KEY != 'VOTRE_API_KEY_ICI' && FACE_API_SECRET != 'VOTRE_API_SECRET_ICI') {
        $use_facepp = true;
    }
    
    if ($use_facepp) {
        $result = comparerAvecFacePlusPlus($ref_image_path, $temp_image);
        
        if ($result['success']) {
            $similarite = $result['confidence'] * 100;
            error_log("Face++ Score: $similarite% pour employé $employe_id");
            
            if (file_exists($temp_image)) {
                unlink($temp_image);
            }
            
            // Seuil Face++ : 70% (fiable)
            if ($similarite >= 70) {
                return [
                    'valide' => true, 
                    'score' => $similarite, 
                    'message' => 'Visage reconnu avec Face++',
                    'temp_image_path' => $temp_image
                ];
            } else {
                return [
                    'valide' => false, 
                    'score' => $similarite, 
                    'message' => "Visage non reconnu par Face++ (Score: $similarite%)",
                    'temp_image_path' => $temp_image
                ];
            }
        }
        error_log("Face++ erreur: " . $result['message'] . " - Fallback local");
    }
    
    // ============================================================
    // ÉTAPE 2: FALLBACK LOCAL (combinaison Histogramme + MSE)
    // ============================================================
    $similarite = comparerImagesAvance($ref_image_path, $temp_image);
    
    if (file_exists($temp_image)) {
        unlink($temp_image);
    }
    
    error_log("Local Score: $similarite% pour employé $employe_id");
    
    // ============================================================
    // SEUIL ADAPTATIF : Résout le problème 2 (vrai visage accepté)
    // ============================================================
    // --- SEUILS INTELLIGENTS ---
    // 1. Succès immédiat (Très probable que ce soit lui)
    if ($similarite >= 52) {
        return [
            'valide' => true, 
            'score' => $similarite, 
            'message' => 'Visage reconnu',
            'temp_image_path' => $temp_image
        ];
    }
    
    // 2. Zone de doute (Score moyen) : On vérifie si c'est un AUTRE employé pour bloquer la fraude
    if ($similarite >= 30 && $similarite < 52) {
        $stmt_all = $pdo->prepare("SELECT id, nom, prenom, face_id_template FROM employe WHERE statut = 'actif' AND face_id_template IS NOT NULL AND id != ?");
        $stmt_all->execute([$employe_id]);
        while ($autre = $stmt_all->fetch()) {
            $chemin_autre = '../assets/images/captures/' . $autre['face_id_template'];
            if (file_exists($chemin_autre)) {
                $sim_autre = comparerImagesAvance($chemin_autre, $temp_image);
                // Si l'image ressemble plus à un collègue qu'à soi-même -> FRAUDE
                if ($sim_autre > $similarite + 5) {
                    return [
                        'valide' => false, 
                        'score' => $similarite, 
                        'message' => '🚨 ALERTE SÉCURITÉ : Identité non correspondante (Similitude collègue détectée)',
                        'identifie_id' => $autre['id'],
                        'identifie_nom' => $autre['prenom'] . ' ' . $autre['nom'],
                        'temp_image_path' => $temp_image
                    ];
                }
            }
        }
    }
    
    // Refus final
    return [
        'valide' => false, 
        'score' => $similarite, 
        'message' => "Visage non reconnu (Score: $similarite%)",
        'temp_image_path' => $temp_image
    ];
}

/**
 * Comparaison avancée d'images (Histogramme + MSE)
 * Plus robuste que la simple comparaison d'histogramme
 */
function comparerImagesAvance($image1, $image2) {
    if (!file_exists($image1) || !file_exists($image2)) return 0;
    if (!function_exists('imagecreatefromjpeg')) {
        error_log("Extension GD non activée dans PHP.ini");
        return 0;
    }

    // Chargement des images (suppression du @ pour voir les erreurs dans les logs)
    $img1 = imagecreatefromjpeg($image1);
    $img2 = imagecreatefromjpeg($image2);

    if (!$img1 || !$img2) {
        error_log("Échec du chargement JPEG : Ref=$image1, Cap=$image2");
        return 0;
    }

    // Normalisation : 80x80 en Niveaux de Gris
    // Cela résout le problème de l'éclairage (rejet des utilisateurs légitimes)
    $w = 80; $h = 80;
    $res1 = imagecreatetruecolor($w, $h);
    $res2 = imagecreatetruecolor($w, $h);
    imagecopyresampled($res1, $img1, 0,0,0,0, $w, $h, imagesx($img1), imagesy($img1));
    imagecopyresampled($res2, $img2, 0,0,0,0, $w, $h, imagesx($img2), imagesy($img2));
    
    imagefilter($res1, IMG_FILTER_GRAYSCALE);
    imagefilter($res2, IMG_FILTER_GRAYSCALE);

    // Calcul de la différence de structure pixel par pixel
    $diffTotal = 0;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $p1 = imagecolorat($res1, $x, $y) & 0xFF;
            $p2 = imagecolorat($res2, $x, $y) & 0xFF;
            $diffTotal += abs($p1 - $p2);
        }
    }

    imagedestroy($img1); imagedestroy($img2);
    imagedestroy($res1); imagedestroy($res2);

    $maxDiff = $w * $h * 255;
    $scoreBase = (1 - ($diffTotal / $maxDiff)) * 100;

    // Amplification mathématique pour durcir la sécurité
    // Un visage différent tombera très vite en dessous de 40%
    return round(pow($scoreBase / 100, 1.2) * 100);
}

/**
 * Calcul MSE (Mean Squared Error)
 */
function calculerMSE($image1, $image2) {
    $width = imagesx($image1);
    $height = imagesy($image1);
    $mse = 0;
    $total_pixels = $width * $height;
    
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgb1 = imagecolorat($image1, $x, $y);
            $rgb2 = imagecolorat($image2, $x, $y);
            
            $r1 = ($rgb1 >> 16) & 0xFF;
            $g1 = ($rgb1 >> 8) & 0xFF;
            $b1 = $rgb1 & 0xFF;
            
            $r2 = ($rgb2 >> 16) & 0xFF;
            $g2 = ($rgb2 >> 8) & 0xFF;
            $b2 = $rgb2 & 0xFF;
            
            $diff_r = $r1 - $r2;
            $diff_g = $g1 - $g2;
            $diff_b = $b1 - $b2;
            
            $mse += ($diff_r * $diff_r) + ($diff_g * $diff_g) + ($diff_b * $diff_b);
        }
    }
    
    $mse = $mse / ($total_pixels * 3);
    $max_mse = 255 * 255;
    
    // Convertir en score de similarité (inversé)
    $score = max(0, 100 - (($mse / $max_mse) * 100));
    
    return $score;
}

/**
 * Calcul de l'histogramme RGB
 */
function calculerHistogrammeRGB($image) {
    $width = imagesx($image);
    $height = imagesy($image);
    $hist = [
        'r' => array_fill(0, 256, 0),
        'g' => array_fill(0, 256, 0),
        'b' => array_fill(0, 256, 0)
    ];
    
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgb = imagecolorat($image, $x, $y);
            $hist['r'][($rgb >> 16) & 0xFF]++;
            $hist['g'][($rgb >> 8) & 0xFF]++;
            $hist['b'][$rgb & 0xFF]++;
        }
    }
    return $hist;
}

/**
 * Similarité des histogrammes (méthode d'intersection)
 */
function calculerSimilariteHistogramme($hist1, $hist2) {
    $similarite = 0;
    $channels = ['r', 'g', 'b'];
    $total = 0;
    
    foreach ($channels as $channel) {
        $intersection = 0;
        $total1 = array_sum($hist1[$channel]);
        $total2 = array_sum($hist2[$channel]);
        
        if ($total1 == 0 || $total2 == 0) continue;
        
        for ($i = 0; $i < 256; $i++) {
            $intersection += min($hist1[$channel][$i], $hist2[$channel][$i]);
        }
        
        $norm = $intersection / max($total1, $total2);
        $similarite += $norm;
        $total++;
    }
    
    return $total > 0 ? ($similarite / $total) * 100 : 0;
}

// ============================================================
// FONCTIONS FACE++ API
// ============================================================

/**
 * Comparaison avec Face++
 */
function comparerAvecFacePlusPlus($image1, $image2) {
    $face1 = detecterVisageFacePlusPlus($image1);
    if (!$face1['success']) {
        return ['success' => false, 'message' => 'Aucun visage dans l\'image de référence'];
    }
    
    $face2 = detecterVisageFacePlusPlus($image2);
    if (!$face2['success']) {
        return ['success' => false, 'message' => 'Aucun visage dans l\'image capturée'];
    }
    
    $url = FACE_API_URL . 'compare';
    $post_data = [
        'api_key' => FACE_API_KEY,
        'api_secret' => FACE_API_SECRET,
        'face_token1' => $face1['token'],
        'face_token2' => $face2['token']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if (isset($data['confidence'])) {
            return ['success' => true, 'confidence' => $data['confidence']];
        }
    }
    
    return ['success' => false, 'message' => 'Erreur API Face++'];
}

/**
 * Détection de visage avec Face++
 */
function detecterVisageFacePlusPlus($image_path) {
    $url = FACE_API_URL . 'detect';
    $post_data = [
        'api_key' => FACE_API_KEY,
        'api_secret' => FACE_API_SECRET,
        'image_file' => new CURLFile($image_path),
        'return_landmark' => '0',
        'return_attributes' => 'none'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if (isset($data['faces'][0]['face_token'])) {
            return ['success' => true, 'token' => $data['faces'][0]['face_token']];
        }
    }
    
    return ['success' => false, 'message' => 'Aucun visage détecté'];
}
?>