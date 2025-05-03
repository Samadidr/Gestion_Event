<?php
// Démarrer la session pour accéder aux variables de session
session_start();
// Configuration de la base de données
$host = 'localhost';
$dbname = 'pge';
$username = 'root';
$password = '';
// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
// Récupérer les événements futurs
try {
    // Utilisation de la date actuelle pour filtrer les événements
    $sql = "SELECT * FROM evenements WHERE date_event >= CURDATE() ORDER BY date_event ASC LIMIT 3";
    $evenements = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des événements : " . $e->getMessage());
}
// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion d'Événements - Accueil</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8; /* Bleu moderne */
            --secondary-color: #34a853; /* Vert professionnel */
            --text-color: #333;
            --background-color: #f9f9f9;
            --sidebar-width: 280px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
        }
        .container {
            width: 95%;
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
    display: flex;
    align-items: center;
}

.logo img {
    max-height: 70px; /* Ajustez selon la taille de votre logo */
    width: 70px;
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
        .sidebar-toggle {
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 15px;
        }
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 120px 0 60px;
            text-align: center;
        }
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            animation: fadeInUp 1s ease 0.5s;
            opacity: 0;
            animation-fill-mode: forwards;
        }
        .btn {
            display: inline-block;
            background-color: white;
            color: var(--primary-color);
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease 0.7s;
            opacity: 0;
            animation-fill-mode: forwards;
        }
        .btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .hero-photos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        .hero-photos img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        .hero-photos img:hover {
            transform: scale(1.05);
        }
        /* Events Section */
        .events {
            background-color: white;
            padding: 60px 0;
        }
        .events h2 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
        }
        .event {
            background-color: var(--background-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .event:hover {
            transform: translateY(-5px);
        }
        /* Features Section */
        .features {
            background-color: var(--background-color);
            padding: 60px 0;
        }
        .features .container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .features-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .feature {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .feature:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .feature-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }
        .feature i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        /* Footer */
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 50px 0 20px;
        }
        .footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .footer .copyright {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #95a5a6;
            font-size: 0.9rem;
        }
        /* Animations */
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .features-row {
                flex-direction: column;
            }
            .hero-photos img {
                width: 90%;
                margin-bottom: 15px;
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
            <?php if ($isLoggedIn): ?>
                <h3><?= htmlspecialchars($_SESSION['nom']); ?></h3>
                <p><?= htmlspecialchars($_SESSION['email']); ?></p>
            <?php else: ?>
                <h3>Invité</h3>
                <p>Non connecté</p>
            <?php endif; ?>
        </div>
        <div class="sidebar-menu">
            <?php if ($isLoggedIn): ?>
                <a href="mes_evenements.php"><i class="fas fa-calendar"></i> Mes Événements</a>
                <a href="mes_reservations.php"><i class="fas fa-ticket-alt"></i> Mes Réservations</a>
                <a href="deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            <?php else: ?>
                <a href="evenements.php"><i class="fas fa-calendar"></i> Événements</a>
                <a href="inscription.html"><i class="fas fa-user-plus"></i> S'inscrire</a>
                <a href="connexion.php"><i class="fas fa-sign-in-alt"></i> Se connecter</a>
            <?php endif; ?>
        </div>
    </aside>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="images/logo_event.png" alt="EventBladi Logo">
            </div>
            <nav class="nav">
                <div class="sidebar-toggle" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <?php if (!$isLoggedIn): ?>
                    <a href="inscription.html">S'inscrire</a>
                    <a href="connexion.php">Se connecter</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <section class="hero">
        <div class="container">
            <h2>Gérez vos Événements Simplement</h2>
            <p>Créez, partagez et suivez vos événements en toute simplicité</p>
            <a href="evenements.php" class="btn">Explorer les Événements</a>
            <div class="hero-photos">
                <img src="images/event1.jpg" alt="Événement Professionnel" class="fade-in">
                <img src="images/event2.jpg" alt="Conférence" class="fade-in">
                <img src="images/event3.jpg" alt="Réunion d'Entreprise" class="fade-in">
                <img src="images/event4.jpg" alt="Atelier" class="fade-in">
            </div>
        </div>
    </section>
    <section class="events">
        <div class="container">
            <h2>Événements à venir</h2>
            <?php if (!empty($evenements)): ?>
                <?php foreach ($evenements as $evenement): ?>
                    <div class="event slide-up">
                        <h3><?= htmlspecialchars($evenement['titre']); ?></h3>
                        <p><?= htmlspecialchars($evenement['description']); ?></p>
                        <p><strong>Date :</strong> <?= htmlspecialchars($evenement['date_event']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun événement à venir pour le moment.</p>
            <?php endif; ?>
        </div>
    </section>
    <section class="features">
        <div class="container">
            <div class="features-row">
                <div class="feature">
                    <i class="fas fa-calendar-plus"></i>
                    <h3><a href="creer_eve.php" class="feature-link">Créer un Événement</a></h3>
                    <p>Planifiez vos événements en quelques clics.</p>
                </div>
                <div class="feature">
                    <i class="fas fa-ticket-alt"></i>
                    <h3><a href="reservation.php" class="feature-link">Réserver des Places</a></h3>
                    <p>Rejoignez des événements publics facilement.</p>
                </div>
            </div>
            <div class="features-row">
                <div class="feature">
                    <i class="fas fa-building"></i>
                    <h3><a href="salle_dispo.php" class="feature-link">Consulter les Salles</a></h3>
                    <p>Consultez les disponibilités des salles et réservez.</p>
                </div>
                <div class="feature">
                    <i class="fas fa-calendar-alt"></i>
                    <h3><a href="calendrier.php" class="feature-link">Calendrier des Événements</a></h3>
                    <p>Consultez un calendrier interactif des événements.</p>
                </div>
            </div>
        </div>
    </section>
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
                <div>
                <h4>Administration</h4>
                <a href="admin_login.php">Espace Admin</a>
                </div>
            </div>
            <div style="text-align:center; margin-top:30px;">
                &copy; 2025 EventBladi. Tous droits réservés.
            </div>
        </div>
    </footer>
    <!-- Script pour la sidebar -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const closeSidebar = document.getElementById('close-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                sidebar.classList.add('active');
                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            function closeSidebarFunc() {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }

            sidebarToggle.addEventListener('click', openSidebar);
            closeSidebar.addEventListener('click', closeSidebarFunc);
            overlay.addEventListener('click', closeSidebarFunc);
        });
    </script>
</body>
</html>