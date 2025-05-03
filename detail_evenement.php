<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Veuillez vous connecter pour voir les détails de l\'événement.'); window.location.href='connexion.php';</script>";
    exit;
}

$utilisateur_id = $_SESSION['user_id'];

// Vérifier si l'ID de l'événement est passé dans l'URL et est valide
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $evenement_id = $_GET['id'];

    // Connexion à la base de données
    require 'config.php';
    $pdo = new PDO("mysql:host=localhost;dbname=pge;charset=utf8", "root", "");

    // Récupérer les détails de l'événement
    $stmt = $pdo->prepare("SELECT * FROM evenements WHERE id = :id AND utilisateur_id = :utilisateur_id");
    $stmt->bindParam(":id", $evenement_id);
    $stmt->bindParam(":utilisateur_id", $utilisateur_id);
    $stmt->execute();
    $evenement = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifier si l'événement existe
    if (!$evenement) {
        echo "<script>alert('Événement introuvable ou vous n\'avez pas l\'autorisation de le voir.'); window.location.href='mes_evenements.php';</script>";
        exit;
    }

    // Afficher les détails de l'événement
    echo "<h1>" . $evenement['titre'] . "</h1>";
    echo "<p>" . $evenement['description'] . "</p>";
    echo "<p>Date: " . $evenement['date_event'] . "</p>";
    echo "<p>Places disponibles: " . $evenement['places_disponibles'] . "</p>";
} else {
    echo "<script>alert('ID de l\'événement manquant ou invalide.'); window.location.href='mes_evenements.php';</script>";
    exit;
}
?>
