<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.html");
    exit;
}

echo "Bienvenue, " . htmlspecialchars($_SESSION['email']) . " !";
?>
<a href="logout.php">Se déconnecter</a>
