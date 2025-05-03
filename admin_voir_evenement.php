<?php
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config.php';

// Vérifier que l'ID de l'événement est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_evenements.php');
    exit;
}

$eventId = $_GET['id'];

// Récupération des détails de l'événement
try {
    $query = "SELECT e.*, u.nom as organisateur_nom, s.nom as nom_salle 
              FROM evenements e
              LEFT JOIN utilisateurs u ON e.utilisateur_id = u.id
              LEFT JOIN salles s ON e.salle_id = s.id
              WHERE e.id = :id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $eventId]);
    
    if ($stmt->rowCount() === 0) {
        header('Location: admin_evenements.php');
        exit;
    }
    
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupération des réservations pour cet événement
try {
    $reservationsQuery = "SELECT r.*, u.nom as utilisateur_nom, u.email as utilisateur_email 
                         FROM reservations r
                         LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id
                         WHERE r.evenement_id = :event_id
                         ORDER BY r.date_reservation DESC";
    
    $reservationsStmt = $pdo->prepare($reservationsQuery);
    $reservationsStmt->execute([':event_id' => $eventId]);
    $reservations = $reservationsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Fonction pour formater la date
function formatDate($dateTime) {
    return date('d/m/Y', strtotime($dateTime));
}

// Fonction pour formater l'heure
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Fonction pour formater la durée
function formatDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($mins == 0) {
        return $hours . 'h';
    }
    return $hours . 'h ' . $mins . 'min';
}

// Déterminer le statut de l'événement
$now = time();
$eventDate = strtotime($event['date_event']);
$eventEndTime = $eventDate + ($event['duree'] * 60);

