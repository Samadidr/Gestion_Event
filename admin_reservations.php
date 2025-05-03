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
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête avec filtres
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(r.reference LIKE :search OR u.nom LIKE :search OR e.titre LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($statut)) {
    $whereConditions[] = "r.statut = :statut";
    $params[':statut'] = $statut;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Récupération du nombre total de réservations pour la pagination
try {
    $countQuery = "SELECT COUNT(*) as total 
                   FROM reservations r
                   LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id
                   LEFT JOIN evenements e ON r.evenement_id = e.id
                   $whereClause";
    
    $stmt = $pdo->prepare($countQuery);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $totalReservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalReservations / $perPage);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupération des réservations
try {
    $query = "SELECT r.*, u.nom as utilisateur_nom, e.titre as evenement_titre 
              FROM reservations r
              LEFT JOIN utilisateurs u ON r.utilisateur_id = u.id
              LEFT JOIN evenements e ON r.evenement_id = e.id
              $whereClause
              ORDER BY r.date_reservation DESC
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
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Traitement de la suppression d'une réservation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $reservationId = $_GET['delete'];
    
    try {
        $deleteQuery = "DELETE FROM reservations WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute([':id' => $reservationId]);
        
        header('Location: admin_reservations.php?success=1');
        exit;
    } catch (PDOException $e) {
        $deleteError = "Erreur lors de la suppression: " . $e->getMessage();
    }
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réservations | EventBladi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #34a853;
            --accent-color: #ea4335;
            --warning-color: #fbbc05;
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
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-confirmed {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--secondary-color);
        }
        
        .status-pending {
            background-color: rgba(251, 188, 5, 0.1);
            color: var(--warning-color);
        }
        
        .status-cancelled {
            background-color: rgba(234, 67, 53, 0.1);
            color: var(--accent-color);
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
        
        .btn-warning {
            background-color: var(--warning-color);
        }
        
        .btn-warning:hover {
            background-color: #d9a406;
        }
        
        .btn-success {
            background-color: var(--secondary-color);
        }
        
        .btn-success:hover {
            background-color: #2d8e49;
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
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>EventBladi Admin</h3>
            <p>Gestion des réservations</p>
        </div>
        
        <div class="sidebar-menu">
            <h4>Principal</h4>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="admin_evenements.php"><i class="fas fa-calendar-alt"></i> Événements</a></li>
                <li><a href="admin_utilisateurs.php"><i class="fas fa-users"></i> Utilisateurs</a></li>
                <li><a href="admin_salles.php"><i class="fas fa-building"></i> Salles</a></li>
                <li><a href="admin_reservations.php" class="active"><i class="fas fa-ticket-alt"></i> Réservations</a></li>
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
                <h2>Gestion des Réservations</h2>
                <p>Administrez les réservations d'événements</p>
            </div>
            <div class="user-info">
                <span><?= htmlspecialchars($_SESSION['admin_email']) ?></span>
                <a href="admin_deconnexion.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        
        <!-- Notifications -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                La réservation a été supprimée avec succès.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status_updated'])): ?>
            <div class="alert alert-success">
                Le statut de la réservation a été mis à jour avec succès.
            </div>
        <?php endif; ?>
        
        <?php if (isset($deleteError)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($deleteError) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($updateError)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($updateError) ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters and Search -->
        <div class="card">
            <div class="filters">
                <form class="search-form" action="" method="GET">
                    <input type="text" name="search" placeholder="Rechercher une réservation..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                
                
                <a href="admin_ajouter_reservation.php" class="btn"><i class="fas fa-plus"></i> Ajouter une réservation</a>
            </div>
            
            <!-- Reservations Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        
                        <th>Utilisateur</th>
                        <th>Événement</th>
                        <th>Places</th>
                        <th>Date de réservation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reservations)): ?>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= $reservation['id'] ?></td>
                                
                                <td><?= htmlspecialchars($reservation['utilisateur_nom']) ?></td>
                                <td><?= htmlspecialchars($reservation['evenement_titre']) ?></td>
                                <td><?= $reservation['places_reservees'] ?></td>
                                <td><?= htmlspecialchars($reservation['date_reservation']) ?></td>
                                <td>
                                    
                                
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Aucune réservation trouvée</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
    </div>
</body>
</html>