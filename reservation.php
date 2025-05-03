
<?php
session_start();
require_once 'config.php';

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Requête avec jointure pour récupérer les noms des salles
$evenements = $pdo->query("
    SELECT e.*, s.nom AS salle_nom 
    FROM evenements e 
    JOIN salles s ON e.salle_id = s.id
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver des Places - EventBladi</title>
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
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
        }
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        header .container {
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
        nav a {
    text-decoration: none;
    color: var(--text-color);
    font-weight: 500;
    margin-left: 20px;
    position: relative;
    padding: 5px 0;
    transition: color 0.3s ease;
}

nav a::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0%;
    height: 2px;
    background-color: var(--primary-color);
    transition: width 0.3s ease;
}

nav a:hover {
    color: var(--primary-color);
}

nav a:hover::after {
    width: 100%;
}

        .container {
            width: 90%;
            margin: 0 auto;
            padding: 20px 15px;
        }
        .event-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background-color: var(--primary-color);
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        
        .reservation-summary {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .total-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-top: 10px;
        }
        .error-message {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .notification {
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
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
    <header>
        <div class="container">
            <h1 class="logo">EventBladi</h1>
            <nav>
                <a href="accueil.php">Accueil</a>
                <a href="evenements.php">Événements</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="mes_reservations.php">Mes Réservations</a>
                    <a href="deconnexion.php">Déconnexion</a>
                <?php else: ?>
                    <a href="connexion.php">Se connecter</a>
                    <a href="inscription.html">S'inscrire</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <div class="container" style="margin-top: 100px;">
        <h1>Réservation d'Événements</h1>

        <?php if (isset($_SESSION['reservation_message'])): ?>
            <div class="notification <?= $_SESSION['reservation_message_type'] ?>">
                <?= $_SESSION['reservation_message']; ?>
            </div>
            <?php 
            unset($_SESSION['reservation_message']);
            unset($_SESSION['reservation_message_type']);
            ?>
        <?php endif; ?>

        <?php if (empty($evenements)) : ?>
            <p>Aucun événement disponible pour le moment.</p>
        <?php else: ?>
            <form action="reservation_handler.php" method="POST" id="reservationForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="evenement">Sélectionnez un événement :</label>
                    <select id="evenement" name="evenement_id" required>
                        <option value="">-- Choisissez un événement --</option>
                        <?php foreach ($evenements as $evenement): ?>
                            <option value="<?= htmlspecialchars($evenement['id']); ?>" 
                                    data-places-disponibles="<?= htmlspecialchars($evenement['places_disponibles']); ?>"
                                    data-prix-ticket="<?= htmlspecialchars($evenement['prix_ticket']); ?>">
                                <?= htmlspecialchars($evenement['titre']) . 
                                    " - " . htmlspecialchars($evenement['salle_nom']) . 
                                    " (Places : " . htmlspecialchars($evenement['places_disponibles']) . 
                                    ", Date : " . htmlspecialchars($evenement['date_event']) . 
                                    " à " . htmlspecialchars($evenement['heure_debut']) . 
                                    ", Durée : " . htmlspecialchars($evenement['duree']) . " min, Prix : " . 
                                    htmlspecialchars($evenement['prix_ticket']) . " MAD)"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="places">Nombre de places :</label>
                    <input type="number" id="places" name="places_reservees" min="1" required>
                    <div id="places-error" class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="methode_paiement">Méthode de paiement :</label>
                    <select id="methode_paiement" name="methode_paiement" required>
                        <option value="">-- Sélectionnez une méthode de paiement --</option>
                        <option value="carte">Carte bancaire</option>
                        <option value="paypal">PayPal</option>
                        <option value="mobile">Espéce</option>
                    </select>
                </div>

                <div class="reservation-summary">
                    <h3>Résumé de votre réservation</h3>
                    <p>Prix unitaire : <span id="prix-unitaire">0.00</span> MAD</p>
                    <p>Nombre de places : <span id="nombre-places">0</span></p>
                    <p class="total-amount">Montant total : <span id="montant-total">0.00</span> MAD</p>
                    <input type="hidden" name="montant_total" id="montant_total_input" value="0">
                </div>

                <button type="submit" class="btn-primary" id="submit-button">Confirmer la Réservation</button>
            </form>
        <?php endif; ?>
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
        document.querySelector("form").addEventListener("submit", function(e) {
            const submitButton = this.querySelector("button[type='submit']");
            submitButton.disabled = true;
            submitButton.innerText = "Traitement en cours...";
        });

        document.getElementById('evenement').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var placesDisponibles = selectedOption.getAttribute('data-places-disponibles');
            var placesInput = document.getElementById('places');

            if (placesDisponibles) {
                placesInput.setAttribute('max', placesDisponibles);
                document.getElementById('places-error').textContent = '';
            } else {
                placesInput.removeAttribute('max');
            }

            if (placesInput.value > placesDisponibles) {
                placesInput.value = placesDisponibles;
            }

            updateSummary();
        });

        document.getElementById('places').addEventListener('input', function() {
            var max = this.getAttribute('max');
            if (max && parseInt(this.value) > parseInt(max)) {
                document.getElementById('places-error').textContent = 'Il n\'y a que ' + max + ' places disponibles.';
                this.value = max;
            } else if (parseInt(this.value) < 1) {
                document.getElementById('places-error').textContent = 'Vous devez réserver au moins 1 place.';
            } else {
                document.getElementById('places-error').textContent = '';
            }

            updateSummary();
        });

        function updateSummary() {
            var evenementSelect = document.getElementById('evenement');
            var placesInput = document.getElementById('places');
            var selectedOption = evenementSelect.options[evenementSelect.selectedIndex];

            if (selectedOption.value) {
                var prixTicket = parseFloat(selectedOption.getAttribute('data-prix-ticket'));
                var places = parseInt(placesInput.value) || 0;
                var total = prixTicket * places;

                document.getElementById('prix-unitaire').textContent = prixTicket.toFixed(2);
                document.getElementById('nombre-places').textContent = places;
                document.getElementById('montant-total').textContent = total.toFixed(2);
                document.getElementById('montant_total_input').value = total.toFixed(2);
            } else {
                document.getElementById('prix-unitaire').textContent = '0.00';
                document.getElementById('nombre-places').textContent = '0';
                document.getElementById('montant-total').textContent = '0.00';
                document.getElementById('montant_total_input').value = '0';
            }
        }

        window.addEventListener('load', updateSummary);
    </script>
</body>
</html>
