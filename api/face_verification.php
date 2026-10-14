<?php
/**
 * face_verification.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Vérification avec signature HMAC pour empêcher la manipulation du score.
 * ─────────────────────────────────────────────────────────────────────────────
 */
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
header('Content-Type: application/json');

// ════════════════════════════════════════════════════════════
// CLÉ SECRÈTE (IDENTIQUE À CELLE DE pointage.php)
// ════════════════════════════════════════════════════════════
define('FACE_SIGNATURE_SECRET', 'Changez_Cette_Cle_Secrete_2026_X9kL2mP');
define('FACE_SIGNATURE_MAX_AGE', 30);

function genererSignatureFace($score, $timestamp, $image_preview, $user_id) {
    $payload = $score . '|' . $timestamp . '|' . $image_preview . '|' . $user_id;
    return hash_hmac('sha256', $payload, FACE_SIGNATURE_SECRET . '_' . $user_id);
}

// ── Lecture de la requête ──
$data           = json_decode(file_get_contents('php://input'), true);
$image_data     = $data['image']       ?? '';
$employe_id     = (int)($data['employe_id']  ?? 0);
$face_score     = (float)($data['face_score'] ?? -1);
$is_first       = (bool)($data['is_first']   ?? false);
$signature      = $data['signature']   ?? '';
$timestamp      = (int)($data['timestamp'] ?? 0);

if (empty($image_data) || !$employe_id) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

// ════════════════════════════════════════════════════════════
// VÉRIFICATION DE LA SIGNATURE (sauf pour le premier enregistrement)
// ════════════════════════════════════════════════════════════
if (!$is_first) {
    // Vérifier l'âge du timestamp (anti-replay)
    $age = abs(time() - $timestamp);
    if ($age > FACE_SIGNATURE_MAX_AGE) {
        echo json_encode([
            'success' => false,
            'message' => 'Signature expirée. Rechargez la page.'
        ]);
        exit();
    }
    
    // Recalculer la signature
    $image_preview = substr($image_data, 0, 100);
    $expected_sig  = genererSignatureFace($face_score, $timestamp, $image_preview, $employe_id);
    
    if (!hash_equals($expected_sig, $signature)) {
        // Log de la tentative suspecte
        $pdo->prepare("INSERT INTO alerte (pointage_id, type_alerte, description, statut, user_id) VALUES (NULL,'signature_invalide_api',?,'en_attente',?)")
            ->execute(["Signature invalide via API. ID: $employe_id. Score: $face_score", $employe_id]);
        
        echo json_encode([
            'success' => false,
            'message' => 'Signature invalide. Tentative de manipulation détectée.'
        ]);
        exit();
    }
}

// ── Dossier de captures ──
$capture_dir = '../assets/images/captures/';
if (!is_dir($capture_dir)) {
    mkdir($capture_dir, 0777, true);
}

// ── Décoder et sauvegarder la capture ──
$raw      = str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $image_data);
$filename = $capture_dir . 'face_' . $employe_id . '_' . time() . '.jpg';
file_put_contents($filename, base64_decode($raw));

// ════════════════════════════════════════════════════════════
// CAS 1 : Premier enregistrement
// ════════════════════════════════════════════════════════════
if ($is_first) {
    $template_name = 'face_template_' . $employe_id . '_' . time() . '.jpg';
    copy($filename, $capture_dir . $template_name);
    $stmt = $pdo->prepare("UPDATE employe SET face_id_template = ? WHERE id = ?");
    $stmt->execute([$template_name, $employe_id]);
    
    $stmt = $pdo->prepare("
        INSERT INTO verification_face (employe_id, score_confiance, image_capture, statut)
        VALUES (?, 100, ?, 'succès')
    ");
    $stmt->execute([$employe_id, basename($filename)]);
    
    echo json_encode([
        'success'          => true,
        'score'            => 100,
        'template_created' => true,
        'message'          => 'Template créé avec succès'
    ]);
    exit();
}

// ════════════════════════════════════════════════════════════
// CAS 2 : Comparaison (seuil abaissé à 0.48)
// ════════════════════════════════════════════════════════════
const SEUIL = 0.48;
$valide = false;
$score_pct = 0;

if ($face_score < 0) {
    $valide    = false;
    $score_pct = 0;
    $message   = 'Aucun visage détecté';
} elseif ($face_score <= SEUIL) {
    $valide    = true;
    $score_pct = round((1 - $face_score) * 100);
    $message   = 'Visage reconnu';
} else {
    $valide    = false;
    $score_pct = round((1 - $face_score) * 100);
    $message   = 'Visage non reconnu';
}

$statut = $valide ? 'succès' : 'échec';
$stmt = $pdo->prepare("
    INSERT INTO verification_face (employe_id, score_confiance, image_capture, statut)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$employe_id, $score_pct, basename($filename), $statut]);

if (!$valide) {
    $stmt = $pdo->prepare("
        INSERT INTO alerte (pointage_id, type_alerte, description, statut, user_id)
        VALUES (NULL, 'face_non_reconnue', ?, 'en_attente', ?)
    ");
    $stmt->execute([
        "Visage non reconnu. Employé ID: $employe_id. Distance: $face_score (seuil: " . SEUIL . ")",
        $employe_id
    ]);
}

echo json_encode([
    'success' => $valide,
    'score'   => $score_pct,
    'message' => $message
]);