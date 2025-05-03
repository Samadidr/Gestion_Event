<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $utilisateur_id = $_SESSION['user_id'];
} else {
    echo "<script>alert('Veuillez vous connecter avant de créer un événement.'); window.location.href='connexion.php';</script>";
    exit;
}
// Connexion à la base de données
require 'config.php';
$pdo = new PDO("mysql:host=localhost;dbname=pge;charset=utf8", "root", "");

// Récupérer les salles disponibles
$stmt = $pdo->query("SELECT id, nom, capacite FROM salles");
$salles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Événement - EventBladi</title>
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding: 0;
        }

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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
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

        .form-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 100px 15px 50px;
        }

        .form-container .form-container-inner {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }

        .form-container h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 600;
        }

        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, 
        .form-group textarea:focus, 
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .btn-primary {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .back-home-btn {
            display: block;
            text-align: center;
            background-color: var(--secondary-color);
            color: white;
            padding: 12px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .back-home-btn:hover {
            background-color: #27ae60;
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
            
            .form-container .form-container-inner {
                margin: 0 15px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">EventBladi</h1>
            <nav class="nav">
                <a href="accueil.php">Accueil</a>
            </nav>
        </div>
    </header>

    <div class="form-container">
        <div class="form-container-inner">
            <h2>Créer un Événement</h2>
            <form action="" method="POST" id="eventForm">
                <div class="form-group">
                    <label for="titre">Titre de l'Événement :</label>
                    <input type="text" id="titre" name="titre" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="description">Description :</label>
                    <textarea id="description" name="description" required maxlength="500" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="date_event">Date de l'Événement :</label>
                    <input type="date" id="date_event" name="date_event" required min="<?= date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="heure_debut">Heure de début :</label>
                    <input type="time" id="heure_debut" name="heure_debut" required>
                </div>
                <div class="form-group">
                    <label for="duree">Durée (en minutes) :</label>
                    <input type="number" id="duree" name="duree" min="15" required>
                </div>
                <div class="form-group">
                    <label for="salle_id">Salle :</label>
                    <select id="salle_id" name="salle_id" required>
                        <option value="">-- Sélectionnez une salle --</option>
                        <?php foreach ($salles as $salle): ?>
                            <option value="<?= $salle['id']; ?>" data-capacite="<?= $salle['capacite']; ?>">
                                <?= htmlspecialchars($salle['nom']) . " (Capacité : " . $salle['capacite'] . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="places_disponibles">Nombre de places disponibles :</label>
                    <input type="number" id="places_disponibles" name="places_disponibles" min="1" required>
                </div>
                <div class="form-group">
                    <label for="prix_ticket">Prix du Ticket (MAD)</label>
                    <input type="number" id="prix_ticket" name="prix_ticket" step="0.01" min="0" required>
                </div>
                <button type="submit" class="btn-primary">Créer l'Événement</button>
            </form>
            <a href="accueil.php" class="back-home-btn">Retour à l'accueil</a>
        </div>
    </div>
    
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
    
    <script>
        document.getElementById('salle_id').addEventListener('change', function () {
            var selectedOption = this.options[this.selectedIndex];
            var capaciteSalle = selectedOption.getAttribute('data-capacite');
            var placesInput = document.getElementById('places_disponibles');
            placesInput.setAttribute('max', capaciteSalle);
            placesInput.value = Math.min(placesInput.value, capaciteSalle);
        });

        document.getElementById('eventForm').addEventListener('submit', function(e) {
            var titre = document.getElementById('titre').value.trim();
            var description = document.getElementById('description').value.trim();
            var dateEvent = new Date(document.getElementById('date_event').value);
            var today = new Date();
            var salleId = document.getElementById('salle_id').value;
            var places = document.getElementById('places_disponibles').value;
            var prixTicket = document.getElementById('prix_ticket').value;
            var heureDébut = document.getElementById('heure_debut').value;
            var duree = document.getElementById('duree').value;

            if (titre.length > 100) {
                alert('Le titre ne doit pas dépasser 100 caractères.');
                e.preventDefault();
                return;
            }

            if (description.length > 500) {
                alert('La description ne doit pas dépasser 500 caractères.');
                e.preventDefault();
                return;
            }

            if (dateEvent < today) {
                alert('La date de l\'événement doit être dans le futur.');
                e.preventDefault();
                return;
            }

            if (!heureDébut) {
                alert('Veuillez spécifier l\'heure de début de l\'événement.');
                e.preventDefault();
                return;
            }
            
            if (duree < 15) {
                alert('La durée de l\'événement doit être d\'au moins 1 minutes.');
                e.preventDefault();
                return;
            }

            if (!salleId) {
                alert('Veuillez sélectionner une salle.');
                e.preventDefault();
                return;
            }

            if (places < 1) {
                alert('Le nombre de places doit être au moins de 1.');
                e.preventDefault();
                return;
            }

            if (prixTicket < 0) {
                alert('Le prix du ticket ne peut pas être négatif.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titre = $_POST["titre"];
    $description = $_POST["description"];
    $date_event = $_POST["date_event"] . ' ' . $_POST["heure_debut"] . ':00';
    $heure_debut = $_POST["heure_debut"];  
    $duree = $_POST["duree"];              
    $places_disponibles = $_POST["places_disponibles"];
    $salle_id = $_POST["salle_id"];
    $prix_ticket = $_POST["prix_ticket"];

    // Vérifier la capacité de la salle
    $stmt = $pdo->prepare("SELECT capacite FROM salles WHERE id = :salle_id");
    $stmt->bindParam(":salle_id", $salle_id);
    $stmt->execute();
    $salle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$salle) {
        echo "<script>alert('Erreur : Salle introuvable.');</script>";
        exit;
    }

    if ($places_disponibles > $salle['capacite']) {
        echo "<script>alert('Erreur : Le nombre de places dépasse la capacité de la salle.');</script>";
        exit;
    }

    // Insérer l'événement avec les nouveaux champs
    $sql = "INSERT INTO evenements (titre, description, date_event, heure_debut, duree, places_disponibles, created_at, salle_id, prix_ticket, utilisateur_id) 
            VALUES (:titre, :description, :date_event, :heure_debut, :duree, :places_disponibles, NOW(), :salle_id, :prix_ticket, :utilisateur_id)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":titre", $titre);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":date_event", $date_event);
    $stmt->bindParam(":heure_debut", $heure_debut);  // Nouveau paramètre
    $stmt->bindParam(":duree", $duree);              // Nouveau paramètre
    $stmt->bindParam(":places_disponibles", $places_disponibles);
    $stmt->bindParam(":salle_id", $salle_id);
    $stmt->bindParam(":prix_ticket", $prix_ticket);
    $stmt->bindParam(":utilisateur_id", $utilisateur_id);

    if ($stmt->execute()) {
        echo "<script>alert('Événement ajouté avec succès !'); window.location.href='evenements.php';</script>";
    } else {
        echo "<script>alert('Erreur lors de l\'ajout de l\'événement.');</script>";
    }
}
?>