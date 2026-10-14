/**
 * GestiPoint - Scripts principaux
 */

// Attendre le chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    
    // Animation fade-in sur les cartes
    document.querySelectorAll('.card, .stat-card, .action-card').forEach(el => {
        el.classList.add('fade-in');
    });
    
    // Auto-fermeture des alertes après 5 secondes
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    // Menu mobile toggle
    const menuBtn = document.querySelector('.mobile-menu-btn');
    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('open');
        });
    }
    
    // Activer les tooltips Bootstrap
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltips.length && typeof bootstrap !== 'undefined') {
        tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
    }
});

/**
 * Fonction de confirmation
 */
function confirmer(message) {
    return confirm(message || 'Êtes-vous sûr de vouloir effectuer cette action ?');
}

/**
 * Formater la date
 */
function formaterDate(date) {
    let d = new Date(date);
    return d.toLocaleDateString('fr-FR');
}

/**
 * Formater l'heure
 */
function formaterHeure(heure) {
    if (!heure) return '--:--';
    return heure.substring(0, 5);
}

/**
 * Afficher un message de notification
 */
function notifier(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    
    const container = document.querySelector('.main-content');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.style.transition = 'opacity 0.5s';
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);
    }
}

/**
 * Charger un fichier via AJAX
 */
function charger(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Erreur:', error));
}

/**
 * Envoyer des données via AJAX (POST)
 */
function envoyer(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => callback(data))
    .catch(error => console.error('Erreur:', error));
}

/**
 * Exporter vers Excel
 */
function exporterExcel(url) {
    window.location.href = url;
}

/**
 * Exporter vers PDF
 */
function exporterPDF(url) {
    window.open(url, '_blank');
}

/**
 * Imprimer la page
 */
function imprimer() {
    window.print();
}