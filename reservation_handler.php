<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

date_default_timezone_set('Africa/Casablanca');

// Log pour le débogage
error_log("Démarrage du traitement de la réservation");

// Vérifie si la requête est bien POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Tentative d'accès direct à reservation_handler.php");
    header("Location: reservation.php");
    exit();
}

// Vérifie le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("Erreur token CSRF: reçu=" . ($_POST['csrf_token'] ?? 'manquant') . ", attendu=" . ($_SESSION['csrf_token'] ?? 'manquant'));
    die("Erreur de sécurité : token CSRF invalide.");
}
unset($_SESSION['csrf_token']);

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("Utilisateur non connecté");
    $_SESSION['reservation_message'] = "Veuillez vous connecter pour faire une réservation.";
    $_SESSION['reservation_message_type'] = "error";
    header("Location: connexion.php");
    exit();
}

// Récupération et validation des données
$evenement_id = isset($_POST['evenement_id']) ? intval($_POST['evenement_id']) : 0;
$places_reservees = isset($_POST['places_reservees']) ? intval($_POST['places_reservees']) : 0;
$methode_paiement = $_POST['methode_paiement'] ?? '';

error_log("Données reçues: evenement_id=$evenement_id, places=$places_reservees, methode=$methode_paiement");

$methode_paiement_map = [
    'carte' => 'carte bancaire',
    'paypal' => 'paypal',
    'mobile' => 'espece'
];

$methode_paiement = $methode_paiement_map[$methode_paiement] ?? 'carte bancaire';

if (!$evenement_id) {
    $_SESSION['reservation_message'] = "Veuillez sélectionner un événement valide.";
    $_SESSION['reservation_message_type'] = "error";
    header("Location: reservation.php");
    exit();
}

if (!$places_reservees || $places_reservees <= 0) {
    $_SESSION['reservation_message'] = "Veuillez indiquer un nombre de places valide.";
    $_SESSION['reservation_message_type'] = "error";
    header("Location: reservation.php");
    exit();
}

$utilisateur_id = $_SESSION['user_id'];
$date_reservation = date('Y-m-d H:i:s');

// Connexion à la base de données
$host = 'localhost';
$dbname = 'PGE';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Connexion à la base de données réussie");
} catch (PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    die("Erreur de connexion à la base de données.");
}

try {
    // Récupération des informations de l'événement
    $stmt = $pdo->prepare("
        SELECT id, titre, places_disponibles, prix_ticket 
        FROM evenements 
        WHERE id = ?
    ");
    $stmt->execute([$evenement_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        error_log("Événement introuvable: $evenement_id");
        throw new Exception("Événement introuvable.");
    }

    if ($places_reservees > $event['places_disponibles']) {
        error_log("Places insuffisantes: demandé=$places_reservees, disponible=" . $event['places_disponibles']);
        throw new Exception("Nombre de places demandées supérieur aux places disponibles.");
    }

    $prix_total = $event['prix_ticket'] * $places_reservees;
    error_log("Calcul prix: unitaire=" . $event['prix_ticket'] . ", places=$places_reservees, total=$prix_total");

    // Données à afficher dans resume_reservation.php
    $_SESSION['reservation_data'] = [
        'evenement_id' => $evenement_id,
        'titre_evenement' => $event['titre'],
        'places_reservees' => $places_reservees,
        'methode_paiement' => $methode_paiement,
        'prix_total' => $prix_total,
        'date_reservation' => $date_reservation
    ];

    error_log("Données de réservation sauvegardées en session. Redirection vers résumé.");
    header("Location: resume_reservation.php");
    exit();

} catch (Exception $e) {
    error_log("Erreur lors de la réservation : " . $e->getMessage());
    $_SESSION['reservation_message'] = "Erreur : " . $e->getMessage();
    $_SESSION['reservation_message_type'] = "error";
    header("Location: reservation.php");
    exit();
}
?>