/**
 * GestiPoint - Scripts de pointage (QR code, GPS)
 */

let html5QrCode;
let actionEnCours = 'arrivee';
let positionGPS = null;

/**
 * Récupérer la position GPS
 */
function getPosition() {
    return new Promise((resolve, reject) => {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(resolve, reject);
        } else {
            reject("GPS non disponible sur ce navigateur");
        }
    });
}

/**
 * Vérifier GPS avant pointage
 */
async function verifierGPS(action) {
    const gpsStatus = document.getElementById('gpsStatus');
    if (gpsStatus) {
        gpsStatus.innerHTML = '<div class="loading"></div> Vérification GPS en cours...';
        gpsStatus.style.display = 'block';
    }
    
    try {
        const position = await getPosition();
        positionGPS = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
        };
        
        if (document.getElementById('latitude')) {
            document.getElementById('latitude').value = positionGPS.latitude;
            document.getElementById('longitude').value = positionGPS.longitude;
        }
        
        if (document.getElementById('actionType')) {
            document.getElementById('actionType').value = action;
        }
        
        if (gpsStatus) {
            gpsStatus.innerHTML = `<i class="fas fa-check-circle" style="color: #10b981;"></i> ✅ Position validée : ${positionGPS.latitude.toFixed(4)}, ${positionGPS.longitude.toFixed(4)}`;
            setTimeout(() => {
                gpsStatus.style.display = 'none';
            }, 3000);
        }
        
        return true;
    } catch(error) {
        if (gpsStatus) {
            gpsStatus.innerHTML = `<i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> ⚠️ ${error.message || error}`;
        }
        return false;
    }
}

/**
 * Ouvrir le scanner QR code
 */
function ouvrirScanner(action) {
    actionEnCours = action;
    const modal = document.getElementById('qrModal');
    if (modal) {
        modal.style.display = 'flex';
    }
    
    const qrReader = document.getElementById('qr-reader');
    if (qrReader) {
        qrReader.innerHTML = '';
    }
    
    html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        onScanSuccess,
        onScanError
    ).catch(err => {
        console.error("Erreur démarrage caméra:", err);
        alert("Impossible d'accéder à la caméra. Vérifiez les permissions.");
        fermerScanner();
    });
}

/**
 * Fermer le scanner QR code
 */
function fermerScanner() {
    const modal = document.getElementById('qrModal');
    if (modal) {
        modal.style.display = 'none';
    }
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().catch(err => console.error(err));
    }
}

/**
 * Succès du scan QR code
 */
function onScanSuccess(decodedText) {
    fermerScanner();
    
    const gpsStatus = document.getElementById('gpsStatus');
    if (gpsStatus) {
        gpsStatus.innerHTML = '<div class="loading"></div> Vérification QR code...';
        gpsStatus.style.display = 'block';
    }
    
    // Envoyer au serveur
    fetch('../api/qrcode_scanner.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ qr_code: decodedText })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (document.getElementById('methodeType')) {
                document.getElementById('methodeType').value = 'qrcode';
            }
            if (document.getElementById('actionType')) {
                document.getElementById('actionType').value = actionEnCours;
            }
            
            // Vérifier GPS avant soumission
            verifierGPS(actionEnCours).then(valide => {
                if (valide) {
                    document.getElementById('pointageForm').submit();
                } else {
                    if (gpsStatus) {
                        gpsStatus.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> ❌ Position GPS non valide pour le pointage';
                    }
                    alert("❌ Position GPS non valide pour le pointage");
                }
            });
        } else {
            if (gpsStatus) {
                gpsStatus.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> ❌ QR code invalide';
            }
            alert("QR code invalide : " + data.message);
        }
    })
    .catch(error => {
        if (gpsStatus) {
            gpsStatus.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> ❌ Erreur de communication';
        }
        console.error(error);
        alert("Erreur de communication avec le serveur");
    });
}

/**
 * Erreur du scan QR code
 */
function onScanError(error) {
    console.log("Erreur scan: " + error);
}

/**
 * Mettre à jour l'heure en temps réel
 */
function updateCurrentTime() {
    const timeElement = document.getElementById('currentTime');
    if (timeElement) {
        const now = new Date();
        timeElement.innerHTML = now.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }
}

// Démarrer la mise à jour de l'heure
setInterval(updateCurrentTime, 1000);
updateCurrentTime();