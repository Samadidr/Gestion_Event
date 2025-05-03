<?php
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config.php';

// Récupérer les statistiques
try {
    // Nombre total d'utilisateurs
    $query = "SELECT COUNT(*) as total_users FROM utilisateurs";
    $stmt = $pdo->query($query);
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    
    // Nombre total d'événements
    $query = "SELECT COUNT(*) as total_events FROM evenements";
    $stmt = $pdo->query($query);
    $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['total_events'];
    
    // Nombre total de salles
    $query = "SELECT COUNT(*) as total_rooms FROM salles";
    $stmt = $pdo->query($query);
    $totalRooms = $stmt->fetch(PDO::FETCH_ASSOC)['total_rooms'];
    
    // Nombre total de réservations
    $query = "SELECT COUNT(*) as total_reservations FROM reservations";
    $stmt = $pdo->query($query);
    $totalReservations = $stmt->fetch(PDO::FETCH_ASSOC)['total_reservations'];
    
    // Événements récents
    $query = "SELECT * FROM evenements ORDER BY date_event DESC LIMIT 5";
    $stmt = $pdo->query($query);
    $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Utilisateurs récents
    $query = "SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT 5";
    $stmt = $pdo->query($query);
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin | EventBladi</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-card-header i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding-bottom: 10px;
        }
        
        .card-header h3 {
            color: var(--text-color);
        }
        
        .card-header a {
            color: var(--primary-color);
            text-decoration: none;
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
            
            .stats-grid {
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
            <p>Tableau de bord</p>
        </div>
        
        <div class="sidebar-menu">
            <h4>Principal</h4>
            <ul>
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="admin_evenements.php"><i class="fas fa-calendar-alt"></i> Événements</a></li>
                <li><a href="admin_utilisateurs.php"><i class="fas fa-users"></i> Utilisateurs</a></li>
                <li><a href="admin_salles.php"><i class="fas fa-building"></i> Salles</a></li>
                <li><a href="admin_reservations.php"><i class="fas fa-ticket-alt"></i> Réservations</a></li>
            </ul>
            
            <h4>Paramètres</h4>
            <ul>
                <li><a href="admin_deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <h2>Tableau de bord</h2>
                <p>Bienvenue, <?= htmlspecialchars($_SESSION['admin_nom']) ?></p>
            </div>
            <div class="user-info">
                <span><?= htmlspecialchars($_SESSION['admin_email']) ?></span>
                <a href="admin_deconnexion.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <h4>Utilisateurs</h4>
                    <i class="fas fa-users"></i>
                </div>
                <h3><?= $totalUsers ?></h3>
                <p>Total des utilisateurs</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <h4>Événements</h4>
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3><?= $totalEvents ?></h3>
                <p>Total des événements</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <h4>Salles</h4>
                    <i class="fas fa-building"></i>
                </div>
                <h3><?= $totalRooms ?></h3>
                <p>Total des salles</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <h4>Réservations</h4>
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3><?= $totalReservations ?></h3>
                <p>Total des réservations</p>
            </div>
        </div>
        
        <!-- Recent Events -->
        <div class="card">
            <div class="card-header">
                <h3>Événements récents</h3>
                <a href="admin_evenements.php">Voir tous</a>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Date</th>
                        <th>Organisateur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentEvents)): ?>
                        <?php foreach ($recentEvents as $event): ?>
                            <tr>
                                <td><?= $event['id'] ?></td>
                                <td><?= htmlspecialchars($event['titre']) ?></td>
                                <td><?= htmlspecialchars($event['date_event']) ?></td>
                                <td><?= htmlspecialchars($event['utilisateur_id']) ?></td>
                                <td class="actions">
                                    <a href="admin_voir_evenement.php?id=<?= $event['id'] ?>" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="admin_modifier_evenement.php?id=<?= $event['id'] ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="admin_supprimer_evenement.php?id=<?= $event['id'] ?>" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement?');"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucun événement récent</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Users -->
        <div class="card">
            <div class="card-header">
                <h3>Utilisateurs récents</h3>
                <a href="admin_utilisateurs.php">Voir tous</a>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Date d'inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentUsers)): ?>
                        <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['nom']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['date_inscription']) ?></td>
                                <td class="actions">
                                    <a href="admin_voir_utilisateur.php?id=<?= $user['id'] ?>" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="admin_modifier_utilisateur.php?id=<?= $user['id'] ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="admin_supprimer_utilisateur.php?id=<?= $user['id'] ?>" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?');"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucun utilisateur récent</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>