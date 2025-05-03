<?php
require_once 'config.php'; // Connexion PDO

function isSalleDisponible($pdo, $salleId, $dateDebut, $dateFin, $heureDebut, $heureFin)
{
    // Vérifions si les colonnes existent et utilisons les bons noms de colonnes
    try {
        $query = "SELECT * FROM evenements 
                WHERE salle_id = :salle_id 
                AND date_event BETWEEN :date_debut AND :date_fin";
                
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':salle_id' => $salleId,
            ':date_debut' => $dateDebut,
            ':date_fin' => $dateFin
        ]);

        // Si nous avons des événements qui se chevauchent, la salle n'est pas disponible
        return $stmt->rowCount() === 0;
    } catch (PDOException $e) {
        // En cas d'erreur, on suppose que la salle est disponible
        error_log("Erreur lors de la vérification de disponibilité: " . $e->getMessage());
        return true;
    }
}

$dateDebut = $_GET['date_debut'] ?? '';
$dateFin = $_GET['date_fin'] ?? '';
$heureDebut = $_GET['heure_debut'] ?? '';
$heureFin = $_GET['heure_fin'] ?? '';
$filtrage = !empty($dateDebut) && !empty($dateFin) && !empty($heureDebut) && !empty($heureFin);

$query = "SELECT * FROM salles";
$stmt = $pdo->query($query);
$salles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Disponibilité des salles</title>
    <style>
        :root {
            --primary-color: #3498db;
            --text-color: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 80px;
            margin: 0;
            background: #f4f7f9;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 40px;
        }

        form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 40px;
        }

        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        form input {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        .salle {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .salle h3 {
            color: var(--text-color);
        }

        .disponible {
            color: green;
            font-weight: bold;
        }

        .indisponible {
            color: red;
            font-weight: bold;
        }

        .links {
            margin-top: 15px;
        }

        .links a {
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            margin-right: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .links a:hover {
            background-color: #2980b9;
        }

        /* Header */
        .header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
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

        /* Footer */
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 50px 0 20px;
            margin-top: 60px;
        }

        .footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .footer-grid > div {
            display: flex;
            flex-direction: column;
        }

        .footer-grid h4 {
            color: #3498db;
            font-size: 1.2rem;
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-grid h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #3498db;
        }

        .footer-grid p {
            margin-bottom: 10px;
            line-height: 1.6;
            color: #bdc3c7;
        }

        .footer-grid a {
            color: #ecf0f1;
            text-decoration: none;
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }

        .footer-grid a:hover {
            color: #3498db;
        }

        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
            }

            form {
                flex-direction: column;
                align-items: center;
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

<div class="container">

    <h2>Consulter la disponibilité des salles</h2>

    <form method="get" action="">
        <div>
            <label for="date_debut">Date de début :</label>
            <input type="date" name="date_debut" id="date_debut" required value="<?= htmlspecialchars($dateDebut) ?>">
        </div>
        <div>
            <label for="date_fin">Date de fin :</label>
            <input type="date" name="date_fin" id="date_fin" required value="<?= htmlspecialchars($dateFin) ?>">
        </div>
        <div>
            <label for="heure_debut">Heure de début :</label>
            <input type="time" name="heure_debut" id="heure_debut" required value="<?= htmlspecialchars($heureDebut) ?>">
        </div>
        <div>
            <label for="heure_fin">Heure de fin :</label>
            <input type="time" name="heure_fin" id="heure_fin" required value="<?= htmlspecialchars($heureFin) ?>">
        </div>
        <div>
            <button type="submit">Rechercher</button>
        </div>
    </form>

    <?php if ($filtrage): ?>
        <p style="text-align:center;">Résultats pour la période du <strong><?= htmlspecialchars($dateDebut) ?></strong> au <strong><?= htmlspecialchars($dateFin) ?></strong>
            entre <strong><?= htmlspecialchars($heureDebut) ?></strong> et <strong><?= htmlspecialchars($heureFin) ?></strong></p>
    <?php endif; ?>

    <?php foreach ($salles as $salle):
        $estDisponible = !$filtrage || isSalleDisponible($pdo, $salle['id'], $dateDebut, $dateFin, $heureDebut, $heureFin);
        ?>
        <div class="salle">
            <h3><?= htmlspecialchars($salle['nom']) ?></h3>
            <p><strong>Capacité :</strong> <?= htmlspecialchars($salle['capacite']) ?></p>
            <p><strong>Localisation :</strong> 
   <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode(htmlspecialchars($salle['localisation'])) ?>" 
      target="_blank" style="color: var(--primary-color); text-decoration: underline;">
      <?= htmlspecialchars($salle['localisation']) ?>
      <i class="fas fa-map-marker-alt" style="margin-left: 5px;"></i>
   </a>
</p>
            <p class="<?= $estDisponible ? 'disponible' : 'indisponible' ?>">
                <?= $estDisponible ? 'Disponible' : 'Indisponible' ?> pour cette période
            </p>
            <div class="links">
                <a href="creer_eve.php?salle_id=<?= $salle['id'] ?>">Réserver</a>
                <a href="voir_salle.php?id=<?= htmlspecialchars($salle['id']) ?>">Voir la salle</a>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <h4>EventBladi</h4>
                <p>Plateforme de gestion d'événements moderne et intuitive.</p>
            </div>
            <div>
                <h4>Liens Rapides</h4>
                <a href="accueil.php">Accueil</a>
                <a href="evenements.php">Événements</a>
                <a href="contact.php">Contact</a>
            </div>
            <div>
                <h4>Contactez-nous</h4>
                <p>Email: support@eventbladi.com</p>
                <p>Téléphone: +212 6 00 00 01 02</p>
            </div>
        </div>
        <div style="text-align:center; margin-top:30px;">
            &copy; 2025 EventBladi. Tous droits réservés.
        </div>
    </div>
</footer>

</body>
</html>