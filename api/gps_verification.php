<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$latitude = $data['latitude'] ?? 0;
$longitude = $data['longitude'] ?? 0;
$employe_id = $data['employe_id'] ?? 0;

if (!$latitude || !$longitude) {
    echo json_encode(['success' => false, 'message' => 'Position GPS non disponible']);
    exit();
}

// Récupérer les zones GPS autorisées
$stmt = $pdo->query("SELECT * FROM zone_gps");
$zones = $stmt->fetchAll();

$est_autorise = false;
$zone_trouvee = null;

foreach ($zones as $zone) {
    // Calcul de la distance entre le point et le centre de la zone (formule de Haversine)
    $distance = calculerDistance($latitude, $longitude, $zone['latitude'], $zone['longitude']);
    
    if ($distance <= $zone['rayon']) {
        $est_autorise = true;
        $zone_trouvee = $zone;
        break;
    }
}

echo json_encode([
    'success' => $est_autorise,
    'zone' => $zone_trouvee ? $zone_trouvee['nom'] : null,
    'message' => $est_autorise ? 'Position autorisée' : 'Vous êtes hors de la zone autorisée'
]);

function calculerDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // mètres
    
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
?>