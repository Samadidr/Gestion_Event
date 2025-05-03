<?php
// Démarrer la session pour accéder aux variables de session
session_start();
// Définir explicitement le fuseau horaire pour éviter les problèmes de décalage
date_default_timezone_set('Africa/Casablanca');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header('Location: connexion.php');
    exit();
}

// Configuration de la base de données
$host = 'localhost';
$dbname = 'pge';
$username = 'root';
$password = '';

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fixer le fuseau horaire de la connexion MySQL pour correspondre au fuseau PHP
    $pdo->exec("SET time_zone = '+01:00'"); // Ajustez selon votre fuseau horaire (Europe/Paris = +01:00 ou +02:00 en été)
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Récupérer les réservations de l'utilisateur avec les détails des événements
try {
    // Utilisez DISTINCT pour éviter les doublons
    $sql = "SELECT r.id, r.evenement_id, r.places_reservees, r.date_reservation, 
               r.methode_paiement, r.montant_total,
               e.titre, e.description, e.date_event, e.places_disponibles, e.prix_ticket,
               s.nom as salle_nom, s.localisation as salle_adresse
        FROM reservations r
        JOIN evenements e ON r.evenement_id = e.id
        LEFT JOIN salles s ON e.salle_id = s.id
        WHERE r.utilisateur_id = ? 
        ORDER BY e.date_event DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Utiliser un tableau associatif pour éliminer les doublons par ID
$filtered_reservations = [];
foreach ($reservations as $reservation) {
    $filtered_reservations[$reservation['id']] = $reservation;
}
$reservations = array_values($filtered_reservations);

    // Vérifier s'il y a des doublons dans les résultats (pour débogage)
    $reservation_ids = [];
    foreach ($reservations as $index => $reservation) {
        if (in_array($reservation['id'], $reservation_ids)) {
            // Si c'est un doublon, le supprimer
            unset($reservations[$index]);
        } else {
            $reservation_ids[] = $reservation['id'];
        }
    }
    // Réindexer le tableau après suppression des doublons
    $reservations = array_values($reservations);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des réservations : " . $e->getMessage());
}

