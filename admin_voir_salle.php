<?php
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config.php';

// Vérifier si l'ID de la salle est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin_salles.php');
    exit;
}

$salle_id = intval($_GET['id']);

try {
    // Récupérer les informations de la salle
    $stmt = $pdo->prepare("SELECT * FROM salles WHERE id = :id");
    $stmt->execute([':id' => $salle_id]);
    $salle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$salle) {
        header('Location: admin_salles.php');
        exit;
    }
    
    // Récupérer les événements associés à cette salle
    $stmt = $pdo->prepare("
        SELECT e.*, COUNT(r.id) as nombre_reservations 
        FROM evenements e 
        LEFT JOIN reservations r ON e.id = r.evenement_id 
        WHERE e.salle_id = :salle_id 
        GROUP BY e.id 
        ORDER BY e.date_debut DESC
    ");
    $stmt->execute([':salle_id' => $salle_id]);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la Salle | Admin EventBladi</title>
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
        
        .salle-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-item h4 {
            color: var(--light-text);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .detail-item p {
            font-size: 1.1rem;
        }
        
        .capacity {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--secondary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .related-events h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
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
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
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
            
            .salle-details {
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
                <h2>Détails de la Salle</h2>
                <p>Informations détaillées sur la salle</p>
            </div>
            <div class="action-buttons">
                <a href="admin_modifier_salle.php?id=<?= $salle['id'] ?>" class="btn"><i class="fas fa-edit"></i> Modifier</a>
                <a href="admin_salles.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </div>
        
        <div class="card">
            <div class="salle-details">
                <div class="detail-item">
                    <h4>Nom de la salle</h4>
                    <p><?= htmlspecialchars($salle['nom']) ?></p>
                </div>
                
                <div class="detail-item">
                    <h4>Adresse</h4>
                    <p><?= htmlspecialchars($salle['localisation']) ?></p>
                </div>
                
                <div class="detail-item">
                    <h4>Capacité</h4>
                    <p><span class="capacity"><?= $salle['capacite'] ?> personnes</span></p>
                </div>
                
                <div class="detail-item">
                    <h4>Équipements</h4>
                    <p><?= !empty($salle['equipements']) ? htmlspecialchars($salle['equipements']) : 'Aucun équipement spécifié' ?></p>
                </div>
                
                <?php if (isset($salle['prix_location']) && !empty($salle['prix_location'])): ?>
                <div class="detail-item">
                    <h4>Prix de location</h4>
                    <p><?= htmlspecialchars($salle['prix_location']) ?> DH</p>
                </div>
                <?php endif; ?>
                
                <?php if (isset($salle['description']) && !empty($salle['description'])): ?>
                <div class="detail-item" style="grid-column: span 2;">
                    <h4>Description</h4>
                    <p><?= nl2br(htmlspecialchars($salle['description'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="related-events">
                <h3>Événements dans cette salle</h3>
                
                <?php if (!empty($evenements)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom de l'événement</th>
                            <th>Date</th>
                            <th>Réservations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evenements as $evenement): ?>
                        <tr>
                            <td><?= htmlspecialchars($evenement['titre']) ?></td>
                            <td>
                                <?= date('d/m/Y H:i', strtotime($evenement['date_debut'])) ?>
                                <?php if ($evenement['date_fin']): ?>
                                - <?= date('d/m/Y H:i', strtotime($evenement['date_fin'])) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $evenement['nombre_reservations'] ?></td>
                            <td>
                                <a href="admin_voir_evenement.php?id=<?= $evenement['id'] ?>" title="Voir"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Aucun événement n'est prévu dans cette salle.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>