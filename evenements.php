<?php
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

// Récupérer les événements futurs et non expirés
try {
    $sql = "SELECT * FROM evenements WHERE date_event >= CURDATE() ORDER BY date_event ASC";
    $evenements = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des événements : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - EventBladi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --text-color: #333;
            --background-color: #f4f6f7;
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

        /* Hero Section for Events */
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            text-align: center;
        }

        .event:hover {
            transform: translateY(-5px);
        }

        .event-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .event-btn:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
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

.footer .copyright {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    color: #95a5a6;
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
}
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">EventBladi</h1>
            <nav class="nav">
                <a href="accueil.php"><i class="fas fa-home"></i> Accueil</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h2>Découvrez nos Événements</h2>
        </div>
    </section>

    <section class="events">
        <div class="container">
            <h2>Événements à venir</h2>
            <?php if (!empty($evenements)): ?>
                <?php foreach ($evenements as $evenement): ?>
                    <div class="event">
                        <h3><?= htmlspecialchars($evenement['titre']); ?></h3>
                        <p><?= htmlspecialchars($evenement['description']); ?></p>
                        <p><strong>Date :</strong> <?= htmlspecialchars($evenement['date_event']); ?></p>
                        <p><strong>Places disponibles :</strong> <?= htmlspecialchars($evenement['places_disponibles']); ?></p>
                        <a href="reservation.php?id=<?= $evenement['id']; ?>" class="event-btn">Réserver</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">Aucun événement à venir pour le moment.</p>
            <?php endif; ?>
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
            </div>
            <div style="text-align:center; margin-top:30px;">
                &copy; 2025 EventBladi. Tous droits réservés.
            </div>
        </div>
    </footer>
</body>
</html>