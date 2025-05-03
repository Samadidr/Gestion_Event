<?php
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_utilisateurs.php');
    exit;
}

$userId = $_GET['id'];

// Récupérer les informations de l'utilisateur
try {
    $query = "SELECT * FROM utilisateurs WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $userId]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$utilisateur) {
        header('Location: admin_utilisateurs.php?error=user_not_found');
        exit;
    }

    // Récupérer les réservations de l'utilisateur
    $queryReservations = "SELECT r.*, e.titre as event_titre 
                         FROM reservations r 
                         JOIN evenements e ON r.evenement_id = e.id 
                         WHERE r.utilisateur_id = :id 
                         ORDER BY r.date_reservation DESC";
    $stmtReservations = $pdo->prepare($queryReservations);
    $stmtReservations->execute([':id' => $userId]);
    $reservations = $stmtReservations->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les événements organisés par l'utilisateur
    $queryEvents = "SELECT * FROM evenements WHERE utilisateur_id = :id ORDER BY date_event DESC";
    $stmtEvents = $pdo->prepare($queryEvents);
    $stmtEvents->execute([':id' => $userId]);
    $evenements = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Utilisateur - <?= htmlspecialchars($utilisateur['nom']) ?> | EventBladi Admin</title>
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
        
        .actions-group {
            display: flex;
            gap: 10px;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--light-text);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1rem;
            color: var(--text-color);
        }
        
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding-bottom: 10px;
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
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--secondary-color);
        }
        
        .badge-pending {
            background-color: rgba(251, 188, 5, 0.1);
            color: #fbbc05;
        }
        
        .badge-cancelled {
            background-color: rgba(234, 67, 53, 0.1);
            color: var(--accent-color);
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
            font-size: 0.9rem;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn:hover {
            background-color: #1557b0;
        }
        
        .btn-danger {
            background-color: var(--accent-color);
        }
        
        .btn-danger:hover {
            background-color: #c62828;
        }
        
        .btn-secondary {
            background-color: #757575;
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: var(--light-text);
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
            
            .user-details {
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
            <p>Gestion des utilisateurs</p>
        </div>
        
        <div class="sidebar-menu">
            <h4>Principal</h4>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="admin_evenements.php"><i class="fas fa-calendar-alt"></i> Événements</a></li>
                <li><a href="admin_utilisateurs.php" class="active"><i class="fas fa-users"></i> Utilisateurs</a></li>
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
                <h2>Détails de l'utilisateur</h2>
                <p>Consultez les informations de <?= htmlspecialchars($utilisateur['nom']) ?></p>
            </div>
            <div class="actions-group">
                <a href="admin_modifier_utilisateur.php?id=<?= $utilisateur['id'] ?>" class="btn"><i class="fas fa-edit"></i> Modifier</a>
                <a href="admin_utilisateurs.php?delete=<?= $utilisateur['id'] ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?');"><i class="fas fa-trash-alt"></i> Supprimer</a>
                <a href="admin_utilisateurs.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </div>
        
        <div class="card">
            <h3 class="section-title">Informations personnelles</h3>
            <div class="user-details">
                <div class="detail-item">
                    <div class="detail-label">Nom complet</div>
                    <div class="detail-value"><?= htmlspecialchars($utilisateur['nom']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?= htmlspecialchars($utilisateur['email']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Téléphone</div>
                    <div class="detail-value"><?= htmlspecialchars($utilisateur['telephone'] ?? 'Non spécifié') ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Date d'inscription</div>
                    <div class="detail-value"><?= htmlspecialchars($utilisateur['date_inscription']) ?></div>
                </div>
                <?php if (isset($utilisateur['ville'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Ville</div>
                    <div class="detail-value"><?= htmlspecialchars($utilisateur['ville']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (isset($utilisateur['adresse'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Adresse</div>
                    <div class="detail-value"><?= htmlspecialchars($utilisateur['adresse']) ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <h3 class="section-title">Réservations</h3>
            <?php if (!empty($reservations)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Événement</th>
                            <th>Date de réservation</th>
                            <th>Nombre de places</th>
                            <th>Statut</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= $reservation['id'] ?></td>
                                <td><?= htmlspecialchars($reservation['event_titre']) ?></td>
                                <td><?= htmlspecialchars($reservation['date_reservation']) ?></td>
                                <td><?= $reservation['places_reservees'] ?></td>
                                <td>
                                    <?php 
                                    $eventId = $_GET['id'];
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
                                    $now = time();
                                    $eventDate = strtotime($event['date_event']);
                                    $eventEndTime = $eventDate + ($event['duree'] * 60);
                                    
                                    if ($eventDate > $now) {
                                        
                                        $statusLabel = 'À venir';
                                    } elseif ($now <= $eventEndTime) {
                                        
                                        $statusLabel = 'En cours';
                                    } else {
                                        
                                        $statusLabel = 'Passé';
                                    }
                                    ?>
                                    <span class="badge <?= $statusLabel ?>"><?= htmlspecialchars(ucfirst($statusLabel)) ?></span>
                                </td>
                                
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>Aucune réservation trouvée pour cet utilisateur.</p>
                </div>
            <?php endif; ?>
            
            <h3 class="section-title">Événements organisés</h3>
            <?php if (!empty($evenements)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Date de début</th>
                            <th>Duree</th>
                            <th>Statut</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evenements as $event): ?>
                            <tr>
                                <td><?= $event['id'] ?></td>
                                <td><?= htmlspecialchars($event['titre']) ?></td>
                                <td><?= htmlspecialchars($event['date_event']) ?></td>
                                <td><?= htmlspecialchars($event['duree']) ?></td>
                                <td>
                                    <?php 
                                    $eventId = $_GET['id'];
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
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($statusLabel)) ?></span>
                                </td>
                                <td>
                                    <a href="admin_voir_evenement.php?id=<?= $event['id'] ?>" title="Voir"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>Aucun événement organisé par cet utilisateur.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>