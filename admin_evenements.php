<?php
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filtres
$search = isset($_GET['search']) ? $_GET['search'] : '';
$salle_filter = isset($_GET['salle_id']) ? (int)$_GET['salle_id'] : '';

// Construction de la requête avec filtres
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(e.titre LIKE :search OR e.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($salle_filter)) {
    $whereConditions[] = "e.salle_id = :salle_id";
    $params[':salle_id'] = $salle_filter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Récupération du nombre total d'événements pour la pagination
try {
    $countQuery = "SELECT COUNT(*) as total 
                   FROM evenements e
                   LEFT JOIN utilisateurs u ON e.utilisateur_id = u.id
                   $whereClause";
    
    $stmt = $pdo->prepare($countQuery);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $totalEvenements = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalEvenements / $perPage);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupération des salles pour le filtre
try {
    $salleQuery = "SELECT id, nom FROM salles ORDER BY nom";
    $salleStmt = $pdo->query($salleQuery);
    $salles = $salleStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupération des événements
try {
    $query = "SELECT e.*, u.nom as organisateur_nom, s.nom as nom_salle 
              FROM evenements e
              LEFT JOIN utilisateurs u ON e.utilisateur_id = u.id
              LEFT JOIN salles s ON e.salle_id = s.id
              $whereClause
              ORDER BY e.date_event DESC
              LIMIT :offset, :perPage";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Traitement de la suppression d'un événement
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $eventId = $_GET['delete'];
    
    try {
        // Vérifier si l'événement a des réservations
        $checkQuery = "SELECT COUNT(*) as reservations_count FROM reservations WHERE evenement_id = :event_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([':event_id' => $eventId]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['reservations_count'] > 0) {
            $deleteError = "Impossible de supprimer cet événement car il a des réservations associées.";
        } else {
            // Supprimer l'événement
            $deleteQuery = "DELETE FROM evenements WHERE id = :id";
            $deleteStmt = $pdo->prepare($deleteQuery);
            $deleteStmt->execute([':id' => $eventId]);
            
            header('Location: admin_evenements.php?success=1');
            exit;
        }
    } catch (PDOException $e) {
        $deleteError = "Erreur lors de la suppression: " . $e->getMessage();
    }
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Événements | EventBladi Admin</title>
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
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            flex: 1;
            min-width: 300px;
        }
        
        .search-form input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            font-family: inherit;
        }
        
        .search-form button {
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }
        
        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
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
        
        .table .actions {
            display: flex;
            gap: 10px;
        }
        
        .table .actions a {
            color: var(--text-color);
            font-size: 1rem;
            transition: color 0.3s;
        }
        
        .table .actions a:hover {
            color: var(--primary-color);
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-primary {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--primary-color);
        }
        
        .badge-secondary {
            background-color: rgba(117, 117, 117, 0.1);
            color: var(--light-text);
        }
        
        .price-tag {
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: var(--text-color);
            background-color: var(--card-bg);
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background-color: rgba(26, 115, 232, 0.1);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .pagination .active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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
        
        .btn-danger {
            background-color: var(--accent-color);
        }
        
        .btn-danger:hover {
            background-color: #c62828;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(52, 168, 83, 0.1);
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .alert-danger {
            background-color: rgba(234, 67, 53, 0.1);
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
        }
        
        .truncate {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .event-status {
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 3px;
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
            
            .filters {
                flex-direction: column;
            }
            
            .search-form, .filter-form {
                width: 100%;
            }
            
            .table {
                display: block;
                overflow-x: auto;
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
                <h2>Gestion des Événements</h2>
                <p>Administrez les événements de la plateforme</p>
            </div>
            <div class="user-info">
                <span><?= htmlspecialchars($_SESSION['admin_email']) ?></span>
                <a href="admin_deconnexion.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        
        <!-- Notifications -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                L'événement a été supprimé avec succès.
            </div>
        <?php endif; ?>
        
        <?php if (isset($deleteError)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($deleteError) ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters and Search -->
        <div class="card">
            <div class="filters">
                <form class="search-form" action="" method="GET">
                    <input type="text" name="search" placeholder="Rechercher un événement..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <form class="filter-form" action="" method="GET">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <?php endif; ?>
                    
                    <select name="salle_id" onchange="this.form.submit()">
                        <option value="">Toutes les salles</option>
                        <?php foreach ($salles as $salle): ?>
                            <option value="<?= $salle['id'] ?>" <?= $salle_filter === $salle['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($salle['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                
                <a href="admin_ajouter_evenement.php" class="btn"><i class="fas fa-plus"></i> Ajouter un événement</a>
            </div>
            
            <!-- Events Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Durée</th>
                        <th>Salle</th>
                        <th>Organisateur</th>
                        <th>Prix</th>
                        <th>Places</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($evenements)): ?>
                        <?php foreach ($evenements as $event): ?>
                            <?php
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
                            <tr>
                                <td><?= $event['id'] ?></td>
                                <td>
                                    <div class="truncate" title="<?= htmlspecialchars($event['titre']) ?>">
                                        <?= htmlspecialchars($event['titre']) ?>
                                    </div>
                                    <span class="event-status status-<?= $status ?>"><?= $statusLabel ?></span>
                                </td>
                                <td><?= formatDate($event['date_event']) ?></td>
                                <td><?= formatTime($event['heure_debut']) ?></td>
                                <td><?= formatDuration($event['duree']) ?></td>
                                <td><?= htmlspecialchars($event['nom_salle']) ?></td>
                                <td><?= htmlspecialchars($event['organisateur_nom'] ?? 'Non spécifié') ?></td>
                                <td><span class="price-tag"><?= number_format($event['prix_ticket'], 2, ',', ' ') ?> MAD</span></td>
                                <td><?= $event['places_disponibles'] ?></td>
                                <td class="actions">
                                    <a href="admin_voir_evenement.php?id=<?= $event['id'] ?>" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="admin_modifier_evenement.php?id=<?= $event['id'] ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="admin_evenements.php?delete=<?= $event['id'] ?>" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement?');"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">Aucun événement trouvé</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($salle_filter) ? '&salle_id=' . $salle_filter : '' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($salle_filter) ? '&salle_id=' . $salle_filter : '' ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($salle_filter) ? '&salle_id=' . $salle_filter : '' ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>