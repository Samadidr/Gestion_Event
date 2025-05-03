<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'pge';
$username = 'root';
$password = '';

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifier si un ID de salle est passé
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de salle invalide");
}

$salle_id = $_GET['id'];

// Récupérer les détails de la salle
try {
    $stmt = $pdo->prepare("SELECT * FROM salles WHERE id = ?");
    $stmt->execute([$salle_id]);
    $salle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$salle) {
        die("Salle non trouvée");
    }

    // Récupérer les photos de la salle (supposons que vous ayez une table 'photos_salles')
    $stmt_photos = $pdo->prepare("SELECT * FROM photos_salles WHERE salle_id = ?");
    $stmt_photos->execute([$salle_id]);
    $photos = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la Salle - <?= htmlspecialchars($salle['nom']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --text-color: #333;
            --background-color: #f4f6f7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 15px;
        }

        .header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .logo {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: bold;
        }
        .nav {
            display: flex;
            gap: 20px;
        }

        .nav a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav a:hover {
            color: var(--primary-color);
        }

        .salle-details-container {
            margin-top: 100px;
        }

        .salle-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .galerie-photos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .photo-item {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .photo-item:hover {
            transform: scale(1.05);
        }

        .photo-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .salle-infos {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-retour {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .galerie-photos {
                grid-template-columns: 1fr;
            }
        }

        
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">EventBladi</h1>
            <nav class="nav">
                <a href="accueil.php">Accueil</a>
            </nav>
        </div>
    </header>

    <div class="container salle-details-container">
        <a href="salle_dispo.php" class="btn-retour">← Retour aux salles</a>

        <div class="salle-header">
            <h1><?= htmlspecialchars($salle['nom']); ?></h1>
            <p><?= htmlspecialchars($salle['localisation']); ?> | Capacité : <?= htmlspecialchars($salle['capacite']); ?> personnes</p>
        </div>

        

        <h2>Galerie Photos</h2>
        <div class="galerie-photos">
            <?php if (empty($photos)): ?>
                <p>Aucune photo disponible pour cette salle.</p>
            <?php else: ?>
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-item">
                        <img src="<?= htmlspecialchars($photo['chemin_photo']); ?>" alt="Photo de la salle">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>