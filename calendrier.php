<?php
// Connexion à la base de données
require 'config.php';
$pdo = new PDO("mysql:host=localhost;dbname=pge;charset=utf8", "root", "");

// Récupérer tous les événements avec le nom de la salle associée
try {
    $sql = "
        SELECT e.*, s.nom AS salle_nom 
        FROM evenements e
        LEFT JOIN salles s ON e.salle_id = s.id
        ORDER BY e.date_event ASC
    ";
    $stmt = $pdo->query($sql);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des événements : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier des Événements - EventBladi</title>
    
    <!-- FullCalendar CSS et JS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/locales/fr.js'></script>

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

        .calendar-container {
            flex-grow: 1;
            width: 100%;
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 0 15px;
        }

        #calendar {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 20px;
        }

        /* Modal des détails de l'événement */
        #eventModal {
            display: none;
            position: fixed;
            z-index: 1100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .close-modal {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: black;
        }

        .modal-body {
            margin-top: 20px;
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
                <a href="accueil.php">Accueil</a>
                
            </nav>
        </div>
    </header>

    <div class="calendar-container">
        <div id="calendar"></div>
    </div>

    <!-- Modal pour les détails de l'événement -->
    <div id="eventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalEventTitle"></h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p><strong>Date :</strong> <span id="modalEventDate"></span></p>
                <p><strong>Salle :</strong> <span id="modalEventSalle"></span></p>
                <p><strong>Description :</strong> <span id="modalEventDescription"></span></p>
                <p><strong>Places disponibles :</strong> <span id="modalEventPlaces"></span></p>
            </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            var events = [
                <?php foreach ($evenements as $event): ?>
                {
                    title: <?= json_encode($event['titre']); ?>,
                    start: <?= json_encode($event['date_event']); ?>,
                    description: <?= json_encode($event['description']); ?>,
                    places_disponibles: <?= $event['places_disponibles']; ?>,
                    salle_nom: <?= json_encode($event['salle_nom']); ?>
                },
                <?php endforeach; ?>
            ];

            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'fr',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: events,
                eventClick: function(info) {
                    var modal = document.getElementById('eventModal');
                    var event = info.event;

                    document.getElementById('modalEventTitle').textContent = event.title;
                    document.getElementById('modalEventDate').textContent = event.startStr;
                    document.getElementById('modalEventSalle').textContent = event.extendedProps.salle_nom || 'Salle non spécifiée';
                    document.getElementById('modalEventDescription').textContent = event.extendedProps.description;
                    document.getElementById('modalEventPlaces').textContent = event.extendedProps.places_disponibles;

                    modal.style.display = 'block';
                }
            });

            calendar.render();

            // Gestion de la fermeture du modal
            var closeModal = document.querySelector('.close-modal');
            closeModal.onclick = function() {
                document.getElementById('eventModal').style.display = 'none';
            }

            // Fermer le modal en cliquant en dehors
            window.onclick = function(event) {
                var modal = document.getElementById('eventModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>