<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header('Location: connexion.php');
    exit();
}

try {
    // Connexion à la base de données avec PDO
    $pdo = new PDO('mysql:host=localhost;dbname=PGE', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer l'ID de l'utilisateur connecté
    $user_id = $_SESSION['user_id'];

    // Requête pour récupérer les événements de l'utilisateur
    $sql = "SELECT * FROM evenements WHERE utilisateur_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Récupérer les événements
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // En cas d'erreur, afficher un message d'erreur
    echo "Erreur : " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Événements - EventBladi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    :root {
    --primary-color: #3498db;
    --secondary-color: #2ecc71;
    --text-color: #333;
    --background-color: #f4f6f7;
    --sidebar-width: 250px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Arial', sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background-color: var(--background-color);
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

/* Sidebar Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 800;
    display: none;
}

/* Sidebar Styles */
.sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: var(--sidebar-width);
            height: 100%;
            background-color: #ffffff;
            padding-top: 70px;
            z-index: 900;
            transition: all 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            border-right: 1px solid #e0e0e0;
        }
        .sidebar.active {
            left: 0;
        }
        .sidebar-user {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .sidebar-user i {
            font-size: 50px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .sidebar-menu {
            padding: 20px 0;
        }
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .sidebar-menu a:hover {
            background-color: #f0f0f0;
            border-left-color: var(--primary-color);
        }
        .close-sidebar {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 20px;
            color: #333;
            cursor: pointer;
        }

/* Header Styles */
.header {
    background-color: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
}

.header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 70px;
}

.logo {
    color: var(--primary-color);
    font-size: 1.8rem;
    font-weight: bold;
}

.nav {
    display: flex;
    gap: 20px;
    align-items: center;
}

.nav a {
    text-decoration: none;
    color: var(--text-color);
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav a:hover {
    color: var(--primary-color);
}

/* Hamburger Menu Icon */
.sidebar-toggle {
    cursor: pointer;
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-right: 15px;
}

/* Main Content */
.main-content {
    padding: 100px 0 60px;
}

.page-title {
    text-align: center;
    margin-bottom: 30px;
    color: var(--primary-color);
}

/* Events List */
.events-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.event-card {
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 20px;
    transition: transform 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
}

.event-card h3 {
    color: var(--primary-color);
    margin-bottom: 10px;
}

.event-card p {
    margin-bottom: 8px;
}

.event-actions {
    display: flex;
    justify-content: center;
    margin-top: 15px;
    width: 100%;
}

.btn-edit, .btn-delete {
    padding: 8px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    transition: background-color 0.3s ease;
    text-align: center;
    font-weight: 500;
}

.btn-edit {
    background-color: var(--secondary-color);
    margin-right: 5px;
    flex: 1;
}

.btn-edit:hover {
    background-color: #27ae60;
}

.btn-delete {
    background-color: #e74c3c;
    flex: 1;
    width: 100%;
    display: inline-block;
}

.btn-delete:hover {
    background-color: #c0392b;
}

/* Style spécifique pour le bouton supprimer autonome */
.event-actions .btn-delete {
    width: 100%;
    height:30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.event-actions .btn-delete i {
    margin-right: 5px;
}

/* Empty Message */
.empty-message {
    text-align: center;
    padding: 50px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.empty-message i {
    font-size: 3rem;
    color: #95a5a6;
    margin-bottom: 20px;
}

.empty-message h3 {
    margin-bottom: 10px;
    color: #34495e;
}

.empty-message p {
    color: #7f8c8d;
    margin-bottom: 20px;
}

.cta-button {
    display: inline-block;
    background-color: var(--primary-color);
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.cta-button:hover {
    background-color: #2980b9;
}

/* Footer */
.footer {
    background-color: #2c3e50;
    color: #ecf0f1;
    padding: 50px 0 20px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.footer-grid > div {
    display: flex;
    flex-direction: column;
}

.footer-grid h4 {
    color: #3498db;
    font-size: 1.2rem;
    margin-bottom: 15px;
    position: relative;
    padding-bottom: 10px;
}

.footer-grid h4::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background-color: #3498db;
}

.footer-grid p {
    margin-bottom: 10px;
    line-height: 1.6;
    color: #bdc3c7;
}

.footer-grid a {
    color: #ecf0f1;
    text-decoration: none;
    margin-bottom: 8px;
    transition: color 0.3s ease;
}

.footer-grid a:hover {
    color: #3498db;
}

/* Responsive Design */
@media (max-width: 768px) {
    .events-list {
        grid-template-columns: 1fr;
    }

    .footer-grid {
        grid-template-columns: 1fr;
    }
}

    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <span class="close-sidebar" id="close-sidebar"><i class="fas fa-times"></i></span>
        <div class="sidebar-user">
            <i class="fas fa-user-circle"></i>
            <h3><?= htmlspecialchars($_SESSION['nom']); ?></h3>
            <p><?= htmlspecialchars($_SESSION['email']); ?></p>
        </div>
        <div class="sidebar-menu">
            <a href="accueil.php"><i class="fas fa-home"></i> Accueil</a>
            <a href="mes_evenements.php"><i class="fas fa-calendar"></i> Mes Événements</a>
            <a href="mes_reservations.php"><i class="fas fa-ticket-alt"></i> Mes Réservations</a>
            
            <a href="deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </aside>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <h1 class="logo">EventBladi</h1>
            <nav class="nav">
                <div class="sidebar-toggle" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <a href="accueil.php">Accueil</a>
                <a href="evenements.php">Événements</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
            <h2 class="page-title">Mes Événements</h2>

            <?php if (empty($evenements)): ?>
                <div class="empty-message">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Aucun événement trouvé</h3>
                    <p>Vous n'avez pas encore créé d'événement.</p>
                    <a href="creer_evenement.php" class="cta-button">Créer un événement</a>
                </div>
            <?php else: ?>
                <div class="events-list">
                    <?php foreach ($evenements as $event): ?>
                        <div class="event-card">
                            <h3><?= htmlspecialchars($event['titre']); ?></h3>
                            <p><strong>Date :</strong> <?= date('d/m/Y ', strtotime($event['date_event'])); ?></p>
                            <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($event['description'])); ?></p>
                            <p><strong>Places disponibles :</strong> <?= $event['places_disponibles']; ?></p>
                            <p><strong>Créé le :</strong> <?= date('d/m/Y à H:i', strtotime($event['created_at'])); ?></p>
                            
                            <!-- Optionnel : bouton modifier/supprimer -->
                            <div class="event-actions">
                                
                                <a href="supprimer_evenement.php?id=<?= $event['id']; ?>" class="btn-delete" onclick="return confirm('Confirmer la suppression de cet événement ?');"><i class="fas fa-trash-alt"></i> Supprimer</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>EventBladi</h4>
                    <p>Plateforme de gestion d'événements moderne et intuitive.</p>
                </div>
                <div>
                    <h4>Liens Rapides</h4>
                    <a href="accueil.php">Accueil</a>
                    <a href="evenements.php">Événements</a>
                    <a href="contact.php">Contact</a>
                </div>
                <div>
                    <h4>Contactez-nous</h4>
                    <p>Email: support@eventbladi.com</p>
                    <p>Téléphone: +212 6 00 00 01 02</p>
                </div>
            </div>
            <div style="text-align:center; margin-top:30px;">
                &copy; 2025 EventBladi. Tous droits réservés.
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Éléments du DOM
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const closeSidebar = document.getElementById('close-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            // Fonction pour ouvrir le sidebar
            function openSidebar() {
                sidebar.classList.add('active');
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Empêche le défilement
            }
            
            // Fonction pour fermer le sidebar
            function closeSidebarFunc() {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
                document.body.style.overflow = ''; // Réactive le défilement
            }
            
            // Événements
            sidebarToggle.addEventListener('click', openSidebar);
            closeSidebar.addEventListener('click', closeSidebarFunc);
            overlay.addEventListener('click', closeSidebarFunc);
        });
    </script>
</body>
</html>
