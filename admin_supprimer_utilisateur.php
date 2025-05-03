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
$confirm = isset($_GET['confirm']) ? $_GET['confirm'] : '';
$error = false;
$errorMsg = '';

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

    // Vérifier si l'utilisateur a des réservations ou des événements
    $checkQuery = "SELECT 
                    (SELECT COUNT(*) FROM reservations WHERE utilisateur_id = :user_id) as reservations_count,
                    (SELECT COUNT(*) FROM evenements WHERE utilisateur_id = :user_id) as events_count";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([':user_id' => $userId]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    $hasReservations = $result['reservations_count'] > 0;
    $hasEvents = $result['events_count'] > 0;

    // Si l'utilisateur confirme la suppression forcée
    if ($confirm === 'force') {
        try {
            // Commencer une transaction
            $pdo->beginTransaction();
            
            // Supprimer les réservations de l'utilisateur
            $deleteReservationsQuery = "DELETE FROM reservations WHERE utilisateur_id = :user_id";
            $deleteReservationsStmt = $pdo->prepare($deleteReservationsQuery);
            $deleteReservationsStmt->execute([':user_id' => $userId]);
            
            // CORRECTION: Utiliser utilisateur_id au lieu de organisateur_id
            $updateEventsQuery = "UPDATE evenements SET utilisateur_id = NULL WHERE utilisateur_id = :user_id";
            $updateEventsStmt = $pdo->prepare($updateEventsQuery);
            $updateEventsStmt->execute([':user_id' => $userId]);
            
            // Supprimer l'utilisateur
            $deleteUserQuery = "DELETE FROM utilisateurs WHERE id = :id";
            $deleteUserStmt = $pdo->prepare($deleteUserQuery);
            $deleteUserStmt->execute([':id' => $userId]);
            
            // Valider la transaction
            $pdo->commit();
            
            // Rediriger avec un message de succès
            header('Location: admin_utilisateurs.php?success=1');
            exit;
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollBack();
            $error = true;
            $errorMsg = "Erreur lors de la suppression: " . $e->getMessage();
        }
    }
    // Si l'utilisateur confirme la suppression simple
    elseif ($confirm === 'yes') {
        if ($hasReservations || $hasEvents) {
            $error = true;
            $errorMsg = "Impossible de supprimer cet utilisateur car il a des réservations ou des événements associés. Utilisez la suppression forcée pour supprimer également ces données.";
        } else {
            try {
                // Supprimer l'utilisateur
                $deleteUserQuery = "DELETE FROM utilisateurs WHERE id = :id";
                $deleteUserStmt = $pdo->prepare($deleteUserQuery);
                $deleteUserStmt->execute([':id' => $userId]);
                
                // Rediriger avec un message de succès
                header('Location: admin_utilisateurs.php?success=1');
                exit;
            } catch (PDOException $e) {
                $error = true;
                $errorMsg = "Erreur lors de la suppression: " . $e->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer Utilisateur - <?= htmlspecialchars($utilisateur['nom']) ?> | EventBladi Admin</title>
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
        
        .card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: rgba(234, 67, 53, 0.1);
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
        }
        
        .alert-warning {
            background-color: rgba(251, 188, 5, 0.1);
            border: 1px solid #fbbc05;
            color: #b07503;
        }
        
        .user-info {
            background-color: rgba(0,0,0,0.02);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .user-info p {
            margin-bottom: 5px;
        }
        
        .user-info strong {
            font-weight: 500;
        }
        
        .confirmation {
            padding: 20px;
            text-align: center;
        }
        
        .confirmation h3 {
            color: var(--accent-color);
            margin-bottom: 15px;
        }
        
        .confirmation p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c62828;
        }
        
        .btn-warning {
            background-color: #fbbc05;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #b07503;
        }
        
        .btn-secondary {
            background-color: #757575;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        .data-list {
            list-style: none;
            margin: 20px 0;
        }
        
        .data-list li {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .data-list li:last-child {
            border-bottom: none;
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
            
            .btn-group {
                flex-direction: column;
                gap: 10px;
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
                <h2>Supprimer l'utilisateur</h2>
                <p>Confirmation de suppression pour <?= htmlspecialchars($utilisateur['nom']) ?></p>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="user-info">
                <p><strong>ID:</strong> <?= $utilisateur['id'] ?></p>
                <p><strong>Nom:</strong> <?= htmlspecialchars($utilisateur['nom']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($utilisateur['email']) ?></p>
                <p><strong>Téléphone:</strong> <?= htmlspecialchars($utilisateur['telephone'] ?? 'Non spécifié') ?></p>
                <p><strong>Date d'inscription:</strong> <?= htmlspecialchars($utilisateur['date_inscription']) ?></p>
            </div>
            
            <div class="confirmation">
                <h3>Attention!</h3>
                <p>Vous êtes sur le point de supprimer cet utilisateur. Cette action est irréversible.</p>
                
                <?php if ($hasReservations || $hasEvents): ?>
                    <div class="alert alert-warning">
                        <p><strong>Attention:</strong> Cet utilisateur a des données associées:</p>
                        <ul class="data-list">
                            <?php if ($hasReservations): ?>
                                <li><i class="fas fa-ticket-alt"></i> <?= $result['reservations_count'] ?> réservation(s)</li>
                            <?php endif; ?>
                            <?php if ($hasEvents): ?>
                                <li><i class="fas fa-calendar-alt"></i> <?= $result['events_count'] ?> événement(s) organisé(s)</li>
                            <?php endif; ?>
                        </ul>
                        <p>Vous pouvez soit:</p>
                        <ul>
                            <li>Annuler la suppression</li>
                            <li>Forcer la suppression (ce qui supprimera également toutes les réservations de cet utilisateur et mettra à jour les événements)</li>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="btn-group">
                    <a href="admin_utilisateurs.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annuler</a>
                    
                    <?php if (!$hasReservations && !$hasEvents): ?>
                        <a href="admin_supprimer_utilisateur.php?id=<?= $userId ?>&confirm=yes" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Confirmer la suppression</a>
                    <?php else: ?>
                        <a href="admin_supprimer_utilisateur.php?id=<?= $userId ?>&confirm=force" class="btn btn-warning" onclick="return confirm('Êtes-vous absolument sûr de vouloir supprimer cet utilisateur et toutes ses données associées?');"><i class="fas fa-exclamation-triangle"></i> Forcer la suppression</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>