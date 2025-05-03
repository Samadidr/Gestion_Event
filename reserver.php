<?php
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'PGE';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifier si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Récupérer les données du formulaire
        $evenement_id = $_POST['evenement'];
        $places_reservees = intval($_POST['places']);

        // Début de la transaction
        $pdo->beginTransaction();

        // Vérifier la disponibilité des places
        $stmt = $pdo->prepare("SELECT * FROM evenements WHERE id = ? FOR UPDATE");
        $stmt->execute([$evenement_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            throw new Exception("Événement non trouvé.");
        }

        if ($places_reservees > $event['places_disponibles']) {
            throw new Exception("Nombre de places indisponible.");
        }

        // Mettre à jour le nombre de places disponibles
        $stmt = $pdo->prepare("UPDATE evenements SET places_disponibles = places_disponibles - ? WHERE id = ?");
        $stmt->execute([$places_reservees, $evenement_id]);

        // Enregistrer la réservation 
        $stmt = $pdo->prepare("INSERT INTO reservations (evenement_id, places_reservees, date_reservation) VALUES (?, ?, NOW())");
        $stmt->execute([$evenement_id, $places_reservees]);

        // Valider la transaction
        $pdo->commit();

        // Définir un message de succès
        $_SESSION['reservation_message'] = "🎉 Réservation réussie ! Vous avez réservé {$places_reservees} place(s) pour l'événement.";
        $_SESSION['reservation_message_type'] = 'success';
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();

        // En cas d'erreur, définir un message d'erreur
        $_SESSION['reservation_message'] = "❌ Erreur : " . $e->getMessage();
        $_SESSION['reservation_message_type'] = 'error';
    }

    // Rediriger vers la page de réservation
    header("Location: reservation.php");
    exit();
} else {
    // Rediriger si accès direct au script
    header("Location: reservation.php");
    exit();
}