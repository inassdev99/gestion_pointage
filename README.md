⏱️ GestiPoint
Le pointage des employés, centralisé dans une plateforme web.
Présences · Pauses · Absences · Suivi RH




Une application de gestion de pointage conçue pour remplacer les feuilles papier et faciliter le suivi quotidien des équipes.
Présentation • Fonctionnalités • Interfaces • Installation • Structure
</div>

🎯 Présentation
GestiPoint réunit le pointage, les demandes des employés et les outils de suivi RH dans une même application. Chaque profil dispose d'un espace adapté : l'employé enregistre sa présence, le manager suit les équipes, l'administrateur gère les paramètres et le viewer consulte les informations.
Le projet comprend des mécanismes de reconnaissance faciale, de vérification GPS et de contrôle des adresses IP. Leur fonctionnement dépend de la configuration, des données et des autorisations du navigateur.
✨ Fonctionnalités
Domaine	Fonctions présentes dans le code
Pointage	Arrivée, départ, début et fin de pause ; calcul du temps de travail
Identification	Connexion par identifiant et mot de passe ; interface de reconnaissance faciale
QR code	Endpoints de génération et de lecture, à vérifier lors de l'intégration
Contrôles	Zones GPS, adresses IP autorisées, alertes de pointage suspect
Ressources humaines	Employés, départements et suivi des équipes
Demandes	Absences, congés, déplacements et réclamations
Communication	Notifications destinées aux employés
Suivi	Tableaux de bord, historique, rapports et statistiques selon le rôle
Exports	Export compatible Excel et page de rapport imprimable en PDF


Les fonctionnalités sont recensées à partir des fichiers fournis. Leur validation de bout en bout nécessite la base de données du projet.

👥 Espaces utilisateurs
Profil	Utilisation principale	Dossier
Administrateur	Gestion des employés, suivi des pointages, alertes et configuration GPS/IP	admin/
Manager / RH	Suivi des équipes, traitement des demandes et rapports	Manager/
Employé	Pointage, profil personnel, demandes et notifications	employe/
Viewer	Consultation des tableaux de bord et des informations de suivi	viewer/


Les droits effectifs sont définis par les contrôles PHP de chaque page.
🖼️ Interfaces de l’application
Les captures suivantes ont été fournies depuis l’application. Certaines vues présentent une période sans pointages ; elles illustrent aussi les états vides de l’interface.
Accueil
Page de présentation de GestiPoint : aperçu de la solution, accès à la connexion et mise en avant des fonctionnalités.

Connexion
Formulaire de connexion avec identifiant et mot de passe, et accès rapide aux profils de démonstration.

Tableau de bord administrateur
Vue synthétique des employés actifs, présents, retards et absences, avec une liste des employés et des pointages du jour.

Gestion du personnel
Liste des employés avec matricule, département et statut. Les actions permettent d’ajouter, modifier ou supprimer un employé.

Espace employé
Accueil personnel de l’employé avec un accès direct à la page de pointage.

Pointage et reconnaissance faciale
Interface de pointage affichant l’heure, le bouton « ARRIVÉE (Face ID) », l’état de la pause et la mention de vérification GPS automatique. La capture montre l’interface avant pointage, sans résultat de vérification faciale.

Enregistrement d’une absence
Fenêtre de création d’une action pour un employé : type d’action, dates, motif et statut de l’absence.

Historique des pointages
Consultation des pointages par mois avec récapitulatif et détail. La période illustrée ne contient aucun pointage.

Rapports et statistiques
Rapports filtrés par période, indicateurs de présence, absences par type et boutons d’export Excel et PDF. Les indicateurs reflètent les données disponibles au moment de la capture.

📐 Conception UML
Diagramme de cas d’utilisation
Ce diagramme présente les acteurs employé, administrateur / RH et visiteur, ainsi que les interactions avec le système de sécurité. Il décrit notamment le pointage, les pauses, la consultation de l’historique, la gestion des employés et départements, les demandes, les notifications et les vérifications de visage, IP et GPS. Il représente la conception fonctionnelle ; il ne constitue pas une validation des contrôles de sécurité.

Modèle de classes — fichier source
Le fichier [diaggeti.uml](docs/uml/diaggeti.uml) contient un projet XML au format historique StarUML. Il comprend notamment les classes utilisateur, admin, employe, viwer, Departement, Pointage, Absence, Reclamation, Alerte, Notification, VerificationFace, ZoneGPS et IPAutorisee. Des variantes de classes sont également présentes dans le modèle.
Ouvrir ce fichier avec une version ou un outil compatible avec le format StarUML historique pour consulter et modifier le diagramme. Ce modèle de conception ne remplace pas l’export SQL nécessaire à l’installation.
🛠️ Technologies
Couche	Technologies
Backend	PHP, sessions, PDO
Base de données	MySQL
Frontend	HTML, CSS, JavaScript, Bootstrap 5
Interface	Font Awesome, police Manrope
Reconnaissance faciale	face-api.js ; modèles chargés localement ou depuis des sources distantes
Échanges client/serveur	Endpoints PHP dans api/


