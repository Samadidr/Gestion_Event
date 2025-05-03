<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Log pour le débogage
error_log("Démarrage de la finalisation de réservation");

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Accès direct à finaliser_reservation.php détecté");
    header("Location: reservation.php");
    exit();
}

// Vérification de l'existence des données de réservation
if (!isset($_SESSION['reservation_data'])) {
    error_log("Données de réservation manquantes dans la session");
    $_SESSION['reservation_message'] = "Erreur: Informations de réservation manquantes.";
    $_SESSION['reservation_message_type'] = "error";
    header("Location: reservation.php");
    exit();
}

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Utilisateur non connecté lors de la finalisation");
    $_SESSION['reservation_message'] = "Veuillez vous connecter pour finaliser votre réservation.";
    $_SESSION['reservation_message_type'] = "error";
    header("Location: connexion.php");
    exit();
}

// Récupération des données de réservation
$reservation_data = $_SESSION['reservation_data'];
$utilisateur_id = $_SESSION['user_id'];
$evenement_id = $reservation_data['evenement_id'];
$places_reservees = $reservation_data['places_reservees'];
$methode_paiement = $reservation_data['methode_paiement'];
$prix_total = $reservation_data['prix_total'];
$date_reservation = $reservation_data['date_reservation'];

error_log("Données récupérées: user=$utilisateur_id, event=$evenement_id, places=$places_reservees");

// Connexion à la base de données
$host = 'localhost';
$dbname = 'PGE';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Connexion BDD réussie lors de la finalisation");
} catch (PDOException $e) {
    error_log("Erreur de connexion BDD: " . $e->getMessage());
    die("Erreur de connexion à la base de données.");
}

try {
    // Vérification des places disponibles avant finalisation
    $stmt = $pdo->prepare("SELECT places_disponibles FROM evenements WHERE id = ?");
    $stmt->execute([$evenement_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        error_log("Événement $evenement_id non trouvé lors de la finalisation");
        throw new Exception("Événement introuvable.");
    }

    if ($places_reservees > $event['places_disponibles']) {
        error_log("Places insuffisantes lors finalisation: demandé=$places_reservees, disponible=" . $event['places_disponibles']);
        throw new Exception("Le nombre de places demandées n'est plus disponible.");
    }

    // Démarre la transaction
    $pdo->beginTransaction();
    error_log("Transaction démarrée");

    // Insère la réservation
    $stmt = $pdo->prepare("
        INSERT INTO reservations (
            utilisateur_id, 
            evenement_id, 
            places_reservees, 
            montant_total, 
            methode_paiement, 
            date_reservation
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $utilisateur_id,
        $evenement_id,
        $places_reservees,
        $prix_total,
        $methode_paiement,
        $date_reservation
    ]);
    
    $reservation_id = $pdo->lastInsertId();
    error_log("Réservation insérée avec ID: $reservation_id");

    // Met à jour les places restantes
    $stmt = $pdo->prepare("
        UPDATE evenements 
        SET places_disponibles = places_disponibles - ? 
        WHERE id = ?
    ");
    $stmt->execute([$places_reservees, $evenement_id]);
    error_log("Places disponibles mises à jour");

    // Commit
    $pdo->commit();
    error_log("Transaction validée avec succès");

    // Message de succès
    $_SESSION['reservation_message'] = "Votre réservation a été finalisée avec succès ! Numéro de réservation: #" . $reservation_id;
    $_SESSION['reservation_message_type'] = "success";

    // Nettoyage des données de session
    unset($_SESSION['reservation_data']);

    // Redirection vers la page de reçu au lieu de mes_reservations.php
    error_log("Redirection vers recu_reservation.php avec ID: $reservation_id");
    header("Location: recu_reservation.php?id=$reservation_id");
    exit();

} catch (Exception $e) {
    // Rollback en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        error_log("Transaction annulée suite à une erreur");
    }
    
    error_log("Erreur lors de la finalisation: " . $e->getMessage());
    $_SESSION['reservation_message'] = "Erreur lors de la finalisation: " . $e->getMessage();
    $_SESSION['reservation_message_type'] = "error";
    header("Location: reservation.php");
    exit();
}
?>