if ($eventDate > $now) {
    $status = 'upcoming';
    $statusLabel = 'À venir';
} elseif ($now <= $eventEndTime) {
    $status = 'ongoing';
    $statusLabel = 'En cours';
} else {
    $status = 'past';
    $statusLabel = 'Passé';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Événement | EventBladi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #34a853;
            --accent-color: #ea4335;
            --text-color: #333;
            --light-text: #757575;
            --background-color: #f5f5f5;
            --card-bg: #ffffff;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--card-bg);
            border-right: 1px solid rgba(0,0,0,0.1);
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            color: var(--primary-color);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu h4 {
            padding: 0 20px;
            margin-bottom: 10px;
            color: var(--light-text);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title h2 {
            color: var(--text-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 10px;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding-bottom: 15px;
        }
        
        .event-title h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .event-actions {
            display: flex;
            gap: 10px;
        }
        
        .event-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .status-upcoming {
            background-color: #d1f4ff;
            color: #0277bd;
        }
        
        .status-ongoing {
            background-color: #dcedc8;
            color: #33691e;
        }
        
        .status-past {
            background-color: #ffecb3;
            color: #ff8f00;
        }
        
        .event-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-item label {
            display: block;
            font-size: 0.8rem;
            color: var(--light-text);
            margin-bottom: 5px;
        }
        
        .detail-item .value {
            font-weight: 500;
        }
        
        .description-section {
            margin-bottom: 30px;
        }
        
        .description-section h4 {
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .description-content {
            line-height: 1.6;
            color: var(--text-color);
            white-space: pre-line;
        }
        
        .reservations-section h4 {
            margin-bottom: 15px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .reservations-count {
            font-size: 0.9rem;
            color: var(--light-text);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .table th {
            background-color: rgba(0,0,0,0.02);
            font-weight: 500;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .price-tag {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #1557b0;
        }
        
        .btn-secondary {
            background-color: #757575;
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        .btn-danger {
            background-color: var(--accent-color);
        }
        
        .btn-danger:hover {
            background-color: #c62828;
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                overflow-y: visible;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .event-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .event-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .event-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>EventBladi Admin</h3>
            <p>Gestion des événements</p>
        </div>
        
        <div class="sidebar-menu">
            <h4>Principal</h4>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="admin_evenements.php" class="active"><i class="fas fa-calendar-alt"></i> Événements</a></li>
                <li><a href="admin_utilisateurs.php"><i class="fas fa-users"></i> Utilisateurs</a></li>
                <li><a href="admin_salles.php"><i class="fas fa-building"></i> Salles</a></li>
                <li><a href="admin_reservations.php"><i class="fas fa-ticket-alt"></i> Réservations</a></li>
            </ul>
            
            <h4>Paramètres</h4>
            <ul>
                <li><a href="admin_profile.php"><i class="fas fa-user-circle"></i> Mon Profil</a></li>
                <li><a href="admin_deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h2>Détails de l'Événement</h2>
                <p>Visualisez toutes les informations de l'événement</p>
            </div>
            <div class="user-info">
                <span><?= htmlspecialchars($_SESSION['admin_email']) ?></span>
                <a href="admin_deconnexion.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        
        <div class="card">
            <div class="event-header">
                <div class="event-title">
                    <h3><?= htmlspecialchars($event['titre']) ?></h3>
                    <span class="event-status status-<?= $status ?>"><?= $statusLabel ?></span>
                </div>
                <div class="event-actions">
                    <a href="admin_modifier_evenement.php?id=<?= $event['id'] ?>" class="btn"><i class="fas fa-edit"></i> Modifier</a>
                    <a href="admin_evenements.php?delete=<?= $event['id'] ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement?');"><i class="fas fa-trash-alt"></i> Supprimer</a>
                    <a href="admin_evenements.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
                </div>
            </div>
            
            <div class="event-details">
                <div class="detail-column">
                    <div class="detail-item">
                        <label>Date</label>
                        <div class="value"><?= formatDate($event['date_event']) ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Heure de début</label>
                        <div class="value"><?= formatTime($event['heure_debut']) ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Durée</label>
                        <div class="value"><?= formatDuration($event['duree']) ?></div>
                    </div>
                </div>
                
                <div class="detail-column">
                    <div class="detail-item">
                        <label>Salle</label>
                        <div class="value"><?= htmlspecialchars($event['nom_salle']) ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Organisateur</label>
                        <div class="value"><?= htmlspecialchars($event['organisateur_nom'] ?? 'Non spécifié') ?></div>
                    </div>
                    <div class="detail-item">
                        <label>Places disponibles</label>
                        <div class="value"><?= $event['places_disponibles'] ?></div>
                    </div>
                </div>
                
                <div class="detail-column">
                    <div class="detail-item">
                        <label>Prix du ticket</label>
                        <div class="value price-tag"><?= number_format($event['prix_ticket'], 2, ',', ' ') ?> MAD</div>
                    </div>
                    <div class="detail-item">
                        <label>Date de création</label>
                        <div class="value"><?= formatDate($event['created_at']) ?></div>
                    </div>
                    
                </div>
            </div>
            
            <div class="description-section">
                <h4>Description</h4>
                <div class="description-content">
                    <?= nl2br(htmlspecialchars($event['description'])) ?>
                </div>
            </div>
            
            <div class="reservations-section">
                <h4>
                    Réservations
                    <span class="reservations-count"><?= count($reservations) ?> réservation(s)</span>
                </h4>
                
                <?php if (!empty($reservations)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Date de réservation</th>
                                <th>Nombre de places</th>
                                <th>Total payé</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?= $reservation['id'] ?></td>
                                    <td><?= htmlspecialchars($reservation['utilisateur_nom']) ?></td>
                                    <td><?= htmlspecialchars($reservation['utilisateur_email']) ?></td>
                                    <td><?= formatDate($reservation['date_reservation']) ?></td>
                                    <td><?= $reservation['places_reservees'] ?></td>
                                    <td><span class="price-tag"><?= number_format($reservation['montant_total'], 2, ',', ' ') ?> MAD</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Aucune réservation pour cet événement.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>