🚀 Installation locale
1. Préparer l'environnement
Utiliser un environnement tel que XAMPP avec Apache, PHP et MySQL, ainsi que l'extension PHP pdo_mysql. Un navigateur récent et une connexion Internet sont utiles pour les bibliothèques chargées depuis des CDN.
2. Placer les fichiers
Décompresser le projet dans le dossier web :
C:\xampp\htdocs\gestion_pointage\
Démarrer Apache et MySQL depuis le panneau XAMPP.
3. Restaurer la base de données
Créer une base nommée gestion_pointage dans phpMyAdmin puis importer l'export SQL correspondant à cette version du projet.
L'archive fournie ne contient aucun fichier .sql. Il faut récupérer cet export depuis l'environnement de développement original avant de pouvoir utiliser les pages connectées. Créer une base vide ne suffit pas : le schéma et les données attendus sont nécessaires.

4. Configurer la connexion
Adapter les valeurs présentes dans includes/config.php à l'environnement local :
$host = 'localhost';
$dbname = 'gestion_pointage';
$username = 'root';
$password = ''; // Exemple local uniquement ; adapter à votre serveur.
Ce fichier contient actuellement deux blocs de connexion PDO : vérifier les deux occurrences lors d'un changement de paramètres.
5. Ouvrir l'application
http://localhost/gestion_pointage/
http://localhost/gestion_pointage/login.php
Utiliser un compte local de test correspondant aux données importées. Le fichier de connexion contient aussi des comptes de démonstration codés en dur ; leurs mots de passe ne sont pas reproduits dans cette documentation.
6. Préparer la caméra et la localisation
Autoriser la caméra et la géolocalisation dans le navigateur. Pour une utilisation distante, servir l'application en HTTPS ; localhost convient généralement au développement local.
Le dossier local prévu pour les modèles est assets/models/face-api/, absent de l'archive. La page de pointage prévoit des sources distantes de secours : vérifier leur accessibilité, ou installer les modèles attendus localement. Les captures sont enregistrées dans assets/images/captures/, qui doit être accessible en écriture au serveur PHP.
📁 Structure du projet
gestion_pointage/
├── admin/                  # Administration, alertes, rapports et paramètres
├── Manager/                # Espace manager / RH
├── employe/                # Espace personnel et pointage
├── viewer/                 # Consultation
├── api/                    # Visage, GPS, QR code et notifications
├── includes/               # Configuration, authentification et fonctions
├── assets/
│   ├── css/                # Styles
│   ├── js/                 # Scripts et bibliothèque face-api.js
│   └── images/captures/    # Captures faciales ; données privées
├── docs/
│   ├── screenshots/        # Les 10 images fournies
│   └── uml/diaggeti.uml     # Modèle UML source
├── index.php               # Accueil
├── login.php               # Connexion
├── register.php            # Inscription
├── logout.php              # Déconnexion
└── README.md
Cette arborescence résume les dossiers principaux ; les illustrations de l'accueil et les scripts utilitaires restent également à la racine.
🔎 Points à connaître sur cette version
- Casse des chemins : le dossier livré est Manager/, alors que plusieurs liens utilisent manager/. Harmoniser les chemins avant un déploiement sur un système sensible à la casse, notamment Linux.
- Export PDF : le fichier export_pdf.php produit une page HTML imprimable ; utiliser la fonction « Enregistrer en PDF » du navigateur.
- Reconnaissance faciale : il s'agit d'une intégration web basée sur face-api.js, sans certification équivalente au Face ID d'Apple. Tester les refus et les acceptations avec plusieurs utilisateurs.
- Configuration Face++ : des constantes de configuration sont présentes dans includes/config.php ; elles ne démontrent pas qu'un service Face++ est utilisé par le parcours actif.
- Base de données : le schéma complet et le nombre exact de tables ne peuvent pas être certifiés sans l'export SQL. Le code de connexion référence notamment utilisateur.
🔐 Avant une publication du code ou une mise en production
Cette version est un projet de démonstration à préparer avant un usage réel :
- Retirer les comptes codés en dur et remplacer la comparaison directe des mots de passe par password_hash() et password_verify().
- Externaliser les secrets et renforcer la validation serveur des contrôles de pointage ; un score facial transmis par le client ne constitue pas, à lui seul, une preuve fiable d'identité.
- Vérifier les autorisations, la protection CSRF et la validation des données pour chaque opération.
- Exclure les photographies, captures faciales et données personnelles d'un dépôt public.
- Protéger ou retirer les scripts utilitaires sensibles, notamment reset_all.php, avant un déploiement.
🧭 Évolutions possibles
- Fournir un schéma SQL versionné et des données de démonstration anonymisées.
- Centraliser la configuration et uniformiser les noms de dossiers.
- Ajouter des tests sur les pointages, les pauses et les permissions.
- Améliorer la validation de la reconnaissance faciale et la gestion des erreurs GPS.
- Étendre l'expérience mobile et automatiser les rapports.
