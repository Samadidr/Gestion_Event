<?php
// Connexion à la base de données
require_once 'config.php';

$admin_email = "admin@eventbladi.com";
$admin_password = "EventAdmin2025!"; // Mot de passe en clair
$admin_nom = "Administrateur";

// Hachage du mot de passe
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

try {
    // Vérifier si l'administrateur existe déjà
    $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE email = ?");
    $stmt->execute([$admin_email]);
    
    if ($stmt->rowCount() > 0) {
        echo "Un administrateur avec cet email existe déjà.";
    } else {
        // Insérer le nouvel administrateur
        $stmt = $pdo->prepare("INSERT INTO administrateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
        $stmt->execute([$admin_nom, $admin_email, $hashed_password]);
        
        echo "Administrateur créé avec succès!";
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>