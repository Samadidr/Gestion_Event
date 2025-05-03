<?php
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config.php';

// Traitement de la suppression d'une salle
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $salle_id = intval($_GET['id']);
    try {
        // Vérifier si la salle est utilisée dans des événements
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM evenements WHERE salle_id = :salle_id");
        $stmt->execute([':salle_id' => $salle_id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $message = "Impossible de supprimer cette salle car elle est utilisée dans un ou plusieurs événements.";
            $messageType = "error";
        } else {
            // Supprimer la salle
            $stmt = $pdo->prepare("DELETE FROM salles WHERE id = :id");
            $stmt->execute([':id' => $salle_id]);
            $message = "La salle a été supprimée avec succès.";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur lors de la suppression: " . $e->getMessage();
        $messageType = "error";
    }
}

// Récupération des salles
try {
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 10; // Nombre de salles par page
    $offset = ($page - 1) * $limit;
    
    // Récupérer le nombre total de salles
    $stmt = $pdo->query("SELECT COUNT(*) FROM salles");
    $totalSalles = $stmt->fetchColumn();
    $totalPages = ceil($totalSalles / $limit);
    
    // Recherche de salles
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $whereClause = '';
    $params = [];
    
    if (!empty($search)) {
        $whereClause = " WHERE nom LIKE :search OR localisation LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    // Récupérer les salles avec pagination et recherche
    $query = "SELECT * FROM salles $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $salles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des salles: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Salles | Admin EventBladi</title>
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
        
        .action-buttons {
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
        
        .search-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
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
            color: var(--text-color);
            text-decoration: none;
            border-radius: 5px;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
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
        
        .message {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .capacity {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--secondary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
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
            
            .search-filter {
                flex-direction: column;
                gap: 10px;
            }
            
            .search-box {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>EventBladi Admin</h3>
            <p>Gestion des salles</p>
        </div>
        
        <div class="sidebar-menu">
            <h4>Principal</h4>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="admin_evenements.php"><i class="fas fa-calendar-alt"></i> Événements</a></li>
                <li><a href="admin_utilisateurs.php"><i class="fas fa-users"></i> Utilisateurs</a></li>
                <li><a href="admin_salles.php" class="active"><i class="fas fa-building"></i> Salles</a></li>
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
                <h2>Gestion des Salles</h2>
                <p>Gérez toutes les salles disponibles pour les événements</p>
            </div>
            <div class="action-buttons">
                <a href="admin_ajouter_salle.php" class="btn"><i class="fas fa-plus"></i> Ajouter une salle</a>
            </div>
        </div>
        
        <?php if (isset($message) && isset($messageType)): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="search-filter">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <form action="" method="GET">
                        <input type="text" name="search" placeholder="Rechercher une salle..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </form>
                </div>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Adresse</th>
                        <th>Capacité</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($salles)): ?>
                        <?php foreach ($salles as $salle): ?>
                            <tr>
                                <td><?= $salle['id'] ?></td>
                                <td><?= htmlspecialchars($salle['nom']) ?></td>
                                <td><?= htmlspecialchars($salle['localisation']) ?></td>
                                <td><span class="capacity"><?= $salle['capacite'] ?> personnes</span></td>
                                
                                
                                <td class="actions">
                                    <a href="admin_voir_salle.php?id=<?= $salle['id'] ?>" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="admin_modifier_salle.php?id=<?= $salle['id'] ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <a href="admin_salles.php?action=delete&id=<?= $salle['id'] ?>" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette salle?');"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Aucune salle trouvée</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>