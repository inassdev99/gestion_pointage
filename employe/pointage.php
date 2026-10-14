<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/auth.php';
if ($user_role != 'employe') {
    rediriger('../login.php');
}
$titre      = "Pointage";
$message    = '';
$erreur     = '';
$aujourdhui = date('Y-m-d');
$upload_dir = '../assets/images/captures/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ════════════════════════════════════════════════════════════
// CLÉ SECRÈTE POUR LA SIGNATURE (CHANGEZ-LA !)
// ════════════════════════════════════════════════════════════
define('FACE_SIGNATURE_SECRET', 'Changez_Cette_Cle_Secrete_2026_X9kL2mP');
define('FACE_SIGNATURE_MAX_AGE', 180); // 3 minutes
define('FACE_SCORE_DECIMALS', 4); // 🔥 Nombre de décimales pour le score (doit être identique en JS et PHP)

// Fonction de génération de signature HMAC
function genererSignatureFace($score, $timestamp, $image_preview, $user_id) {
    $payload = $score . '|' . $timestamp . '|' . $image_preview . '|' . $user_id;
    return hash_hmac('sha256', $payload, FACE_SIGNATURE_SECRET . '_' . $user_id);
}

$stmt = $pdo->prepare("SELECT * FROM pointage WHERE employe_id = ? AND date_pointage = ?");
$stmt->execute([$user_id, $aujourdhui]);
$pointage = $stmt->fetch();

// 🔥 Détection AJAX : évite le rechargement complet de la page (et donc
// le re-téléchargement de face-api.js + des modèles) à chaque action.
$is_ajax = (!empty($_POST['ajax']) && $_POST['ajax'] === '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

function repondreAjax($success, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success], $data));
    exit();
}

function getMinutesTravaillees($arrivee) {
    if (!$arrivee) return 0;
    return floor((time() - strtotime($arrivee)) / 60);
}

