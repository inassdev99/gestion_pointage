// Menu mobile toggle
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter la classe fade-in aux éléments
    document.querySelectorAll('.stat-card, .card').forEach(el => {
        el.classList.add('fade-in');
    });
});

// Fonction de confirmation
function confirmer(message) {
    return confirm(message || 'Êtes-vous sûr de vouloir effectuer cette action ?');
}

// Auto-fermeture des alertes
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);