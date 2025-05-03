<?php
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config.php';

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = false;

// Vérifier si l'événement existe
try {
    $query = "SELECT * FROM evenements WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: admin_evenements.php?error=event_not_found');
        exit;
    }
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupérer les salles pour le formulaire
try {
    $salleQuery = "SELECT id, nom FROM salles ORDER BY nom";
    $salleStmt = $pdo->query($salleQuery);
    $salles = $salleStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupérer les utilisateurs pour le formulaire
try {
    $userQuery = "SELECT id, nom, email FROM utilisateurs ORDER BY nom";
    $userStmt = $pdo->query($userQuery);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $date_event = trim($_POST['date_event']);
    $heure_debut = trim($_POST['heure_debut']);
    $duree = (int)$_POST['duree'];
    $salle_id = (int)$_POST['salle_id'];
    $utilisateur_id = !empty($_POST['utilisateur_id']) ? (int)$_POST['utilisateur_id'] : null;
    $prix_ticket = (float)$_POST['prix_ticket'];
    $places_disponibles = (int)$_POST['places_disponibles'];
    $statut = isset($_POST['statut']) ? 1 : 0;
    $image = $_POST['image_actuelle']; // Conserver l'image actuelle par défaut

    // Validation des champs
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire.";
    }
    
    if (empty($date_event)) {
        $errors[] = "La date est obligatoire.";
    }
    
    if (empty($heure_debut)) {
        $errors[] = "L'heure de début est obligatoire.";
    }
    
    if ($duree <= 0) {
        $errors[] = "La durée doit être supérieure à 0.";
    }
    
    if ($salle_id <= 0) {
        $errors[] = "Veuillez sélectionner une salle.";
    }
    
    if ($prix_ticket < 0) {
        $errors[] = "Le prix ne peut pas être négatif.";
    }
    
    if ($places_disponibles < 0) {
        $errors[] = "Le nombre de places disponibles ne peut pas être négatif.";
    }

    // Traitement de l'image si une nouvelle est téléchargée
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if ($_FILES['image']['size'] > $maxSize) {
            $errors[] = "L'image est trop volumineuse (max 5MB).";
        }
        
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $errors[] = "Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.";
        }
        
        if (empty($errors)) {
            $uploadDir = '../uploads/events/';
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                // Supprimer l'ancienne image si elle existe
                if (!empty($event['image']) && file_exists($uploadDir . $event['image'])) {
                    unlink($uploadDir . $event['image']);
                }
                $image = $fileName;
            } else {
                $errors[] = "Erreur lors du téléchargement de l'image.";
            }
        }
    }
    
    // Si aucune erreur, mettre à jour l'événement
    if (empty($errors)) {
        try {
            $updateQuery = "UPDATE evenements SET 
                titre = :titre,
                description = :description,
                date_event = :date_event,
                heure_debut = :heure_debut,
                duree = :duree,
                salle_id = :salle_id,
                utilisateur_id = :utilisateur_id,
                prix_ticket = :prix_ticket,
                places_disponibles = :places_disponibles,
                image = :image,
                statut = :statut
                WHERE id = :id";
            
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                ':titre' => $titre,
                ':description' => $description,
                ':date_event' => $date_event,
                ':heure_debut' => $heure_debut,
                ':duree' => $duree,
                ':salle_id' => $salle_id,
                ':utilisateur_id' => $utilisateur_id,
                ':prix_ticket' => $prix_ticket,
                ':places_disponibles' => $places_disponibles,
                ':image' => $image,
                ':statut' => $statut,
                ':id' => $eventId
            ]);
            
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Événement | EventBladi Admin</title>
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: inherit;
        }
        
        textarea.form-control {
            height: 150px;
            resize: vertical;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .form-check input {
            margin-right: 10px;
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
            font-family: inherit;
            font-size: inherit;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #1557b0;
        }
        
        .btn-secondary {
            background-color: var(--light-text);
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .form-row .form-group {
            flex: 1;
            min-width: 200px;
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
        
        .current-image {
            max-width: 200px;
            margin-top: 10px;
            border-radius: 5px;
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
            
            .form-row {
                flex-direction: column;
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
                <h2>Modifier un Événement</h2>
                <p>Mettre à jour les informations de l'événement</p>
            </div>
            <div class="user-info">
                <span><?= htmlspecialchars($_SESSION['admin_email']) ?></span>
                <a href="admin_deconnexion.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        
        <!-- Notifications -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                L'événement a été mis à jour avec succès.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <div class="card">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titre">Titre de l'événement*</label>
                    <input type="text" id="titre" name="titre" class="form-control" value="<?= htmlspecialchars($event['titre']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($event['description']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_event">Date*</label>
                        <input type="date" id="date_event" name="date_event" class="form-control" value="<?= date('Y-m-d', strtotime($event['date_event'])) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="heure_debut">Heure de début*</label>
                        <input type="time" id="heure_debut" name="heure_debut" class="form-control" value="<?= date('H:i', strtotime($event['heure_debut'])) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="duree">Durée (minutes)*</label>
                        <input type="number" id="duree" name="duree" class="form-control" value="<?= $event['duree'] ?>" min="1" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salle_id">Salle*</label>
                        <select id="salle_id" name="salle_id" class="form-control" required>
                            <option value="">Choisir une salle</option>
                            <?php foreach ($salles as $salle): ?>
                                <option value="<?= $salle['id'] ?>" <?= $event['salle_id'] === $salle['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($salle['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="utilisateur_id">Organisateur</label>
                        <select id="utilisateur_id" name="utilisateur_id" class="form-control">
                            <option value="">Sélectionner un organisateur</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $event['utilisateur_id'] === $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['nom']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prix_ticket">Prix du ticket (MAD)*</label>
                        <input type="number" id="prix_ticket" name="prix_ticket" class="form-control" value="<?= $event['prix_ticket'] ?>" min="0" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="places_disponibles">Places disponibles*</label>
                        <input type="number" id="places_disponibles" name="places_disponibles" class="form-control" value="<?= $event['places_disponibles'] ?>" min="0" required>
                    </div>
                </div>
                
                
                
                
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn">Mettre à jour l'événement</button>
                    <a href="admin_evenements.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>