function getTemplateBase64($employe_id, $pdo) {
    $stmt = $pdo->prepare("SELECT face_id_template FROM employe WHERE id = ?");
    $stmt->execute([$employe_id]);
    $row = $stmt->fetch();
    if (!$row || !$row['face_id_template']) return null;
    $path = '../assets/images/captures/' . $row['face_id_template'];
    if (!file_exists($path)) return null;
    return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action          = $_POST['action']        ?? '';
    $methode         = $_POST['methode']       ?? 'manuel';
    $latitude        = $_POST['latitude']      ?? null;
    $longitude       = $_POST['longitude']     ?? null;
    $ip              = $_SERVER['REMOTE_ADDR'];
    $face_image_data = $_POST['face_image']    ?? null;
    $face_score      = (float)($_POST['face_score']   ?? -1);
    $face_is_first   = ($_POST['face_is_first'] ?? '0') === '1';
    $face_signature  = $_POST['face_signature']  ?? '';
    $face_timestamp  = (int)($_POST['face_timestamp'] ?? 0);
    $face_face_size  = (int)($_POST['face_size']      ?? 0);

    if ($methode === 'face_id') {
        if (!$face_image_data) {
            $erreur = "❌ Données Face ID manquantes.";
        }
        // ── VÉRIFICATION DE LA SIGNATURE (anti-manipulation) ──
        elseif (!$face_is_first) {
            $age = abs(time() - $face_timestamp);
            if ($age > FACE_SIGNATURE_MAX_AGE) {
                error_log("[FaceID] Signature expirée - Age: {$age}s, Server: " . time() . ", Client: " . $face_timestamp);
                $erreur = "❌ Signature expirée ({$age}s). Rechargez la page et réessayez.";
                $pdo->prepare("INSERT INTO alerte (pointage_id, type_alerte, description, statut, user_id) VALUES (NULL,'signature_expiree',?,'en_attente',?)")
                    ->execute(["Signature expirée (age: {$age}s). ID: $user_id", $user_id]);
            } else {
                $image_preview = substr($face_image_data, 0, 100);

                // 🔥 L7AL: N7addo l'score l 4 décimales bash ykoun nfs l'format li f JavaScript
                $face_score_str = number_format($face_score, FACE_SCORE_DECIMALS, '.', '');
                $expected_sig  = genererSignatureFace($face_score_str, $face_timestamp, $image_preview, $user_id);

                if (!hash_equals($expected_sig, $face_signature)) {
                    $erreur = "❌ Signature invalide. Tentative de manipulation détectée.";
                    $pdo->prepare("INSERT INTO alerte (pointage_id, type_alerte, description, statut, user_id) VALUES (NULL,'signature_invalide',?,'en_attente',?)")
                        ->execute(["Signature invalide. ID: $user_id. Score: $face_score", $user_id]);
                }
            }
        }

        // ── VÉRIFICATION DU VISAGE ──
        if (empty($erreur)) {
            if ($face_score < 0 && !$face_is_first) {
                $erreur = "❌ Aucun visage détecté. Placez-vous bien face à la caméra.";
                $pdo->prepare("INSERT INTO alerte (pointage_id, type_alerte, description, statut, user_id) VALUES (NULL,'face_non_detecte',?,'en_attente',?)")
                    ->execute(["Aucun visage détecté. Employé ID: $user_id", $user_id]);
            } elseif ($face_is_first) {
                $template_name = 'face_template_' . $user_id . '_' . time() . '.jpg';
                $raw = str_replace(['data:image/jpeg;base64,',' '],['','+'], $face_image_data);
                file_put_contents($upload_dir . $template_name, base64_decode($raw));
                $pdo->prepare("UPDATE employe SET face_id_template = ? WHERE id = ?")->execute([$template_name, $user_id]);
                $message = "✅ Template Face ID créé. Vous pouvez maintenant pointer.";
                if ($is_ajax) repondreAjax(true, ['message' => $message, 'template_cree' => true]);
                header("Refresh:2; url=pointage.php"); exit();
            } else {
                // ════════════════════════════════════════════════════════════
                // SEUIL ABAISSÉ À 0.48 (plus strict)
                // ════════════════════════════════════════════════════════════
                $SEUIL = 0.48;
                if ($face_score <= $SEUIL) {
                    $img_name = 'face_' . time() . '_' . $user_id . '.jpg';
                    $raw = str_replace(['data:image/jpeg;base64,',' '],['','+'], $face_image_data);
                    file_put_contents($upload_dir . $img_name, base64_decode($raw));
                    $pdo->prepare("INSERT INTO verification_face (employe_id, score_confiance, image_capture, statut) VALUES (?,?,?,'succès')")
                        ->execute([$user_id, round((1-$face_score)*100), $img_name]);
                } else {
                    $pdo->prepare("INSERT INTO verification_face (employe_id, score_confiance, image_capture, statut) VALUES (?,?,NULL,'échec')")
                        ->execute([$user_id, round((1-$face_score)*100)]);
                    $pdo->prepare("INSERT INTO alerte (pointage_id, type_alerte, description, statut, user_id) VALUES (NULL,'face_non_reconnue',?,'en_attente',?)")
                        ->execute(["Visage non reconnu. ID: $user_id. Distance: $face_score", $user_id]);
                    $erreur = "❌ Visage non reconnu. Rapprochez-vous et assurez-vous d'un bon éclairage.";
                }
            }
        }
    }

    if (empty($erreur) && $latitude && $longitude) {
        $zones = $pdo->query("SELECT * FROM zone_gps")->fetchAll();
        $gps_valide = false;
        foreach ($zones as $zone) {
            if (calculerDistance($latitude, $longitude, $zone['latitude'], $zone['longitude']) <= $zone['rayon']) { $gps_valide = true; break; }
        }
        if (!$gps_valide) {
            $erreur = "❌ Hors zone GPS autorisée !";
            $pdo->prepare("INSERT INTO alerte (pointage_id, type_alerte, description, statut, user_id) VALUES (NULL,'hors_zone',?,'en_attente',?)")
                ->execute(["Hors zone GPS. Lat: $latitude, Lng: $longitude", $user_id]);
        }
    }

    if (empty($erreur)) {
        $min = getMinutesTravaillees($pointage['heure_arrivee'] ?? null);
        if ($action === 'pause_debut' && $min < 240) { $r=240-$min; $erreur="⏰ Pause après 4h. Encore ".floor($r/60)."h".($r%60)."min."; }
        elseif ($action === 'depart' && $min < 60) { $erreur="⏰ Départ après 1h. Encore ".(60-$min)." min."; }
    }

    if (empty($erreur)) {
        if ($action==='arrivee' && !$pointage) {
            $pdo->prepare("INSERT INTO pointage (employe_id,date_pointage,heure_arrivee,methode_auth,ip_address,latitude,longitude) VALUES (?,?,NOW(),?,?,?,?)")
                ->execute([$user_id,$aujourdhui,$methode,$ip,$latitude,$longitude]);
            $message="✅ Arrivée enregistrée !";
            if ($is_ajax) repondreAjax(true, ['message' => $message, 'action' => 'arrivee']);
            header("Refresh:1; url=pointage.php"); exit();
        } elseif ($action==='pause_debut' && $pointage && !$pointage['heure_pause_debut']) {
            $pdo->prepare("UPDATE pointage SET heure_pause_debut=NOW() WHERE id=?")->execute([$pointage['id']]);
            if ($is_ajax) repondreAjax(true, ['message' => '☕ Pause démarrée.', 'action' => 'pause_debut']);
            header("Refresh:1; url=pointage.php"); exit();
        } elseif ($action==='pause_fin' && $pointage && $pointage['heure_pause_debut'] && !$pointage['heure_pause_fin']) {
            $pdo->prepare("UPDATE pointage SET heure_pause_fin=NOW() WHERE id=?")->execute([$pointage['id']]);
            if ($is_ajax) repondreAjax(true, ['message' => '▶️ Travail repris.', 'action' => 'pause_fin']);
            header("Refresh:1; url=pointage.php"); exit();
        } elseif ($action==='depart' && $pointage && !$pointage['heure_depart']) {
            $pdo->prepare("UPDATE pointage SET heure_depart=NOW() WHERE id=?")->execute([$pointage['id']]);
            if ($is_ajax) repondreAjax(true, ['message' => '✅ Départ enregistré.', 'action' => 'depart']);
            header("Refresh:1; url=pointage.php"); exit();
        }
    }

    // 🔥 Si on arrive ici en AJAX, une erreur a été détectée plus haut
    // (signature, GPS, visage non reconnu, délai non atteint, etc.)
    if ($is_ajax) {
        repondreAjax(false, ['erreur' => $erreur ?: "❌ Erreur inconnue."]);
    }
}