// Traiter l'annulation d'une réservation si demandée
if (isset($_POST['annuler_reservation']) && isset($_POST['reservation_id'])) {
    $reservation_id = intval($_POST['reservation_id']);
    // Récupérer l'événement et le nombre de places réservées pour cette réservation
    try {
        $sql = "SELECT evenement_id, places_reservees FROM reservations WHERE id = ? AND utilisateur_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$reservation_id, $user_id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reservation) {
            $evenement_id = $reservation['evenement_id'];
            $places_reservees = $reservation['places_reservees'];
            // Début de la transaction
            $pdo->beginTransaction();
            // Supprimer la réservation
            $delete_sql = "DELETE FROM reservations WHERE id = ?";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute([$reservation_id]);
            // Mettre à jour le nombre de places disponibles pour l'événement
            $update_sql = "UPDATE evenements SET places_disponibles = places_disponibles + ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$places_reservees, $evenement_id]);
            // Commit de la transaction
            $pdo->commit();
            // Rediriger pour éviter la soumission multiple du formulaire
            header('Location: mes_reservations.php?annulation=success');
            exit();
        } else {
            // Si la réservation n'existe pas ou n'appartient pas à l'utilisateur
            $_SESSION['reservation_message'] = "Réservation introuvable ou non liée à votre compte.";
            $_SESSION['reservation_message_type'] = "error";
            header('Location: mes_reservations.php');
            exit();
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Erreur lors de l'annulation de la réservation : " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - EventBladi</title>
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
        /* Réservations */
        .reservations-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .reservation-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .reservation-card:hover {
            transform: translateY(-5px);
        }
        .reservation-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            position: relative;
        }
        .reservation-header h3 {
            margin-bottom: 5px;
        }
        .reservation-date {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        .reservation-body {
            padding: 15px;
        }
        .reservation-detail {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .reservation-detail i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
            margin-top: 3px;
        }
        .event-price {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin: 15px 0;
            border-left: 4px solid var(--primary-color);
        }
        .price-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .total-price {
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            margin-top: 5px;
        }
        .btn-cancel {
            width: 100%;
            padding: 8px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        .btn-cancel:hover {
            background-color: #c0392b;
        }
        .btn-cancel:disabled {
            background-color: #7f8c8d;
            cursor: not-allowed;
        }
        .event-status {
            width: 100%;
            padding: 8px;
            background-color: #7f8c8d;
            color: white;
            border: none;
            border-radius: 5px;
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }
        .payment-badge {
            display: inline-block;
            padding: 3px 8px;
            background-color: #34495e;
            color: white;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-bottom: 10px;
        }
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
        .notification {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }


        /* CSS pour la section des reçus téléchargés */
        .recus-section {
            margin-top: 3rem;
            margin-bottom: 3rem;
        }
        
        .section-title {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
            display: inline-block;
        }
        
        .recus-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .recus-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .recus-table th {
            background-color: #f5f7fa;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            text-align: left;
        }
        
        .recus-table th:last-child {
            text-align: right;
        }
        
        .recus-table td {
            padding: 16px;
            border-top: 1px solid #edf2f7;
            font-size: 0.875rem;
            vertical-align: middle;
        }
        
        .recus-table tr:hover {
            background-color: #f9fafb;
        }
        
        .reference-number {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .event-name {
            color: #4a5568;
        }
        
        .event-date {
            color: #718096;
        }
        
        .event-price {
            color: #2d3748;
            font-weight: 500;
        }
        
        .action-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .action-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .download-link {
            color: #2ecc71;
            margin-left: 12px;
        }
        
        .download-link:hover {
            color: #27ae60;
        }
        
        .no-recus-message {
            text-align: center;
            padding: 2rem;
            color: #a0aec0;
            font-style: italic;
        }
        
        /* Badge pour indiquer le status du téléchargement */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
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
            .reservations-list {
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
            <h2 class="page-title">Mes Réservations</h2>
            <?php if (isset($_GET['annulation']) && $_GET['annulation'] == 'success'): ?>
                <div class="notification success">
                    La réservation a été annulée avec succès et les places ont été libérées.
                </div>
            <?php endif; ?>
            <?php if (empty($reservations)): ?>
                <div class="empty-message">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Aucune réservation trouvée</h3>
                    <p>Vous n'avez pas encore fait de réservation d'événement.</p>
                    <a href="evenements.php" class="cta-button">Explorer les événements</a>
                </div>
            <?php else: ?>
                <div class="reservations-list">
                    <?php foreach ($reservations as $reservation): ?>
                        <?php
                            // Utiliser DateTime pour une gestion correcte des fuseaux horaires
                            $date_obj = new DateTime($reservation['date_reservation']);
                            $date_formatted = $date_obj->format('d/m/Y à H:i');
                            
                            // Vérifier si l'événement est passé
                            $event_date = strtotime($reservation['date_event']);
                            $is_past_event = $event_date < time();
                        ?>
                        <div class="reservation-card">
                            <div class="reservation-header">
                                <h3><?= htmlspecialchars($reservation['titre']); ?></h3>
                                <div class="reservation-date">
                                    <i class="fas fa-calendar-day"></i>
                                    <span><?= date('d/m/Y', strtotime($reservation['date_event'])); ?></span>
                                </div>
                            </div>
                            <div class="reservation-body">
                                <span class="payment-badge">
                                    <i class="fas fa-credit-card"></i> 
                                    <?= htmlspecialchars($reservation['methode_paiement']); ?>
                                </span>
                                <?php if (!empty($reservation['salle_nom'])): ?>
                                <div class="reservation-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>
                                        <?= htmlspecialchars($reservation['salle_nom']); ?>
                                        <?php if (!empty($reservation['salle_adresse'])): ?>
                                            <br><small><?= htmlspecialchars($reservation['salle_adresse']); ?></small>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class="reservation-detail">
                                    <i class="fas fa-users"></i>
                                    <span><?= htmlspecialchars($reservation['places_reservees']); ?> place(s)</span>
                                </div>
                                <div class="reservation-detail">
                                    <i class="fas fa-clock"></i>
                                    <span>Réservé le <?= $date_formatted; ?></span>
                                </div>
                                <div class="event-price">
                                    <div class="price-detail">
                                        <span>Prix unitaire:</span>
                                        <span><?= number_format($reservation['prix_ticket'], 2); ?> DH</span>
                                    </div>
                                    <div class="price-detail">
                                        <span>Nombre de places:</span>
                                        <span><?= $reservation['places_reservees']; ?></span>
                                    </div>
                                    <div class="price-detail total-price">
                                        <span>Montant total:</span>
                                        <span><?= number_format($reservation['montant_total'], 2); ?> DH</span>
                                    </div>
                                </div>
                                
                                <?php if ($is_past_event): ?>
                                    <!-- Afficher un message pour les événements passés -->
                                    <div class="event-status">
                                        <i class="fas fa-history"></i> Événement passé
                                    </div>
                                <?php else: ?>
                                    <!-- Afficher le bouton d'annulation pour les événements à venir -->
                                    <form method="POST">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id']; ?>">
                                        <input type="hidden" name="evenement_id" value="<?= $reservation['evenement_id']; ?>">
                                        <input type="hidden" name="places_reservees" value="<?= $reservation['places_reservees']; ?>">
                                        <button type="submit" name="annuler_reservation" class="btn-cancel" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                            <i class="fas fa-times-circle"></i> Annuler cette réservation
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
   <!-- Section pour les reçus téléchargés -->
<div class="container">
    <div class="recus-section">
        <h2 class="section-title">Mes reçus téléchargés</h2>
        <?php
        // Récupérer les réservations avec reçus téléchargés
        $stmt = $pdo->prepare("
            SELECT r.id, e.titre, r.date_reservation, r.places_reservees, r.montant_total
            FROM reservations r
            JOIN evenements e ON r.evenement_id = e.id
            WHERE r.utilisateur_id = ? AND r.recu_telecharge = 1
            ORDER BY r.date_reservation DESC
        ");
        $stmt->execute([$user_id]);
        $recus_telecharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recus_telecharges)) {
            echo '<div class="recus-container"><p class="no-recus-message">Vous n\'avez pas encore téléchargé de reçus.</p></div>';
        } else {
        ?>
        <div class="recus-container">
            <table class="recus-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Événement</th>
                        <th>Date</th>
                        <th>Places</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recus_telecharges as $recu): ?>
                    <tr>
                        <td class="reference-number">
                            #EB-<?= str_pad($recu['id'], 6, '0', STR_PAD_LEFT) ?>
                        </td>
                        <td class="event-name">
                            <?= htmlspecialchars($recu['titre']) ?>
                        </td>
                        <td class="event-date">
                            <?= (new DateTime($recu['date_reservation']))->format('d/m/Y') ?>
                        </td>
                        <td>
                            <?= $recu['places_reservees'] ?>
                        </td>
                        <td class="event-price">
                            <?= number_format($recu['montant_total'], 2) ?> MAD
                        </td>
                        <td style="text-align: right;">
                            <a href="recu_reservation.php?id=<?= $recu['id'] ?>" class="action-link">Voir</a>
                            <a href="recu_reservation.php?id=<?= $recu['id'] ?>&action=download" class="action-link download-link">Re-télécharger</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    </div>
</div>
    
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