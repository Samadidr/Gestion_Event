<?php
// Connexion à la base de données via PDO
$host = "localhost";
$dbname = "PGE";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Échec de la connexion : " . $e->getMessage());
}

$message = "";

// Ajouter une activité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $titre = htmlspecialchars(trim($_POST['titre']));
    $description = htmlspecialchars(trim($_POST['description']));
    $date_event = $_POST['date_event'];

    if (!empty($date_event) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_event)) {
        $sql = "INSERT INTO evenements (titre, description, date_event) VALUES (:titre, :description, :date_event)";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute(['titre' => $titre, 'description' => $description, 'date_event' => $date_event])) {
            $message = "Activité ajoutée avec succès !";
        } else {
            $message = "Erreur lors de l'ajout de l'activité.";
        }
    } else {
        $message = "Veuillez entrer une date valide au format AAAA-MM-JJ.";
    }
}

// Suppression d'une activité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer'])) {
    $id = (int) $_POST['id'];

    $sql = "DELETE FROM evenements WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute(['id' => $id])) {
        $message = "Activité supprimée avec succès.";
    } else {
        $message = "Erreur lors de la suppression.";
    }
}

// Récupération des activités
$sql = "SELECT * FROM evenements ORDER BY date_event DESC";
$evenements = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer Vos Activités</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: #ffffff;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            margin: 10px 0 5px;
        }
        input[type="text"], textarea, input[type="date"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            color: green;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }

        .back-home-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            justify-content: center;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin-left: 290px;
        }
        .back-home-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gérer Vos Activités</h1>

        <!-- Formulaire pour ajouter une activité -->
        <form action="" method="post">
            <label for="titre">Titre de l'activité :</label>
            <input type="text" id="titre" name="titre" required>

            <label for="description">Description :</label>
            <textarea id="description" name="description" required></textarea>

            <label for="date_event">Date de l'événement :</label>
            <input type="date" id="date_event" name="date_event" required>

            <button type="submit" name="ajouter">Ajouter l'activité</button>
        </form><br>
        <a href="accueil.php" class="back-home-btn">Retour à la page d'accueil</a>

        <!-- Message de succès ou d'erreur -->
        <?php if (!empty($message)): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <!-- Liste des activités -->
        <h2>Liste des Activités</h2>
        <?php if (!empty($evenements)): ?>
            <table>
                <tr>
                    <th>Titre</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($evenements as $evenement): ?>
                    <tr>
                        <td><?= htmlspecialchars($evenement['titre']) ?></td>
                        <td><?= htmlspecialchars($evenement['description']) ?></td>
                        <td><?= htmlspecialchars($evenement['date_event']) ?></td>
                        <td>
                            <form action="" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $evenement['id'] ?>">
                                <button type="submit" name="supprimer" class="delete-btn">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Aucune activité enregistrée.</p>
        <?php endif; ?>
    </div>
</body>
</html>