$stmt = $pdo->prepare("SELECT * FROM pointage WHERE employe_id = ? AND date_pointage = ?");
$stmt->execute([$user_id, $aujourdhui]);
$pointage = $stmt->fetch();
$template_base64 = getTemplateBase64($user_id, $pdo);
$has_template    = ($template_base64 !== null);
include '../includes/header.php';
?>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background-color:#ffffff!important}
.pointage-container{background:#ffffff;min-height:calc(100vh - 60px);display:flex;align-items:center;justify-content:center;padding:20px;position:relative}
.wave-bg{position:absolute;bottom:0;left:0;right:0;height:260px;z-index:0;pointer-events:none}
.pointage-card{background:transparent;padding:30px;width:100%;max-width:860px;position:relative;z-index:2}
.current-time{font-size:3.8rem;font-weight:900;text-align:center;color:#1e293b;margin-bottom:28px;font-family:monospace;letter-spacing:2px}
.alert{padding:13px 16px;border-radius:12px;margin-bottom:20px;font-size:1.1rem;font-weight:600}
.alert-success{background:#dcfce7;color:#166534}
.alert-danger{background:#fee2e2;color:#991b1b}
.info-row{display:flex;justify-content:space-between;padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:1.2rem;color:#475569;font-weight:500}
.heure-info{background:#eff6ff;border:1px solid #bfdbfe;padding:13px 16px;border-radius:14px;text-align:center;margin:16px 0;font-size:1.15rem;color:#1e40af;font-weight:600}
.btn-block{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:16px;margin:12px 0;border:none;border-radius:60px;font-size:1.3rem;font-weight:800;cursor:pointer;transition:transform .18s,box-shadow .18s}
.btn-arrivee{background:#2563eb;color:#fff;box-shadow:0 6px 20px rgba(37,99,235,.30)}
.btn-arrivee:hover{background:#1d4ed8;transform:translateY(-2px)}
.btn-arrivee:disabled{background:#94a3b8;cursor:not-allowed;transform:none;box-shadow:none}
.btn-depart{background:#ef4444;color:#fff;box-shadow:0 6px 20px rgba(239,68,68,.25)}
.btn-depart:hover{background:#dc2626;transform:translateY(-2px)}
.btn-pause{background:#f59e0b;color:#fff}
.btn-pause:hover{background:#d97706;transform:translateY(-2px)}
.btn-reprendre{background:#0ea5e9;color:#fff}
.btn-reprendre:hover{background:#0284c7;transform:translateY(-2px)}
.btn-disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
.gps-info{font-size:13px;color:#64748b;margin-top:18px;text-align:center;padding:10px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;font-weight:500}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.75);justify-content:center;align-items:center;z-index:1000}
.modal-content{background:#fff;padding:30px 28px;border-radius:24px;max-width:420px;width:90%;text-align:center}
.modal-content h3{margin-bottom:16px;color:#1e293b;font-size:1.2rem;font-weight:800}
.video-wrapper{position:relative;display:inline-block;width:100%}
#faceVideo{width:100%;border-radius:14px;background:#000;display:block}
#faceOverlay{position:absolute;top:0;left:0;width:100%;height:100%;border-radius:14px;pointer-events:none}
#faceStatus{margin-top:12px;font-size:.95rem;color:#475569;min-height:28px;font-weight:600}
.close-modal{margin-top:16px;background:#64748b;color:#fff;border:none;padding:11px 28px;border-radius:60px;cursor:pointer;font-weight:700}
.spinner{display:inline-block;width:16px;height:16px;border:2px solid #e2e8f0;border-top:2px solid #2563eb;border-radius:50%;animation:spin .8s linear infinite;margin-right:6px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:768px){.current-time{font-size:2rem}.pointage-card{padding:20px 14px}}
</style>

<script>
(function() {
    var s = document.createElement('script');
    s.src = '../assets/js/face-api.min.js';
    s.onload = function() { window._faceApiSource = 'local'; };
    s.onerror = function() {
        var s2 = document.createElement('script');
        s2.src = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
        s2.onload = function() { window._faceApiSource = 'cdn'; };
        s2.onerror = function() {
            var s3 = document.createElement('script');
            s3.src = 'https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js';
            s3.onerror = function() {
                document.getElementById('btnArrivee').innerHTML = '⚠️ Face-api.js introuvable';
            };
            document.head.appendChild(s3);
        };
        document.head.appendChild(s2);
    };
    document.head.appendChild(s);
})();
</script>

<div class="pointage-container">
    <div class="pointage-card">
        <div class="current-time" id="currentTime"></div>
        <?php if($message):?><div class="alert alert-success"><?=htmlspecialchars($message)?></div><?php endif;?>
        <?php if($erreur):?><div class="alert alert-danger"><?=htmlspecialchars($erreur)?></div><?php endif;?>
        <?php if($pointage && $pointage['heure_arrivee']):?>
            <div class="info-row"><span>Arrivée</span><span><strong><?=date('H:i:s',strtotime($pointage['heure_arrivee']))?></strong></span></div>
            <?php $total_min=getMinutesTravaillees($pointage['heure_arrivee']);$hh=floor($total_min/60);$mm=$total_min%60;?>
            <div class="heure-info"><i class="fas fa-hourglass-half"></i> Heures travaillées : <strong><?="{$hh}h {$mm}min"?></strong></div>
            <?php if($pointage['heure_pause_debut']):?><div class="info-row"><span>Pause début</span><span><strong><?=date('H:i:s',strtotime($pointage['heure_pause_debut']))?></strong></span></div><?php endif;?>
            <?php if($pointage['heure_pause_fin']):?><div class="info-row"><span>Pause fin</span><span><strong><?=date('H:i:s',strtotime($pointage['heure_pause_fin']))?></strong></span></div><?php endif;?>
            <?php if($pointage['heure_depart']):?>
                <div class="info-row"><span>Départ</span><span><strong><?=date('H:i:s',strtotime($pointage['heure_depart']))?></strong></span></div>
                <div class="info-row" style="border-top:2px solid #2563eb;margin-top:8px;padding-top:12px;color:#1e293b;font-weight:700;"><span>Total</span><span><?=calculerHeures($pointage['heure_arrivee'],$pointage['heure_pause_debut'],$pointage['heure_pause_fin'],$pointage['heure_depart'])?></span></div>
            <?php endif;?>
        <?php endif;?>

        <form method="POST" id="pointageForm" style="margin-top:24px;">
            <input type="hidden" name="action"         id="actionType"     value="">
            <input type="hidden" name="methode"        id="methodeType"    value="face_id">
            <input type="hidden" name="latitude"       id="latitude">
            <input type="hidden" name="longitude"      id="longitude">
            <input type="hidden" name="face_score"     id="faceScore"      value="-1">
            <input type="hidden" name="face_image"     id="faceImage"      value="">
            <input type="hidden" name="face_is_first"  id="faceIsFirst"    value="0">
            <input type="hidden" name="face_signature" id="faceSignature"  value="">
            <input type="hidden" name="face_timestamp" id="faceTimestamp"  value="">
            <input type="hidden" name="face_size"      id="faceSize"       value="0">

            <?php if(!$pointage || !$pointage['heure_arrivee']):?>
                <button type="button" class="btn-block btn-arrivee" id="btnArrivee" onclick="demanderArrivee()" disabled>
                    <span class="spinner" id="btnSpinner"></span><span id="btnLabel">Chargement Face ID…</span>
                </button>
            <?php elseif(!$pointage['heure_depart']):?>
                <button type="button" class="btn-block btn-depart" onclick="confirmerDepart()"><i class="fas fa-sign-out-alt"></i> DÉPART (après 1h)</button>
            <?php else:?>
                <button type="button" class="btn-block btn-disabled" disabled><i class="fas fa-check-circle"></i> JOURNÉE TERMINÉE</button>
            <?php endif;?>

            <?php if($pointage && $pointage['heure_arrivee'] && !$pointage['heure_depart']):?>
                <?php if($pointage['heure_pause_debut'] && !$pointage['heure_pause_fin']):?>
                    <button type="button" class="btn-block btn-reprendre" onclick="actionPauseFin()"><i class="fas fa-play"></i> REPRENDRE LE TRAVAIL</button>
                <?php else:?>
                    <button type="button" class="btn-block btn-pause" onclick="actionPauseDebut()"><i class="fas fa-coffee"></i> PAUSE (après 4h)</button>
                <?php endif;?>
            <?php else:?>
                <button type="button" class="btn-block btn-disabled" disabled><i class="fas fa-coffee"></i> PAUSE (indisponible)</button>
            <?php endif;?>

            <div class="gps-info" id="gpsStatus"><i class="fas fa-map-marker-alt"></i> Vérification GPS automatique</div>
        </form>
    </div>

    <svg class="wave-bg" viewBox="0 0 1440 260" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0,100 C280,180 520,30 760,90 C1000,150 1220,60 1440,105 L1440,260 L0,260 Z" fill="#f0f7ff" opacity="0.6"/>
        <path d="M0,145 C220,215 500,80 720,120 C910,155 1100,95 1300,135 L1440,260 L0,260 Z" fill="#dbeafe" opacity="0.5"/>
        <path d="M0,195 C260,158 540,225 800,180 C1020,145 1200,190 1440,170 L1440,260 L0,260 Z" fill="#bfdbfe" opacity="0.4"/>
    </svg>
</div>

<!-- Modal Face ID -->
<div id="faceModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-camera"></i> Reconnaissance faciale</h3>
        <div class="video-wrapper">
            <video id="faceVideo" autoplay playsinline muted></video>
            <canvas id="faceOverlay"></canvas>
        </div>
        <canvas id="faceCanvas" style="display:none;"></canvas>
        <p id="faceStatus">Initialisation…</p>
        <button onclick="fermerFaceID()" class="close-modal">Annuler</button>
    </div>
</div>

<script>
const HAS_TEMPLATE   = <?=json_encode($has_template)?>;
const TEMPLATE_B64   = <?=json_encode($template_base64)?>;
const MODELS_PATH    = '../assets/models/face-api';
const SEUIL_DISTANCE = 0.48;
const USER_ID        = <?=json_encode($user_id)?>;
const FACE_SIGNATURE_SECRET = 'Changez_Cette_Cle_Secrete_2026_X9kL2mP';
const FACE_MIN_FACE_SIZE    = 100;
const FACE_SCORE_DECIMALS   = 4; // 🔥 Nfs l'3adad li f PHP

// 🔥 Version des modèles : changez cette valeur si vous mettez à jour les modèles
// pour invalider automatiquement le cache du descripteur de référence.
const FACE_TEMPLATE_VERSION = '1';

let streamVideo   = null;
let faceApiReady  = false;
let refDescriptor = null;
let loopActive    = false;

(function tick(){document.getElementById('currentTime').textContent=new Date().toLocaleTimeString('fr-FR');setTimeout(tick,1000);})();

function setStatus(html){ document.getElementById('faceStatus').innerHTML = html; }

function attendreFaceApi(callback, tentatives) {
    tentatives = tentatives || 0;
    if (typeof faceapi !== 'undefined') {
        callback();
    } else if (tentatives > 100) {
        const btn = document.getElementById('btnArrivee');
        if (btn) {
            btn.innerHTML = '⚠️ Face ID indisponible — vérifiez votre connexion';
            btn.classList.add('btn-disabled');
            btn.disabled = true;
        }
    } else {
        setTimeout(function(){ attendreFaceApi(callback, tentatives + 1); }, 100);
    }
}

// ════════════════════════════════════════════════════════════
// 🔥 CORRECTIF #1 : Chargement PARALLÈLE des sources de modèles
// au lieu d'un essai séquentiel (qui attend chaque échec avant
// de tenter la source suivante).
// ════════════════════════════════════════════════════════════
async function chargerDepuisSource(src, timeoutMs) {
    const timeout = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('timeout')), timeoutMs)
    );
    const chargement = Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(src),
        faceapi.nets.faceLandmark68TinyNet.loadFromUri(src),
        faceapi.nets.faceRecognitionNet.loadFromUri(src),
    ]);
    await Promise.race([chargement, timeout]);
    return src;
}

async function chargerModeles() {
    const btn      = document.getElementById('btnArrivee');
    const spinner  = document.getElementById('btnSpinner');
    const label    = document.getElementById('btnLabel');

    if (label) label.textContent = 'Chargement Face ID…';

    // On tente d'abord le local seul (rapide si présent), avec un timeout court.
    // Si ça échoue, on tente TOUTES les sources CDN en parallèle et on prend
    // la première qui réussit — beaucoup plus rapide que du séquentiel.
    let loaded = false;
    try {
        await chargerDepuisSource(MODELS_PATH, 2500);
        loaded = true;
        console.log('✅ Modèles chargés depuis : local');
    } catch (e) {
        console.warn('❌ Local indisponible, tentative CDN en parallèle…');
        const cdnSources = [
            'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/',
            'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights',
            'https://justadudewhohacks.github.io/face-api.js/models'
        ];
        try {
            const gagnant = await Promise.any(
                cdnSources.map(src => chargerDepuisSource(src, 8000))
            );
            loaded = true;
            console.log('✅ Modèles chargés depuis :', gagnant);
        } catch (e2) {
            console.error('🚨 Toutes les sources ont échoué.', e2);
        }
    }

    if (!loaded) {
        console.error('🚨 Vérifiez le dossier ' + MODELS_PATH);
        if (btn) {
            btn.innerHTML = '⚠️ Modèles Face ID introuvables — F12 pour debug';
            btn.classList.add('btn-disabled');
            btn.disabled = true;
        }
        return;
    }

    faceApiReady = true;
    refDescriptor = await obtenirRefDescriptor();

    if (btn) {
        btn.disabled = false;
        if (spinner) spinner.style.display = 'none';
        if (label)   label.textContent     = 'ARRIVÉE (Face ID)';
    }
}

window.addEventListener('DOMContentLoaded', function() {
    attendreFaceApi(chargerModeles);
});

// ════════════════════════════════════════════════════════════
// 🔥 CORRECTIF #2 : Cache du descripteur de référence.
// Évite de refaire la détection sur l'image de référence
// à chaque chargement de page — gros gain de temps.
// ════════════════════════════════════════════════════════════
function cleCacheDescripteur() {
    return 'faceRefDescriptor_v' + FACE_TEMPLATE_VERSION + '_' + USER_ID;
}

async function obtenirRefDescriptor() {
    if (!HAS_TEMPLATE || !TEMPLATE_B64) return null;

    const cacheKey = cleCacheDescripteur();
    try {
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
            const arr = JSON.parse(cached);
            if (Array.isArray(arr) && arr.length > 0) {
                return new Float32Array(arr);
            }
        }
    } catch (e) {
        console.warn('Cache descripteur illisible, recalcul…', e);
    }

    const descriptor = await descriptorDepuisB64(TEMPLATE_B64);
    if (descriptor) {
        try {
            sessionStorage.setItem(cacheKey, JSON.stringify(Array.from(descriptor)));
        } catch (e) {
            console.warn('Impossible de mettre en cache le descripteur', e);
        }
    }
    return descriptor;
}

async function descriptorDepuisB64(b64) {
    return new Promise(resolve => {
        const img = new Image();
        img.onload = async () => {
            try {
                const det = await faceapi
                    .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({inputSize:320}))
                    .withFaceLandmarks(true).withFaceDescriptor();
                resolve(det ? det.descriptor : null);
            } catch { resolve(null); }
        };
        img.onerror = () => resolve(null);
        img.src = b64;
    });
}

async function verifierGPS() {
    const div = document.getElementById('gpsStatus');
    div.innerHTML = '<span class="spinner"></span> Vérification GPS…';
    try {
        const pos = await new Promise((ok,ko) =>
            navigator.geolocation
                ? navigator.geolocation.getCurrentPosition(ok,ko,{timeout:8000})
                : ko(new Error('GPS non disponible'))
        );
        document.getElementById('latitude').value  = pos.coords.latitude;
        document.getElementById('longitude').value = pos.coords.longitude;
        div.innerHTML = '<i class="fas fa-check-circle" style="color:#22c55e"></i> Position vérifiée';
        return true;
    } catch(e) {
        div.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#ef4444"></i> ' + e.message;
        return false;
    }
}

// ════════════════════════════════════════════════════════════
// 🔥 CORRECTIF #3 : Soumission AJAX (aucune navigation/rechargement).
// Le script + les modèles Face ID restent chargés en mémoire pour
// toute la durée de la session, quel que soit le nombre d'actions.
// ════════════════════════════════════════════════════════════
async function soumettreFormulaireAjax() {
    const form = document.getElementById('pointageForm');
    const formData = new FormData(form);
    formData.set('ajax', '1');
    try {
        const resp = await fetch(window.location.pathname, {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        });
        const data = await resp.json();
        if (data.success) {
            if (data.template_cree) {
                // Cas unique : le template vient d'être créé. HAS_TEMPLATE/TEMPLATE_B64
                // sont figés en JS pour cette page, un rechargement complet est requis
                // ici (une seule fois, pas à chaque pointage).
                window.location.reload();
                return;
            }
            await rafraichirCarte();
            appliquerEtatBouton();
        } else {
            afficherErreur(data.erreur || 'Erreur inconnue.');
        }
    } catch (e) {
        afficherErreur('Erreur réseau : ' + e.message);
    }
}

async function rafraichirCarte() {
    try {
        const resp = await fetch(window.location.pathname, { method: 'GET' });
        const html = await resp.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newCard = doc.querySelector('.pointage-card');
        const oldCard = document.querySelector('.pointage-card');
        if (newCard && oldCard) {
            oldCard.innerHTML = newCard.innerHTML;
        }
    } catch (e) {
        console.warn('Rafraîchissement partiel échoué, rechargement complet.', e);
        window.location.reload();
    }
}

function afficherErreur(msg) {
    const card = document.querySelector('.pointage-card');
    let alertDiv = card.querySelector('.alert-danger');
    if (!alertDiv) {
        alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger';
        const time = card.querySelector('.current-time');
        card.insertBefore(alertDiv, time ? time.nextSibling : card.firstChild);
    }
    alertDiv.textContent = msg;
}

// Réapplique l'état "prêt" du bouton Face ID après un rafraîchissement
// partiel de la carte (le HTML frais le renvoie toujours désactivé).
function appliquerEtatBouton() {
    const btn = document.getElementById('btnArrivee');
    if (!btn) return; // déjà pointé, pas de bouton arrivée affiché
    if (faceApiReady) {
        btn.disabled = false;
        btn.innerHTML = 'ARRIVÉE (Face ID)';
    } else {
        attendreFaceApi(appliquerEtatBouton);
    }
}

function confirmerDepart() {
    if(!confirm('Valider votre départ ? (Requis : 1h minimum)')) return;
    document.getElementById('actionType').value='depart';
    document.getElementById('methodeType').value='manuel';
    soumettreFormulaireAjax();
}
function actionPauseDebut() {
    if(!confirm('Démarrer la pause ? (Requis : 4h minimum)')) return;
    document.getElementById('actionType').value='pause_debut';
    document.getElementById('methodeType').value='manuel';
    soumettreFormulaireAjax();
}
function actionPauseFin() {
    document.getElementById('actionType').value='pause_fin';
    document.getElementById('methodeType').value='manuel';
    soumettreFormulaireAjax();
}

function demanderArrivee() { ouvrirFaceID(); }

function ouvrirFaceID() {
    if (!faceApiReady) { alert('Les modèles Face ID chargent encore. Patientez quelques secondes.'); return; }
    document.getElementById('faceModal').style.display = 'flex';
    setStatus('<span class="spinner"></span> Démarrage caméra…');
    navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:640,height:480}})
        .then(stream => {
            streamVideo = stream;
            const video = document.getElementById('faceVideo');
            video.srcObject = stream;
            video.onloadedmetadata = () => {
                video.play();
                setStatus('Regardez la caméra…');
                loopActive = true;
                lancerBoucleDetection(video);
            };
        })
        .catch(err => { alert('Erreur caméra : ' + err.message); fermerFaceID(); });
}

async function lancerBoucleDetection(video) {
    const overlay = document.getElementById('faceOverlay');
    const ctx     = overlay.getContext('2d');
    let stableFrames = 0;
    const NEEDED     = 3;
    while (loopActive) {
        await new Promise(r => requestAnimationFrame(r));
        if (!loopActive) break;
        overlay.width  = video.videoWidth  || 640;
        overlay.height = video.videoHeight || 480;
        let detection = null;
        try {
            detection = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({inputSize:320,scoreThreshold:0.5}))
                .withFaceLandmarks(true).withFaceDescriptor();
        } catch {}
        ctx.clearRect(0, 0, overlay.width, overlay.height);
        if (detection) {
            const b = detection.detection.box;
            ctx.strokeStyle='#22c55e'; ctx.lineWidth=3;
            ctx.strokeRect(b.x,b.y,b.width,b.height);
            stableFrames++;
            setStatus('<span class="spinner"></span> Visage détecté ('+stableFrames+'/'+NEEDED+')…');
            if (stableFrames >= NEEDED) {
                loopActive = false;
                await traiterCapture(video, detection);
                return;
            }
        } else {
            stableFrames = 0;
            setStatus('Aucun visage détecté — repositionnez-vous.');
        }
    }
}

async function genererSignature(scoreStr, timestamp, imagePreview) {
    // 🔥 scoreStr doit être une string formatée (ex: "0.4835")
    const payload = `${scoreStr}|${timestamp}|${imagePreview}|${USER_ID}`;
    const key = FACE_SIGNATURE_SECRET + '_' + USER_ID;
    const encoder = new TextEncoder();
    const keyData = encoder.encode(key);
    const msgData = encoder.encode(payload);
    const cryptoKey = await crypto.subtle.importKey(
        'raw', keyData, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']
    );
    const signature = await crypto.subtle.sign('HMAC', cryptoKey, msgData);
    return Array.from(new Uint8Array(signature))
        .map(b => b.toString(16).padStart(2, '0')).join('');
}

async function traiterCapture(video, detection) {
    const canvas = document.getElementById('faceCanvas');
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/jpeg', 0.9);
    fermerFaceID();

    let faceScore   = -1;
    let isFirstTime = !HAS_TEMPLATE;
    let faceSize    = 0;

    if (isFirstTime) {
        faceScore = 0;
        faceSize  = Math.min(detection.detection.box.width, detection.detection.box.height);
    } else if (refDescriptor) {
        const box = detection.detection.box;
        faceSize = Math.min(box.width, box.height);

        if (faceSize < FACE_MIN_FACE_SIZE) {
            alert('⚠️ Visage trop petit ou trop loin.\nRapprochez-vous de la caméra.');
            return;
        }

        faceScore = faceapi.euclideanDistance(refDescriptor, detection.descriptor);
    } else {
        faceScore = 1.0;
    }

    const gpsOk = await verifierGPS();
    if (!gpsOk) { alert('GPS invalide — pointage impossible.'); return; }

    const timestamp = Math.floor(Date.now() / 1000);
    const imagePreview = imageData.substring(0, 100);

    // 🔥 L7AL: N7addo l'score l 4 décimales bash ykoun nfs l'format f JS w PHP
    const scoreStr = faceScore.toFixed(FACE_SCORE_DECIMALS);
    const signature = await genererSignature(scoreStr, timestamp, imagePreview);

    document.getElementById('faceImage').value     = imageData;
    document.getElementById('faceScore').value     = scoreStr; // Sift l'score li m7addod
    document.getElementById('faceIsFirst').value   = isFirstTime ? '1' : '0';
    document.getElementById('faceSignature').value = signature;
    document.getElementById('faceTimestamp').value = timestamp;
    document.getElementById('faceSize').value      = Math.round(faceSize);
    document.getElementById('actionType').value    = 'arrivee';
    document.getElementById('methodeType').value   = 'face_id';
    await soumettreFormulaireAjax();
}

function fermerFaceID() {
    loopActive = false;
    document.getElementById('faceModal').style.display = 'none';
    if (streamVideo) { streamVideo.getTracks().forEach(t=>t.stop()); streamVideo=null; }
}
</script>
<?php include '../includes/footer.php'; ?>