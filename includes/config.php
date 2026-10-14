<?php
$host = 'localhost';
$dbname = 'gestion_pointage';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Casablanca');
?><?php
$host = 'localhost';
$dbname = 'gestion_pointage';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Synchroniser le fuseau horaire entre PHP et MySQL
    $pdo->exec("SET time_zone = '" . date('P') . "'");
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Casablanca');

// ═══ FACE++ CONFIGURATION ═══
define('FACE_API_KEY', 'VOTRE_API_KEY_ICI');
define('FACE_API_SECRET', 'VOTRE_API_SECRET_ICI');
define('FACE_API_URL', 'https://api-cn.faceplusplus.com/facepp/v3/');

// ═══ OU ═══
// Pour la version US (si vous préférez)
// define('FACE_API_URL', 'https://api-us.faceplusplus.com/facepp/v3/');

?>