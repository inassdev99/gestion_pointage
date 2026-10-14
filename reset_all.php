<?php
// Détruire la session
session_start();
session_destroy();

// Supprimer les cookies de session
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
    }
}

// Rediriger vers login
header("Location: login.php");
exit();
?>