<?php
// setup_images.php - Installation complète des images

echo "<h1>Installation des images GestiPoint</h1>";

// =====================================================
// 1. CRÉATION DES DOSSIERS
// =====================================================
echo "<h2>1. Création des dossiers...</h2>";
$folders = [
    'assets/images/employes',
    'assets/images/captures',
    'assets/images/icons',
    'assets/uploads/justificatifs'
];

foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
        echo "✅ Dossier créé : $folder<br>";
    } else {
        echo "⚠️ Dossier existe déjà : $folder<br>";
    }
}

// =====================================================
// 2. CRÉATION DES FICHIERS .gitkeep
// =====================================================
echo "<h2>2. Création des fichiers .gitkeep...</h2>";
$gitkeep_files = [
    'assets/images/captures/.gitkeep',
    'assets/uploads/justificatifs/.gitkeep'
];

foreach ($gitkeep_files as $file) {
    if (!file_exists($file)) {
        file_put_contents($file, "# Dossier pour stocker les fichiers");
        echo "✅ Fichier créé : $file<br>";
    } else {
        echo "⚠️ Fichier existe déjà : $file<br>";
    }
}

// =====================================================
// 3. CRÉATION DES ICÔNES SVG
// =====================================================
echo "<h2>3. Création des icônes SVG...</h2>";

$icons = [
    'qr-code.svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1e3c72" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><line x1="14" y1="14" x2="21" y2="14"></line><line x1="14" y1="18" x2="21" y2="18"></line><line x1="18" y1="14" x2="18" y2="21"></line><line x1="21" y1="18" x2="21" y2="21"></line><line x1="14" y1="21" x2="17" y2="21"></line></svg>',
    
    'face-id.svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1e3c72" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8V6a2 2 0 0 1 2-2h2"></path><path d="M20 8V6a2 2 0 0 0-2-2h-2"></path><path d="M4 16v2a2 2 0 0 0 2 2h2"></path><path d="M20 16v2a2 2 0 0 1-2 2h-2"></path><circle cx="12" cy="12" r="3"></circle></svg>',
    
    'gps.svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#1e3c72" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>'
];

foreach ($icons as $name => $content) {
    $path = 'assets/images/icons/' . $name;
    if (!file_exists($path)) {
        file_put_contents($path, $content);
        echo "✅ Icône créée : $name<br>";
    } else {
        echo "⚠️ Icône existe déjà : $name<br>";
    }
}

// =====================================================
// 4. TÉLÉCHARGEMENT DES PHOTOS DES EMPLOYÉS
// =====================================================
echo "<h2>4. Téléchargement des photos des employés...</h2>";

$employees = [
    ['name' => 'karim_benjelloun', 'sex' => 'men', 'id' => 1],
    ['name' => 'nadia_elfassi', 'sex' => 'women', 'id' => 2],
    ['name' => 'mehdi_alaoui', 'sex' => 'men', 'id' => 3],
    ['name' => 'sara_cherkaoui', 'sex' => 'women', 'id' => 4],
    ['name' => 'youssef_tazi', 'sex' => 'men', 'id' => 5],
];

foreach ($employees as $emp) {
    $url = "https://randomuser.me/api/portraits/" . $emp['sex'] . "/" . $emp['id'] . ".jpg";
    $path = "assets/images/employes/" . $emp['name'] . ".jpg";
    
    if (!file_exists($path)) {
        $image = @file_get_contents($url);
        if ($image) {
            file_put_contents($path, $image);
            echo "✅ Photo téléchargée : " . $emp['name'] . ".jpg<br>";
        } else {
            echo "❌ Erreur : " . $emp['name'] . "<br>";
        }
    } else {
        echo "⚠️ Photo existe déjà : " . $emp['name'] . ".jpg<br>";
    }
}

// =====================================================
// 5. CRÉATION D'UNE PHOTO PAR DÉFAUT
// =====================================================
echo "<h2>5. Création de la photo par défaut...</h2>";

$default_avatar = 'assets/images/employes/default.png';
if (!file_exists($default_avatar)) {
    // Créer une image PNG simple avec GD
    $img = imagecreate(150, 150);
    $bg = imagecolorallocate($img, 203, 213, 225); // #cbd5e0
    $text_color = imagecolorallocate($img, 100, 116, 139); // #64748b
    imagefill($img, 0, 0, $bg);
    imagestring($img, 5, 45, 65, "User", $text_color);
    imagepng($img, $default_avatar);
    imagedestroy($img);
    echo "✅ Image par défaut créée : default.png<br>";
} else {
    echo "⚠️ Image par défaut existe déjà<br>";
}

// =====================================================
// 6. CRÉATION D'UN FAVICON
// =====================================================
echo "<h2>6. Création du favicon...</h2>";

$favicon = 'assets/images/favicon.ico';
if (!file_exists($favicon)) {
    // Créer un favicon simple
    $img = imagecreate(32, 32);
    $bg = imagecolorallocate($img, 30, 60, 114); // #1e3c72
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 5, 8, 12, "G", $white);
    imagepng($img, str_replace('.ico', '.png', $favicon));
    // Convertir en ICO (simplifié)
    copy(str_replace('.ico', '.png', $favicon), $favicon);
    imagedestroy($img);
    echo "✅ Favicon créé : favicon.ico<br>";
}

// =====================================================
// 7. CRÉATION DU LOGO
// =====================================================
echo "<h2>7. Création du logo...</h2>";

$logo = 'assets/images/logo.png';
if (!file_exists($logo)) {
    $img = imagecreate(200, 60);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $primary = imagecolorallocate($img, 30, 60, 114);
    $accent = imagecolorallocate($img, 102, 126, 234);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 5, 10, 22, "GestiPoint", $primary);
    imagepng($img, $logo);
    imagedestroy($img);
    echo "✅ Logo créé : logo.png<br>";
}

echo "<hr>";
echo "<h2 style='color: green;'>✅ Installation terminée avec succès !</h2>";
echo "<p>Tu peux maintenant utiliser toutes les images dans ton projet.</p>";
?>