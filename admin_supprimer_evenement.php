<?php
session_start();

// Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require_once 'config.php';

// Vérifier si l'ID de l'événement est présent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_evenements.php?error=id_invalide');
    exit;
}

$eventId = (int)$_GET['id'];
$forcerSuppression = isset($_GET['forcer']) && $_GET['forcer'] == 1;

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Vérifier si l'événement existe
    $checkEventQuery = "SELECT * FROM evenements WHERE id = :id";
    $checkEventStmt = $pdo->prepare($checkEventQuery);
    $checkEventStmt->execute([':id' => $eventId]);
    $event = $checkEventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        // L'événement n'existe pas
        $pdo->rollBack();
        header('Location: admin_evenements.php?error=evenement_inexistant');
        exit;
    }
    
    // Vérifier si l'événement a des réservations
    $checkReservationsQuery = "SELECT COUNT(*) as reservations_count FROM reservations WHERE evenement_id = :event_id";
    $checkReservationsStmt = $pdo->prepare($checkReservationsQuery);
    $checkReservationsStmt->execute([':event_id' => $eventId]);
    $result = $checkReservationsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['reservations_count'] > 0 && !$forcerSuppression) {
        // L'événement a des réservations et l'option forcer est désactivée
        $pdo->rollBack();
        // Rediriger avec un paramètre pour afficher la confirmation
        header("Location: admin_evenements.php?confirm_delete=$eventId&reservations=" . $result['reservations_count']);
        exit;
    }
    
    // Si on arrive ici, soit l'événement n'a pas de réservations, soit on force la suppression
    
    // Supprimer d'abord les réservations associées si elles existent
    if ($result['reservations_count'] > 0) {
        $deleteReservationsQuery = "DELETE FROM reservations WHERE evenement_id = :event_id";
        $deleteReservationsStmt = $pdo->prepare($deleteReservationsQuery);
        $deleteReservationsStmt->execute([':event_id' => $eventId]);
    }
    
    // Supprimer l'événement
    $deleteEventQuery = "DELETE FROM evenements WHERE id = :id";
    $deleteEventStmt = $pdo->prepare($deleteEventQuery);
    $deleteEventStmt->execute([':id' => $eventId]);
    
    // Valider la transaction
    $pdo->commit();
    
    // Rediriger avec un message de succès
    header('Location: admin_evenements.php?success=1&message=evenement_supprime');
    exit;
    
} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    $pdo->rollBack();
    header('Location: admin_evenements.php?error=db_error&message=' . urlencode($e->getMessage()));
    exit;
}