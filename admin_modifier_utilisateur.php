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
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Variables pour les messages
$success = false;
$error = false;
$errorMsg = "";

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $ville = isset($_POST['ville']) ? trim($_POST['ville']) : null;
    $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : null;
    
    // Validation des champs
    if (empty($nom) || empty($email)) {
        $error = true;
        $errorMsg = "Le nom et l'email sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $errorMsg = "L'adresse email n'est pas valide.";
    } else {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $checkQuery = "SELECT id FROM utilisateurs WHERE email = :email AND id != :id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([':email' => $email, ':id' => $userId]);
        if ($checkStmt->rowCount() > 0) {
            $error = true;
            $errorMsg = "Cette adresse email est déjà utilisée par un autre utilisateur.";
        } else {
            // Si un nouveau mot de passe est fourni
            $password_update = "";
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirmPassword'];
                
                if ($password !== $confirmPassword) {
                    $error = true;
                    $errorMsg = "Les mots de passe ne correspondent pas.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $password_update = ", mot_de_passe = :password";
                }
            }
            
            if (!$error) {
                try {
                    $updateQuery = "UPDATE utilisateurs SET 
                                    nom = :nom, 
                                    email = :email, 
                                    telephone = :telephone, 
                                    ville = :ville, 
                                    adresse = :adresse" . $password_update . "
                                    WHERE id = :id";
                    
                    $updateStmt = $pdo->prepare($updateQuery);
                    $params = [
                        ':nom' => $nom,
                        ':email' => $email,
                        ':telephone' => $telephone,
                        ':ville' => $ville,
                        ':adresse' => $adresse,
                        ':id' => $userId
                    ];
                    
                    if (!empty($_POST['password'])) {
                        $params[':password'] = $hashed_password;
                    }
                    
                    $updateStmt->execute($params);
                    
                    // Rafraîchir les données de l'utilisateur
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([':id' => $userId]);
                    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $success = true;
                } catch (PDOException $e) {
                    $error = true;
                    $errorMsg = "Erreur lors de la mise à jour: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Utilisateur - <?= htmlspecialchars($utilisateur['nom']) ?> | EventBladi Admin</title>
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
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
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-section-title {
            font-size: 1.1rem;
            margin: 30px 0 15px;
            color: var(--primary-color);
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding-bottom: 10px;
        }
        
        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
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
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .btn-group {
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
                <h2>Modifier l'utilisateur</h2>
                <p>Informations de <?= htmlspecialchars($utilisateur['nom']) ?></p>
            </div>
            <div>
                <a href="admin_utilisateurs.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Les informations de l'utilisateur ont été mises à jour avec succès.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form action="admin_modifier_utilisateur.php?id=<?= $userId ?>" method="post">
                <h3 class="form-section-title">Informations personnelles</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom" class="form-label">Nom complet *</label>
                        <input type="text" id="nom" name="nom" class="form-control" value="<?= htmlspecialchars($utilisateur['nom']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($utilisateur['email']) ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" value="<?= htmlspecialchars($utilisateur['telephone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ville" class="form-label">Ville</label>
                        <input type="text" id="ville" name="ville" class="form-control" value="<?= htmlspecialchars($utilisateur['ville'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="adresse" class="form-label">Adresse</label>
                    <textarea id="adresse" name="adresse" class="form-control" rows="3"><?= htmlspecialchars($utilisateur['adresse'] ?? '') ?></textarea>
                </div>
                
                <h3 class="form-section-title">Mot de passe</h3>
                <p>Laissez vide pour conserver le mot de passe actuel</p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="form-control">
                    </div>
                </div>
                
                <div class="btn-group">
                    <a href="admin_supprimer_utilisateur.php?id=<?= $userId ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?');"><i class="fas fa-trash-alt"></i> Supprimer</a>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>