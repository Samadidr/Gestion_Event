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

// Récupérer les informations de la salle
try {
    $stmt = $pdo->prepare("SELECT * FROM salles WHERE id = :id");
    $stmt->execute([':id' => $salle_id]);
    $salle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$salle) {
        header('Location: admin_salles.php');
        exit;
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données: " . $e->getMessage());
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $localisation = trim($_POST['localisation']);
    $capacite = intval($_POST['capacite']);
    
    $errors = [];
    
    // Validation des champs
    if (empty($nom)) {
        $errors[] = "Le nom de la salle est obligatoire.";
    }
    
    if (empty($localisation)) {
        $errors[] = "L'adresse de la salle est obligatoire.";
    }
    
    if ($capacite <= 0) {
        $errors[] = "La capacité doit être un nombre positif.";
    }
    
    // Si aucune erreur, procéder à la mise à jour
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE salles 
                SET nom = :nom, 
                    localisation = :localisation, 
                    capacite = :capacite, 
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':nom' => $nom,
                ':localisation' => $localisation,
                ':capacite' => $capacite,
                ':id' => $salle_id
            ]);
            
            $message = "La salle a été mise à jour avec succès.";
            $messageType = "success";
            
            // Mettre à jour les données de la salle après modification
            $stmt = $pdo->prepare("SELECT * FROM salles WHERE id = :id");
            $stmt->execute([':id' => $salle_id]);
            $salle = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $message = "Erreur lors de la mise à jour: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la Salle | Admin EventBladi</title>
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
            font-size: 1rem;
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
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .error-list {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        
        .error-list ul {
            margin: 5px 0 0 20px;
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
                <h2>Modifier la Salle</h2>
                <p>Mettre à jour les informations de la salle</p>
            </div>
        </div>
        
        <?php if (isset($message) && isset($messageType)): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <strong>Veuillez corriger les erreurs suivantes:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nom">Nom de la salle *</label>
                    <input type="text" id="nom" name="nom" class="form-control" value="<?= htmlspecialchars($salle['nom']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="localisation">Localisation *</label>
                    <input type="text" id="localisation" name="localisation" class="form-control" value="<?= htmlspecialchars($salle['localisation']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="capacite">Capacité (nombre de personnes) *</label>
                    <input type="number" id="capacite" name="capacite" class="form-control" value="<?= $salle['capacite'] ?>" min="1" required>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn">Enregistrer les modifications</button>
                    <a href="admin_voir_salle.php?id=<?= $salle_id ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>